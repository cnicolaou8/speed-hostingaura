<?php
header("Content-Type: application/json");
require_once 'config.php';
session_start();

// ══════════════════════════════════════════════════════════════
// DATABASE CONNECTION
// ══════════════════════════════════════════════════════════════

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// GET POST DATA
// ══════════════════════════════════════════════════════════════

$data = json_decode(file_get_contents('php://input'), true);

$isp = trim($data['isp'] ?? 'Unknown ISP');
$downloadSpeed = floatval($data['dl'] ?? 0);
$uploadSpeed = floatval($data['ul'] ?? 0);
$ping = intval($data['ping'] ?? 0);
$device = trim($data['device'] ?? 'Unknown Device');

// ══════════════════════════════════════════════════════════════
// GET CLIENT INFO
// ══════════════════════════════════════════════════════════════

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userId = $_SESSION['user_id'] ?? null;

// ══════════════════════════════════════════════════════════════
// GENERATE UNIQUE TEST ID
// ══════════════════════════════════════════════════════════════

$testId = 'HA-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

// ══════════════════════════════════════════════════════════════
// INSERT INTO DATABASE
// ══════════════════════════════════════════════════════════════

$stmt = $conn->prepare("INSERT INTO speed_results 
                        (test_id, user_id, ip_address, isp, device, download_speed, upload_speed, ping, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

$stmt->bind_param("sisssddi", $testId, $userId, $ip, $isp, $device, $downloadSpeed, $uploadSpeed, $ping);

if ($stmt->execute()) {
    $shareUrl = SITE_URL . "/result.php?id=" . $testId;
    
    echo json_encode([
        "status" => "success",
        "test_id" => $testId,
        "share_url" => $shareUrl,
        "message" => "Test saved successfully"
    ]);
    
    error_log("Speed test saved: $testId - Device: $device - DL: $downloadSpeed Mbps - UL: $uploadSpeed Mbps");
} else {
    error_log("Failed to save speed test: " . $stmt->error);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save test results"
    ]);
}

$stmt->close();
$conn->close();
?>