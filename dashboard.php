<?php
/**
 * HostingAura Speed Test - Dashboard
 * Version: 1.0.0
 * Description: User dashboard with test history and profile settings
 */

session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'config.php';

// Get user data
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? 'user@example.com';

// Fetch user's test history
try {
    $db = getDBConnection();
    
    // Get total tests count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM speed_results WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_tests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get recent tests (last 10)
    $stmt = $db->prepare("
        SELECT 
            id,
            download_speed,
            upload_speed,
            ping,
            jitter,
            isp,
            ip_address,
            location,
            created_at
        FROM speed_results 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get average speeds
    $stmt = $db->prepare("
        SELECT 
            AVG(download_speed) as avg_download,
            AVG(upload_speed) as avg_upload,
            AVG(ping) as avg_ping
        FROM speed_results 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $averages = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $recent_tests = [];
    $total_tests = 0;
    $averages = ['avg_download' => 0, 'avg_upload' => 0, 'avg_ping' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HostingAura Speed Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #1D4ED8, #0EA5E9);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .logo-text h1 {
            font-size: 20px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .logo-text p {
            font-size: 12px;
            color: #94a3b8;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: #6366f1;
            transform: translateY(-2px);
        }
        
        .stat-label {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-unit {
            font-size: 16px;
            color: #64748b;
            margin-left: 4px;
        }
        
        /* Test History */
        .section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #e2e8f0;
        }
        
        .btn-new-test {
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-new-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        /* Test Table */
        .test-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .test-table th {
            text-align: left;
            padding: 12px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        }
        
        .test-table td {
            padding: 16px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .test-table tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }
        
        .speed-value {
            font-weight: 600;
            color: #6366f1;
        }
        
        .speed-good {
            color: #10b981;
        }
        
        .speed-medium {
            color: #f59e0b;
        }
        
        .speed-poor {
            color: #ef4444;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        /* Profile Menu Styles */
        .profile-menu-container {
            position: relative;
        }
        
        .profile-icon {
            background: transparent;
            border: 2px solid rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #e2e8f0;
        }
        
        .profile-icon:hover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
        }
        
        .profile-dropdown {
            position: absolute;
            top: 54px;
            right: 0;
            background: #1a1a2e;
            border: 1px solid #2a2a3e;
            border-radius: 12px;
            min-width: 240px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            z-index: 1000;
            animation: slideDown 0.2s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-header {
            padding: 16px;
            border-bottom: 1px solid #2a2a3e;
        }
        
        .profile-name {
            font-weight: 600;
            color: #e2e8f0;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .profile-email {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .profile-menu-divider {
            height: 1px;
            background: #2a2a3e;
            margin: 8px 0;
        }
        
        .profile-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .profile-menu-item:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
        .profile-menu-item svg {
            flex-shrink: 0;
        }
        
        .profile-menu-item-danger {
            color: #ef4444;
        }
        
        .profile-menu-item-danger:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
        }
        
        /* Modal Base */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            position: relative;
            background: #0a0a0f;
            border: 1px solid #2a2a3e;
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 2001;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #2a2a3e;
        }
        
        .modal-header h2 {
            font-size: 24px;
            color: #e2e8f0;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 32px;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        /* Settings Modal */
        .settings-body {
            padding: 24px;
        }
        
        .settings-section {
            margin-bottom: 32px;
        }
        
        .settings-section h3 {
            color: #6366f1;
            font-size: 16px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid #2a2a3e;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .setting-info {
            flex: 1;
        }
        
        .setting-label {
            color: #e2e8f0;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .setting-description {
            color: #94a3b8;
            font-size: 13px;
        }
        
        .setting-item-static {
            display: flex;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid #2a2a3e;
        }
        
        .setting-value {
            color: #94a3b8;
            font-family: monospace;
            font-size: 13px;
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #475569;
            transition: 0.3s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #6366f1;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        /* Schedule Configuration */
        .schedule-config {
            margin-top: 16px;
            padding: 16px;
            background: rgba(99, 102, 241, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
        }
        
        .schedule-time,
        .schedule-days {
            margin-bottom: 16px;
        }
        
        .schedule-time label,
        .schedule-days > label {
            display: block;
            color: #e2e8f0;
            font-size: 13px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .time-input {
            width: 100%;
            padding: 10px;
            background: #1a1a2e;
            border: 1px solid #2a2a3e;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 14px;
        }
        
        .days-selector {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .day-checkbox {
            flex: 1;
            min-width: 45px;
        }
        
        .day-checkbox input {
            display: none;
        }
        
        .day-checkbox span {
            display: block;
            padding: 8px;
            background: #1a1a2e;
            border: 1px solid #2a2a3e;
            border-radius: 6px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .day-checkbox input:checked + span {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }
        
        .btn-save-schedule {
            width: 100%;
            margin-top: 16px;
            padding: 12px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-save-schedule:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        /* About Modal */
        .about-body {
            padding: 24px;
            text-align: center;
        }
        
        .about-logo {
            margin-bottom: 24px;
        }
        
        .about-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, #1D4ED8, #0EA5E9);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: white;
        }
        
        .about-logo h3 {
            color: #e2e8f0;
            margin-bottom: 8px;
        }
        
        .version {
            color: #94a3b8;
            font-size: 14px;
        }
        
        .about-description {
            margin: 24px 0;
            color: #cbd5e1;
            line-height: 1.6;
        }
        
        .about-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 24px 0;
        }
        
        .about-link {
            padding: 12px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            color: #6366f1;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .about-link:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
        }
        
        .about-footer {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #2a2a3e;
            color: #64748b;
            font-size: 13px;
        }
        
        /* Help Modal */
        .help-body {
            padding: 24px;
        }
        
        .help-section {
            margin-bottom: 32px;
        }
        
        .help-section h3 {
            color: #6366f1;
            font-size: 16px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        .help-section ul {
            list-style: none;
            padding: 0;
        }
        
        .help-section li {
            padding: 12px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.02);
            border-left: 3px solid #6366f1;
            border-radius: 4px;
            color: #cbd5e1;
        }
        
        .help-section li strong {
            color: #e2e8f0;
            display: block;
            margin-bottom: 4px;
        }
        
        .faq-item {
            margin-bottom: 16px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid #2a2a3e;
            border-radius: 8px;
        }
        
        .faq-item strong {
            color: #e2e8f0;
            display: block;
            margin-bottom: 8px;
        }
        
        .faq-item p {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
        
        .help-contact-btn {
            display: inline-block;
            margin-top: 12px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .help-contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .test-table {
                font-size: 12px;
            }
            
            .test-table th,
            .test-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Profile Menu -->
        <div class="header">
            <div class="logo">
                <div class="logo-icon">HA</div>
                <div class="logo-text">
                    <h1>hostingaura</h1>
                    <p>SPEED TEST</p>
                </div>
            </div>
            
            <!-- Profile Menu -->
            <div class="profile-menu-container">
                <button class="profile-icon" onclick="toggleProfileMenu()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="10" r="3"/>
                        <path d="M6.168 18.849A4 4 0 0 1 10 16h4a4 4 0 0 1 3.834 2.855"/>
                    </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="profile-dropdown" class="profile-dropdown" style="display: none;">
                    <div class="profile-header">
                        <div class="profile-name">User</div>
                        <div class="profile-email"><?php echo htmlspecialchars($user_email); ?></div>
                    </div>
                    
                    <div class="profile-menu-divider"></div>
                    
                    <a href="#" onclick="openSettings(); return false;" class="profile-menu-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m-6-6L12 9.8M23 12h-6m-6 0H1m18.2 5.2l-4.2-4.2m-6 6L12 14.2"/>
                        </svg>
                        Settings
                    </a>
                    
                    <a href="#" onclick="openAbout(); return false;" class="profile-menu-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4m0-4h.01"/>
                        </svg>
                        About
                    </a>
                    
                    <a href="#" onclick="openHelp(); return false;" class="profile-menu-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3m.08 4h.01"/>
                        </svg>
                        Help
                    </a>
                    
                    <div class="profile-menu-divider"></div>
                    
                    <a href="logout.php" class="profile-menu-item profile-menu-item-danger">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14l5-5-5-5m5 5H9"/>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Tests</div>
                <div class="stat-value"><?php echo number_format($total_tests); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Avg Download</div>
                <div class="stat-value">
                    <?php echo number_format($averages['avg_download'] ?? 0, 1); ?>
                    <span class="stat-unit">Mbps</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Avg Upload</div>
                <div class="stat-value">
                    <?php echo number_format($averages['avg_upload'] ?? 0, 1); ?>
                    <span class="stat-unit">Mbps</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Avg Ping</div>
                <div class="stat-value">
                    <?php echo number_format($averages['avg_ping'] ?? 0, 0); ?>
                    <span class="stat-unit">ms</span>
                </div>
            </div>
        </div>
        
        <!-- Test History -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Tests</h2>
                <a href="index.php" class="btn-new-test">+ New Test</a>
            </div>
            
            <?php if (empty($recent_tests)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <p>No tests yet. Run your first speed test!</p>
                </div>
            <?php else: ?>
                <table class="test-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Ping</th>
                            <th>ISP</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_tests as $test): 
                            $download = floatval($test['download_speed']);
                            $upload = floatval($test['upload_speed']);
                            $ping = floatval($test['ping']);
                            
                            // Color classes based on speed
                            $download_class = $download >= 100 ? 'speed-good' : ($download >= 50 ? 'speed-medium' : 'speed-poor');
                            $upload_class = $upload >= 50 ? 'speed-good' : ($upload >= 20 ? 'speed-medium' : 'speed-poor');
                            $ping_class = $ping <= 30 ? 'speed-good' : ($ping <= 60 ? 'speed-medium' : 'speed-poor');
                        ?>
                        <tr>
                            <td><?php echo date('M j, Y H:i', strtotime($test['created_at'])); ?></td>
                            <td class="speed-value <?php echo $download_class; ?>">
                                <?php echo number_format($download, 1); ?> Mbps
                            </td>
                            <td class="speed-value <?php echo $upload_class; ?>">
                                <?php echo number_format($upload, 1); ?> Mbps
                            </td>
                            <td class="speed-value <?php echo $ping_class; ?>">
                                <?php echo number_format($ping, 0); ?> ms
                            </td>
                            <td><?php echo htmlspecialchars($test['isp'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($test['location'] ?? 'Unknown'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Settings Modal -->
    <div id="settings-modal" class="modal">
        <div class="modal-overlay" onclick="closeSettings()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>⚙️ Settings</h2>
                <button class="modal-close" onclick="closeSettings()">×</button>
            </div>
            
            <div class="settings-body">
                <!-- Notifications Section -->
                <div class="settings-section">
                    <h3>🔔 Notifications</h3>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-label">Push Notifications</div>
                            <div class="setting-description">Receive test reminders and updates</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="push-notifications-toggle" onchange="togglePushNotifications()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- Scheduled Tests Section -->
                <div class="settings-section">
                    <h3>⏰ Scheduled Tests</h3>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-label">Auto Speed Tests</div>
                            <div class="setting-description">Automatically run tests on schedule</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="scheduled-tests-toggle" onchange="toggleScheduledTests()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <!-- Schedule Configuration -->
                    <div id="schedule-settings" class="schedule-config" style="display: none;">
                        <div class="schedule-time">
                            <label>Test Time:</label>
                            <input type="time" id="test-time" value="09:00" class="time-input">
                        </div>
                        
                        <div class="schedule-days">
                            <label>Days of Week:</label>
                            <div class="days-selector">
                                <label class="day-checkbox">
                                    <input type="checkbox" value="1">
                                    <span>Sun</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="2" checked>
                                    <span>Mon</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="3" checked>
                                    <span>Tue</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="4" checked>
                                    <span>Wed</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="5" checked>
                                    <span>Thu</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="6" checked>
                                    <span>Fri</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" value="7">
                                    <span>Sat</span>
                                </label>
                            </div>
                        </div>
                        
                        <button onclick="saveSchedule()" class="btn-save-schedule">Save Schedule</button>
                    </div>
                </div>
                
                <!-- Security Section -->
                <div class="settings-section">
                    <h3>🔐 Security</h3>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <div class="setting-label">Face ID / Touch ID</div>
                            <div class="setting-description">Use biometric login for quick access</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="faceid-toggle" onchange="toggleFaceID()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- App Info Section -->
                <div class="settings-section">
                    <h3>ℹ️ App Information</h3>
                    
                    <div class="setting-item-static">
                        <div class="setting-label">Version</div>
                        <div class="setting-value">1.0.0 (Build 1)</div>
                    </div>
                    
                    <div class="setting-item-static">
                        <div class="setting-label">Bundle ID</div>
                        <div class="setting-value">com.hostingaura.speedtest</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- About Modal -->
    <div id="about-modal" class="modal">
        <div class="modal-overlay" onclick="closeAbout()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>About HostingAura</h2>
                <button class="modal-close" onclick="closeAbout()">×</button>
            </div>
            
            <div class="about-body">
                <div class="about-logo">
                    <div class="about-icon">HA</div>
                    <h3>hostingaura speedtest</h3>
                    <p class="version">Version 1.0.0</p>
                </div>
                
                <div class="about-description">
                    <p>Enterprise-grade network performance testing tool. Measure your internet speed with professional accuracy and detailed analytics.</p>
                </div>
                
                <div class="about-links">
                    <a href="https://hostingaura.com" target="_blank" class="about-link">
                        🌐 Visit Website
                    </a>
                    <a href="https://hostingaura.com/privacy-policy" target="_blank" class="about-link">
                        🔒 Privacy Policy
                    </a>
                    <a href="https://hostingaura.com/terms" target="_blank" class="about-link">
                        📄 Terms of Service
                    </a>
                </div>
                
                <div class="about-footer">
                    <p>© 2024 HostingAura. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help Modal -->
    <div id="help-modal" class="modal">
        <div class="modal-overlay" onclick="closeHelp()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Help & Support</h2>
                <button class="modal-close" onclick="closeHelp()">×</button>
            </div>
            
            <div class="help-body">
                <div class="help-section">
                    <h3>🚀 Getting Started</h3>
                    <ul>
                        <li><strong>Run a Test:</strong> Tap "Start Speed Test" on the home screen</li>
                        <li><strong>View History:</strong> All your past tests are saved in your dashboard</li>
                        <li><strong>Share Results:</strong> Tap "Share Link" after completing a test</li>
                    </ul>
                </div>
                
                <div class="help-section">
                    <h3>⚙️ Settings</h3>
                    <ul>
                        <li><strong>Push Notifications:</strong> Get reminders for scheduled tests</li>
                        <li><strong>Auto Tests:</strong> Schedule tests to run automatically</li>
                        <li><strong>Face ID:</strong> Enable biometric login for quick access</li>
                    </ul>
                </div>
                
                <div class="help-section">
                    <h3>❓ FAQs</h3>
                    <div class="faq-item">
                        <strong>Why are my results different from other speed tests?</strong>
                        <p>Different speed tests use different servers and methods. HostingAura uses enterprise-grade infrastructure for accurate results.</p>
                    </div>
                    <div class="faq-item">
                        <strong>How much data does a speed test use?</strong>
                        <p>Approximately 250-500MB per test (download + upload combined).</p>
                    </div>
                </div>
                
                <div class="help-section">
                    <h3>📧 Contact Support</h3>
                    <p>Need more help? Contact us:</p>
                    <a href="mailto:support@hostingaura.com" class="help-contact-btn">
                        Email Support
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Profile Menu Functions
        function toggleProfileMenu() {
            const dropdown = document.getElementById('profile-dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.profile-menu-container');
            if (container && !container.contains(event.target)) {
                document.getElementById('profile-dropdown').style.display = 'none';
            }
        });
        
        // Modal Functions
        function openSettings() {
            document.getElementById('profile-dropdown').style.display = 'none';
            document.getElementById('settings-modal').style.display = 'flex';
            loadSavedSettings();
        }
        
        function closeSettings() {
            document.getElementById('settings-modal').style.display = 'none';
        }
        
        function openAbout() {
            document.getElementById('profile-dropdown').style.display = 'none';
            document.getElementById('about-modal').style.display = 'flex';
        }
        
        function closeAbout() {
            document.getElementById('about-modal').style.display = 'none';
        }
        
        function openHelp() {
            document.getElementById('profile-dropdown').style.display = 'none';
            document.getElementById('help-modal').style.display = 'flex';
        }
        
        function closeHelp() {
            document.getElementById('help-modal').style.display = 'none';
        }
        
        // Settings Functions
        function loadSavedSettings() {
            const pushEnabled = localStorage.getItem('push_notifications_enabled') === 'true';
            document.getElementById('push-notifications-toggle').checked = pushEnabled;
            
            const scheduleData = localStorage.getItem('scheduled_tests');
            if (scheduleData) {
                const schedule = JSON.parse(scheduleData);
                document.getElementById('scheduled-tests-toggle').checked = schedule.enabled;
                if (schedule.enabled) {
                    document.getElementById('schedule-settings').style.display = 'block';
                    document.getElementById('test-time').value = schedule.time || '09:00';
                    
                    const dayCheckboxes = document.querySelectorAll('.days-selector input[type="checkbox"]');
                    dayCheckboxes.forEach(cb => {
                        cb.checked = schedule.days && schedule.days.includes(parseInt(cb.value));
                    });
                }
            }
            
            const faceIdEnabled = localStorage.getItem('biometric_enabled') === 'true';
            document.getElementById('faceid-toggle').checked = faceIdEnabled;
        }
        
        async function togglePushNotifications() {
            const enabled = document.getElementById('push-notifications-toggle').checked;
            
            if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                try {
                    const module = await import('./native-features.js');
                    if (enabled) {
                        const success = await module.enablePushNotifications();
                        if (!success) {
                            document.getElementById('push-notifications-toggle').checked = false;
                            alert('Please enable notifications in iOS Settings');
                        }
                    } else {
                        module.disablePushNotifications();
                    }
                } catch (error) {
                    console.error('Native features error:', error);
                }
            } else {
                localStorage.setItem('push_notifications_enabled', enabled ? 'true' : 'false');
                if (enabled) {
                    alert('Push notifications will be enabled in the iOS app');
                }
            }
        }
        
        async function toggleScheduledTests() {
            const enabled = document.getElementById('scheduled-tests-toggle').checked;
            
            if (enabled) {
                document.getElementById('schedule-settings').style.display = 'block';
            } else {
                document.getElementById('schedule-settings').style.display = 'none';
                
                if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                    try {
                        const module = await import('./native-features.js');
                        await module.setupScheduledTests({ enabled: false });
                    } catch (error) {
                        console.error('Schedule disable error:', error);
                    }
                }
                
                localStorage.setItem('scheduled_tests', JSON.stringify({ enabled: false }));
            }
        }
        
        async function saveSchedule() {
            const time = document.getElementById('test-time').value;
            const dayCheckboxes = document.querySelectorAll('.days-selector input[type="checkbox"]:checked');
            const days = Array.from(dayCheckboxes).map(cb => parseInt(cb.value));
            
            if (days.length === 0) {
                alert('Please select at least one day');
                return;
            }
            
            const schedule = {
                enabled: true,
                time: time,
                days: days
            };
            
            localStorage.setItem('scheduled_tests', JSON.stringify(schedule));
            
            if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                try {
                    const module = await import('./native-features.js');
                    const success = await module.setupScheduledTests(schedule);
                    if (success) {
                        alert('✅ Schedule saved! Tests will run automatically at ' + time);
                    }
                } catch (error) {
                    alert('Schedule saved locally');
                }
            } else {
                alert('✅ Schedule saved! Will activate in the iOS app.');
            }
        }
        
        async function toggleFaceID() {
            const enabled = document.getElementById('faceid-toggle').checked;
            
            if (window.Capacitor && window.Capacitor.isNativePlatform()) {
                try {
                    const module = await import('./native-features.js');
                    if (enabled) {
                        const available = await module.isBiometricAvailable();
                        if (!available) {
                            document.getElementById('faceid-toggle').checked = false;
                            alert('Face ID/Touch ID not available');
                            return;
                        }
                        
                        const success = await module.enableBiometricAuth();
                        if (!success) {
                            document.getElementById('faceid-toggle').checked = false;
                        }
                    } else {
                        localStorage.setItem('biometric_enabled', 'false');
                    }
                } catch (error) {
                    document.getElementById('faceid-toggle').checked = false;
                }
            } else {
                localStorage.setItem('biometric_enabled', enabled ? 'true' : 'false');
                if (enabled) {
                    alert('Face ID will be enabled in the iOS app');
                }
            }
        }
    </script>
</body>
</html>