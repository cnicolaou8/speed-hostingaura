<?php
// ══════════════════════════════════════════════════════════════
// results.php — Public shareable speed test result page
// Accessed via: results.php?id=XXXXXXXX
// Displays download, upload, ping, ISP and test date
// Works for both guests and logged-in users
// ══════════════════════════════════════════════════════════════
require_once 'config.php';

// ── DATABASE CONNECTION ───────────────────────────────────────
$conn = getDBConnection();
if (!$conn) {
    die("Server error. Please try again later.");
}

// ── GET TEST ID FROM URL ──────────────────────────────────────
$testId = trim($_GET['id'] ?? '');

// Validate test ID format (must be 8 alphanumeric characters)
if (!preg_match('/^[a-z0-9]{8}$/', $testId)) {
    header("Location: index.html");
    exit;
}

// ══════════════════════════════════════════════════════════════
// FETCH RESULT FROM DATABASE
// ══════════════════════════════════════════════════════════════
$stmt = $conn->prepare("SELECT * FROM speed_results WHERE test_id = ?");
$stmt->bind_param("s", $testId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Redirect home if test ID not found
if (!$result) {
    header("Location: index.html");
    exit;
}

// ── FORMAT DATE ───────────────────────────────────────────────
$date = new DateTime($result['created_at']);
$formattedDate = $date->format('F j, Y \a\t g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>

  <!-- SEO & Share Meta Tags -->
  <title>Speed Test Result — <?= htmlspecialchars($result['download_speed']) ?> Mbps — HostingAura</title>
  <meta name="description" content="Download: <?= htmlspecialchars($result['download_speed']) ?> Mbps | Upload: <?= htmlspecialchars($result['upload_speed']) ?> Mbps | Ping: <?= htmlspecialchars($result['ping']) ?> ms">

  <!-- Open Graph (for sharing on social media) -->
  <meta property="og:title" content="My Speed Test — HostingAura"/>
  <meta property="og:description" content="⬇️ <?= htmlspecialchars($result['download_speed']) ?> Mbps  ⬆️ <?= htmlspecialchars($result['upload_speed']) ?> Mbps  📡 <?= htmlspecialchars($result['ping']) ?> ms"/>
  <meta property="og:url" content="<?= SITE_URL ?>/results.php?id=<?= htmlspecialchars($testId) ?>"/>

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #070711;
      background-image: radial-gradient(ellipse 90% 55% at 50% 0%, rgba(99,102,241,.15) 0%, transparent 65%);
      color: #e2e8f0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .card {
      background: #0c0c1c;
      border: 1px solid #1e1e3a;
      border-radius: 20px;
      padding: 3rem;
      max-width: 600px;
      width: 100%;
      text-align: center;
    }

    /* Logo */
    .logo { font-size: 1.5rem; font-weight: 800; margin-bottom: 2rem; }
    .logo-hosting {
      background: linear-gradient(90deg, #38bdf8, #6366f1);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .logo-aura {
      background: linear-gradient(90deg, #a855f7, #ec4899);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* Result Stats */
    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin: 2rem 0;
    }
    .stat {
      background: #0f0f1f;
      border: 1px solid #181830;
      border-radius: 12px;
      padding: 1.5rem 1rem;
    }
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #6366f1;
      margin-bottom: 0.25rem;
    }
    .stat-unit {
      font-size: 0.75rem;
      color: #475569;
      margin-bottom: 0.5rem;
    }
    .stat-label {
      font-size: 0.7rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Meta Info */
    .meta {
      color: #475569;
      font-size: 0.85rem;
      margin: 1.5rem 0;
      line-height: 1.8;
    }
    .meta span { color: #64748b; }

    /* Buttons */
    .actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem; }
    .btn {
      padding: 12px 24px;
      border-radius: 10px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      border: none;
      transition: 0.2s;
    }
    .btn-primary {
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99,102,241,0.3); }
    .btn-secondary {
      background: #1e1e3a;
      color: #94a3b8;
      border: 1px solid #334155;
    }
    .btn-secondary:hover { background: #2e2e4a; color: #e2e8f0; }

    /* Test ID */
    .test-id {
      font-family: monospace;
      color: #334155;
      font-size: 0.75rem;
      margin-top: 1.5rem;
    }

    @media (max-width: 480px) {
      .card { padding: 2rem 1.5rem; }
      .stats { grid-template-columns: 1fr; }
      .stat-value { font-size: 1.5rem; }
    }
  </style>
</head>
<body>
  <div class="card">

    <!-- Logo -->
    <div class="logo">
      <span class="logo-hosting">hosting</span><span class="logo-aura">aura</span>
    </div>

    <h2 style="color:#e2e8f0; margin-bottom:0.5rem">Speed Test Result</h2>
    <p style="color:#64748b; font-size:0.9rem; margin-bottom:1rem"><?= $formattedDate ?></p>

    <!-- Stats -->
    <div class="stats">
      <div class="stat">
        <div class="stat-value"><?= htmlspecialchars($result['download_speed']) ?></div>
        <div class="stat-unit">Mbps</div>
        <div class="stat-label">⬇️ Download</div>
      </div>
      <div class="stat">
        <div class="stat-value"><?= htmlspecialchars($result['upload_speed']) ?></div>
        <div class="stat-unit">Mbps</div>
        <div class="stat-label">⬆️ Upload</div>
      </div>
      <div class="stat">
        <div class="stat-value"><?= htmlspecialchars($result['ping']) ?></div>
        <div class="stat-unit">ms</div>
        <div class="stat-label">📡 Ping</div>
      </div>
    </div>

    <!-- Meta Info -->
    <div class="meta">
      <span>ISP:</span> <?= htmlspecialchars($result['isp']) ?><br>
      <span>Test ID:</span> <?= htmlspecialchars($result['test_id']) ?>
    </div>

    <!-- Action Buttons -->
    <div class="actions">
      <a href="index.html" class="btn btn-primary">Run New Test</a>
      <button class="btn btn-secondary" onclick="copyLink()">📋 Copy Link</button>
    </div>

    <div class="test-id">speed.hostingaura.com/results.php?id=<?= htmlspecialchars($testId) ?></div>

  </div>

  <script>
    // Copy shareable link to clipboard
    function copyLink() {
      navigator.clipboard.writeText(window.location.href).then(() => {
        alert('Link copied to clipboard!');
      });
    }
  </script>
</body>
</html>