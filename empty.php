<?php
// ══════════════════════════════════════════════════════════════
// empty.php — Deletes temporary files used during speed testing
// Called via AJAX from index.html after upload test completes
// Keeps the uploads folder clean by removing test files
// ══════════════════════════════════════════════════════════════
header("Content-Type: application/json");

// ── DEFINE UPLOAD FOLDER PATH ────────────────────────────────
$uploadDir = __DIR__ . '/uploads/';

// ── CHECK IF FOLDER EXISTS ────────────────────────────────────
if (!is_dir($uploadDir)) {
    echo json_encode(["status" => "success", "message" => "Nothing to clean"]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// DELETE ALL FILES IN UPLOADS FOLDER
// Skips . and .. (current and parent directory references)
// ══════════════════════════════════════════════════════════════
$files   = scandir($uploadDir);
$deleted = 0;

foreach ($files as $file) {
    // Skip directories — only delete files
    if ($file === '.' || $file === '..') continue;

    $filePath = $uploadDir . $file;

    if (is_file($filePath)) {
        unlink($filePath);
        $deleted++;
    }
}

// ══════════════════════════════════════════════════════════════
// SUCCESS
// ══════════════════════════════════════════════════════════════
echo json_encode([
    "status"  => "success",
    "deleted" => $deleted
]);
?>