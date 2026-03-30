<?php
header("Content-Type: application/json");
require_once 'config.php';
session_start();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$data = json_decode(file_get_contents('php://input'), true);

$contact  = filter_var($data['contact'] ?? '', FILTER_SANITIZE_EMAIL);
$otp      = $conn->real_escape_string($data['otp'] ?? '');
$password = $data['password'] ?? '';

// Detect if contact is phone or email
$isPhone = preg_match('/^\+?[0-9\s\-]{7,15}$/', $contact);

// Check OTP
$sql    = "SELECT * FROM otp_verifications
           WHERE contact='$contact' AND otp_code='$otp'
           AND expires_at > NOW() AND verified=0
           ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid or expired OTP"]);
    exit;
}

// Mark OTP as used
$conn->query("UPDATE otp_verifications SET verified=1 WHERE contact='$contact' AND otp_code='$otp'");

// Insert into correct column based on contact type
$hash = password_hash($password, PASSWORD_BCRYPT);
if ($isPhone) {
    $stmt = $conn->prepare("INSERT IGNORE INTO users (phone, password_hash) VALUES (?, ?)");
} else {
    $stmt = $conn->prepare("INSERT IGNORE INTO users (email, password_hash) VALUES (?, ?)");
}
$stmt->bind_param("ss", $contact, $hash);
$stmt->execute();

// Get user id
$stmt2 = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt2->bind_param("ss", $contact, $contact);
$stmt2->execute();
$user = $stmt2->get_result()->fetch_assoc();

$_SESSION['user_id'] = $user['id'];
echo json_encode(["status" => "success", "message" => "Account created and logged in"]);
$conn->close();
?>