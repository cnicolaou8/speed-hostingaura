<?php
// ══════════════════════════════════════════════════════════════
// save_result.php — Saves speed test results to the database
// Called via AJAX from index.html after a speed test completes
// Works for both guests (no user_id) and logged-in users
// Returns a unique test_id used for sharing results
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
$download = floatval($data['download'] ?? 0);
$upload   = floatval($data['upload']   ?? 0);
$ping     = intval($data['ping']       ?? 0);
$isp      = trim($data['isp']          ?? 'Unknown');

// ── GET USER ID (null if guest) ───────────────────────────────
$userId = getUserId();

// ── GET USER IP ADDRESS ───────────────────────────────────────
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// ══════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ══════════════════════════════════════════════════════════════
if ($download <= 0 || $upload <= 0 || $ping <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid speed test results"]);
    $conn->close();
    exit;
}

// Sanitize ISP name — allow only safe characters
$isp = preg_replace('/[^a-zA-Z0-9\s\.\-]/', '', $isp);
$isp = substr($isp, 0, 255); // Max 255 characters

// ══════════════════════════════════════════════════════════════
// GENERATE UNIQUE TEST ID (8 characters)
// Keeps regenerating until a unique one is found
// ══════════════════════════════════════════════════════════════
do {
    $testId = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
    $stmt_check = $conn->prepare("SELECT id FROM speed_results WHERE test_id = ?");
    $stmt_check->bind_param("s", $testId);
    $stmt_check->execute();
    $stmt_check->store_result();
    $exists = $stmt_check->num_rows > 0;
    $stmt_check->close();
} while ($exists);

// ══════════════════════════════════════════════════════════════
// SAVE RESULT TO DATABASE
// ══════════════════════════════════════════════════════════════
$stmt = $conn->prepare("INSERT INTO speed_results 
                        (test_id, user_id, ip_address, isp, download_speed, upload_speed, ping, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("siisdd i",
    $testId,
    $userId,
    $ipAddress,
    $isp,
    $download,
    $upload,
    $ping
);

if (!$stmt->execute()) {
    error_log("Failed to save speed result: " . $conn->error);
    echo json_encode(["status" => "error", "message" => "Failed to save result. Please try again."]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// ══════════════════════════════════════════════════════════════
// SUCCESS — Return test ID for sharing
// ══════════════════════════════════════════════════════════════
echo json_encode([
    "status"  => "success",
    "test_id" => $testId,
    "share_url" => SITE_URL . "/results.php?id=" . $testId
]);

$conn->close();
?>