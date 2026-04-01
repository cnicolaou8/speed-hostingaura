<?php
/**
 * Server Health Monitor for HostingAura
 * 
 * Access at: https://speed.hostingaura.com/monitor.php
 * 
 * This provides real-time statistics about server performance,
 * database connections, and system resources.
 */

session_start();
require_once 'config.php';
require_once 'db_pool.php';

header('Content-Type: application/json');

$status = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'php_version' => PHP_VERSION,
        'hostname' => gethostname(),
    ],
    'performance' => [
        'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A',
        'memory' => [
            'used_mb' => round(memory_get_usage(true) / 1048576, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'limit' => ini_get('memory_limit')
        ],
        'execution_time' => [
            'max' => ini_get('max_execution_time') . 's',
            'current' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's'
        ]
    ],
    'database' => [
        'status' => 'unknown',
        'pool_stats' => []
    ],
    'php_config' => [
        'max_execution_time' => ini_get('max_execution_time') . 's',
        'memory_limit' => ini_get('memory_limit'),
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize')
    ]
];

// Get database connection pool statistics
try {
    $dbStats = getDBPoolStats();
    $status['database']['status'] = 'connected';
    $status['database']['pool_stats'] = $dbStats;
    
    // Get additional database info
    $conn = getDBConnection();
    
    // Count total speed tests
    $result = $conn->query("SELECT COUNT(*) as count FROM speed_results");
    if ($result) {
        $row = $result->fetch_assoc();
        $status['database']['total_tests'] = $row['count'];
    }
    
    // Count total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        $status['database']['total_users'] = $row['count'];
    }
    
    // Count total reports
    $result = $conn->query("SELECT COUNT(*) as count FROM report_issues");
    if ($result) {
        $row = $result->fetch_assoc();
        $status['database']['total_reports'] = $row['count'];
    }
    
    // Get tests in last 24 hours
    $result = $conn->query("SELECT COUNT(*) as count FROM speed_results WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    if ($result) {
        $row = $result->fetch_assoc();
        $status['database']['tests_last_24h'] = $row['count'];
    }
    
    // Check table sizes
    $result = $conn->query("
        SELECT 
            table_name,
            ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.TABLES
        WHERE table_schema = '" . DB_NAME . "'
        ORDER BY (data_length + index_length) DESC
    ");
    
    if ($result) {
        $tables = [];
        while ($row = $result->fetch_assoc()) {
            $tables[$row['table_name']] = $row['size_mb'] . ' MB';
        }
        $status['database']['table_sizes'] = $tables;
    }
    
} catch (Exception $e) {
    $status['database']['status'] = 'error';
    $status['database']['error'] = $e->getMessage();
}

// Add health check status
$health = 'healthy';
$warnings = [];

// Check memory usage
$memoryUsedPercent = (memory_get_usage(true) / (int)str_replace('M', '', ini_get('memory_limit'))) * 100;
if ($memoryUsedPercent > 80) {
    $health = 'warning';
    $warnings[] = 'High memory usage: ' . round($memoryUsedPercent, 1) . '%';
}

// Check database connections
if (isset($status['database']['pool_stats']['threads_connected']) && 
    isset($status['database']['pool_stats']['max_connections'])) {
    $connectionPercent = ($status['database']['pool_stats']['threads_connected'] / 
                         $status['database']['pool_stats']['max_connections']) * 100;
    if ($connectionPercent > 70) {
        $health = 'warning';
        $warnings[] = 'High database connection usage: ' . round($connectionPercent, 1) . '%';
    }
}

$status['health'] = [
    'status' => $health,
    'warnings' => $warnings
];

// Pretty print if accessed from browser
if (isset($_GET['pretty'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>HostingAura Server Monitor</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; 
            background: #0f0f1a; 
            color: #e2e8f0; 
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { 
            background: linear-gradient(135deg, #6366f1, #8b5cf6); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .section { 
            background: #1a1a2e; 
            border: 1px solid #2a2a3e; 
            border-radius: 10px; 
            padding: 20px; 
            margin: 20px 0; 
        }
        .section h2 { 
            color: #6366f1; 
            margin-top: 0; 
            font-size: 1.2rem;
        }
        .stat { 
            display: flex; 
            justify-content: space-between; 
            padding: 8px 0; 
            border-bottom: 1px solid #2a2a3e; 
        }
        .stat:last-child { border-bottom: none; }
        .label { color: #94a3b8; }
        .value { 
            color: #e2e8f0; 
            font-weight: 600; 
            font-family: monospace; 
        }
        .healthy { color: #22c55e; }
        .warning { color: #f59e0b; }
        .error { color: #ef4444; }
        .timestamp { 
            color: #64748b; 
            font-size: 0.9rem; 
            text-align: center; 
            margin-top: 20px; 
        }
    </style>
</head>
<body>
    <h1>🚀 HostingAura Server Monitor</h1>
    <div class="section">
        <h2>🏥 Health Status</h2>
        <div class="stat">
            <span class="label">Overall Status:</span>
            <span class="value ' . $health . '">' . strtoupper($health) . '</span>
        </div>';
    
    if (!empty($warnings)) {
        foreach ($warnings as $warning) {
            echo '<div class="stat"><span class="label warning">⚠️ ' . htmlspecialchars($warning) . '</span></div>';
        }
    }
    
    echo '</div>';
    
    // Continue with pretty HTML output...
    echo '<div class="section">
        <h2>💾 Database</h2>';
    foreach ($status['database'] as $key => $value) {
        if (is_array($value)) {
            echo '<div style="margin: 10px 0;"><strong>' . htmlspecialchars($key) . ':</strong></div>';
            foreach ($value as $k => $v) {
                echo '<div class="stat"><span class="label">' . htmlspecialchars($k) . ':</span><span class="value">' . htmlspecialchars($v) . '</span></div>';
            }
        } else {
            echo '<div class="stat"><span class="label">' . htmlspecialchars($key) . ':</span><span class="value">' . htmlspecialchars($value) . '</span></div>';
        }
    }
    echo '</div>';
    
    echo '<div class="timestamp">Last updated: ' . $status['timestamp'] . ' | <a href="?pretty" style="color: #6366f1;">Refresh</a> | <a href="monitor.php" style="color: #6366f1;">JSON</a></div>';
    echo '</body></html>';
} else {
    // JSON output
    echo json_encode($status, JSON_PRETTY_PRINT);
}
?>
