<?php
header("Content-Type: application/json");
require_once 'config.php';
session_start();

// Database connection
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Server error. Please try again."]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$contact = trim($data['contact'] ?? '');
$otp = trim($data['otp'] ?? '');
$newPassword = $data['password'] ?? '';

// ══════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ══════════════════════════════════════════════════════════════

if (empty($contact) || empty($otp) || empty($newPassword)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Validate OTP format
if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(["status" => "error", "message" => "Invalid OTP format"]);
    exit;
}

// Validate password strength
if (strlen($newPassword) < 8) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long"]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// RATE LIMITING: Max 5 verification attempts per 15 minutes
// ══════════════════════════════════════════════════════════════

$fifteenMinutesAgo = date('Y-m-d H:i:s', strtotime('-15 minutes'));
$stmt_rate = $conn->prepare("SELECT COUNT(*) as attempts 
                              FROM otp_verification_attempts 
                              WHERE contact = ? AND attempted_at > ?");
$stmt_rate->bind_param("ss", $contact, $fifteenMinutesAgo);
$stmt_rate->execute();
$attempts = $stmt_rate->get_result()->fetch_assoc()['attempts'];
$stmt_rate->close();

if ($attempts >= OTP_MAX_ATTEMPTS) {
    // Calculate time remaining
    $stmt_last = $conn->prepare("SELECT attempted_at FROM otp_verification_attempts 
                                  WHERE contact = ? AND attempted_at > ? 
                                  ORDER BY attempted_at ASC LIMIT 1");
    $stmt_last->bind_param("ss", $contact, $fifteenMinutesAgo);
    $stmt_last->execute();
    $last_attempt = $stmt_last->get_result()->fetch_assoc();
    $stmt_last->close();
    
    if ($last_attempt) {
        $unlock_time = strtotime($last_attempt['attempted_at']) + 900; // 15 minutes (900 seconds)
        $seconds_left = max(0, $unlock_time - time());
        $minutes_left = ceil($seconds_left / 60);
        
        echo json_encode([
            "status" => "error",
            "message" => "Too many failed attempts. Please wait $minutes_left minute(s) before trying again."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Too many attempts. Please try again in 15 minutes."
        ]);
    }
    
    $conn->close();
    exit;
}

// Log verification attempt
$stmt_log = $conn->prepare("INSERT INTO otp_verification_attempts (contact, attempted_at) VALUES (?, NOW())");
$stmt_log->bind_param("s", $contact);
$stmt_log->execute();
$stmt_log->close();

// ══════════════════════════════════════════════════════════════
// VERIFY OTP (atomic operation)
// ══════════════════════════════════════════════════════════════

$stmt_verify = $conn->prepare("UPDATE otp_verifications 
                               SET verified = 1 
                               WHERE contact = ? AND otp_code = ? 
                               AND expires_at > NOW() AND verified = 0");
$stmt_verify->bind_param("ss", $contact, $otp);
$stmt_verify->execute();

if ($stmt_verify->affected_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid or expired code. Please request a new one."]);
    $stmt_verify->close();
    $conn->close();
    exit;
}
$stmt_verify->close();

// ══════════════════════════════════════════════════════════════
// UPDATE PASSWORD
// ══════════════════════════════════════════════════════════════

$passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

$stmt_update = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ? OR phone = ?");
$stmt_update->bind_param("sss", $passwordHash, $contact, $contact);

if (!$stmt_update->execute() || $stmt_update->affected_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Failed to reset password. Please try again."]);
    $stmt_update->close();
    $conn->close();
    exit;
}
$stmt_update->close();

// ══════════════════════════════════════════════════════════════
// GET USER ID AND LOG IN
// ══════════════════════════════════════════════════════════════

$stmt_user = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt_user->bind_param("ss", $contact, $contact);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if ($user) {
    // Set session and log in user
    $_SESSION['user_id'] = $user['id'];
    session_regenerate_id(true);
    
    // Update last login
    $stmt_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt_login->bind_param("i", $user['id']);
    $stmt_login->execute();
    $stmt_login->close();
    
    // ══════════════════════════════════════════════════════════════
    // CLEAR ALL VERIFICATION ATTEMPTS (Important!)
    // ══════════════════════════════════════════════════════════════
    $stmt_clear = $conn->prepare("DELETE FROM otp_verification_attempts WHERE contact = ?");
    $stmt_clear->bind_param("s", $contact);
    $stmt_clear->execute();
    $stmt_clear->close();
    
    error_log("Password reset successful for user ID: " . $user['id']);
}

echo json_encode([
    "status" => "success",
    "message" => "Password reset successfully! Logging you in..."
]);

$conn->close();
?>