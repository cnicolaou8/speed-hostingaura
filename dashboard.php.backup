<?php
// ══════════════════════════════════════════════════════════════
// dashboard.php — User's personal speed test history page
// Requires login — redirects to index.html if not logged in
// Shows statistics (avg download, upload, ping) and paginated
// history table with links to share individual results
// ══════════════════════════════════════════════════════════════
require_once 'config.php';
session_start();

// ── SECURITY HEADERS ─────────────────────────────────────────
header("X-Frame-Options: DENY");              // Prevent clickjacking
header("X-Content-Type-Options: nosniff");    // Prevent MIME sniffing
header("Referrer-Policy: strict-origin-when-cross-origin");

// ── REQUIRE LOGIN ─────────────────────────────────────────────
requireLogin();

// ── DATABASE CONNECTION ───────────────────────────────────────
$conn = getDBConnection();
if (!$conn) {
    die("Server error. Please try again later.");
}

$userId = getUserId();

// ══════════════════════════════════════════════════════════════
// FETCH USER INFO
// ══════════════════════════════════════════════════════════════
$stmt_user = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
$stmt_user->bind_param("i", $userId);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Use email if available, otherwise phone
$username = $user['email'] ?? $user['phone'] ?? 'User';

// ══════════════════════════════════════════════════════════════
// FETCH USER STATISTICS
// ══════════════════════════════════════════════════════════════
$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_tests,
    ROUND(AVG(download_speed), 1) as avg_dl,
    ROUND(AVG(upload_speed), 1) as avg_ul,
    ROUND(AVG(ping), 0) as avg_ping,
    MAX(download_speed) as max_dl,
    MAX(upload_speed) as max_ul
    FROM speed_results WHERE user_id = ?");
$stmt_stats->bind_param("i", $userId);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// ══════════════════════════════════════════════════════════════
// PAGINATION — 20 results per page
// ══════════════════════════════════════════════════════════════
$page       = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage    = 20;
$offset     = ($page - 1) * $perPage;
$totalTests = $stats['total_tests'];
$totalPages = max(1, ceil($totalTests / $perPage));

// ══════════════════════════════════════════════════════════════
// FETCH PAGINATED RESULTS
// ══════════════════════════════════════════════════════════════
$stmt_results = $conn->prepare("SELECT * FROM speed_results 
                                 WHERE user_id = ? 
                                 ORDER BY created_at DESC 
                                 LIMIT ? OFFSET ?");
$stmt_results->bind_param("iii", $userId, $perPage, $offset);
$stmt_results->execute();
$results = $stmt_results->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>My Speed Test History — HostingAura</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #070711;
      background-image: radial-gradient(ellipse 90% 55% at 50% 0%, rgba(99,102,241,.15) 0%, transparent 65%);
      color: #e2e8f0;
      padding: 2rem;
      min-height: 100vh;
    }
    .container { max-width: 1200px; margin: 0 auto; }

    /* ── Header ── */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .logo { font-size: 1.5rem; font-weight: 800; }
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
    .user-info { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .username { color: #94a3b8; font-size: 0.9rem; }

    /* ── Buttons ── */
    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      transition: 0.2s;
      display: inline-block;
    }
    .btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99,102,241,0.3); }
    .btn-secondary { background: #1e1e3a; color: #94a3b8; border: 1px solid #334155; }
    .btn-secondary:hover { background: #2e2e4a; color: #e2e8f0; }

    /* ── Page Title ── */
    h1 { color: #e2e8f0; margin-bottom: 0.5rem; font-size: 2rem; }
    .subtitle { color: #64748b; margin-bottom: 2rem; }

    /* ── Stats Cards ── */
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .stat-card { background: #0c0c1c; border: 1px solid #181830; border-radius: 12px; padding: 1.5rem; }
    .stat-value { font-size: 2.5rem; font-weight: 700; color: #6366f1; margin-bottom: 0.5rem; }
    .stat-label { color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }

    /* ── Results Table ── */
    .table-container {
      background: #0c0c1c;
      border: 1px solid #181830;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 2rem;
    }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #181830; }
    th { background: #0f0f1f; color: #6366f1; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; }
    td { color: #94a3b8; font-size: 0.9rem; }
    tr:hover { background: #0f0f1f; }
    tr:last-child td { border-bottom: none; }
    .test-id { font-family: monospace; color: #6366f1; font-size: 0.85rem; }
    a.share { color: #6366f1; text-decoration: none; font-weight: 600; }
    a.share:hover { text-decoration: underline; }
    .no-results { color: #64748b; text-align: center; padding: 3rem; }

    /* ── Pagination ── */
    .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap; }
    .page-btn {
      padding: 10px 16px;
      background: #1e1e3a;
      color: #94a3b8;
      border: 1px solid #334155;
      border-radius: 8px;
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .page-btn:hover { background: #2e2e4a; color: #e2e8f0; }
    .page-btn.active { background: #6366f1; color: #fff; border-color: #6366f1; }
    .page-btn.disabled { opacity: 0.5; pointer-events: none; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      body { padding: 1rem; }
      table { font-size: 0.8rem; }
      th, td { padding: 0.75rem 0.5rem; }
      .stats { grid-template-columns: 1fr; }
      h1 { font-size: 1.5rem; }
    }
  </style>
</head>
<body>
  <div class="container">

    <!-- Header -->
    <div class="header">
      <div class="logo">
        <span class="logo-hosting">hosting</span><span class="logo-aura">aura</span>
      </div>
      <div class="user-info">
        <span class="username">👤 <?= htmlspecialchars($username) ?></span>
        <a href="index.php" class="btn btn-primary">Run Test</a>
        <a href="logout.php" class="btn btn-secondary">Logout</a>
      </div>
    </div>

    <!-- Page Title -->
    <h1>Speed Test History</h1>
    <p class="subtitle">Your complete testing history</p>

    <!-- Stats Cards -->
    <?php if ($totalTests > 0): ?>
    <div class="stats">
      <div class="stat-card">
        <div class="stat-value"><?= number_format($totalTests) ?></div>
        <div class="stat-label">Total Tests</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $stats['avg_dl'] ?? '0' ?></div>
        <div class="stat-label">Avg Download (Mbps)</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $stats['avg_ul'] ?? '0' ?></div>
        <div class="stat-label">Avg Upload (Mbps)</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $stats['avg_ping'] ?? '0' ?>ms</div>
        <div class="stat-label">Avg Ping</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Results Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Download</th>
            <th>Upload</th>
            <th>Ping</th>
            <th>ISP</th>
            <th>Test ID</th>
            <th>Share</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($results->num_rows === 0): ?>
            <tr>
              <td colspan="7" class="no-results">
                No tests yet.<br><br>
                <a href="index.html" class="btn btn-primary">Run Your First Test</a>
              </td>
            </tr>
          <?php else: ?>
            <?php while ($row = $results->fetch_assoc()):
              $date = new DateTime($row['created_at']);
            ?>
            <tr>
              <td><?= $date->format('M j, Y g:i A') ?></td>
              <td><?= htmlspecialchars($row['download_speed']) ?> Mbps</td>
              <td><?= htmlspecialchars($row['upload_speed']) ?> Mbps</td>
              <td><?= htmlspecialchars($row['ping']) ?> ms</td>
              <td><?= htmlspecialchars($row['isp']) ?></td>
              <td><span class="test-id"><?= htmlspecialchars($row['test_id']) ?></span></td>
              <td><a class="share" href="results.php?id=<?= htmlspecialchars($row['test_id']) ?>" target="_blank">View →</a></td>
            </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="page-btn">← Previous</a>
      <?php endif; ?>

      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="page-btn">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</body>
</html>
<?php
$stmt_results->close();
$conn->close();
?>