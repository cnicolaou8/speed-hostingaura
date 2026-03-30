<?php
// ══════════════════════════════════════════════════════════════
// check_session.php — Returns current login status as JSON
// Called via AJAX from index.html to check if user is logged in
// Used to show/hide login button and username in the header
// ══════════════════════════════════════════════════════════════
header("Content-Type: application/json");
require_once 'config.php';
session_start();

if (isLoggedIn()) {

    // ── USER IS LOGGED IN — Return their info ─────────────────
    $conn = getDBConnection();

    if (!$conn) {
        echo json_encode(["loggedIn" => true, "username" => "User"]);
        exit;
    }

    // Fetch email or phone to display as username
    $stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", getUserId());
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    // Use email if available, otherwise use phone number
    $username = $user['email'] ?? $user['phone'] ?? 'User';

    echo json_encode([
        "loggedIn" => true,
        "username" => $username
    ]);

} else {

    // ── USER IS NOT LOGGED IN ─────────────────────────────────
    echo json_encode(["loggedIn" => false]);

}
?>