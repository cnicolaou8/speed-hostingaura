<?php
header("Content-Type: application/json");
require_once 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $uid  = intval($_SESSION['user_id']);
    $res  = $conn->query("SELECT email FROM users WHERE id=$uid");
    $row  = $res->fetch_assoc();
    echo json_encode(["logged_in" => true, "email" => $row['email']]);
    $conn->close();
} else {
    echo json_encode(["logged_in" => false]);
}
?>