<?php
/**
 * HostingAura Admin Dashboard with 2FA
 * Password + Google Authenticator Two-Factor Authentication
 * Version: 2.0
 * 
 * SECURITY SETUP REQUIRED:
 * 1. Change $admin_password below
 * 2. Generate new $totp_secret (see instructions below)
 * 3. Keep these credentials SECURE!
 */

session_start();
require_once 'config.php';

// ═══════════════════════════════════════════════════════════════
// SECURITY CREDENTIALS - CHANGE THESE IMMEDIATELY!
// ═══════════════════════════════════════════════════════════════

$admin_password = ''; // ⚠️ CHANGE THIS PASSWORD!
$totp_secret = ''; // ⚠️ CHANGE THIS SECRET!

/**
 * CRITICAL: Generate a new TOTP secret (Base32 format)
 * 
 * IMPORTANT RULES:
 * - Use ONLY these characters: A-Z (uppercase) and 2-7
 * - Length: 16-20 characters recommended
 * - NO lowercase, NO 0, 1, 8, 9, NO special characters
 * 
 * Generate new secret:
 * Method 1: https://www.random.org/strings/?num=1&len=16&upperalpha=on&unique=on&format=plain
 *           Then manually replace any letters after Z with numbers 2-7
 * 
 * Method 2: Use this valid example format:
 *           KBZG6Y3FMRQW2LKN (only A-Z and 2-7)
 * 
 * Valid characters: ABCDEFGHIJKLMNOPQRSTUVWXYZ234567
 * Example valid secrets:
 * - JBSWY3DPEHPK3PXP
 * - KBZGCY3FMRQW2LKN  
 * - MFRGGZDFMZTWQ2LK
 */

// ═══════════════════════════════════════════════════════════════
// TOTP (Time-based One-Time Password) FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function base32_decode($secret) {
    if (empty($secret)) return '';
    
    $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32charsFlipped = array_flip(str_split($base32chars));
    
    $paddingCharCount = substr_count($secret, '=');
    $allowedValues = array(6, 4, 3, 1, 0);
    
    if (!in_array($paddingCharCount, $allowedValues)) return false;
    
    for ($i = 0; $i < 4; $i++) {
        if ($paddingCharCount == $allowedValues[$i] &&
            substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
            return false;
        }
    }
    
    $secret = str_replace('=', '', $secret);
    $secret = str_split($secret);
    $binaryString = '';
    
    for ($i = 0; $i < count($secret); $i = $i + 8) {
        $x = '';
        if (!in_array($secret[$i], str_split($base32chars))) return false;
        
        for ($j = 0; $j < 8; $j++) {
            $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
        }
        
        $eightBits = str_split($x, 8);
        for ($z = 0; $z < count($eightBits); $z++) {
            $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
        }
    }
    
    return $binaryString;
}

function get_totp_token($secret, $timeSlice = null) {
    if ($timeSlice === null) {
        $timeSlice = floor(time() / 30);
    }
    
    $secretkey = base32_decode($secret);
    $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
    $hm = hash_hmac('SHA1', $time, $secretkey, true);
    $offset = ord(substr($hm, -1)) & 0x0F;
    $hashpart = substr($hm, $offset, 4);
    $value = unpack('N', $hashpart);
    $value = $value[1];
    $value = $value & 0x7FFFFFFF;
    $modulo = pow(10, 6);
    
    return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
}

function verify_totp($secret, $code, $discrepancy = 2) {
    $currentTimeSlice = floor(time() / 30);
    
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $calculatedCode = get_totp_token($secret, $currentTimeSlice + $i);
        if ($calculatedCode == $code) {
            return true;
        }
    }
    
    return false;
}

// ═══════════════════════════════════════════════════════════════
// LOGOUT HANDLER
// ═══════════════════════════════════════════════════════════════

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// AUTHENTICATION STEP 1: PASSWORD
// ═══════════════════════════════════════════════════════════════

if (!isset($_SESSION['admin_password_verified'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === $admin_password) {
            $_SESSION['admin_password_verified'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = 'Incorrect password';
        }
    }
    
    // Show password login form
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
            .step-indicator {
                color: #64748b;
                font-size: 12px;
                margin-bottom: 20px;
                text-align: center;
                padding: 10px;
                background: rgba(99,102,241,0.1);
                border-radius: 8px;
                font-weight: 500;
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
                box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
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
                margin-top: 10px;
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
            .security-badge {
                text-align: center;
                margin-top: 20px;
                padding: 10px;
                background: rgba(34,197,94,0.1);
                border-radius: 6px;
                font-size: 12px;
                color: #4ade80;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔐 Admin Login</h1>
            <p class="subtitle">HostingAura Speed Test</p>
            <div class="step-indicator">🔒 Step 1 of 2: Password</div>
            <?php if (isset($error)): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Enter admin password" 
                    required 
                    autofocus 
                    autocomplete="current-password"
                >
                <button type="submit">Continue to 2FA →</button>
            </form>
            <div class="security-badge">
                🛡️ Protected with 2FA
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════════
// AUTHENTICATION STEP 2: 2FA (TOTP)
// ═══════════════════════════════════════════════════════════════

if (!isset($_SESSION['admin_2fa_verified'])) {
    // Check if first-time setup
    $show_setup = !isset($_SESSION['2fa_setup_completed']);
    
    if (isset($_POST['totp_code'])) {
        $code = preg_replace('/[^0-9]/', '', $_POST['totp_code']);
        
        if (verify_totp($totp_secret, $code)) {
            $_SESSION['admin_2fa_verified'] = true;
            $_SESSION['2fa_setup_completed'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error_2fa = 'Invalid code. Please try again.';
        }
    }
    
    // Generate QR code URL for authenticator apps
    $app_name = 'HostingAura Admin';
    $qr_code_url = 'otpauth://totp/' . urlencode($app_name) . '?secret=' . $totp_secret . '&issuer=' . urlencode('HostingAura');
    
    // Use QR Server API instead of Google Charts (more reliable)
    $qr_image_url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qr_code_url);
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>2FA Verification - HostingAura</title>
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
                padding: 20px;
            }
            .login-box {
                background: #1a1a2e;
                padding: 40px;
                border-radius: 16px;
                border: 1px solid rgba(99,102,241,0.3);
                max-width: 550px;
                width: 100%;
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
            .step-indicator {
                color: #64748b;
                font-size: 12px;
                margin-bottom: 20px;
                text-align: center;
                padding: 10px;
                background: rgba(99,102,241,0.1);
                border-radius: 8px;
                font-weight: 500;
            }
            .setup-section {
                background: rgba(99,102,241,0.05);
                border: 1px solid rgba(99,102,241,0.2);
                border-radius: 12px;
                padding: 24px;
                margin-bottom: 24px;
            }
            .setup-title {
                color: #6366f1;
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 16px;
                text-align: center;
            }
            .qr-code {
                text-align: center;
                margin: 24px 0;
            }
            .qr-code img {
                border-radius: 12px;
                background: white;
                padding: 15px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }
            .secret-code {
                background: #0a0a0f;
                padding: 16px;
                border-radius: 8px;
                text-align: center;
                font-family: 'Courier New', monospace;
                color: #6366f1;
                font-size: 18px;
                letter-spacing: 3px;
                margin: 16px 0;
                cursor: pointer;
                border: 1px solid #2a2a3e;
                transition: all 0.2s ease;
                word-wrap: break-word;
            }
            .secret-code:hover {
                background: #1a1a2e;
                border-color: #6366f1;
            }
            .instructions {
                color: #94a3b8;
                font-size: 14px;
                line-height: 1.8;
                margin: 16px 0;
            }
            .instructions strong {
                color: #e2e8f0;
            }
            .app-icons {
                display: flex;
                justify-content: center;
                gap: 24px;
                margin-top: 20px;
                flex-wrap: wrap;
            }
            .app-icon {
                text-align: center;
                flex: 1;
                min-width: 100px;
            }
            .app-icon-emoji {
                font-size: 32px;
                margin-bottom: 8px;
            }
            .app-icon-name {
                color: #64748b;
                font-size: 12px;
            }
            input {
                width: 100%;
                padding: 16px;
                margin: 10px 0;
                background: #0a0a0f;
                border: 1px solid #2a2a3e;
                border-radius: 8px;
                color: #e2e8f0;
                font-size: 28px;
                text-align: center;
                letter-spacing: 10px;
                font-family: 'Courier New', monospace;
                font-weight: bold;
            }
            input:focus {
                outline: none;
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
            }
            input::placeholder {
                color: #475569;
                letter-spacing: 8px;
            }
            button {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                border: none;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(99,102,241,0.4);
            }
            .error {
                color: #ef4444;
                font-size: 14px;
                margin-top: 10px;
                padding: 12px;
                background: rgba(239,68,68,0.1);
                border-radius: 6px;
                border: 1px solid rgba(239,68,68,0.3);
                text-align: center;
            }
            .back-link {
                margin-top: 24px;
                text-align: center;
            }
            .back-link a {
                color: #64748b;
                text-decoration: none;
                font-size: 13px;
                transition: color 0.2s;
            }
            .back-link a:hover {
                color: #6366f1;
            }
            .copy-hint {
                text-align: center;
                color: #64748b;
                font-size: 12px;
                margin-top: 8px;
            }
            .prompt-text {
                text-align: center;
                padding: 20px;
                color: #94a3b8;
                margin-bottom: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔐 Two-Factor Authentication</h1>
            <p class="subtitle">HostingAura Speed Test</p>
            <div class="step-indicator">🔒 Step 2 of 2: Authenticator Code</div>
            
            <?php if ($show_setup): ?>
            <div class="setup-section">
                <div class="setup-title">📱 First Time Setup</div>
                
                <div class="instructions">
                    <strong>1. Download an authenticator app:</strong>
                </div>
                <div class="app-icons">
                    <div class="app-icon">
                        <div class="app-icon-emoji">📱</div>
                        <div class="app-icon-name">Google<br>Authenticator</div>
                    </div>
                    <div class="app-icon">
                        <div class="app-icon-emoji">🔐</div>
                        <div class="app-icon-name">Microsoft<br>Authenticator</div>
                    </div>
                    <div class="app-icon">
                        <div class="app-icon-emoji">🛡️</div>
                        <div class="app-icon-name">Authy</div>
                    </div>
                </div>
                
                <div class="instructions" style="margin-top: 24px;">
                    <strong>2. Scan this QR code:</strong>
                </div>
                
                <div class="qr-code">
                    <img src="<?= $qr_image_url ?>" alt="QR Code" width="250" height="250">
                </div>
                
                <div class="instructions">
                    <strong>3. Or enter this secret key manually:</strong>
                    <br><small style="color: #64748b; font-size: 12px;">Only use: A-Z and 2-7 (no spaces)</small>
                </div>
                <div class="secret-code" onclick="copySecret()" title="Click to copy (without spaces)">
                    <?= $totp_secret ?>
                </div>
                <div class="copy-hint">👆 Click to copy (automatically removes spaces)</div>
            </div>
            <?php else: ?>
            <div class="prompt-text">
                Open your authenticator app and enter the 6-digit code
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_2fa)): ?>
                <div class="error">❌ <?= htmlspecialchars($error_2fa) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="totp-form">
                <input 
                    type="text" 
                    name="totp_code" 
                    id="totp-input"
                    placeholder="000000" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    required 
                    autofocus
                    autocomplete="off"
                >
                <button type="submit">✓ Verify & Login</button>
            </form>
            
            <div class="back-link">
                <a href="?logout">← Back to password</a>
            </div>
        </div>
        
        <script>
            function copySecret() {
                // Remove any spaces and ensure uppercase
                const secret = '<?= $totp_secret ?>'.replace(/\s/g, '').toUpperCase();
                
                // Validate Base32 format (A-Z and 2-7 only)
                const validChars = /^[A-Z2-7]+$/;
                if (!validChars.test(secret)) {
                    alert('⚠️ Secret key contains invalid characters! Use only A-Z and 2-7.');
                    return;
                }
                
                navigator.clipboard.writeText(secret).then(() => {
                    alert('✓ Secret key copied to clipboard!\n\n' + secret);
                }).catch(() => {
                    prompt('Copy this secret key (A-Z and 2-7 only):', secret);
                });
            }
            
            // Auto-format and submit
            const input = document.getElementById('totp-input');
            input.addEventListener('input', function(e) {
                // Only allow numbers
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                // Auto-submit when 6 digits entered
                if (e.target.value.length === 6) {
                    setTimeout(() => {
                        document.getElementById('totp-form').submit();
                    }, 300);
                }
            });
            
            // Focus input on load
            window.addEventListener('load', () => {
                input.focus();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════════
// BOTH PASSWORD AND 2FA VERIFIED - LOAD DASHBOARD
// ═══════════════════════════════════════════════════════════════

// FETCH ALL DATA
$db = getDBConnection();

// Get view parameter
$view = $_GET['view'] ?? 'overview';

// Get filter parameter (for filtering by user)
$filter_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

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
    $where_clause = $filter_user_id ? "WHERE sr.user_id = $filter_user_id" : "";
    
    $tests_query = $db->query("
        SELECT 
            sr.*,
            u.email as user_email,
            u.phone as user_phone,
            u.id as linked_user_id
        FROM speed_results sr
        LEFT JOIN users u ON sr.user_id = u.id
        $where_clause
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
            align-items: center;
        }
        
        .security-badge {
            background: rgba(34,197,94,0.1);
            color: #4ade80;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(34,197,94,0.2);
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
            margin-bottom: 8px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .stat-meta {
            color: #64748b;
            font-size: 12px;
        }
        
        /* Navigation Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(99,102,241,0.2);
            padding-bottom: 0;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .tab:hover {
            color: #e2e8f0;
        }
        
        .tab.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }
        
        /* Content Section */
        .content-section {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 16px;
            padding: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px;
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            border-bottom: 1px solid rgba(99,102,241,0.2);
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(99,102,241,0.1);
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: rgba(99,102,241,0.05);
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: rgba(34,197,94,0.1);
            color: #4ade80;
        }
        
        .badge-error {
            background: rgba(239,68,68,0.1);
            color: #ef4444;
        }
        
        /* Welcome Section */
        .welcome-section {
            text-align: center;
            padding: 60px 20px;
        }
        
        .welcome-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .welcome-section p {
            color: #94a3b8;
            margin-bottom: 40px;
        }
        
        .feature-list {
            text-align: left;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .feature-item {
            padding: 15px 20px;
            margin: 10px 0;
            background: rgba(99,102,241,0.05);
            border-radius: 8px;
            border-left: 3px solid #6366f1;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 12px;
        }
        
        /* Refresh Button */
        .refresh-btn {
            background: none;
            border: none;
            color: #64748b;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .refresh-btn:hover {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>📊 Admin Dashboard</h1>
        <div class="header-actions">
            <span class="security-badge">🛡️ 2FA Protected</span>
            <a href="?view=overview" class="btn btn-primary">📈 Overview</a>
            <a href="?logout" class="btn btn-secondary">🚪 Logout</a>
        </div>
    </div>
    
    <!-- Main Container -->
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
        <div class="tabs">
            <a href="?view=overview" class="tab <?= $view === 'overview' ? 'active' : '' ?>">📊 Overview</a>
            <a href="?view=users" class="tab <?= $view === 'users' ? 'active' : '' ?>">👥 Users</a>
            <a href="?view=tests" class="tab <?= $view === 'tests' ? 'active' : '' ?>">🚀 Speed Tests</a>
            <a href="?view=sms" class="tab <?= $view === 'sms' ? 'active' : '' ?>">📱 SMS Logs</a>
            <a href="?view=emails" class="tab <?= $view === 'emails' ? 'active' : '' ?>">📧 Email Logs</a>
        </div>
        
        <!-- Content Section -->
        <div class="content-section">
            
            <?php if ($view === 'overview'): ?>
                <!-- Overview -->
                <div class="welcome-section">
                    <h2>📊 System Overview</h2>
                    <p>Welcome to the HostingAura Admin Dashboard!</p>
                    
                    <p style="margin-bottom: 30px;">Use the tabs above to view detailed information about:</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <strong>👥 Users</strong> - All registered users and their activity
                        </div>
                        <div class="feature-item">
                            <strong>🚀 Speed Tests</strong> - All speed tests run on the platform
                        </div>
                        <div class="feature-item">
                            <strong>📱 SMS Logs</strong> - All SMS messages sent (OTPs, notifications)
                        </div>
                        <div class="feature-item">
                            <strong>📧 Email Logs</strong> - All emails sent to users
                        </div>
                    </div>
                    
                    <div class="footer">
                        Last updated: <?= date('M d, Y H:i:s T') ?> 
                        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
                    </div>
                </div>
                
            <?php elseif ($view === 'users'): ?>
                <!-- Users Table -->
                <div class="section-header">
                    <h2 class="section-title">👥 Registered Users</h2>
                    <a href="?view=users&export=users" class="btn btn-secondary">📥 Export CSV</a>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Contact</th>
                            <th>Phone</th>
                            <th>Tests</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_data as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td>
                                <a href="?view=tests&user_id=<?= $user['id'] ?>" style="color: #6366f1; text-decoration: none;">
                                    <?= htmlspecialchars($user['email'] ?? $user['phone'] ?? 'N/A') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                            <td>
                                <a href="?view=tests&user_id=<?= $user['id'] ?>" style="color: #6366f1; text-decoration: none;">
                                    <?= $user['test_count'] ?> tests
                                </a>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php elseif ($view === 'tests'): ?>
                <!-- Speed Tests Table -->
                <div class="section-header">
                    <h2 class="section-title">
                        🚀 Speed Test Results
                        <?php if ($filter_user_id): ?>
                            <?php 
                            $filter_user = $db->query("SELECT email, phone FROM users WHERE id = $filter_user_id")->fetch_assoc();
                            $filter_display = $filter_user['email'] ?: $filter_user['phone'] ?: "User #$filter_user_id";
                            ?>
                            <span style="color: #6366f1; font-size: 16px;"> 
                                — Filtered by: <?= htmlspecialchars($filter_display) ?>
                                <a href="?view=tests" style="color: #ef4444; text-decoration: none; margin-left: 10px;">✕ Clear</a>
                            </span>
                        <?php endif; ?>
                    </h2>
                    <a href="?view=tests<?= $filter_user_id ? '&user_id=' . $filter_user_id : '' ?>&export=tests" class="btn btn-secondary">📥 Export CSV</a>
                </div>
                
                <table class="data-table">
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
                            <td>
                                <?php 
                                $user_display = 'Guest';
                                $user_link = '';
                                if (!empty($test['linked_user_id'])) {
                                    // Show email if exists, otherwise phone, otherwise user ID
                                    $user_display = $test['user_email'] ?: ($test['user_phone'] ?: 'User #' . $test['linked_user_id']);
                                    $user_link = '?view=tests&user_id=' . $test['linked_user_id'];
                                }
                                ?>
                                <?php if ($user_link): ?>
                                    <a href="<?= $user_link ?>" style="color: #6366f1; text-decoration: none; hover: text-decoration: underline;">
                                        <?= htmlspecialchars($user_display) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($user_display) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($test['download_speed'], 2) ?> Mbps</td>
                            <td><?= number_format($test['upload_speed'], 2) ?> Mbps</td>
                            <td><?= $test['ping'] ?> ms</td>
                            <td><?= htmlspecialchars($test['isp'] ?? 'Unknown') ?></td>
                            <td><?= date('M d, Y H:i', strtotime($test['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php elseif ($view === 'sms'): ?>
                <!-- SMS Logs Table -->
                <div class="section-header">
                    <h2 class="section-title">📱 SMS Log</h2>
                    <a href="?view=sms&export=sms" class="btn btn-secondary">📥 Export CSV</a>
                </div>
                
                <table class="data-table">
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
                            <td><?= htmlspecialchars(substr($sms['message_text'], 0, 50)) ?>...</td>
                            <td><?= htmlspecialchars($sms['message_type']) ?></td>
                            <td>
                                <span class="badge badge-<?= $sms['status'] === 'sent' ? 'success' : 'error' ?>">
                                    <?= $sms['status'] ?>
                                </span>
                            </td>
                            <td>€<?= number_format($sms['cost'], 2) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($sms['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php elseif ($view === 'emails'): ?>
                <!-- Email Logs Table -->
                <div class="section-header">
                    <h2 class="section-title">📧 Email Log</h2>
                    <a href="?view=emails&export=emails" class="btn btn-secondary">📥 Export CSV</a>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
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
                            <td><?= htmlspecialchars($email['recipient_email']) ?></td>
                            <td><?= htmlspecialchars($email['subject']) ?></td>
                            <td><?= htmlspecialchars($email['email_type']) ?></td>
                            <td>
                                <span class="badge badge-<?= $email['status'] === 'sent' ? 'success' : 'error' ?>">
                                    <?= $email['status'] ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($email['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php endif; ?>
            
        </div>
        
    </div>
</body>
</html>