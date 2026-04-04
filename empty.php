<?php
// ══════════════════════════════════════════════════════════════
// empty.php — Upload sink for speed test
// Streams and discards data as fast as possible
// ══════════════════════════════════════════════════════════════

// Disable output buffering completely
if (ob_get_level()) ob_end_clean();

// No time limit, no memory waste
set_time_limit(30);

// Allow cross-origin uploads
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Stream and discard — never buffer into memory
$input = fopen('php://input', 'rb');
$bytes = 0;
if ($input) {
    while (!feof($input)) {
        $chunk  = fread($input, 65536); // 64KB chunks
        $bytes += strlen($chunk);
    }
    fclose($input);
}

echo json_encode(['ok' => true, 'bytes' => $bytes]);