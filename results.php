<?php
require_once 'config.php';

// Get test ID from URL
$testId = $_GET['id'] ?? '';

if (empty($testId)) {
    header("Location: index.php");
    exit;
}

// Sanitize test ID
$testId = preg_replace('/[^A-Z0-9\-]/', '', $testId);

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Fetch test result
$stmt = $conn->prepare("SELECT * FROM speed_results WHERE test_id = ?");
$stmt->bind_param("s", $testId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$result) {
    header("Location: index.php");
    exit;
}

// Format date
$date = new DateTime($result['created_at']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Speed Test Result — HostingAura</title>

<!-- Open Graph / Social Media Preview -->
<meta property="og:title" content="HostingAura Speed Test Result"/>
<meta property="og:description" content="Download: <?= $result['download_speed'] ?> Mbps | Upload: <?= $result['upload_speed'] ?> Mbps | Ping: <?= $result['ping'] ?> ms"/>
<meta property="og:type" content="website"/>
<meta property="og:url" content="<?= SITE_URL ?>/result.php?id=<?= $testId ?>"/>

<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
  min-height:100vh;
  background:#070711;
  background-image:radial-gradient(ellipse 90% 55% at 50% 0%,rgba(99,102,241,.15) 0%,transparent 65%);
  color:#e2e8f0;
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:40px 20px;
}

.container{
  max-width:600px;
  width:100%;
}

.header{
  text-align:center;
  margin-bottom:30px;
}

.logo-text{
  font-size:1.8rem;
  font-weight:800;
  letter-spacing:-.5px;
  margin-bottom:8px;
}

.logo-hosting{
  background:linear-gradient(90deg,#38bdf8,#6366f1);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}

.logo-aura{
  background:linear-gradient(90deg,#a855f7,#ec4899);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}

.subtitle{
  font-size:0.75rem;
  letter-spacing:0.3em;
  text-transform:uppercase;
  color:#475569;
}

.result-card{
  background:#0c0c1c;
  border:1px solid #1e1e3a;
  border-radius:20px;
  padding:40px 30px;
  margin-bottom:20px;
}

.test-id{
  text-align:center;
  font-size:0.7rem;
  letter-spacing:0.1em;
  text-transform:uppercase;
  color:#475569;
  margin-bottom:30px;
}

.test-id-value{
  color:#6366f1;
  font-weight:700;
  font-family:monospace;
}

.speed-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:20px;
  margin-bottom:30px;
}

.speed-item{
  text-align:center;
}

.speed-value{
  font-size:2.5rem;
  font-weight:800;
  color:#e2e8f0;
  line-height:1;
  margin-bottom:8px;
}

.speed-label{
  font-size:0.65rem;
  letter-spacing:0.15em;
  text-transform:uppercase;
  color:#475569;
}

.info-grid{
  display:grid;
  grid-template-columns:1fr;
  gap:12px;
  margin-top:30px;
  padding-top:30px;
  border-top:1px solid #1e1e3a;
}

.info-item{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:10px 0;
}

.info-label{
  font-size:0.75rem;
  color:#64748b;
  letter-spacing:0.05em;
}

.info-value{
  font-size:0.85rem;
  color:#cbd5e1;
  font-weight:600;
  text-align:right;
}

.device-badge{
  background:#0c0c1c;
  border:1px solid #1e1e3a;
  padding:6px 12px;
  border-radius:8px;
  font-size:0.75rem;
  color:#94a3b8;
  display:inline-block;
}

.actions{
  display:flex;
  gap:12px;
  justify-content:center;
}

.btn{
  padding:12px 30px;
  border-radius:999px;
  font-size:0.85rem;
  font-weight:600;
  cursor:pointer;
  text-decoration:none;
  transition:0.2s;
  border:none;
}

.btn-primary{
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  color:#fff;
}

.btn-primary:hover{
  opacity:0.85;
}

.btn-secondary{
  background:#1e1e3a;
  color:#94a3b8;
  border:1px solid #334155;
}

.btn-secondary:hover{
  border-color:#6366f1;
  color:#6366f1;
}

.footer{
  text-align:center;
  margin-top:30px;
  font-size:0.7rem;
  color:#475569;
}

@media (max-width: 600px) {
  .speed-grid{
    grid-template-columns:1fr;
    gap:15px;
  }
  
  .speed-value{
    font-size:2rem;
  }
  
  .result-card{
    padding:30px 20px;
  }
}
</style>
</head>
<body>

<div class="container">
  <!-- HEADER -->
  <div class="header">
    <div class="logo-text">
      <span class="logo-hosting">hosting</span><span class="logo-aura">aura</span>
    </div>
    <div class="subtitle">Speed Test Result</div>
  </div>

  <!-- RESULT CARD -->
  <div class="result-card">
    <div class="test-id">
      Test ID: <span class="test-id-value"><?= htmlspecialchars($testId) ?></span>
    </div>

    <!-- SPEED RESULTS -->
    <div class="speed-grid">
      <div class="speed-item">
        <div class="speed-value"><?= $result['download_speed'] ?></div>
        <div class="speed-label">Download (Mbps)</div>
      </div>
      
      <div class="speed-item">
        <div class="speed-value"><?= $result['upload_speed'] ?></div>
        <div class="speed-label">Upload (Mbps)</div>
      </div>
      
      <div class="speed-item">
        <div class="speed-value"><?= $result['ping'] ?></div>
        <div class="speed-label">Ping (ms)</div>
      </div>
    </div>

    <!-- INFO GRID -->
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">📱 Device</div>
        <div class="info-value">
          <span class="device-badge"><?= htmlspecialchars($result['device']) ?></span>
        </div>
      </div>
      
      <div class="info-item">
        <div class="info-label">🌐 ISP</div>
        <div class="info-value"><?= htmlspecialchars($result['isp']) ?></div>
      </div>
      
      <div class="info-item">
        <div class="info-label">📍 IP Address</div>
        <div class="info-value"><?= htmlspecialchars($result['ip_address']) ?></div>
      </div>
      
      <div class="info-item">
        <div class="info-label">📅 Date</div>
        <div class="info-value"><?= $date->format('M j, Y') ?> at <?= $date->format('g:i A') ?></div>
      </div>
    </div>
  </div>

  <!-- ACTIONS -->
  <div class="actions">
    <a href="index.php" class="btn btn-primary">Run Your Test</a>
    <button class="btn btn-secondary" onclick="copyLink()">Copy Link</button>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    Powered by <strong>HostingAura</strong> — Enterprise Speed Test
  </div>
</div>

<script>
function copyLink() {
  const url = window.location.href;
  navigator.clipboard.writeText(url).then(() => {
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = 'Link Copied!';
    btn.style.color = '#22c55e';
    setTimeout(() => {
      btn.textContent = originalText;
      btn.style.color = '';
    }, 2000);
  }).catch(() => {
    alert('Link: ' + url);
  });
}
</script>
</body>
</html>