<?php
// Disable all buffering
while (@ob_end_flush());

header('HTTP/1.1 200 OK');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: text/plain');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handle = fopen("php://input", "rb");
    if ($handle) {
        $chunkSize = 65536;
        while (!feof($handle)) {
            $data = fread($handle, $chunkSize);
        }
        fclose($handle);
    }
}

echo "OK";
flush();
exit;
?>