<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed.");
}

$testId = $conn->real_escape_string($_GET['id'] ?? '');
$res    = $conn->query("SELECT * FROM speed_results WHERE test_id = '$testId'");
$data   = $res->fetch_assoc();

if (!$data) {
    die("Speed test result not found.");
}

$timestamp     = new DateTime($data['created_at']);
$formattedDate = $timestamp->format('M j, Y');
$formattedTime = $timestamp->format('g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Test Results — HostingAura</title>
<style>
body{background:#070711;color:#e2e8f0;font-family:-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
.box{background:#0c0c1c;border:1px solid #181830;padding:40px;border-radius:24px;text-align:center;width:320px;box-shadow:0 10px 40px rgba(0,0,0,0.6)}
.title{font-size:0.8rem;color:#6366f1;text-transform:uppercase;letter-spacing:3px;margin-bottom:30px}
.test-id-display{font-size:0.7rem;color:#64748b;margin-bottom:20px;font-family:monospace}
.test-id-display .lbl{color:#475569;text-transform:uppercase;letter-spacing:2px;font-size:0.6rem;display:block;margin-bottom:5px}
.test-id-display .tid{color:#6366f1;font-weight:700;font-size:0.85rem}
.timestamp{font-size:0.72rem;color:#64748b;margin-bottom:25px;line-height:1.5}
.timestamp-date{display:block;font-weight:600;color:#94a3b8}
.timestamp-time{display:block;font-size:0.68rem;color:#475569;margin-top:3px}
.val{font-size:3.5rem;font-weight:800;margin:5px 0;line-height:1}
.label{font-size:0.65rem;color:#475569;text-transform:uppercase;margin-bottom:25px;letter-spacing:1px}
.isp-tag{font-size:0.7rem;color:#94a3b8;margin-bottom:20px;display:block;opacity:0.7}
.btn{display:inline-block;margin-top:30px;color:#fff;background:linear-gradient(135deg,#6366f1,#8b5cf6);text-decoration:none;font-size:0.85rem;font-weight:600;padding:12px 30px;border-radius:99px;transition:0.2s}
.btn:hover{transform:scale(1.05)}
.privacy-footer{margin-top:30px;padding-top:20px;border-top:1px solid #181830;font-size:0.65rem;color:#64748b;line-height:1.6}
.privacy-footer a{color:#6366f1;text-decoration:none;font-weight:600}
.privacy-footer a:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="box">
    <div class="title">HostingAura Result</div>
    <div class="test-id-display">
        <span class="lbl">Test ID</span>
        <span class="tid"><?php echo htmlspecialchars($testId); ?></span>
    </div>
    <div class="timestamp">
        <span class="timestamp-date"><?php echo $formattedDate; ?></span>
        <span class="timestamp-time"><?php echo $formattedTime; ?></span>
    </div>
    <span class="isp-tag">ISP: <?php echo htmlspecialchars($data['isp']); ?></span>
    <div class="val" style="color:#6366f1"><?php echo $data['download_speed']; ?></div>
    <div class="label">Mbps Download</div>
    <div class="val" style="color:#ec4899"><?php echo $data['upload_speed']; ?></div>
    <div class="label">Mbps Upload</div>
    <div style="color:#94a3b8;font-size:0.8rem">Latency: <?php echo $data['ping']; ?>ms</div>
    <a href="index.html" class="btn">Test Again</a>
    <div class="privacy-footer">
        By using our speed test service, you agreed to our <a href="privacy.html" target="_blank">Privacy Policy</a>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>