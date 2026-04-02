<?php
/**
 * API: Save iOS App Launch Notification Email
 * File: api/notify-app-launch.php
 * 
 * Saves user emails who want to be notified when iOS app launches
 */

// CORS headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../config.php';

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate email
$email = isset($data['email']) ? trim($data['email']) : '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM app_launch_notifications WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email already registered
        echo json_encode([
            'success' => true,
            'message' => 'You are already on the notification list!',
            'already_registered' => true
        ]);
        exit;
    }
    $stmt->close();
    
    // Insert new email
    $stmt = $db->prepare("
        INSERT INTO app_launch_notifications 
        (email, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt->bind_param("sss", $email, $ip_address, $user_agent);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'You\'re on the list! We\'ll email you when the app launches.'
        ]);
    } else {
        throw new Exception('Failed to save email');
    }
    
    $stmt->close();
    $db->close();
    
} catch (Exception $e) {
    error_log("App launch notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
}
?>