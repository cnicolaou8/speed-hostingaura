<?php
// ══════════════════════════════════════════════════════════════
// verify_otp.php — Verifies OTP and creates new user account
// Called via AJAX from index.html after user enters OTP code
// Creates user, sets session, and logs them in automatically
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

// ── READ JSON INPUT ───────────────────────────────────────────
$data     = json_decode(file_get_contents('php://input'), true);
$contact  = trim($data['contact'] ?? '');
$otp      = trim($data['otp'] ?? '');
$password = $data['password'] ?? '';

// ══════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ══════════════════════════════════════════════════════════════
if (empty($contact) || empty($otp) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// OTP must be exactly 6 digits
if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(["status" => "error", "message" => "Invalid OTP format"]);
    exit;
}

// Password must be at least 8 characters
if (strlen($password) < 8) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters"]);
    exit;
}

// Detect if contact is email or phone
$isPhone = false;
if (isValidEmail($contact)) {
    $isPhone = false;
} else {
    $contact = cleanPhone($contact);
    if (isValidPhone($contact)) {
        $isPhone = true;
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid email or phone number"]);
        exit;
    }
}

// ══════════════════════════════════════════════════════════════
// RATE LIMITING — Max 5 wrong OTP attempts per 5 minutes
// ══════════════════════════════════════════════════════════════
$fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
$stmt_rate = $conn->prepare("SELECT COUNT(*) as attempts FROM otp_verification_attempts 
                              WHERE contact = ? AND attempted_at > ?");
$stmt_rate->bind_param("ss", $contact, $fiveMinutesAgo);
$stmt_rate->execute();
$attempts = $stmt_rate->get_result()->fetch_assoc()['attempts'];
$stmt_rate->close();

if ($attempts >= OTP_MAX_ATTEMPTS) {
    echo json_encode(["status" => "error", "message" => "Too many attempts. Please request a new OTP."]);
    $conn->close();
    exit;
}

// Log this verification attempt
$stmt_log = $conn->prepare("INSERT INTO otp_verification_attempts (contact, attempted_at) VALUES (?, NOW())");
$stmt_log->bind_param("s", $contact);
$stmt_log->execute();
$stmt_log->close();

// ══════════════════════════════════════════════════════════════
// VERIFY OTP — Atomic update prevents race conditions
// Marks OTP as used (verified=1) only if valid and not expired
// ══════════════════════════════════════════════════════════════
$stmt_verify = $conn->prepare("UPDATE otp_verifications 
                                SET verified = 1 
                                WHERE contact = ? AND otp_code = ? 
                                AND expires_at > NOW() AND verified = 0");
$stmt_verify->bind_param("ss", $contact, $otp);
$stmt_verify->execute();

if ($stmt_verify->affected_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid or expired OTP. Please request a new one."]);
    $stmt_verify->close();
    $conn->close();
    exit;
}
$stmt_verify->close();

// ══════════════════════════════════════════════════════════════
// CREATE USER ACCOUNT
// Password is hashed with BCRYPT before storing
// ══════════════════════════════════════════════════════════════
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

if ($isPhone) {
    $stmt_create = $conn->prepare("INSERT INTO users (phone, password_hash, created_at) VALUES (?, ?, NOW())");
} else {
    $stmt_create = $conn->prepare("INSERT INTO users (email, password_hash, created_at) VALUES (?, ?, NOW())");
}

$stmt_create->bind_param("ss", $contact, $passwordHash);

if (!$stmt_create->execute()) {
    // Error 1062 = duplicate entry (account already exists)
    if ($conn->errno === 1062) {
        echo json_encode(["status" => "error", "message" => "An account with this contact already exists"]);
    } else {
        error_log("User creation failed: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Account creation failed. Please try again."]);
    }
    $stmt_create->close();
    $conn->close();
    exit;
}
$stmt_create->close();

// ══════════════════════════════════════════════════════════════
// FETCH NEW USER ID AND START SESSION
// ══════════════════════════════════════════════════════════════
$stmt_user = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt_user->bind_param("ss", $contact, $contact);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$user) {
    error_log("User lookup failed after creation for: $contact");
    echo json_encode(["status" => "error", "message" => "Account creation failed"]);
    $conn->close();
    exit;
}

// Set session and regenerate ID to prevent session fixation attacks
$_SESSION['user_id'] = $user['id'];
session_regenerate_id(true);

// Clear verification attempts for this contact
$stmt_clear = $conn->prepare("DELETE FROM otp_verification_attempts WHERE contact = ?");
$stmt_clear->bind_param("s", $contact);
$stmt_clear->execute();
$stmt_clear->close();

// ══════════════════════════════════════════════════════════════
// SUCCESS
// ══════════════════════════════════════════════════════════════
error_log("New user registered: ID " . $user['id'] . " via " . ($isPhone ? "phone" : "email"));
echo json_encode([
    "status"  => "success",
    "message" => "Account created! Welcome to HostingAura."
]);

$conn->close();
?>