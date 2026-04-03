<?php
/**
 * 2FA Debug Tool
 * Tests TOTP code generation and shows server/phone time comparison
 */

session_start();
require_once 'config.php';

// Your TOTP secret (use the same one from admin-dashboard.php)
$totp_secret = ''; // ⚠️ USE YOUR ACTUAL SECRET!

// ═══════════════════════════════════════════════════════════════
// TOTP FUNCTIONS (Same as main dashboard)
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

function verify_totp($secret, $code, $discrepancy = 1) {
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
// DIAGNOSTIC INFO
// ═══════════════════════════════════════════════════════════════

$server_time = time();
$server_datetime = date('Y-m-d H:i:s T', $server_time);
$current_timeslice = floor($server_time / 30);

// Generate codes for current time and nearby windows
$codes = [];
for ($i = -3; $i <= 3; $i++) {
    $timeslice = $current_timeslice + $i;
    $code = get_totp_token($totp_secret, $timeslice);
    $timestamp = $timeslice * 30;
    $codes[] = [
        'offset' => $i,
        'code' => $code,
        'time' => date('H:i:s', $timestamp),
        'valid' => ($i >= -1 && $i <= 1) ? 'YES' : 'NO'
    ];
}

// Test if user submitted a code
$test_result = null;
if (isset($_POST['test_code'])) {
    $test_code = preg_replace('/[^0-9]/', '', $_POST['test_code']);
    $is_valid = verify_totp($totp_secret, $test_code);
    $test_result = [
        'code' => $test_code,
        'valid' => $is_valid
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Debug Tool - HostingAura</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            color: #e2e8f0;
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #94a3b8;
            margin-bottom: 40px;
            font-size: 14px;
        }
        .info-box {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .info-title {
            color: #6366f1;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(99,102,241,0.1);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #94a3b8;
        }
        .info-value {
            color: #e2e8f0;
            font-weight: 600;
            font-family: monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: rgba(99,102,241,0.1);
            padding: 12px;
            text-align: left;
            color: #6366f1;
            font-size: 12px;
            text-transform: uppercase;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid rgba(99,102,241,0.1);
        }
        tr:hover {
            background: rgba(99,102,241,0.05);
        }
        .current-code {
            background: rgba(34,197,94,0.1);
            color: #4ade80;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
        }
        .valid-badge {
            background: rgba(34,197,94,0.1);
            color: #4ade80;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .invalid-badge {
            background: rgba(239,68,68,0.1);
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .test-form {
            margin-top: 20px;
            padding: 20px;
            background: rgba(99,102,241,0.05);
            border-radius: 8px;
        }
        input {
            width: 100%;
            padding: 14px;
            margin: 10px 0;
            background: #0a0a0f;
            border: 1px solid #2a2a3e;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-family: monospace;
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
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .success {
            background: rgba(34,197,94,0.1);
            color: #4ade80;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid rgba(34,197,94,0.3);
        }
        .error {
            background: rgba(239,68,68,0.1);
            color: #ef4444;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid rgba(239,68,68,0.3);
        }
        .warning {
            background: rgba(251,191,36,0.1);
            color: #fbbf24;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid rgba(251,191,36,0.3);
        }
        .refresh-note {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 2FA Debug Tool</h1>
        <p class="subtitle">Diagnose TOTP code issues</p>
        
        <!-- Server Info -->
        <div class="info-box">
            <div class="info-title">🖥️ Server Information</div>
            <div class="info-row">
                <span class="info-label">Server Time:</span>
                <span class="info-value"><?= $server_datetime ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Unix Timestamp:</span>
                <span class="info-value"><?= $server_time ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Time Slice:</span>
                <span class="info-value"><?= $current_timeslice ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">TOTP Secret:</span>
                <span class="info-value"><?= $totp_secret ?></span>
            </div>
        </div>
        
        <!-- Current Valid Codes -->
        <div class="info-box">
            <div class="info-title">⏰ Time Windows & Valid Codes</div>
            <p style="color: #94a3b8; font-size: 13px; margin-bottom: 16px;">
                Current code and nearby windows (±90 seconds tolerance)
            </p>
            
            <table>
                <thead>
                    <tr>
                        <th>Offset</th>
                        <th>Time</th>
                        <th>Code</th>
                        <th>Accepted?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($codes as $c): ?>
                    <tr>
                        <td><?= $c['offset'] > 0 ? '+' : '' ?><?= $c['offset'] ?></td>
                        <td><?= $c['time'] ?></td>
                        <td>
                            <?php if ($c['offset'] == 0): ?>
                                <span class="current-code"><?= $c['code'] ?></span>
                            <?php else: ?>
                                <?= $c['code'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['valid'] == 'YES'): ?>
                                <span class="valid-badge">✓ YES</span>
                            <?php else: ?>
                                <span class="invalid-badge">✗ NO</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Test Code -->
        <div class="info-box">
            <div class="info-title">🧪 Test Your Code</div>
            <p style="color: #94a3b8; font-size: 13px; margin-bottom: 16px;">
                Enter the code from your authenticator app to test if it works
            </p>
            
            <form method="POST" class="test-form">
                <input 
                    type="text" 
                    name="test_code" 
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    autofocus
                    autocomplete="off"
                >
                <button type="submit">🔍 Test This Code</button>
            </form>
            
            <?php if ($test_result): ?>
                <?php if ($test_result['valid']): ?>
                    <div class="success">
                        ✓ Code <strong><?= $test_result['code'] ?></strong> is VALID! 
                        This code would work in the login form.
                    </div>
                <?php else: ?>
                    <div class="error">
                        ✗ Code <strong><?= $test_result['code'] ?></strong> is INVALID!
                        <br><br>
                        <strong>Possible reasons:</strong>
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <li>Phone and server time are not synchronized</li>
                            <li>Code expired (they change every 30 seconds)</li>
                            <li>Wrong secret key in authenticator app</li>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Troubleshooting -->
        <div class="info-box">
            <div class="info-title">🔧 Troubleshooting Guide</div>
            
            <div style="margin-top: 16px;">
                <strong style="color: #6366f1;">If codes keep failing:</strong>
                <ol style="margin-left: 20px; margin-top: 10px; line-height: 1.8; color: #94a3b8;">
                    <li><strong style="color: #e2e8f0;">Check phone time:</strong> Settings → Date & Time → Enable "Automatic"</li>
                    <li><strong style="color: #e2e8f0;">Check server time:</strong> See "Server Time" above - should match your timezone</li>
                    <li><strong style="color: #e2e8f0;">Re-scan QR code:</strong> Delete entry in authenticator app and scan again</li>
                    <li><strong style="color: #e2e8f0;">Try current code:</strong> Use the code shown in green above (refreshes every 30 sec)</li>
                </ol>
            </div>
            
            <div class="warning" style="margin-top: 16px;">
                <strong>⚠️ Time Sync Check:</strong><br>
                Compare "Server Time" above with your phone's time. If they differ by more than 90 seconds, codes won't work!
                <br><br>
                <strong>Your Phone Time:</strong> <span id="phone-time"></span>
            </div>
        </div>
        
        <div class="refresh-note">
            Page refreshes automatically every 15 seconds • Last refresh: <?= date('H:i:s') ?>
        </div>
    </div>
    
    <script>
        // Show phone time
        function updatePhoneTime() {
            const now = new Date();
            document.getElementById('phone-time').textContent = now.toLocaleString();
        }
        updatePhoneTime();
        setInterval(updatePhoneTime, 1000);
        
        // Auto-refresh page every 15 seconds
        setTimeout(() => {
            window.location.reload();
        }, 15000);
    </script>
</body>
</html>