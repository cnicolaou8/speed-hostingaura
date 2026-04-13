<?php
// empty.php - LibreSpeed upload/ping endpoint
// Receives upload data and discards it
// Returns minimal response

header('HTTP/1.1 200 OK');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: text/plain');
header('Content-Length: 0');
header('Connection: keep-alive');

// Read and discard POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_get_contents('php://input');
}

// Return empty response
exit;