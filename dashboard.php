<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$uid  = intval($_SESSION['user_id']);
$results = $conn->query("SELECT * FROM speed_results WHERE user_id=$uid ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>My Speed Test History — HostingAura</title>
  <style>
    body { font-family: sans-serif; background: #0f0f1a; color: #fff; padding: 2rem; }
    h1 { color: #00d4ff; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 10px 14px; border-bottom: 1px solid #333; text-align: left; }
    th { background: #1a1a2e; color: #00d4ff; }
    tr:hover { background: #1a1a2e; }
    a { color: #00d4ff; }
    .logout { float: right; color: #ff4d4d; text-decoration: none; }
  </style>
</head>
<body>
  <a href="logout.php" class="logout">Logout</a>
  <h1>My Speed Test History</h1>
  <p>All your results on <strong>speed.hostingaura.com</strong></p>
  <table>
    <tr>
      <th>Date</th><th>Download (Mbps)</th><th>Upload (Mbps)</th>
      <th>Ping (ms)</th><th>ISP</th><th>Share</th>
    </tr>
    <?php while ($row = $results->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['created_at']) ?></td>
      <td><?= htmlspecialchars($row['download_speed']) ?></td>
      <td><?= htmlspecialchars($row['upload_speed']) ?></td>
      <td><?= htmlspecialchars($row['ping']) ?></td>
      <td><?= htmlspecialchars($row['isp']) ?></td>
      <td><a href="results.php?id=<?= $row['test_id'] ?>">View</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>