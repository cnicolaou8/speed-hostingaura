<?php
/**
 * Admin: View iOS App Launch Notification Signups
 * File: admin-app-launch-signups.php
 * 
 * SECURITY: Add authentication before deploying to production!
 */

session_start();
require_once 'config.php';

// ═══════════════════════════════════════════════════════════════
// SIMPLE PASSWORD PROTECTION (Change this password!)
// ═══════════════════════════════════════════════════════════════
$admin_password = 'PASSWORD!!!'; // CHANGE THIS!

if (!isset($_SESSION['app_launch_admin_logged_in'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === $admin_password) {
            $_SESSION['app_launch_admin_logged_in'] = true;
        } else {
            $error = 'Incorrect password';
        }
    }
    
    if (!isset($_SESSION['app_launch_admin_logged_in'])) {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login - App Launch Signups</title>
            <style>
                body { 
                    font-family: -apple-system, sans-serif; 
                    background: #0a0a0f; 
                    color: #e2e8f0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .login-box {
                    background: #1a1a2e;
                    padding: 40px;
                    border-radius: 12px;
                    border: 1px solid #2a2a3e;
                    width: 300px;
                }
                h2 { margin-top: 0; color: #6366f1; }
                input {
                    width: 100%;
                    padding: 12px;
                    margin: 10px 0;
                    background: #0a0a0f;
                    border: 1px solid #2a2a3e;
                    border-radius: 6px;
                    color: #e2e8f0;
                    font-size: 14px;
                }
                button {
                    width: 100%;
                    padding: 12px;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    border: none;
                    border-radius: 6px;
                    color: white;
                    font-weight: 600;
                    cursor: pointer;
                }
                .error { color: #ef4444; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>🔐 Admin Login</h2>
                <?php if (isset($error)): ?>
                    <p class="error"><?= $error ?></p>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Enter password" required autofocus>
                    <button type="submit">Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['app_launch_admin_logged_in']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// FETCH DATA
// ═══════════════════════════════════════════════════════════════
$db = getDBConnection();

// Get total count
$total_result = $db->query("SELECT COUNT(*) as total FROM app_launch_notifications");
$total_signups = $total_result->fetch_assoc()['total'];

// Get notified count
$notified_result = $db->query("SELECT COUNT(*) as total FROM app_launch_notifications WHERE notified = TRUE");
$total_notified = $notified_result->fetch_assoc()['total'];

// Get all signups
$stmt = $db->query("SELECT * FROM app_launch_notifications ORDER BY created_at DESC");
$signups = $stmt->fetch_all(MYSQLI_ASSOC);

// Export to CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="app-launch-signups-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Email', 'IP Address', 'Notified', 'Signup Date']);
    
    foreach ($signups as $row) {
        fputcsv($output, [
            $row['email'],
            $row['ip_address'],
            $row['notified'] ? 'Yes' : 'No',
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iOS App Launch Signups - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            color: #e2e8f0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 12px;
        }
        
        h1 {
            font-size: 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.4);
        }
        
        .btn-secondary {
            background: #1e1e3a;
            color: #94a3b8;
            border: 1px solid #334155;
        }
        
        .btn-secondary:hover {
            background: #2e2e4a;
            color: #e2e8f0;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 12px;
            padding: 20px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #94a3b8;
            font-size: 14px;
        }
        
        .table-container {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 12px;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #0f0f1f;
            color: #6366f1;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(99,102,241,0.2);
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #94a3b8;
        }
        
        tr:hover {
            background: rgba(99,102,241,0.05);
        }
        
        .email {
            color: #6366f1;
            font-weight: 500;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-yes {
            background: rgba(16,185,129,0.2);
            color: #10b981;
        }
        
        .badge-no {
            background: rgba(239,68,68,0.2);
            color: #ef4444;
        }
        
        .empty {
            text-align: center;
            padding: 60px;
            color: #64748b;
        }
        
        .copy-btn {
            background: none;
            border: none;
            color: #6366f1;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .copy-btn:hover {
            background: rgba(99,102,241,0.1);
        }
        
        .tooltip {
            position: relative;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            background-color: #6366f1;
            color: white;
            text-align: center;
            padding: 5px 10px;
            border-radius: 6px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -40px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }
        
        .tooltip.show .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 iOS App Launch Signups</h1>
            <div class="actions">
                <a href="?export=csv" class="btn btn-primary">📥 Export CSV</a>
                <button onclick="copyAllEmails()" class="btn btn-secondary">📋 Copy All Emails</button>
                <a href="?logout" class="btn btn-secondary">🚪 Logout</a>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($total_signups) ?></div>
                <div class="stat-label">Total Signups</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= number_format($total_signups - $total_notified) ?></div>
                <div class="stat-label">Pending Notifications</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= number_format($total_notified) ?></div>
                <div class="stat-label">Already Notified</div>
            </div>
        </div>
        
        <div class="table-container">
            <?php if (empty($signups)): ?>
                <div class="empty">
                    <p>📭 No signups yet!</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>IP Address</th>
                            <th>Notified</th>
                            <th>Signup Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signups as $signup): ?>
                        <tr>
                            <td class="email"><?= htmlspecialchars($signup['email']) ?></td>
                            <td><?= htmlspecialchars($signup['ip_address'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($signup['notified']): ?>
                                    <span class="badge badge-yes">✓ Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-no">✗ No</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y H:i', strtotime($signup['created_at'])) ?></td>
                            <td>
                                <div class="tooltip" id="tooltip-<?= $signup['id'] ?>">
                                    <button class="copy-btn" onclick="copyEmail('<?= htmlspecialchars($signup['email']) ?>', <?= $signup['id'] ?>)">
                                        📋 Copy
                                    </button>
                                    <span class="tooltiptext">Copied!</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyEmail(email, id) {
            navigator.clipboard.writeText(email);
            
            // Show tooltip
            const tooltip = document.getElementById('tooltip-' + id);
            tooltip.classList.add('show');
            
            setTimeout(() => {
                tooltip.classList.remove('show');
            }, 2000);
        }
        
        function copyAllEmails() {
            const emails = <?= json_encode(array_column($signups, 'email')) ?>;
            const emailList = emails.join(', ');
            
            navigator.clipboard.writeText(emailList);
            
            alert('✅ All emails copied to clipboard!\n\n' + emails.length + ' emails ready to paste.');
        }
    </script>
</body>
</html>