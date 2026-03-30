<?php
header("Content-Type: application/json");
require_once 'config.php';
session_start();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$data = json_decode(file_get_contents('php://input'), true);

$contact  = trim($data['contact'] ?? '');
$password = $data['password'] ?? '';

if (!$contact || !$password) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields"]);
    exit;
}

// ── CHECK LOCKOUT (3 failed attempts in last 2 mins) ──
$lockout_window = date('Y-m-d H:i:s', strtotime('-2 minutes'));
$stmt_check = $conn->prepare("SELECT attempted_at FROM login_attempts WHERE contact = ? AND attempted_at > ? ORDER BY attempted_at DESC LIMIT 1");
$stmt_check->bind_param("ss", $contact, $lockout_window);
$stmt_check->execute();
$last_attempt = $stmt_check->get_result()->fetch_assoc();

// Count attempts in window
$stmt_count = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE contact = ? AND attempted_at > ?");
$stmt_count->bind_param("ss", $contact, $lockout_window);
$stmt_count->execute();
$attempts = $stmt_count->get_result()->fetch_assoc()['attempts'];

if ($attempts >= 3 && $last_attempt) {
    $locked_at    = strtotime($last_attempt['attempted_at']);
    $unlock_at    = $locked_at + 120; // 2 minutes
    $seconds_left = max(0, $unlock_at - time());

    echo json_encode([
        "status"       => "locked",
        "message"      => "Account temporarily locked. Please try again in:",
        "seconds_left" => $seconds_left
    ]);
    exit;
}

// ── CHECK CREDENTIALS ──
$stmt = $conn->prepare("SELECT id, password_hash, email, phone FROM users WHERE email = ? OR phone = ?");
$stmt->bind_param("ss", $contact, $contact);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result && password_verify($password, $result['password_hash'])) {
    // ── SUCCESS — clear failed attempts and log in ──
    $stmt_clear = $conn->prepare("DELETE FROM login_attempts WHERE contact = ?");
    $stmt_clear->bind_param("s", $contact);
    $stmt_clear->execute();

    $_SESSION['user_id'] = $result['id'];
    echo json_encode(["status" => "success"]);

} else {
    // ── FAILED — log attempt ──
    $stmt_log = $conn->prepare("INSERT INTO login_attempts (contact) VALUES (?)");
    $stmt_log->bind_param("s", $contact);
    $stmt_log->execute();

    // Count remaining attempts
    $stmt_check2 = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE contact = ? AND attempted_at > ?");
    $stmt_check2->bind_param("ss", $contact, $lockout_window);
    $stmt_check2->execute();
    $new_attempts = $stmt_check2->get_result()->fetch_assoc()['attempts'];
    $remaining = max(0, 3 - $new_attempts);

    // ── SEND ALERT if this attempt triggered the lockout ──
    if ($new_attempts >= 3 && $result) {
        $alert_contact = $result['email'] ?? $result['phone'];
        $is_phone = !filter_var($alert_contact, FILTER_VALIDATE_EMAIL);

        if ($is_phone) {
            // SMS Alert
            $payload = json_encode([
                "messages" => [[
                    "source" => "php",
                    "from"   => OTP_SMS_SENDER,
                    "body"   => "HostingAura Security Alert: Someone tried to log into your " . SITE_NAME . " account and failed 3 times. Your account is locked for 2 minutes. If this wasn't you, please change your password immediately.",
                    "to"     => $alert_contact
                ]]
            ]);
            $ch = curl_init("https://rest.clicksend.com/v3/sms/send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_USERPWD, CLICKSEND_USERNAME . ':' . CLICKSEND_API_KEY);
            curl_exec($ch);
            curl_close($ch);
        } else {
            // Email Alert
            $payload = json_encode([
                "to"      => [["email" => $alert_contact, "name" => "User"]],
                "from"    => ["email" => OTP_FROM_EMAIL, "name" => OTP_FROM_NAME],
                "subject" => "⚠️ Security Alert — " . SITE_NAME,
                "body"    => "
                    <p>Hello,</p>
                    <p>We detected <strong>3 failed login attempts</strong> on your <strong>" . SITE_NAME . "</strong> account.</p>
                    <p>Your account has been <strong>temporarily locked for 2 minutes</strong>.</p>
                    <p>If this was you, simply wait 2 minutes and try again.</p>
                    <p>If this <strong>wasn't you</strong>, we strongly recommend changing your password immediately.</p>
                    <br>
                    <p>— The HostingAura Security Team</p>
                "
            ]);
            $ch = curl_init("https://rest.clicksend.com/v3/transactional-email/send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_USERPWD, CLICKSEND_USERNAME . ':' . CLICKSEND_API_KEY);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    // Return remaining attempts message
    if ($remaining > 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid credentials. $remaining attempt(s) remaining before lockout."
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Account temporarily locked due to multiple failed attempts. Please try again in 2 minutes."
        ]);
    }
}

$conn->close();
?>