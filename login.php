<?php
header("Content-Type: application/json");
require_once 'config.php';
session_start();

// ══════════════════════════════════════════════════════════════
// DATABASE CONNECTION
// ══════════════════════════════════════════════════════════════

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Server error. Please try again."]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// GET INPUT DATA
// ══════════════════════════════════════════════════════════════

$data = json_decode(file_get_contents('php://input'), true);
$contact = trim($data['contact'] ?? '');
$password = $data['password'] ?? '';
$turnstileToken = $data['turnstile'] ?? '';

// ══════════════════════════════════════════════════════════════
// VERIFY CLOUDFLARE TURNSTILE
// ══════════════════════════════════════════════════════════════

if (!verifyTurnstile($turnstileToken)) {
    echo json_encode(["status" => "error", "message" => "Security check failed. Please try again."]);
    $conn->close();
    exit;
}

// ══════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ══════════════════════════════════════════════════════════════

if (empty($contact) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    $conn->close();
    exit;
}

// ══════════════════════════════════════════════════════════════
// RATE LIMITING: Check for account lockout
// ══════════════════════════════════════════════════════════════

$lockoutMinutesAgo = date('Y-m-d H:i:s', strtotime('-' . LOGIN_LOCKOUT_MINUTES . ' minutes'));
$stmt_check_lockout = $conn->prepare("SELECT COUNT(*) as attempts 
                                       FROM login_attempts 
                                       WHERE contact = ? AND attempted_at > ?");
$stmt_check_lockout->bind_param("ss", $contact, $lockoutMinutesAgo);
$stmt_check_lockout->execute();
$lockout_result = $stmt_check_lockout->get_result()->fetch_assoc();
$stmt_check_lockout->close();

if ($lockout_result['attempts'] >= LOGIN_MAX_ATTEMPTS) {
    // Calculate remaining lockout time
    $stmt_first = $conn->prepare("SELECT attempted_at FROM login_attempts 
                                   WHERE contact = ? AND attempted_at > ? 
                                   ORDER BY attempted_at ASC LIMIT 1");
    $stmt_first->bind_param("ss", $contact, $lockoutMinutesAgo);
    $stmt_first->execute();
    $first_attempt = $stmt_first->get_result()->fetch_assoc();
    $stmt_first->close();
    
    if ($first_attempt) {
        $unlock_time = strtotime($first_attempt['attempted_at']) + (LOGIN_LOCKOUT_MINUTES * 60);
        $seconds_left = max(0, $unlock_time - time());
        
        echo json_encode([
            "status" => "locked",
            "message" => "Too many failed login attempts. Account temporarily locked.",
            "seconds_left" => $seconds_left
        ]);
    } else {
        echo json_encode([
            "status" => "locked",
            "message" => "Too many failed login attempts. Please try again later.",
            "seconds_left" => LOGIN_LOCKOUT_MINUTES * 60
        ]);
    }
    
    $conn->close();
    exit;
}

// ══════════════════════════════════════════════════════════════
// FIND USER BY EMAIL OR PHONE
// ══════════════════════════════════════════════════════════════

$stmt_user = $conn->prepare("SELECT id, email, phone, password_hash FROM users WHERE email = ? OR phone = ?");
$stmt_user->bind_param("ss", $contact, $contact);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// ══════════════════════════════════════════════════════════════
// VERIFY PASSWORD
// ══════════════════════════════════════════════════════════════

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Log failed attempt
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt_log = $conn->prepare("INSERT INTO login_attempts (contact, ip_address, user_agent, attempted_at) 
                                 VALUES (?, ?, ?, NOW())");
    $stmt_log->bind_param("sss", $contact, $ip, $userAgent);
    $stmt_log->execute();
    $stmt_log->close();
    
    // Check if this triggers lockout
    $stmt_count = $conn->prepare("SELECT COUNT(*) as attempts 
                                   FROM login_attempts 
                                   WHERE contact = ? AND attempted_at > ?");
    $stmt_count->bind_param("ss", $contact, $lockoutMinutesAgo);
    $stmt_count->execute();
    $attempt_count = $stmt_count->get_result()->fetch_assoc()['attempts'];
    $stmt_count->close();
    
    if ($attempt_count >= LOGIN_MAX_ATTEMPTS) {
        // Send security alert
        sendSecurityAlert($contact, $ip);
        
        echo json_encode([
            "status" => "error",
            "message" => "Invalid credentials. Account locked for " . LOGIN_LOCKOUT_MINUTES . " minutes due to multiple failed attempts."
        ]);
    } else {
        $remaining = LOGIN_MAX_ATTEMPTS - $attempt_count;
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email/phone or password. $remaining attempt(s) remaining."
        ]);
    }
    
    $conn->close();
    exit;
}

// ══════════════════════════════════════════════════════════════
// LOGIN SUCCESSFUL
// ══════════════════════════════════════════════════════════════

// Clear old login attempts
$stmt_clear = $conn->prepare("DELETE FROM login_attempts WHERE contact = ?");
$stmt_clear->bind_param("s", $contact);
$stmt_clear->execute();
$stmt_clear->close();

// Update last login
$stmt_update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$stmt_update->bind_param("i", $user['id']);
$stmt_update->execute();
$stmt_update->close();

// Set session
$_SESSION['user_id'] = $user['id'];
session_regenerate_id(true);

echo json_encode(["status" => "success", "message" => "Login successful"]);

$conn->close();

// ══════════════════════════════════════════════════════════════
// HELPER: Send Security Alert
// ══════════════════════════════════════════════════════════════

function sendSecurityAlert($contact, $ip) {
    $message = "Your " . SITE_NAME . " account has been temporarily locked due to multiple failed login attempts from IP: $ip. If this wasn't you, please reset your password immediately.";
    
    // Determine if contact is email or phone
    if (isValidEmail($contact)) {
        // Send email alert
        $subject = "Security Alert - Account Locked";
        $emailBody = "
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: sans-serif; background: #0f0f1a; color: #e2e8f0; padding: 30px;'>
          <div style='max-width: 500px; margin: 0 auto; background: #0c0c1c; border: 1px solid #1e1e3a; border-radius: 16px; padding: 40px; text-align: center;'>
            <h2 style='color: #ff4d4d; margin-bottom: 20px;'>🔒 Security Alert</h2>
            <p style='color: #cbd5e1; margin-bottom: 15px;'>$message</p>
            <p style='color: #475569; font-size: 0.85rem; margin-top: 30px;'>— The " . SITE_NAME . " Security Team</p>
          </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . OTP_FROM_NAME . " <" . OTP_FROM_EMAIL . ">\r\n";
        
        mail($contact, $subject, $emailBody, $headers);
        
    } elseif (isValidPhone($contact)) {
        // Send SMS alert via ClickSend
        $payload = json_encode([
            "messages" => [[
                "source" => "php",
                "from" => OTP_SMS_SENDER,
                "body" => $message,
                "to" => $contact
            ]]
        ]);
        
        $ch = curl_init("https://rest.clicksend.com/v3/sms/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(CLICKSEND_USERNAME . ':' . CLICKSEND_API_KEY)
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
}
?>