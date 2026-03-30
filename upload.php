<?php
// ══════════════════════════════════════════════════════════════
// upload.php — Receives temporary files during upload speed test
// Called repeatedly by index.html to measure upload speed
// Files are saved temporarily then deleted by empty.php
// ══════════════════════════════════════════════════════════════
header("Content-Type: application/json");

// ── DEFINE UPLOAD FOLDER PATH ────────────────────────────────
$uploadDir = __DIR__ . '/uploads/';

// ── CREATE UPLOADS FOLDER IF IT DOESN'T EXIST ────────────────
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ══════════════════════════════════════════════════════════════
// VALIDATE INCOMING FILE
// ══════════════════════════════════════════════════════════════
if (!isset($_FILES['file'])) {
    echo json_encode(["status" => "error", "message" => "No file received"]);
    exit;
}

$file = $_FILES['file'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "Upload error: " . $file['error']]);
    exit;
}

// Limit file size to 100MB (speed test files only)
$maxSize = 100 * 1024 * 1024; // 100MB in bytes
if ($file['size'] > $maxSize) {
    echo json_encode(["status" => "error", "message" => "File too large"]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// SAVE FILE TEMPORARILY
// Uses a random name to avoid conflicts between parallel tests
// ══════════════════════════════════════════════════════════════
$tempName  = bin2hex(random_bytes(8)) . '.tmp';
$savePath  = $uploadDir . $tempName;

if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    echo json_encode(["status" => "error", "message" => "Failed to save file"]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// SUCCESS — Return file size so JS can calculate upload speed
// ══════════════════════════════════════════════════════════════
echo json_encode([
    "status" => "success",
    "size"   => $file['size']
]);
?>