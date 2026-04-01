<?php
/**
 * HostingAura Speed Test - Dashboard
 * Fixed: PDO → MySQLi, correct columns, jitter/location removed
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$user_id    = (int)$_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? '';
$user_name  = $_SESSION['user_name']  ?? '';

// ── FETCH DASHBOARD DATA ──────────────────────────────────────
$recent_tests = [];
$total_tests  = 0;
$averages     = ['avg_download' => 0, 'avg_upload' => 0, 'avg_ping' => 0];

try {
    $db = getDBConnection();

    // Total tests
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM speed_results WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_tests = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Recent 10 tests
    $stmt = $db->prepare("
        SELECT id, test_id, download_speed, upload_speed, ping,
               isp, ip_address, device, created_at
        FROM speed_results
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Averages
    $stmt = $db->prepare("
        SELECT AVG(download_speed) AS avg_download,
               AVG(upload_speed)   AS avg_upload,
               AVG(ping)           AS avg_ping
        FROM speed_results
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $averages = [
            'avg_download' => round((float)$row['avg_download'], 1),
            'avg_upload'   => round((float)$row['avg_upload'],   1),
            'avg_ping'     => round((float)$row['avg_ping'],     0),
        ];
    }

    // Best download ever
    $stmt = $db->prepare("SELECT MAX(download_speed) AS best_dl FROM speed_results WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $best_dl = (float)$stmt->get_result()->fetch_assoc()['best_dl'];
    $stmt->close();

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

// ── SPEED COLOR HELPER ────────────────────────────────────────
function speedClass($speed, $type = 'download') {
    if ($type === 'ping') {
        if ($speed <= 30)  return 'speed-good';
        if ($speed <= 80)  return 'speed-medium';
        return 'speed-poor';
    }
    if ($speed >= 100) return 'speed-good';
    if ($speed >= 25)  return 'speed-medium';
    return 'speed-poor';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — HostingAura Speed Test</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        /* ── HEADER ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 20px 24px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 16px;
        }

        .logo { display: flex; align-items: center; gap: 12px; }

        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #1D4ED8, #0EA5E9);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px; color: white;
        }

        .logo-text h1 {
            font-size: 18px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-text p { font-size: 11px; color: #64748b; letter-spacing: .05em; }

        .header-actions { display: flex; align-items: center; gap: 12px; }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .2s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,.4); }

        .btn-ghost {
            background: transparent;
            color: #94a3b8;
            border: 1px solid rgba(99,102,241,.25);
        }
        .btn-ghost:hover { color: #e2e8f0; border-color: #6366f1; }

        .btn-danger {
            background: transparent;
            color: #ef4444;
            border: 1px solid rgba(239,68,68,.25);
        }
        .btn-danger:hover { background: rgba(239,68,68,.1); }

        /* ── STATS GRID ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 16px;
            padding: 22px;
            transition: all .25s ease;
        }
        .stat-card:hover { border-color: #6366f1; transform: translateY(-2px); }

        .stat-icon { font-size: 24px; margin-bottom: 10px; }
        .stat-label { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }

        .stat-value {
            font-size: 30px;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .stat-unit { font-size: 14px; color: #475569; margin-left: 2px; }

        /* ── SECTION ── */
        .section {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title { font-size: 18px; font-weight: 600; }

        /* ── TABLE ── */
        .test-table { width: 100%; border-collapse: collapse; }

        .test-table th {
            text-align: left;
            padding: 10px 12px;
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid rgba(99,102,241,.15);
        }

        .test-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(255,255,255,.04);
            font-size: 14px;
        }

        .test-table tr:last-child td { border-bottom: none; }
        .test-table tr:hover td { background: rgba(99,102,241,.04); }

        .speed-good   { color: #10b981; font-weight: 600; }
        .speed-medium { color: #f59e0b; font-weight: 600; }
        .speed-poor   { color: #ef4444; font-weight: 600; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(99,102,241,.15);
            color: #a5b4fc;
        }

        .test-id-code {
            font-family: monospace;
            font-size: 12px;
            color: #64748b;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #475569;
        }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state h3 { color: #94a3b8; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; margin-bottom: 20px; }

        /* ── PROFILE MENU ── */
        .profile-menu-container { position: relative; }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(99,102,241,.08);
            border: 1px solid rgba(99,102,241,.25);
            border-radius: 999px;
            color: #e2e8f0;
            cursor: pointer;
            font-size: 14px;
            transition: all .2s ease;
        }
        .profile-btn:hover { border-color: #6366f1; background: rgba(99,102,241,.15); }

        .profile-avatar {
            width: 28px; height: 28px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: white;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: #13131f;
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 12px;
            min-width: 220px;
            box-shadow: 0 8px 24px rgba(0,0,0,.5);
            z-index: 1000;
            overflow: hidden;
        }
        .profile-dropdown.open { display: block; animation: fadeDown .15s ease; }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .dropdown-header {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .dropdown-header .name { font-weight: 600; font-size: 14px; }
        .dropdown-header .email { font-size: 12px; color: #64748b; margin-top: 2px; }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            transition: background .15s;
        }
        .dropdown-item:hover { background: rgba(99,102,241,.1); color: #e2e8f0; }
        .dropdown-item.danger { color: #f87171; }
        .dropdown-item.danger:hover { background: rgba(239,68,68,.1); }

        .dropdown-divider { height: 1px; background: rgba(255,255,255,.06); margin: 4px 0; }

        /* ── REPORT MODAL ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.75);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: #0d0d1a;
            border: 1px solid rgba(99,102,241,.25);
            border-radius: 16px;
            width: 90%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .modal-header h2 { font-size: 18px; }

        .modal-close {
            background: none; border: none; color: #64748b;
            font-size: 22px; cursor: pointer; line-height: 1;
            padding: 2px 6px; border-radius: 4px;
        }
        .modal-close:hover { background: rgba(239,68,68,.1); color: #ef4444; }

        .modal-body { padding: 24px; }

        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 7px;
            font-weight: 500;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 14px;
            transition: border-color .2s;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #6366f1;
        }
        .form-select option { background: #1a1a2e; }

        textarea.form-control { resize: vertical; min-height: 100px; }

        .form-hint { font-size: 12px; color: #475569; margin-top: 5px; }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: rgba(255,255,255,.02);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 8px;
            cursor: pointer;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,.06);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            display: none;
        }
        .alert-error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
        .alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 14px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .test-table th:nth-child(n+5),
            .test-table td:nth-child(n+5) { display: none; }
            .section-header { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div class="logo">
            <div class="logo-icon">HA</div>
            <div class="logo-text">
                <h1>hostingaura</h1>
                <p>SPEED TEST</p>
            </div>
        </div>

        <div class="header-actions">
            <a href="index.php" class="btn btn-primary">
                ⚡ Run New Test
            </a>

            <div class="profile-menu-container">
                <button class="profile-btn" onclick="toggleProfileMenu()">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user_email, 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($user_name ?: explode('@', $user_email)[0]) ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="profile-dropdown" id="profileDropdown">
                    <div class="dropdown-header">
                        <div class="name"><?= htmlspecialchars($user_name ?: 'My Account') ?></div>
                        <div class="email"><?= htmlspecialchars($user_email) ?></div>
                    </div>
                    <button class="dropdown-item" onclick="openReportModal()">
                        ⚠️ Report an Issue
                    </button>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item danger">
                        🚪 Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🧪</div>
            <div class="stat-label">Total Tests</div>
            <div class="stat-value"><?= $total_tests ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⬇️</div>
            <div class="stat-label">Avg Download</div>
            <div class="stat-value">
                <?= $averages['avg_download'] ?: '—' ?>
                <?php if ($averages['avg_download']): ?>
                    <span class="stat-unit">Mbps</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⬆️</div>
            <div class="stat-label">Avg Upload</div>
            <div class="stat-value">
                <?= $averages['avg_upload'] ?: '—' ?>
                <?php if ($averages['avg_upload']): ?>
                    <span class="stat-unit">Mbps</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📡</div>
            <div class="stat-label">Avg Ping</div>
            <div class="stat-value">
                <?= $averages['avg_ping'] ?: '—' ?>
                <?php if ($averages['avg_ping']): ?>
                    <span class="stat-unit">ms</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TEST HISTORY -->
    <div class="section">
        <div class="section-header">
            <div class="section-title">📋 Test History</div>
            <a href="index.php" class="btn btn-primary">⚡ New Test</a>
        </div>

        <?php if (empty($recent_tests)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h3>No tests yet</h3>
            <p>Run your first speed test to see your results here.</p>
            <a href="index.php" class="btn btn-primary">Run Speed Test</a>
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
                    <th>Device</th>
                    <th>Test ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_tests as $test): ?>
                <tr>
                    <td><?= date('d M Y, H:i', strtotime($test['created_at'])) ?></td>
                    <td class="<?= speedClass($test['download_speed']) ?>">
                        <?= number_format($test['download_speed'], 1) ?> <small>Mbps</small>
                    </td>
                    <td class="<?= speedClass($test['upload_speed']) ?>">
                        <?= number_format($test['upload_speed'], 1) ?> <small>Mbps</small>
                    </td>
                    <td class="<?= speedClass($test['ping'], 'ping') ?>">
                        <?= (int)$test['ping'] ?> <small>ms</small>
                    </td>
                    <td><?= htmlspecialchars($test['isp'] ?: '—') ?></td>
                    <td>
                        <?php if ($test['device']): ?>
                            <span class="badge"><?= htmlspecialchars($test['device']) ?></span>
                        <?php else: ?>
                            <span style="color:#475569">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="test-id-code"><?= htmlspecialchars($test['test_id'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div><!-- /container -->

<!-- REPORT AN ISSUE MODAL -->
<div class="modal-overlay" id="reportModalOverlay" onclick="handleOverlayClick(event)">
    <div class="modal">
        <div class="modal-header">
            <h2>⚠️ Report an Issue</h2>
            <button class="modal-close" onclick="closeReportModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-error"   id="reportError"></div>
            <div class="alert alert-success" id="reportSuccess"></div>

            <div class="form-group">
                <label class="form-label">Category *</label>
                <select class="form-select" id="reportCategory">
                    <option value="wrong_speed">📉 Speed results seem wrong</option>
                    <option value="test_failed">❌ Test failed / crashed</option>
                    <option value="wrong_location">📍 Wrong location or ISP detected</option>
                    <option value="save_failed">💾 Result did not save</option>
                    <option value="other">🔧 Other issue</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Describe the issue *</label>
                <textarea class="form-control" id="reportDescription" placeholder="Please describe what happened in detail..."></textarea>
                <div class="form-hint" id="reportCharCount">0 / 1000 characters</div>
            </div>

            <div class="form-group">
                <label class="form-label for-checkbox checkbox-row" style="cursor:pointer">
                    <input type="checkbox" id="wantsContact" style="accent-color:#6366f1;width:16px;height:16px">
                    <span>I'd like to be contacted about this issue</span>
                </label>
            </div>

            <div class="form-group" id="contactGroup" style="display:none">
                <label class="form-label">Your email or phone</label>
                <input type="text" class="form-control" id="reportContact"
                       placeholder="email@example.com or +35799123456"
                       value="<?= htmlspecialchars($user_email) ?>">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeReportModal()">Cancel</button>
            <button class="btn btn-primary" id="reportSubmitBtn" onclick="submitReport()">
                Submit Report
            </button>
        </div>
    </div>
</div>

<script>
// ── PROFILE MENU ──────────────────────────────────────────────
function toggleProfileMenu() {
    document.getElementById('profileDropdown').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.profile-menu-container')) {
        document.getElementById('profileDropdown').classList.remove('open');
    }
});

// ── REPORT MODAL ──────────────────────────────────────────────
function openReportModal() {
    document.getElementById('profileDropdown').classList.remove('open');
    document.getElementById('reportModalOverlay').classList.add('open');
    document.getElementById('reportDescription').focus();
}

function closeReportModal() {
    document.getElementById('reportModalOverlay').classList.remove('open');
    resetReportForm();
}

function handleOverlayClick(e) {
    if (e.target === document.getElementById('reportModalOverlay')) {
        closeReportModal();
    }
}

function resetReportForm() {
    document.getElementById('reportCategory').value    = 'wrong_speed';
    document.getElementById('reportDescription').value = '';
    document.getElementById('wantsContact').checked    = false;
    document.getElementById('contactGroup').style.display = 'none';
    document.getElementById('reportCharCount').textContent = '0 / 1000 characters';
    showAlert('reportError',   '', false);
    showAlert('reportSuccess', '', false);
    document.getElementById('reportSubmitBtn').disabled    = false;
    document.getElementById('reportSubmitBtn').textContent = 'Submit Report';
}

// Character counter
document.getElementById('reportDescription').addEventListener('input', function() {
    const len = this.value.length;
    document.getElementById('reportCharCount').textContent = len + ' / 1000 characters';
    this.style.borderColor = len > 1000 ? '#ef4444' : '';
});

// Toggle contact field
document.getElementById('wantsContact').addEventListener('change', function() {
    document.getElementById('contactGroup').style.display = this.checked ? 'block' : 'none';
});

function showAlert(id, msg, show) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.style.display = show ? 'block' : 'none';
}

async function submitReport() {
    const desc     = document.getElementById('reportDescription').value.trim();
    const category = document.getElementById('reportCategory').value;
    const wantsCon = document.getElementById('wantsContact').checked;
    const contact  = document.getElementById('reportContact').value.trim();

    showAlert('reportError',   '', false);
    showAlert('reportSuccess', '', false);

    if (!desc) {
        showAlert('reportError', 'Please describe the issue.', true);
        return;
    }
    if (desc.length > 1000) {
        showAlert('reportError', 'Description is too long (max 1000 characters).', true);
        return;
    }
    if (wantsCon && !contact) {
        showAlert('reportError', 'Please provide your email or phone so we can contact you.', true);
        return;
    }

    const btn = document.getElementById('reportSubmitBtn');
    btn.disabled    = true;
    btn.textContent = 'Submitting…';

    const payload = {
        category,
        description:   desc,
        wants_contact: wantsCon,
        contact:       contact,
        turnstile_token: '' // Turnstile widget token — add if you have the widget on the modal
    };

    try {
        const res  = await fetch('report_issue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.status === 'success') {
            showAlert('reportSuccess',
                '✅ Report #' + data.report_id + ' submitted! Thank you for your feedback.', true);
            document.getElementById('reportDescription').value = '';
            btn.textContent = 'Submitted ✓';
        } else {
            showAlert('reportError', data.message || 'Something went wrong. Please try again.', true);
            btn.disabled    = false;
            btn.textContent = 'Submit Report';
        }
    } catch (err) {
        showAlert('reportError', 'Network error. Please check your connection and try again.', true);
        btn.disabled    = false;
        btn.textContent = 'Submit Report';
    }
}

// Keyboard shortcut — Escape closes modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeReportModal();
});
</script>
</body>
</html>
