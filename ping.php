<?php
// Minimal ping endpoint - returns smallest possible response
// No sessions, no DB, no includes - pure speed
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');
header('Content-Length: 4');
echo 'pong';