<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/octet-stream');

$size = isset($_GET['size']) ? intval($_GET['size']) : 5;
$bytes = $size * 1024 * 1024;
$data = random_bytes(min($bytes, 10485760));
echo $data;
?>