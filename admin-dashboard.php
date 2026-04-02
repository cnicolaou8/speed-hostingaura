<?php
/**
 * HostingAura Admin Dashboard
 * Password-protected admin panel for monitoring all system activity
 * 
 * SECURITY: Change the password before deploying!
 */

session_start();
require_once 'config.php';

// ═══════════════════════════════════════════════════════════════
// PASSWORD PROTECTION - CHANGE THIS!
// ═══════════════════════════════════════════════════════════════
$admin_password = ''; // ⚠️ CHANGE THIS PASSWORD!

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = 'Incorrect password';
        }
    }
    
    // Show login form
    if (!isset($_SESSION['admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - HostingAura</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
                    color: #e2e8f0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .login-box {
                    background: #1a1a2e;
                    padding: 40px;
                    border-radius: 16px;
                    border: 1px solid rgba(99,102,241,0.3);
                    width: 400px;
                    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
                }
                h1 {
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    margin-bottom: 10px;
                    font-size: 28px;
                }
                .subtitle {
                    color: #94a3b8;
                    margin-bottom: 30px;
                    font-size: 14px;
                }
                input {
                    width: 100%;
                    padding: 14px;
                    margin: 10px 0;
                    background: #0a0a0f;
                    border: 1px solid #2a2a3e;
                    border-radius: 8px;
                    color: #e2e8f0;
                    font-size: 15px;
                }
                input:focus {
                    outline: none;
                    border-color: #6366f1;
                }
                button {
                    width: 100%;
                    padding: 14px;
                    background: linear-gradient(135deg, #6366f1, #8b5cf6);
                    border: none;
                    border-radius: 8px;
                    color: white;
                    font-weight: 600;
                    font-size: 15px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(99,102,241,0.4);
                }
                .error {
                    color: #ef4444;
                    font-size: 14px;
                    margin-top: 10px;
                    padding: 12px;
                    background: rgba(239,68,68,0.1);
                    border-radius: 6px;
                    border: 1px solid rgba(239,68,68,0.3);
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>🔐 Admin Dashboard</h1>
                <p class="subtitle">HostingAura Speed Test</p>
                <?php if (isset($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Enter admin password" required autofocus>
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
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// FETCH ALL DATA
// ═══════════════════════════════════════════════════════════════

$db = getDBConnection();

// Get view parameter
$view = $_GET['view'] ?? 'overview';

// ── OVERVIEW STATS ─────────────────────────────────────────────

// Total users
$users_result = $db->query("SELECT COUNT(*) as total FROM users");
$total_users = $users_result->fetch_assoc()['total'];

// Total speed tests
$tests_result = $db->query("SELECT COUNT(*) as total FROM speed_results");
$total_tests = $tests_result->fetch_assoc()['total'];

// Total SMS sent
$sms_result = $db->query("SELECT COUNT(*) as total, SUM(cost) as total_cost FROM sms_logs WHERE status = 'sent'");
$sms_stats = $sms_result->fetch_assoc();
$total_sms = $sms_stats['total'] ?? 0;
$total_sms_cost = $sms_stats['total_cost'] ?? 0;

// Total emails sent
$email_result = $db->query("SELECT COUNT(*) as total FROM email_logs WHERE status = 'sent'");
$total_emails = $email_result->fetch_assoc()['total'] ?? 0;

// Recent signups (last 7 days)
$recent_users_result = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$recent_users = $recent_users_result->fetch_assoc()['total'];

// Recent tests (last 24 hours)
$recent_tests_result = $db->query("SELECT COUNT(*) as total FROM speed_results WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$recent_tests = $recent_tests_result->fetch_assoc()['total'];

// ── DETAILED DATA ──────────────────────────────────────────────

$limit = 50; // Records per page

// Users data
if ($view === 'users') {
    $users_query = $db->query("
        SELECT 
            u.*,
            COUNT(sr.id) as test_count
        FROM users u
        LEFT JOIN speed_results sr ON u.id = sr.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT $limit
    ");
    $users_data = $users_query->fetch_all(MYSQLI_ASSOC);
}

// SMS data
if ($view === 'sms') {
    $sms_query = $db->query("
        SELECT s.*, u.email as user_email
        FROM sms_logs s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.created_at DESC
        LIMIT $limit
    ");
    $sms_data = $sms_query->fetch_all(MYSQLI_ASSOC);
}

// Email data
if ($view === 'emails') {
    $email_query = $db->query("
        SELECT e.*, u.email as user_email
        FROM email_logs e
        LEFT JOIN users u ON e.user_id = u.id
        ORDER BY e.created_at DESC
        LIMIT $limit
    ");
    $email_data = $email_query->fetch_all(MYSQLI_ASSOC);
}

// Speed tests data
if ($view === 'tests') {
    $tests_query = $db->query("
        SELECT sr.*, u.email as user_email
        FROM speed_results sr
        LEFT JOIN users u ON sr.user_id = u.id
        ORDER BY sr.created_at DESC
        LIMIT $limit
    ");
    $tests_data = $tests_query->fetch_all(MYSQLI_ASSOC);
}

// CSV Export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $filename = "hostingaura-{$export_type}-" . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if ($export_type === 'users') {
        fputcsv($output, ['ID', 'Email', 'Phone', 'Test Count', 'Created At']);
        foreach ($users_data as $row) {
            fputcsv($output, [
                $row['id'],
                $row['email'] ?? $row['phone'],
                $row['phone'],
                $row['test_count'],
                $row['created_at']
            ]);
        }
    } elseif ($export_type === 'sms') {
        fputcsv($output, ['ID', 'Phone', 'Message', 'Type', 'Status', 'Cost', 'Date']);
        foreach ($sms_data as $row) {
            fputcsv($output, [
                $row['id'],
                $row['recipient_phone'],
                $row['message_text'],
                $row['message_type'],
                $row['status'],
                $row['cost'],
                $row['created_at']
            ]);
        }
    } elseif ($export_type === 'emails') {
        fputcsv($output, ['ID', 'Email', 'Subject', 'Type', 'Status', 'Date']);
        foreach ($email_data as $row) {
            fputcsv($output, [
                $row['id'],
                $row['recipient_email'],
                $row['subject'],
                $row['email_type'],
                $row['status'],
                $row['created_at']
            ]);
        }
    } elseif ($export_type === 'tests') {
        fputcsv($output, ['ID', 'User', 'Download', 'Upload', 'Ping', 'ISP', 'Date']);
        foreach ($tests_data as $row) {
            fputcsv($output, [
                $row['id'],
                $row['user_email'] ?? 'Guest',
                $row['download_speed'],
                $row['upload_speed'],
                $row['ping'],
                $row['isp'],
                $row['created_at']
            ]);
        }
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
    <title>Admin Dashboard - HostingAura</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            color: #e2e8f0;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(99,102,241,0.2);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 24px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: inline-block;
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
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: #6366f1;
            transform: translateY(-2px);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .stat-meta {
            color: #64748b;
            font-size: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        
        /* Navigation Tabs */
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(99,102,241,0.2);
            padding-bottom: 0;
        }
        
        .nav-tab {
            padding: 12px 24px;
            color: #94a3b8;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .nav-tab:hover {
            color: #e2e8f0;
        }
        
        .nav-tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }
        
        /* Table */
        .table-container {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(99,102,241,0.2);
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: #e2e8f0;
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
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid rgba(99,102,241,0.2);
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #94a3b8;
            font-size: 14px;
        }
        
        tr:hover {
            background: rgba(99,102,241,0.05);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: rgba(16,185,129,0.2);
            color: #10b981;
        }
        
        .badge-error {
            background: rgba(239,68,68,0.2);
            color: #ef4444;
        }
        
        .badge-pending {
            background: rgba(245,158,11,0.2);
            color: #f59e0b;
        }
        
        .email-cell {
            color: #6366f1;
            font-weight: 500;
        }
        
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .expand-btn {
            background: none;
            border: none;
            color: #6366f1;
            cursor: pointer;
            font-size: 12px;
            padding: 0;
            margin-left: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        /* Refresh indicator */
        .last-updated {
            font-size: 12px;
            color: #64748b;
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🎛️ Admin Dashboard</h1>
        <div class="header-actions">
            <a href="?view=overview" class="btn btn-secondary">🏠 Overview</a>
            <a href="?logout" class="btn btn-secondary">🚪 Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= number_format($total_users) ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-meta">+<?= $recent_users ?> in last 7 days</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🚀</div>
                <div class="stat-value"><?= number_format($total_tests) ?></div>
                <div class="stat-label">Speed Tests</div>
                <div class="stat-meta">+<?= $recent_tests ?> in last 24 hours</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📱</div>
                <div class="stat-value"><?= number_format($total_sms) ?></div>
                <div class="stat-label">SMS Sent</div>
                <div class="stat-meta">€<?= number_format($total_sms_cost, 2) ?> total cost<br><small style="color: #64748b;">Since Apr 2, 2026</small></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📧</div>
                <div class="stat-value"><?= number_format($total_emails) ?></div>
                <div class="stat-label">Emails Sent</div>
                <div class="stat-meta">All time total<br><small style="color: #64748b;">Since Apr 2, 2026</small></div>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="?view=overview" class="nav-tab <?= $view === 'overview' ? 'active' : '' ?>">
                📊 Overview
            </a>
            <a href="?view=users" class="nav-tab <?= $view === 'users' ? 'active' : '' ?>">
                👥 Users
            </a>
            <a href="?view=tests" class="nav-tab <?= $view === 'tests' ? 'active' : '' ?>">
                🚀 Speed Tests
            </a>
            <a href="?view=sms" class="nav-tab <?= $view === 'sms' ? 'active' : '' ?>">
                📱 SMS Logs
            </a>
            <a href="?view=emails" class="nav-tab <?= $view === 'emails' ? 'active' : '' ?>">
                📧 Email Logs
            </a>
        </div>
        
        <!-- Content Based on View -->
        <?php if ($view === 'overview'): ?>
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">📊 System Overview</h2>
                </div>
                <div style="padding: 40px; text-align: center; color: #94a3b8;">
                    <p style="font-size: 18px; margin-bottom: 20px;">
                        Welcome to the HostingAura Admin Dashboard!
                    </p>
                    <p style="font-size: 14px; line-height: 1.8;">
                        Use the tabs above to view detailed information about:
                        <br><br>
                        <strong>👥 Users</strong> - All registered users and their activity
                        <br>
                        <strong>🚀 Speed Tests</strong> - All speed tests run on the platform
                        <br>
                        <strong>📱 SMS Logs</strong> - All SMS messages sent (OTPs, notifications)
                        <br>
                        <strong>📧 Email Logs</strong> - All emails sent to users
                    </p>
                </div>
            </div>
        
        <?php elseif ($view === 'users'): ?>
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">👥 Registered Users</h2>
                    <a href="?view=users&export=users" class="btn btn-primary">📥 Export CSV</a>
                </div>
                
                <?php if (empty($users_data)): ?>
                    <div class="empty-state">No users yet!</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email / Phone</th>
                                <th>Phone</th>
                                <th>Tests Run</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_data as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td class="email-cell"><?= htmlspecialchars($user['email'] ?? $user['phone']) ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                <td><?= $user['test_count'] ?></td>
                                <td><?= date('M j, Y H:i', strtotime($user['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        
        <?php elseif ($view === 'tests'): ?>
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">🚀 Speed Tests</h2>
                    <a href="?view=tests&export=tests" class="btn btn-primary">📥 Export CSV</a>
                </div>
                
                <?php if (empty($tests_data)): ?>
                    <div class="empty-state">No tests yet!</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Download</th>
                                <th>Upload</th>
                                <th>Ping</th>
                                <th>ISP</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tests_data as $test): ?>
                            <tr>
                                <td><?= $test['id'] ?></td>
                                <td class="email-cell"><?= htmlspecialchars($test['user_email'] ?? 'Guest') ?></td>
                                <td><?= number_format($test['download_speed'], 1) ?> Mbps</td>
                                <td><?= number_format($test['upload_speed'], 1) ?> Mbps</td>
                                <td><?= number_format($test['ping'], 0) ?> ms</td>
                                <td><?= htmlspecialchars($test['isp'] ?? 'Unknown') ?></td>
                                <td><?= date('M j, Y H:i', strtotime($test['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        
        <?php elseif ($view === 'sms'): ?>
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">📱 SMS Logs</h2>
                    <a href="?view=sms&export=sms" class="btn btn-primary">📥 Export CSV</a>
                </div>
                
                <?php if (empty($sms_data)): ?>
                    <div class="empty-state">No SMS sent yet! (Table may need to be created)</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Phone</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Cost</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sms_data as $sms): ?>
                            <tr>
                                <td><?= $sms['id'] ?></td>
                                <td><?= htmlspecialchars($sms['recipient_phone']) ?></td>
                                <td>
                                    <div class="message-preview">
                                        <?= htmlspecialchars($sms['message_text']) ?>
                                    </div>
                                </td>
                                <td><?= ucfirst($sms['message_type']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $sms['status'] === 'sent' ? 'success' : ($sms['status'] === 'failed' ? 'error' : 'pending') ?>">
                                        <?= ucfirst($sms['status']) ?>
                                    </span>
                                </td>
                                <td>€<?= number_format($sms['cost'] ?? 0, 4) ?></td>
                                <td><?= date('M j, Y H:i', strtotime($sms['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        
        <?php elseif ($view === 'emails'): ?>
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">📧 Email Logs</h2>
                    <a href="?view=emails&export=emails" class="btn btn-primary">📥 Export CSV</a>
                </div>
                
                <?php if (empty($email_data)): ?>
                    <div class="empty-state">No emails sent yet! (Table may need to be created)</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($email_data as $email): ?>
                            <tr>
                                <td><?= $email['id'] ?></td>
                                <td class="email-cell"><?= htmlspecialchars($email['recipient_email']) ?></td>
                                <td>
                                    <div class="message-preview">
                                        <?= htmlspecialchars($email['subject']) ?>
                                    </div>
                                </td>
                                <td><?= ucfirst($email['email_type']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $email['status'] === 'sent' ? 'success' : ($email['status'] === 'failed' ? 'error' : 'pending') ?>">
                                        <?= ucfirst($email['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y H:i', strtotime($email['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="last-updated">
            Last updated: <?= date('M j, Y H:i:s') ?>
            <br>
            <a href="?view=<?= $view ?>" style="color: #6366f1; text-decoration: none;">🔄 Refresh</a>
        </div>
    </div>
</body>
</html>