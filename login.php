<?php
// ══════════════════════════════════════════════════════════════
// login.php — Authenticates existing users
// Called via AJAX from index.html login form
// Includes brute force protection, account lockout,
// IP logging and security alert on repeated failures
// ══════════════════════════════════════════════════════════════
header("Content-Type: application/json");
require_once 'config.php';
session_start();

// ── DATABASE CONNECTION ───────────────────────────────────────
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Server error. Please try again."]);
    exit;
}

// ── CLEAN UP OLD LOGIN ATTEMPTS (older than 1 hour) ──────────
$cleanupTime = date('Y-m-d H:i:s', strtotime('-1 hour'));
$conn->query("DELETE FROM login_attempts WHERE attempted_at < '$cleanupTime'");

// ── READ JSON INPUT ───────────────────────────────────────────
$data     = json_decode(file_get_contents('php://input'), true);
$contact  = trim($data['contact'] ?? '');
$password = $data['password'] ?? '';

// ── CAPTURE USER IP AND BROWSER INFO FOR LOGGING ─────────────
$userIp    = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// ══════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ══════════════════════════════════════════════════════════════
if (empty($contact) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields"]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// CHECK ACCOUNT LOCKOUT
// Block login if too many failed attempts within lockout window
// ══════════════════════════════════════════════════════════════
$lockoutWindow = date('Y-m-d H:i:s', strtotime('-' . LOGIN_LOCKOUT_SECONDS . ' seconds'));

// Get most recent failed attempt
$stmt_last = $conn->prepare("SELECT attempted_at FROM login_attempts 
                              WHERE contact = ? AND attempted_at > ? 
                              ORDER BY attempted_at DESC LIMIT 1");
$stmt_last->bind_param("ss", $contact, $lockoutWindow);
$stmt_last->execute();
$lastAttempt = $stmt_last->get_result()->fetch_assoc();
$stmt_last->close();

// Count total attempts in lockout window
$stmt_count = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                               WHERE contact = ? AND attempted_at > ?");
$stmt_count->bind_param("ss", $contact, $lockoutWindow);
$stmt_count->execute();
$attempts = $stmt_count->get_result()->fetch_assoc()['attempts'];
$stmt_count->close();

if ($attempts >= LOGIN_MAX_ATTEMPTS && $lastAttempt) {
    $lockedAt    = strtotime($lastAttempt['attempted_at']);
    $unlockAt    = $lockedAt + LOGIN_LOCKOUT_SECONDS;
    $secondsLeft = max(0, $unlockAt - time());

    error_log("Login blocked (locked): $contact from IP: $userIp");

    echo json_encode([
        "status"       => "locked",
        "message"      => "Account temporarily locked. Please try again in:",
        "seconds_left" => $secondsLeft
    ]);
    $conn->close();
    exit;
}

// ══════════════════════════════════════════════════════════════
// VERIFY CREDENTIALS
// ══════════════════════════════════════════════════════════════
$stmt_login = $conn->prepare("SELECT id, password_hash, email, phone FROM users 
                               WHERE email = ? OR phone = ?");
$stmt_login->bind_param("ss", $contact, $contact);
$stmt_login->execute();
$user = $stmt_login->get_result()->fetch_assoc();
$stmt_login->close();

if ($user && password_verify($password, $user['password_hash'])) {

    // ══════════════════════════════════════════════════════════
    // LOGIN SUCCESSFUL
    // ══════════════════════════════════════════════════════════

    // Clear all previous failed attempts for this contact
    $stmt_clear = $conn->prepare("DELETE FROM login_attempts WHERE contact = ?");
    $stmt_clear->bind_param("s", $contact);
    $stmt_clear->execute();
    $stmt_clear->close();

    // Set session and regenerate ID to prevent session fixation
    $_SESSION['user_id'] = $user['id'];
    session_regenerate_id(true);

    // Update last login timestamp in DB
    $stmt_update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt_update->bind_param("i", $user['id']);
    $stmt_update->execute();
    $stmt_update->close();

    error_log("Successful login: User ID " . $user['id'] . " from IP: $userIp");

    echo json_encode(["status" => "success"]);

} else {

    // ══════════════════════════════════════════════════════════
    // LOGIN FAILED — Log attempt and warn user
    // ══════════════════════════════════════════════════════════

    // Record failed attempt with IP and user agent
    $stmt_fail = $conn->prepare("INSERT INTO login_attempts (contact, ip_address, user_agent, attempted_at) 
                                  VALUES (?, ?, ?, NOW())");
    $stmt_fail->bind_param("sss", $contact, $userIp, $userAgent);
    $stmt_fail->execute();
    $stmt_fail->close();

    // Recount attempts after logging new failure
    $stmt_recount = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                                    WHERE contact = ? AND attempted_at > ?");
    $stmt_recount->bind_param("ss", $contact, $lockoutWindow);
    $stmt_recount->execute();
    $newAttempts = $stmt_recount->get_result()->fetch_assoc()['attempts'];
    $stmt_recount->close();

    $remaining = max(0, LOGIN_MAX_ATTEMPTS - $newAttempts);

    error_log("Failed login: $contact from IP: $userIp (Attempt $newAttempts/" . LOGIN_MAX_ATTEMPTS . ")");

    // Send security alert if account just got locked
    if ($newAttempts >= LOGIN_MAX_ATTEMPTS && $user) {
        $alertContact = $user['email'] ?? $user['phone'];
        sendSecurityAlert($alertContact);
        error_log("Account locked: $contact — Security alert sent");
    }

    if ($remaining > 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid credentials. $remaining attempt(s) remaining before lockout."
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Account locked due to multiple failed attempts. Try again in 2 minutes."
        ]);
    }
}

$conn->close();

// ══════════════════════════════════════════════════════════════
// HELPER — Send security alert email or SMS on account lockout
// ══════════════════════════════════════════════════════════════
function sendSecurityAlert($contact) {
    $isSms = !isValidEmail($contact);

    if ($isSms) {
        // Send SMS alert via ClickSend
        $payload = json_encode([
            "messages" => [[
                "source" => "php",
                "from"   => OTP_SMS_SENDER,
                "body"   => "HostingAura Security Alert: 3 failed login attempts. Account locked 2 minutes. If not you, change your password.",
                "to"     => $contact
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

    } else {
        // Send email alert via PHP mail()
        $subject = "⚠️ Security Alert — " . SITE_NAME;
        $message = "
        <html>
        <body style='font-family:sans-serif;background:#0f0f1a;color:#e2e8f0;padding:30px'>
          <div style='max-width:500px;margin:0 auto;background:#0c0c1c;border:1px solid #1e1e3a;border-radius:16px;padding:30px'>
            <h2 style='color:#ef4444'>⚠️ Security Alert</h2>
            <p>We detected <strong>3 failed login attempts</strong> on your HostingAura account.</p>
            <p>Your account has been <strong>temporarily locked for 2 minutes</strong>.</p>
            <p style='color:#94a3b8'>If this was you, wait 2 minutes and try again.</p>
            <p style='color:#94a3b8'>If this <strong>wasn't you</strong>, change your password immediately.</p>
            <hr style='border:none;border-top:1px solid #1e1e3a;margin:20px 0'>
            <p style='font-size:0.75rem;color:#334155'>— The HostingAura Security Team</p>
          </div>
        </body>
        </html>";

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: HostingAura Security <" . OTP_FROM_EMAIL . ">\r\n";

        mail($contact, $subject, $message, $headers);
    }
}
?>