<?php
header("Content-Type: application/json");
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed"]));
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $testId = substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $ip     = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];

    $isp    = $conn->real_escape_string($data['isp']);
    $dl     = floatval($data['dl']);
    $ul     = floatval($data['ul']);
    $ping   = intval($data['ping']);

    // Attach user_id if logged in
    session_start();
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $user_id_val = $user_id ? $user_id : 'NULL';

    $sql = "INSERT INTO speed_results (test_id, ip_address, isp, download_speed, upload_speed, ping, user_id, created_at)
            VALUES ('$testId', '$ip', '$isp', '$dl', '$ul', '$ping', $user_id_val, NOW())";

    if ($conn->query($sql) === TRUE) {
        echo json_encode([
            "status"    => "success",
            "share_url" => "https://speed.hostingaura.com/results.php?id=" . $testId,
            "test_id"   => $testId
        ]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}
$conn->close();
?>