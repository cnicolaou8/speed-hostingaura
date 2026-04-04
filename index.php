<?php
// ══════════════════════════════════════════════════════════════
// index.php — HostingAura Speed Test (CDN-OPTIMIZED)
// ══════════════════════════════════════════════════════════════
session_start();
require_once 'config.php';

$isLoggedIn  = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userId      = $isLoggedIn ? intval($_SESSION['user_id']) : null;
$userContact = '';

if ($isLoggedIn) {
    $conn = getDBConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result      = $stmt->get_result()->fetch_assoc();
        $userContact = $result['email'] ?? $result['phone'] ?? '';
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<meta name="cf-2fa-verify" content="no-analytics"/>
<title>Speed Test — HostingAura</title>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad" async defer></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;background:#070711;background-image:radial-gradient(ellipse 90% 55% at 50% 0%,rgba(99,102,241,.15) 0%,transparent 65%);color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 16px}
.logo{margin-bottom:18px;text-align:center;line-height:1}
.logo-text{font-size:1.7rem;font-weight:800;letter-spacing:-.5px}
.logo-hosting{background:linear-gradient(90deg,#38bdf8,#6366f1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.logo-aura{background:linear-gradient(90deg,#a855f7,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.beta-badge{display:inline-block;background:rgba(236,72,153,0.15);border:1px solid rgba(236,72,153,0.3);color:#ec4899;font-size:0.5rem;font-weight:700;letter-spacing:0.1em;padding:3px 8px;border-radius:4px;margin-left:8px;vertical-align:middle;position:relative;top:-2px}
.logo-sub{font-size:.58rem;letter-spacing:.38em;color:#475569;text-transform:uppercase;margin-top:4px}
.auth-bar{display:flex;gap:8px;align-items:center;margin-bottom:16px;flex-wrap:wrap;justify-content:center}
.auth-bar .user-info{font-size:.65rem;color:#94a3b8}
.auth-bar .user-info span{color:#6366f1;font-weight:700}
.auth-btn{background:none;border:1px solid #334155;border-radius:999px;padding:5px 16px;font-size:.65rem;color:#94a3b8;cursor:pointer;transition:.2s}
.auth-btn:hover{border-color:#6366f1;color:#6366f1}
.auth-btn.primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;font-weight:600}
.auth-btn.primary:hover{opacity:.85}
.auth-btn.danger{border-color:#ff4d4d;color:#ff4d4d}
.auth-btn.danger:hover{background:#ff4d4d;color:#fff}
.dashboard-btn{background:#1a1a2e;border:1px solid #6366f1;border-radius:999px;padding:5px 16px;font-size:.65rem;color:#6366f1;cursor:pointer;text-decoration:none;transition:.2s}
.dashboard-btn:hover{background:#6366f1;color:#fff}
.infobar{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-bottom:18px}
.badge{background:#0c0c1c;border:1px solid #181830;border-radius:8px;padding:5px 12px;font-size:.65rem;color:#94a3b8;display:flex;align-items:center;gap:5px}
.badge .lbl{font-size:.55rem;letter-spacing:.14em;text-transform:uppercase;color:#475569;margin-right:2px}
.brave-notice{background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px;font-size:.65rem;color:#92400e;margin-bottom:12px;display:none;text-align:center;max-width:360px}
.test-id-box{background:#0c0c1c;border:1px solid #181830;border-radius:8px;padding:8px 12px;font-size:.65rem;color:#94a3b8;margin-bottom:14px;display:none;text-align:center;max-width:360px}
.test-id-box .lbl{font-size:.55rem;letter-spacing:.14em;text-transform:uppercase;color:#475569;margin-right:4px}
.test-id-box .tid{color:#6366f1;font-weight:700;font-family:monospace}
.wrap{position:relative;width:260px;height:260px;margin-bottom:22px;transition:all 0.5s ease;overflow:hidden}
.wrap.hidden{height:0;margin-bottom:0;opacity:0}
.wrap svg{width:100%;height:100%;overflow:visible}
.ci{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}
#val{font-size:3.6rem;font-weight:800;letter-spacing:-3px;line-height:1;font-variant-numeric:tabular-nums}
#unit{font-size:.65rem;color:#475569;letter-spacing:.18em;text-transform:uppercase;margin-top:4px}
#phase{font-size:.58rem;letter-spacing:.28em;text-transform:uppercase;margin-top:10px;color:#6366f1}
.cards{display:flex;gap:10px;margin-bottom:14px;transition:all 0.5s ease}
.card{background:#0c0c1c;border:1px solid #181830;border-radius:14px;padding:12px;text-align:center;min-width:82px;transition:all .5s}
.cards.expanded .card{min-width:120px;padding:20px}
.cv{font-size:1.3rem;font-weight:700;color:#e2e8f0;min-height:1.5rem;transition:all 0.5s ease}
.cards.expanded .cv{font-size:2.8rem}
.cn{font-size:.52rem;letter-spacing:.16em;color:#475569;text-transform:uppercase;transition:all 0.5s ease}
.cards.expanded .cn{font-size:.65rem;margin-top:8px}
.mbbar{background:#0c0c1c;border:1px solid #181830;border-radius:10px;padding:6px 15px;font-size:.64rem;margin-bottom:16px;color:#475569}
.mbv{font-weight:700;color:#6366f1}
.privacy-notice{background:#0c0c1c;border:1px solid #181830;border-radius:12px;padding:12px 16px;font-size:.7rem;color:#94a3b8;line-height:1.5;max-width:360px;margin-bottom:16px;text-align:center}
.privacy-notice a{color:#6366f1;text-decoration:none;font-weight:600}
.privacy-notice a:hover{text-decoration:underline}
.btn-group{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;justify-content:center}
#btn{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:999px;padding:12px 42px;font-size:.9rem;font-weight:600;cursor:pointer;box-shadow:0 4px 20px rgba(99,102,241,0.3);transition:0.2s}
#btn:disabled{opacity:0.5;cursor:not-allowed}
#share-btn{background:#1e1e3a;color:#94a3b8;border:1px solid #334155;border-radius:999px;padding:12px 24px;font-size:.9rem;cursor:pointer;display:none;transition:.2s}
#share-btn:hover{border-color:#6366f1;color:#6366f1}
#report-btn{display:none;background:none;border:none;color:#475569;font-size:.64rem;cursor:pointer;padding:0 0 18px 0;text-decoration:underline;text-underline-offset:3px;transition:.2s;font-family:inherit}
#report-btn:hover{color:#f87171}
.hist-wrap{width:100%;max-width:360px;margin-top:25px}
.hist-item{background:#0c0c1c;border:1px solid #181830;border-radius:10px;display:flex;padding:10px;margin-bottom:7px;font-size:0.75rem;justify-content:space-between;align-items:center}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal{background:#0f0f1a;border:1px solid #1e1e3a;border-radius:18px;padding:28px 24px;width:100%;max-width:360px;position:relative;max-height:92vh;overflow-y:auto}
.modal h2{font-size:1.1rem;font-weight:700;margin-bottom:4px;color:#e2e8f0}
.modal p.sub{font-size:.7rem;color:#475569;margin-bottom:20px}
.modal-close{position:absolute;top:14px;right:16px;background:none;border:none;color:#475569;font-size:1.2rem;cursor:pointer}
.modal-close:hover{color:#e2e8f0}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:#475569;margin-bottom:6px}
.form-group input,.form-group select,.form-group textarea{width:100%;background:#0c0c1c;border:1px solid #1e1e3a;border-radius:8px;padding:10px 12px;font-size:.85rem;color:#e2e8f0;outline:none;transition:.2s;font-family:inherit}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#6366f1}
.form-group textarea{resize:vertical;min-height:90px;line-height:1.5}
.form-group select option{background:#0c0c1c}
.form-group input[readonly]{opacity:.55;cursor:not-allowed}
.form-submit{width:100%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:999px;padding:11px;font-size:.9rem;font-weight:600;cursor:pointer;margin-top:6px;transition:.2s}
.form-submit:hover{opacity:.85}
.form-submit:disabled{opacity:.5;cursor:not-allowed}
.form-submit.red{background:linear-gradient(135deg,#ef4444,#dc2626)}
.modal-msg{font-size:.72rem;text-align:center;margin-top:10px;min-height:1rem}
.modal-msg.error{color:#ff4d4d}
.modal-msg.success{color:#22c55e}
.modal-switch{font-size:.68rem;color:#475569;text-align:center;margin-top:14px}
.modal-switch a{color:#6366f1;cursor:pointer;text-decoration:none;font-weight:600}
.modal-switch a:hover{text-decoration:underline}
.otp-note{font-size:.65rem;color:#475569;text-align:center;margin-bottom:12px}
.type-toggle{display:flex;gap:8px;margin-bottom:16px}
.type-toggle button{flex:1;background:#0c0c1c;border:1px solid #1e1e3a;border-radius:8px;padding:7px;font-size:.68rem;color:#94a3b8;cursor:pointer;transition:.2s}
.type-toggle button.active{border-color:#6366f1;color:#6366f1;background:#1a1a2e}
.turnstile-widget{margin:14px 0;display:flex;justify-content:center;min-height:65px}
.lockout-timer{font-size:2rem;font-weight:800;color:#ff4d4d;letter-spacing:2px;text-align:center;margin:8px 0;font-variant-numeric:tabular-nums}
.lockout-msg{font-size:.72rem;color:#ff4d4d;text-align:center;margin-bottom:4px}
.rpt-snap{display:flex;gap:12px;flex-wrap:wrap;background:#0c0c1c;border:1px solid #181830;border-radius:10px;padding:10px 14px;margin-bottom:16px}
.rpt-snap-col{display:flex;flex-direction:column;gap:2px}
.rpt-snap-lbl{font-size:.52rem;letter-spacing:.13em;text-transform:uppercase;color:#475569}
.rpt-snap-val{font-size:.78rem;font-weight:700;color:#e2e8f0}
.char-hint{font-size:.58rem;color:#475569;text-align:right;margin-top:3px}
.char-hint.near{color:#f59e0b}
.char-hint.over{color:#ff4d4d}
.cb-row{display:flex;align-items:flex-start;gap:10px;margin-bottom:14px}
.cb-row input[type=checkbox]{width:15px;height:15px;margin-top:2px;accent-color:#6366f1;flex-shrink:0;cursor:pointer}
.cb-row label{font-size:.7rem;color:#94a3b8;line-height:1.5;text-transform:none;letter-spacing:0;cursor:pointer}
.opt-tag{display:inline-block;font-size:.53rem;color:#334155;background:#1a1a2e;border-radius:3px;padding:1px 5px;margin-left:5px;font-style:italic;text-transform:lowercase;letter-spacing:0;vertical-align:middle}
</style>
</head>
<body>

<div class="logo">
  <div class="logo-text">
    <span class="logo-hosting">hosting</span><span class="logo-aura">aura</span>
    <span class="beta-badge">BETA</span>
  </div>
  <div class="logo-sub">Enterprise Speed Test</div>
</div>

<div class="auth-bar" id="auth-bar">
  <?php if ($isLoggedIn): ?>
    <span class="user-info">👤 <span><?= htmlspecialchars($userContact) ?></span></span>
    <a href="dashboard.php" class="dashboard-btn">My History</a>
    <button class="auth-btn danger" onclick="doLogout()">Logout</button>
  <?php else: ?>
    <button class="auth-btn" onclick="openModal('modal-login')">Log In</button>
    <button class="auth-btn primary" onclick="openModal('modal-register')">Sign Up</button>
  <?php endif; ?>
</div>

<div class="infobar">
  <div class="badge"><span class="lbl">IP</span><span id="v-ip">...</span></div>
  <div class="badge"><span class="lbl">LOC</span><span id="v-co">...</span></div>
  <div class="badge"><span class="lbl">ISP</span><span id="v-isp">...</span></div>
</div>

<div class="brave-notice" id="brave-notice">⚠️ Using Brave? Disable shields for accurate ISP/location detection</div>

<div class="test-id-box" id="test-id-box">
  <span class="lbl">TEST ID</span><span class="tid" id="test-id-value">–</span>
</div>

<div class="wrap" id="gauge-wrap">
  <svg viewBox="0 0 300 300">
    <defs>
      <linearGradient id="gDL" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#6366f1"/><stop offset="100%" stop-color="#06b6d4"/>
      </linearGradient>
      <linearGradient id="gUL" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#7c3aed"/><stop offset="100%" stop-color="#ec4899"/>
      </linearGradient>
    </defs>
    <circle cx="150" cy="150" r="110" fill="none" stroke="#131325" stroke-width="16"
            stroke-dasharray="518.36 691.15" transform="rotate(135 150 150)"/>
    <circle id="arc" cx="150" cy="150" r="110" fill="none" stroke="url(#gDL)"
            stroke-width="16" stroke-linecap="round" stroke-dasharray="518.36 691.15"
            stroke-dashoffset="518.36" transform="rotate(135 150 150)"
            style="transition:stroke-dashoffset .15s ease-out"/>
  </svg>
  <div class="ci">
    <div id="val">0</div>
    <div id="unit">Mbps</div>
    <div id="phase">READY</div>
  </div>
</div>

<div class="cards" id="cards-wrap">
  <div class="card"><div class="cv" id="rd">–</div><div class="cn">Down</div></div>
  <div class="card"><div class="cv" id="ru">–</div><div class="cn">Up</div></div>
  <div class="card"><div class="cv" id="rp">–</div><div class="cn">Ping</div></div>
</div>

<div class="mbbar">📦 Data Used: <span class="mbv" id="mbv">0.00</span> MB</div>

<div class="privacy-notice">
  By running this test, you agree that we collect your IP address, ISP, location,
  and test results for service improvement. <a href="privacy.html">Privacy Policy</a>
</div>

<div class="btn-group">
  <button id="btn">Start Speed Test</button>
  <button id="share-btn" onclick="shareResults()">Share Link</button>
</div>

<button id="report-btn" onclick="openReportModal()">⚑ Report an issue with this result</button>
<div class="hist-wrap" id="hist-list"></div>

<!-- MODAL — REPORT ISSUE -->
<div class="modal-overlay" id="modal-report">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-report')">✕</button>
    <h2>⚑ Report an Issue</h2>
    <p class="sub">Something wrong with your result? Let us know — we'll look into it.</p>
    <div class="rpt-snap" id="rpt-snap">
      <div class="rpt-snap-col"><span class="rpt-snap-lbl">Test ID</span><span class="rpt-snap-val" id="rpt-snap-id">–</span></div>
      <div class="rpt-snap-col"><span class="rpt-snap-lbl">Download</span><span class="rpt-snap-val" id="rpt-snap-dl">–</span></div>
      <div class="rpt-snap-col"><span class="rpt-snap-lbl">Upload</span><span class="rpt-snap-val" id="rpt-snap-ul">–</span></div>
      <div class="rpt-snap-col"><span class="rpt-snap-lbl">Ping</span><span class="rpt-snap-val" id="rpt-snap-ping">–</span></div>
    </div>
    <div class="form-group">
      <label>Issue Category</label>
      <select id="rpt-category">
        <option value="wrong_speed">📉 Speed results seem wrong</option>
        <option value="test_failed">❌ Test failed / crashed</option>
        <option value="wrong_location">📍 Wrong location or ISP detected</option>
        <option value="save_failed">💾 Result did not save to my history</option>
        <option value="other">🔧 Other</option>
      </select>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea id="rpt-desc" maxlength="1000" placeholder="Describe the issue…" oninput="updateCharHint()"></textarea>
      <div class="char-hint" id="rpt-char-hint">0 / 1000</div>
    </div>
    <div class="cb-row">
      <input type="checkbox" id="rpt-consent" value="1" onchange="toggleContactRequired()"/>
      <label for="rpt-consent">You may contact me if you need more details about this issue</label>
    </div>
    <div class="form-group">
      <label id="rpt-contact-label">Your Email or Phone <span class="opt-tag" id="rpt-contact-optional">optional</span></label>
      <input type="text" id="rpt-contact" placeholder="you@example.com or +357 99 000000"/>
    </div>
    <div class="turnstile-widget" id="report-turnstile-container"></div>
    <button class="form-submit red" id="rpt-submit" onclick="submitReport()" disabled>Submit Report</button>
    <div class="modal-msg" id="rpt-msg"></div>
  </div>
</div>

<!-- MODAL — LOGIN -->
<div class="modal-overlay" id="modal-login">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-login')">✕</button>
    <h2>Welcome back</h2>
    <p class="sub">Log in to view your full speed test history</p>
    <div class="form-group"><label>Email or Phone Number</label><input type="text" id="login-contact" placeholder="you@example.com or +357 99 000000"/></div>
    <div class="form-group"><label>Password</label><input type="password" id="login-pass" placeholder="••••••••"/></div>
    <div class="turnstile-widget" id="login-turnstile-container"></div>
    <button class="form-submit" id="login-submit" onclick="submitLogin()" disabled>Log In</button>
    <div class="modal-msg" id="login-msg"></div>
    <div style="text-align:center;margin-top:10px;">
      <a onclick="switchModal('modal-login','modal-forgot')" style="color:#6366f1;font-size:0.7rem;cursor:pointer;text-decoration:none;">Forgot Password?</a>
    </div>
    <div class="modal-switch">Don't have an account? <a onclick="switchModal('modal-login','modal-register')">Sign up</a></div>
  </div>
</div>

<!-- MODAL — REGISTER STEP 1 -->
<div class="modal-overlay" id="modal-register">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-register')">✕</button>
    <h2>Create Account</h2>
    <p class="sub">Verify your contact to get started</p>
    <div class="type-toggle">
      <button id="tog-email" class="active" onclick="setOtpType('email')">📧 Email</button>
      <button id="tog-sms" onclick="setOtpType('sms')">📱 SMS</button>
    </div>
    <div class="form-group"><label id="contact-label">Email Address</label><input type="email" id="reg-contact" placeholder="you@example.com"/></div>
    <div class="turnstile-widget" id="register-turnstile-container"></div>
    <button class="form-submit" id="reg-send-btn" onclick="sendOtp()" disabled>Send Verification Code</button>
    <div class="modal-msg" id="reg-msg"></div>
    <div class="modal-switch">Already have an account? <a onclick="switchModal('modal-register','modal-login')">Log in</a></div>
  </div>
</div>

<!-- MODAL — REGISTER STEP 2 -->
<div class="modal-overlay" id="modal-verify">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-verify')">✕</button>
    <h2>Verify &amp; Set Password</h2>
    <p class="sub">Enter the code sent to <span id="verify-contact-display" style="color:#6366f1"></span></p>
    <div class="otp-note">⏱ Code expires in 10 minutes</div>
    <div class="form-group"><label>Verification Code</label><input type="text" id="otp-input" placeholder="6-digit code" maxlength="6"/></div>
    <div class="form-group"><label>Create Password</label><input type="password" id="reg-pass" placeholder="Min. 8 characters"/></div>
    <button class="form-submit" id="verify-submit" onclick="submitVerify()">Create Account</button>
    <div class="modal-msg" id="verify-msg"></div>
    <div class="modal-switch"><a onclick="switchModal('modal-verify','modal-register')">← Change contact</a></div>
  </div>
</div>

<!-- MODAL — FORGOT PASSWORD -->
<div class="modal-overlay" id="modal-forgot">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-forgot')">✕</button>
    <h2>Reset Password</h2>
    <p class="sub">Enter your contact info to receive a reset code</p>
    <div class="type-toggle">
      <button id="forgot-tog-email" class="active" onclick="setForgotType('email')">📧 Email</button>
      <button id="forgot-tog-sms" onclick="setForgotType('sms')">📱 SMS</button>
    </div>
    <div class="form-group"><label id="forgot-contact-label">Email Address</label><input type="email" id="forgot-contact" placeholder="you@example.com"/></div>
    <div class="turnstile-widget" id="forgot-turnstile-container"></div>
    <button class="form-submit" id="forgot-submit" onclick="submitForgotPassword()" disabled>Send Reset Code</button>
    <div class="modal-msg" id="forgot-msg"></div>
    <div class="modal-switch"><a onclick="switchModal('modal-forgot','modal-login')">← Back to login</a></div>
  </div>
</div>

<!-- MODAL — RESET PASSWORD -->
<div class="modal-overlay" id="modal-reset">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-reset')">✕</button>
    <h2>Enter Reset Code</h2>
    <p class="sub">Code sent to <span id="reset-contact-display" style="color:#6366f1"></span></p>
    <div class="otp-note">⏱ Code expires in 10 minutes</div>
    <div class="form-group"><label>Reset Code</label><input type="text" id="reset-otp" placeholder="6-digit code" maxlength="6"/></div>
    <div class="form-group"><label>New Password</label><input type="password" id="reset-pass" placeholder="Min. 8 characters"/></div>
    <button class="form-submit" id="reset-submit" onclick="submitResetPassword()">Reset Password</button>
    <div class="modal-msg" id="reset-msg"></div>
    <div class="modal-switch"><a onclick="switchModal('modal-reset','modal-forgot')">← Request new code</a></div>
  </div>
</div>

<script>
'use strict';

// ══════════════════════════════════════════════════════════════
// GLOBALS
// ══════════════════════════════════════════════════════════════
const TURNSTILE_SITE_KEY = '<?= TURNSTILE_SITE_KEY ?>';
const currentUserContact = <?= $isLoggedIn ? json_encode(htmlspecialchars($userContact, ENT_QUOTES)) : 'null' ?>;

let otpType    = 'email';
let forgotType = 'email';

let loginTurnstileToken    = null;
let registerTurnstileToken = null;
let forgotTurnstileToken   = null;
let reportTurnstileToken   = null;

let loginWidgetId    = null;
let registerWidgetId = null;
let forgotWidgetId   = null;
let reportWidgetId   = null;

let lockoutInterval = null;

const ARC_LEN   = 518.36;
const MAX_SPEED = 1000;
const DL_HOST   = 'download.php';  // Used for probe only
const UL_HOST   = 'empty.php';

// ✅ CDN-CACHED STATIC TEST FILES (served from Cloudflare Cyprus edge)
const TEST_FILES = {
  '10':  '/test-files/10mb.bin',
  '25':  '/test-files/25mb.bin',
  '50':  '/test-files/50mb.bin',
  '100': '/test-files/100mb.bin'
};

let totalBytesUsed = 0;
let lastResult     = { shareLink: null, testId: null, dl: '–', ul: '–', ping: '–' };

let history = [];
try { history = JSON.parse(localStorage.getItem('aura_history') || '[]'); } catch(e) {}

// ══════════════════════════════════════════════════════════════
// DEVICE DETECTION
// ══════════════════════════════════════════════════════════════
function detectDevice() {
  const ua = navigator.userAgent;
  let browser = 'Browser';
  if (ua.indexOf('Brave') > -1 || (navigator.brave && navigator.brave.isBrave)) browser = 'Brave';
  else if (ua.indexOf('Edg') > -1) browser = 'Edge';
  else if (ua.indexOf('Chrome') > -1 && ua.indexOf('Safari') > -1) browser = 'Chrome';
  else if (ua.indexOf('Safari') > -1 && ua.indexOf('Chrome') === -1) browser = 'Safari';
  else if (ua.indexOf('Firefox') > -1) browser = 'Firefox';
  else if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) browser = 'Opera';

  if (/iPad/.test(ua)) return `${browser} on ${detectiPadModel()}`;
  if (/iPhone/.test(ua)) return `${browser} on ${detectiPhoneModel()}`;
  if (/Macintosh/.test(ua) && 'ontouchend' in document) return `${browser} on iPad`;
  if (/Mac OS X/.test(ua)) return `${browser} on Mac`;
  if (/Android/.test(ua)) { const m = ua.match(/;\s*([^;)]+)\s+Build/); return `${browser} on ${m ? m[1] : 'Android'}`; }
  if (/Windows NT/.test(ua)) {
    const v = parseFloat((ua.match(/Windows NT (\d+\.\d+)/) || [0,0])[1]);
    return `${browser} on ${v >= 10 ? 'Windows 11' : v >= 6.3 ? 'Windows 10' : 'Windows'}`;
  }
  if (/CrOS/.test(ua)) return `${browser} on Chromebook`;
  if (/Linux/.test(ua)) return `${browser} on Linux`;
  return browser;
}
function detectiPhoneModel() {
  const [w,h,r] = [screen.width, screen.height, devicePixelRatio];
  if (w===440&&h===956&&r===3) return 'iPhone 16 Pro Max';
  if (w===402&&h===874&&r===3) return 'iPhone 16 Pro';
  if (w===430&&h===932&&r===3) return 'iPhone 16 Plus';
  if (w===393&&h===852&&r===3) return 'iPhone 16';
  if (w===428&&h===926&&r===3) return 'iPhone 14 Pro Max';
  if (w===390&&h===844&&r===3) return 'iPhone 14 Pro';
  if (w===414&&h===896&&r===3) return 'iPhone 11 Pro Max';
  if (w===414&&h===896&&r===2) return 'iPhone 11';
  if (w===375&&h===667&&r===2) return 'iPhone SE';
  return 'iPhone';
}
function detectiPadModel() {
  const [w,h,r] = [screen.width, screen.height, devicePixelRatio];
  if (w===1024&&h===1366&&r===2) return 'iPad Pro 12.9"';
  if (w===834&&h===1194&&r===2) return 'iPad Pro 11"';
  if (w===820&&h===1180&&r===2) return 'iPad Air';
  if (w===744&&h===1133&&r===2) return 'iPad Mini';
  return 'iPad';
}

// ══════════════════════════════════════════════════════════════
// TURNSTILE
// ══════════════════════════════════════════════════════════════
window.onTurnstileLoad = function() {};

function renderTurnstileWidget(containerId, callbackName) {
  const container = document.getElementById(containerId);
  if (!container || typeof turnstile === 'undefined') return null;
  try {
    return turnstile.render(container, { sitekey: TURNSTILE_SITE_KEY, theme: 'dark', callback: window[callbackName] });
  } catch(e) { return null; }
}

window.onLoginTurnstileSuccess    = t => { loginTurnstileToken    = t; document.getElementById('login-submit').disabled   = false; };
window.onRegisterTurnstileSuccess = t => { registerTurnstileToken = t; document.getElementById('reg-send-btn').disabled   = false; };
window.onForgotTurnstileSuccess   = t => { forgotTurnstileToken   = t; document.getElementById('forgot-submit').disabled  = false; };
window.onReportTurnstileSuccess   = t => { reportTurnstileToken   = t; document.getElementById('rpt-submit').disabled     = false; };

// ══════════════════════════════════════════════════════════════
// MODAL MANAGEMENT
// ══════════════════════════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add('active');
  setTimeout(() => {
    if (typeof turnstile === 'undefined') return;
    if (id === 'modal-login'    && !loginWidgetId)    loginWidgetId    = renderTurnstileWidget('login-turnstile-container',    'onLoginTurnstileSuccess');
    if (id === 'modal-register' && !registerWidgetId) registerWidgetId = renderTurnstileWidget('register-turnstile-container', 'onRegisterTurnstileSuccess');
    if (id === 'modal-forgot'   && !forgotWidgetId)   forgotWidgetId   = renderTurnstileWidget('forgot-turnstile-container',   'onForgotTurnstileSuccess');
    if (id === 'modal-report'   && !reportWidgetId)   reportWidgetId   = renderTurnstileWidget('report-turnstile-container',   'onReportTurnstileSuccess');
  }, 100);
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
  clearMessages();
  if (typeof turnstile === 'undefined') return;
  try {
    if (id === 'modal-login'    && loginWidgetId    !== null) { turnstile.reset(loginWidgetId);    loginTurnstileToken    = null; document.getElementById('login-submit').disabled  = true; }
    if (id === 'modal-register' && registerWidgetId !== null) { turnstile.reset(registerWidgetId); registerTurnstileToken = null; document.getElementById('reg-send-btn').disabled  = true; }
    if (id === 'modal-forgot'   && forgotWidgetId   !== null) { turnstile.reset(forgotWidgetId);   forgotTurnstileToken   = null; document.getElementById('forgot-submit').disabled = true; }
    if (id === 'modal-report'   && reportWidgetId   !== null) { turnstile.reset(reportWidgetId);   reportTurnstileToken   = null; document.getElementById('rpt-submit').disabled    = true; }
  } catch(e) {}
}

function switchModal(from, to) { closeModal(from); setTimeout(() => openModal(to), 150); }

function clearMessages() {
  ['login-msg','reg-msg','verify-msg','forgot-msg','reset-msg','rpt-msg'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.textContent = ''; el.className = 'modal-msg'; }
  });
}

function setMsg(id, text, type) {
  const el = document.getElementById(id);
  el.textContent = text;
  el.className   = 'modal-msg ' + type;
}

document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('active'); });
});

// ══════════════════════════════════════════════════════════════
// OTP TYPE TOGGLES
// ══════════════════════════════════════════════════════════════
function setOtpType(type) {
  otpType = type;
  document.getElementById('tog-email').classList.toggle('active', type === 'email');
  document.getElementById('tog-sms').classList.toggle('active', type === 'sms');
  const input = document.getElementById('reg-contact');
  const label = document.getElementById('contact-label');
  if (type === 'email') { input.type = 'email'; input.placeholder = 'you@example.com'; label.textContent = 'Email Address'; }
  else                  { input.type = 'tel';   input.placeholder = '+357 99 000000';  label.textContent = 'Phone Number'; }
}

function setForgotType(type) {
  forgotType = type;
  document.getElementById('forgot-tog-email').classList.toggle('active', type === 'email');
  document.getElementById('forgot-tog-sms').classList.toggle('active', type === 'sms');
  const input = document.getElementById('forgot-contact');
  const label = document.getElementById('forgot-contact-label');
  if (type === 'email') { input.type = 'email'; input.placeholder = 'you@example.com'; label.textContent = 'Email Address'; }
  else                  { input.type = 'tel';   input.placeholder = '+357 99 000000';  label.textContent = 'Phone Number'; }
}

// ══════════════════════════════════════════════════════════════
// AUTH — REGISTER
// ══════════════════════════════════════════════════════════════
async function sendOtp() {
  const contact = document.getElementById('reg-contact').value.trim();
  if (!contact) { setMsg('reg-msg', 'Please enter your ' + (otpType === 'email' ? 'email' : 'phone number'), 'error'); return; }
  if (!registerTurnstileToken) { setMsg('reg-msg', 'Please complete the security check', 'error'); return; }
  const btn = document.getElementById('reg-send-btn');
  btn.disabled = true; btn.textContent = 'Sending...';
  try {
    const r = await fetch('sent_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ contact, type: otpType, turnstile_token: registerTurnstileToken }) });
    const d = await r.json();
    if (d.status === 'success') {
      document.getElementById('verify-contact-display').textContent = contact;
      switchModal('modal-register', 'modal-verify');
    } else {
      setMsg('reg-msg', d.message || 'Failed to send code', 'error');
      if (typeof turnstile !== 'undefined' && registerWidgetId !== null) { turnstile.reset(registerWidgetId); registerTurnstileToken = null; btn.disabled = true; }
    }
  } catch(e) {
    setMsg('reg-msg', 'Network error. Please try again.', 'error');
    if (typeof turnstile !== 'undefined' && registerWidgetId !== null) { turnstile.reset(registerWidgetId); registerTurnstileToken = null; btn.disabled = true; }
  }
  btn.textContent = 'Send Verification Code';
}

async function submitVerify() {
  const contact = document.getElementById('reg-contact').value.trim();
  const otp     = document.getElementById('otp-input').value.trim();
  const pass    = document.getElementById('reg-pass').value;
  if (!otp || otp.length !== 6) { setMsg('verify-msg', 'Enter the 6-digit code', 'error'); return; }
  if (!pass || pass.length < 8) { setMsg('verify-msg', 'Password must be at least 8 characters', 'error'); return; }
  const btn = document.getElementById('verify-submit');
  btn.disabled = true; btn.textContent = 'Verifying...';
  try {
    const r = await fetch('verify_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ contact, otp, password: pass }) });
    const d = await r.json();
    if (d.status === 'success') { setMsg('verify-msg', '✅ Account created! Redirecting...', 'success'); setTimeout(() => location.reload(), 1500); }
    else setMsg('verify-msg', d.message || 'Verification failed', 'error');
  } catch(e) { setMsg('verify-msg', 'Network error. Please try again.', 'error'); }
  btn.disabled = false; btn.textContent = 'Create Account';
}

// ══════════════════════════════════════════════════════════════
// AUTH — LOGIN
// ══════════════════════════════════════════════════════════════
async function submitLogin() {
  const contact = document.getElementById('login-contact').value.trim();
  const pass    = document.getElementById('login-pass').value;
  if (!contact || !pass)    { setMsg('login-msg', 'Please fill in all fields', 'error'); return; }
  if (!loginTurnstileToken) { setMsg('login-msg', 'Please complete the security check', 'error'); return; }
  const btn = document.getElementById('login-submit');
  btn.disabled = true; btn.textContent = 'Logging in...';
  try {
    const r = await fetch('login.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ contact, password: pass, turnstile_token: loginTurnstileToken }) });
    const d = await r.json();
    if (d.status === 'success') { setMsg('login-msg', '✅ Logged in! Redirecting...', 'success'); setTimeout(() => location.reload(), 1000); }
    else if (d.status === 'locked') { startLockoutTimer(d.seconds_left, d.message); btn.disabled = true; }
    else {
      setMsg('login-msg', d.message || 'Login failed', 'error');
      if (typeof turnstile !== 'undefined' && loginWidgetId !== null) { turnstile.reset(loginWidgetId); loginTurnstileToken = null; btn.disabled = true; }
    }
  } catch(e) {
    setMsg('login-msg', 'Network error. Please try again.', 'error');
    if (typeof turnstile !== 'undefined' && loginWidgetId !== null) { turnstile.reset(loginWidgetId); loginTurnstileToken = null; btn.disabled = true; }
  }
}

function startLockoutTimer(seconds, message) {
  if (lockoutInterval) clearInterval(lockoutInterval);
  const msgEl = document.getElementById('login-msg');
  function updateDisplay(secs) {
    const m = Math.floor(secs/60), s = secs%60;
    msgEl.innerHTML = `<div class="lockout-msg">${message}</div><div class="lockout-timer">${m}:${String(s).padStart(2,'0')}</div>`;
    msgEl.className = 'modal-msg';
  }
  updateDisplay(seconds);
  let remaining = seconds;
  lockoutInterval = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
      clearInterval(lockoutInterval); lockoutInterval = null;
      msgEl.textContent = ''; msgEl.className = 'modal-msg';
      if (typeof turnstile !== 'undefined' && loginWidgetId !== null) { turnstile.reset(loginWidgetId); loginTurnstileToken = null; }
    } else updateDisplay(remaining);
  }, 1000);
}

// ══════════════════════════════════════════════════════════════
// AUTH — FORGOT / RESET PASSWORD
// ══════════════════════════════════════════════════════════════
async function submitForgotPassword() {
  const contact = document.getElementById('forgot-contact').value.trim();
  if (!contact)              { setMsg('forgot-msg', 'Please enter your ' + (forgotType === 'email' ? 'email' : 'phone number'), 'error'); return; }
  if (!forgotTurnstileToken) { setMsg('forgot-msg', 'Please complete the security check', 'error'); return; }
  const btn = document.getElementById('forgot-submit');
  btn.disabled = true; btn.textContent = 'Sending...';
  try {
    const r = await fetch('forgot_password.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ contact, type: forgotType, turnstile_token: forgotTurnstileToken }) });
    const d = await r.json();
    if (d.status === 'success') { document.getElementById('reset-contact-display').textContent = contact; switchModal('modal-forgot','modal-reset'); }
    else {
      setMsg('forgot-msg', d.message || 'Failed to send reset code', 'error');
      if (typeof turnstile !== 'undefined' && forgotWidgetId !== null) { turnstile.reset(forgotWidgetId); forgotTurnstileToken = null; btn.disabled = true; }
    }
  } catch(e) {
    setMsg('forgot-msg', 'Network error. Please try again.', 'error');
    if (typeof turnstile !== 'undefined' && forgotWidgetId !== null) { turnstile.reset(forgotWidgetId); forgotTurnstileToken = null; btn.disabled = true; }
  }
  btn.textContent = 'Send Reset Code';
}

async function submitResetPassword() {
  const contact = document.getElementById('forgot-contact').value.trim();
  const otp     = document.getElementById('reset-otp').value.trim();
  const pass    = document.getElementById('reset-pass').value;
  if (!otp || otp.length !== 6) { setMsg('reset-msg', 'Enter the 6-digit code', 'error'); return; }
  if (!pass || pass.length < 8) { setMsg('reset-msg', 'Password must be at least 8 characters', 'error'); return; }
  const btn = document.getElementById('reset-submit');
  btn.disabled = true; btn.textContent = 'Resetting...';
  try {
    const r = await fetch('reset_password.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ contact, otp, password: pass }) });
    const d = await r.json();
    if (d.status === 'success') { setMsg('reset-msg', '✅ Password reset! Logging you in...', 'success'); setTimeout(() => location.reload(), 1500); }
    else setMsg('reset-msg', d.message || 'Reset failed', 'error');
  } catch(e) { setMsg('reset-msg', 'Network error. Please try again.', 'error'); }
  btn.disabled = false; btn.textContent = 'Reset Password';
}

async function doLogout() { await fetch('logout.php'); location.reload(); }

// ══════════════════════════════════════════════════════════════
// REPORT ISSUE
// ══════════════════════════════════════════════════════════════
function updateCharHint() {
  const len = document.getElementById('rpt-desc').value.length;
  const el  = document.getElementById('rpt-char-hint');
  el.textContent = len + ' / 1000';
  el.className   = 'char-hint' + (len >= 1000 ? ' over' : len > 900 ? ' near' : '');
}

function toggleContactRequired() {
  const checked = document.getElementById('rpt-consent').checked;
  const label   = document.getElementById('rpt-contact-label');
  label.innerHTML = checked
    ? 'Your Email or Phone <span style="color:#ff4d4d;margin-left:4px">*</span>'
    : 'Your Email or Phone <span class="opt-tag" id="rpt-contact-optional">optional</span>';
}

function openReportModal() {
  document.getElementById('rpt-snap-id').textContent   = lastResult.testId || '—';
  document.getElementById('rpt-snap-dl').textContent   = lastResult.dl + ' Mbps';
  document.getElementById('rpt-snap-ul').textContent   = lastResult.ul + ' Mbps';
  document.getElementById('rpt-snap-ping').textContent = lastResult.ping + ' ms';
  const contactEl = document.getElementById('rpt-contact');
  contactEl.value    = currentUserContact || '';
  contactEl.readOnly = !!currentUserContact;
  document.getElementById('rpt-desc').value            = '';
  document.getElementById('rpt-consent').checked       = false;
  document.getElementById('rpt-char-hint').textContent = '0 / 1000';
  document.getElementById('rpt-char-hint').className   = 'char-hint';
  toggleContactRequired();
  const btn = document.getElementById('rpt-submit');
  btn.disabled = true; btn.textContent = 'Submit Report';
  setMsg('rpt-msg','','');
  openModal('modal-report');
}

async function submitReport() {
  const category = document.getElementById('rpt-category').value;
  const desc     = document.getElementById('rpt-desc').value.trim();
  const contact  = document.getElementById('rpt-contact').value.trim();
  const consent  = document.getElementById('rpt-consent').checked;
  if (!desc)               { setMsg('rpt-msg', 'Please describe the issue before submitting.', 'error'); return; }
  if (desc.length > 1000)  { setMsg('rpt-msg', 'Description must be 1000 characters or fewer.', 'error'); return; }
  if (consent && !contact) { setMsg('rpt-msg', 'Please provide your email or phone so we can contact you.', 'error'); return; }
  if (contact) {
    const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contact);
    const isPhone = /^\+?[\d\s\-]{7,20}$/.test(contact);
    if (!isEmail && !isPhone) { setMsg('rpt-msg', 'Please enter a valid email or phone number.', 'error'); return; }
  }
  if (!reportTurnstileToken) { setMsg('rpt-msg', 'Please complete the security check.', 'error'); return; }
  const btn = document.getElementById('rpt-submit');
  btn.disabled = true; btn.textContent = 'Submitting...';
  try {
    const r = await fetch('report_issue.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ test_id: lastResult.testId||'', category, description: desc, contact, wants_contact: consent, turnstile_token: reportTurnstileToken, dl: lastResult.dl, ul: lastResult.ul, ping: lastResult.ping, isp: document.getElementById('v-isp').textContent||'', device: detectDevice() })
    });
    const d = await r.json();
    if (d.status === 'success') { setMsg('rpt-msg', '✅ Report #' + d.report_id + ' submitted — thank you!', 'success'); btn.textContent = 'Report Submitted'; setTimeout(() => closeModal('modal-report'), 6000); }
    else {
      setMsg('rpt-msg', d.message || 'Failed to submit. Please try again.', 'error');
      btn.disabled = false; btn.textContent = 'Submit Report';
      if (typeof turnstile !== 'undefined' && reportWidgetId !== null) { turnstile.reset(reportWidgetId); reportTurnstileToken = null; btn.disabled = true; }
    }
  } catch(e) {
    setMsg('rpt-msg', 'Network error. Please try again.', 'error');
    btn.disabled = false; btn.textContent = 'Submit Report';
    if (typeof turnstile !== 'undefined' && reportWidgetId !== null) { turnstile.reset(reportWidgetId); reportTurnstileToken = null; btn.disabled = true; }
  }
}

// ══════════════════════════════════════════════════════════════
// GAUGE & UI HELPERS
// ══════════════════════════════════════════════════════════════
const flag = cc => cc ? cc.toUpperCase().replace(/./g, c => String.fromCodePoint(c.charCodeAt(0)+127397)) : '🌍';

function fmtSpeed(s) { return s > 100 ? Math.round(s).toString() : s.toFixed(1); }

function updateGauge(val, gradient) {
  document.getElementById('arc').style.strokeDashoffset = ARC_LEN * (1 - Math.min(val / MAX_SPEED, 1));
  document.getElementById('arc').setAttribute('stroke', gradient);
  document.getElementById('val').textContent = fmtSpeed(val);
}

function showResults() {
  document.getElementById('gauge-wrap').classList.add('hidden');
  document.getElementById('cards-wrap').classList.add('expanded');
}

function resetUI() {
  document.getElementById('gauge-wrap').classList.remove('hidden');
  document.getElementById('cards-wrap').classList.remove('expanded');
  updateGauge(0, 'url(#gDL)');
  document.getElementById('val').textContent  = '0';
  document.getElementById('phase').textContent = 'READY';
  ['rd','ru','rp'].forEach(id => document.getElementById(id).textContent = '–');
}

// ══════════════════════════════════════════════════════════════
// INFO FETCH
// ══════════════════════════════════════════════════════════════
async function detectBrave() {
  if (navigator.brave && await navigator.brave.isBrave()) document.getElementById('brave-notice').style.display = 'block';
}

async function fetchInfo() {
  try {
    const r = await fetch('https://www.cloudflare.com/cdn-cgi/trace');
    const t = await r.text();
    const d = Object.fromEntries(t.split('\n').filter(v=>v).map(v=>v.split('=')));
    document.getElementById('v-ip').textContent = d.ip  || 'Unknown';
    document.getElementById('v-co').textContent = d.loc ? flag(d.loc) + ' ' + d.loc : 'Unknown';
  } catch(e) { document.getElementById('v-ip').textContent = 'Error'; document.getElementById('v-co').textContent = 'Error'; }

  let ispFound = false;
  try {
    const r2 = await fetch('https://ipapi.co/json/');
    const d2 = await r2.json();
    if (d2 && d2.org) { document.getElementById('v-isp').textContent = d2.org; ispFound = true; }
  } catch(e) {}
  if (!ispFound) {
    try {
      const r3 = await fetch('https://ip-api.com/json/');
      const d3 = await r3.json();
      if (d3 && d3.isp) { document.getElementById('v-isp').textContent = d3.isp; ispFound = true; }
    } catch(e) {}
  }
  if (!ispFound) document.getElementById('v-isp').textContent = 'Unknown ISP';
}

// ══════════════════════════════════════════════════════════════
// PING
// ══════════════════════════════════════════════════════════════
async function measurePing() {
  let pings = [];
  for (let i = 0; i < 6; i++) {
    const t0 = performance.now();
    try { await fetch(DL_HOST + '?size=0.01&t=' + t0, { cache: 'no-store' }); } catch(e) {}
    if (i > 0) pings.push(performance.now() - t0);
  }
  return pings.length ? Math.min(...pings) : 0;
}

// ══════════════════════════════════════════════════════════════
// ✅ DOWNLOAD — CDN-OPTIMIZED (uses static cached files)
// ══════════════════════════════════════════════════════════════
async function measureDownload(cb) {
  const DURATION = 10000;  // 10 seconds (more stable results)
  let downloaded = 0;
  let stop       = false;
  let lastUpdate = performance.now();

  // ── Simple file size selection ──────────────────────────────
  document.getElementById('phase').textContent = 'PREPARING';
  let sizeMB = 100;  // ✅ Default to 100 MB (best for most connections)
  
  // Try to detect connection speed
  if (navigator.connection && navigator.connection.downlink) {
    const downlink = navigator.connection.downlink;
    console.log('Browser connection API:', downlink, 'Mbps');
    // Only use smaller files for clearly slow connections
    if      (downlink < 20)  sizeMB = 10;
    else if (downlink < 100) sizeMB = 25;
    else if (downlink < 500) sizeMB = 50;
    else                     sizeMB = 100;  // Fast/Gigabit
  } else {
    // No API available - use 100 MB (works great for most connections)
    console.log('No connection API, using 100 MB file (adaptive)');
  }

  // ✅ Get CDN-cached static file URL
  const fileUrl = TEST_FILES[sizeMB.toString()];
  console.log('Selected file:', fileUrl, '(' + sizeMB + ' MB)');

  // ── Main download phase — 4 parallel workers using CDN ──────
  document.getElementById('phase').textContent = 'DOWNLOAD';
  const dlStart = performance.now();
  setTimeout(() => stop = true, DURATION);

  function dlWorker() {
    return new Promise(res => {
      if (stop) return res();
      const xhr = new XMLHttpRequest();
      let lastLoaded = 0;
      // ✅ NO cache-busting parameter - static files never change!
      xhr.open('GET', fileUrl, true);
      xhr.responseType = 'arraybuffer';
      xhr.onprogress = e => {
        const chunk = e.loaded - lastLoaded;
        lastLoaded  = e.loaded;
        downloaded += chunk; totalBytesUsed += chunk;
        const now = performance.now();
        if (now - lastUpdate > 150) {
          document.getElementById('mbv').textContent = (totalBytesUsed/1048576).toFixed(2);
          const elapsed = (now - dlStart) / 1000;
          if (elapsed > 0.3) { const s = (downloaded*8)/(elapsed*1_000_000); cb(s); updateGauge(s,'url(#gDL)'); document.getElementById('rd').textContent = fmtSpeed(s); }
          lastUpdate = now;
        }
      };
      xhr.onload = xhr.onerror = xhr.onabort = () => { if (!stop) dlWorker().then(res); else res(); };
      xhr.send();
    });
  }

  await Promise.all([dlWorker(), dlWorker(), dlWorker(), dlWorker()]);
  const dlElapsed = (performance.now() - dlStart) / 1000;
  return dlElapsed > 0 ? (downloaded * 8) / (dlElapsed * 1_000_000) : 0;
}

// ══════════════════════════════════════════════════════════════
// UPLOAD — XHR-based adaptive workers
// ══════════════════════════════════════════════════════════════
async function measureUpload(cb) {
  const DURATION = 12000;  // ✅ 12 seconds (was 8) - more ramp-up time
  let uploaded   = 0;
  let stop       = false;
  let lastUpdate = performance.now();

  // ── 2MB probe (was 512KB) ───────────────────────────────────
  let probeSpeed = 0;
  const probeBytes = 2 * 1024 * 1024;  // ✅ 2 MB probe (was 512 KB)
  const probePayload = new Uint8Array(probeBytes);
  await new Promise(res => {
    const xhr = new XMLHttpRequest();
    const t0  = performance.now();
    xhr.open('POST', UL_HOST, true);
    xhr.onload = xhr.onerror = () => {
      const elapsed = (performance.now() - t0) / 1000;
      probeSpeed = elapsed > 0 ? (probeBytes * 8) / (elapsed * 1_000_000) : 0;
      res();
    };
    xhr.send(probePayload);
  });
  console.log('UL probe:', probeSpeed.toFixed(1), 'Mbps');

  // ── Pick payload + workers — AGGRESSIVE (Ookla-matching) ────
  let payloadMB, workerCount;
  if      (probeSpeed >= 400) { payloadMB = 32; workerCount = 16; }  // ✅ Gigabit+: 32 MB × 16 workers
  else if (probeSpeed >= 200) { payloadMB = 24; workerCount = 14; }  // ✅ Very fast: 24 MB × 14 workers
  else if (probeSpeed >= 100) { payloadMB = 16; workerCount = 12; }  // ✅ Fast: 16 MB × 12 workers
  else if (probeSpeed >= 50)  { payloadMB = 12; workerCount = 10; }  // ✅ Medium: 12 MB × 10 workers
  else if (probeSpeed >= 20)  { payloadMB = 8;  workerCount = 8; }   // ✅ Slow: 8 MB × 8 workers
  else                        { payloadMB = 4;  workerCount = 6; }   // ✅ Very slow: 4 MB × 6 workers
  console.log('UL:', payloadMB, 'MB ×', workerCount, 'workers');

  const payload = new Uint8Array(payloadMB * 1024 * 1024);

  // ── Main upload — each worker tracks its OWN bytes/time ─────
  document.getElementById('phase').textContent = 'UPLOAD';
  const ulStart = performance.now();
  setTimeout(() => stop = true, DURATION);

  function ulWorker() {
    return new Promise(res => {
      if (stop) return res();
      const xhr  = new XMLHttpRequest();
      let lastLoaded = 0;
      xhr.open('POST', UL_HOST, true);
      xhr.upload.onprogress = e => {
        const chunk = e.loaded - lastLoaded;
        lastLoaded  = e.loaded;
        uploaded   += chunk; totalBytesUsed += chunk;
        const now = performance.now();
        if (now - lastUpdate > 150) {
          document.getElementById('mbv').textContent = (totalBytesUsed/1048576).toFixed(2);
          const elapsed = (now - ulStart) / 1000;
          if (elapsed > 0.3) { const s = (uploaded*8)/(elapsed*1_000_000); cb(s); updateGauge(s,'url(#gUL)'); document.getElementById('ru').textContent = fmtSpeed(s); }
          lastUpdate = now;
        }
      };
      xhr.onload = xhr.onerror = xhr.onabort = () => { if (!stop) ulWorker().then(res); else res(); };
      xhr.send(payload);
    });
  }

  await Promise.all(Array.from({ length: workerCount }, ulWorker));
  const ulElapsed = (performance.now() - ulStart) / 1000;
  return ulElapsed > 0 ? (uploaded * 8) / (ulElapsed * 1_000_000) : 0;
}

// ══════════════════════════════════════════════════════════════
// RUN TEST — ORCHESTRATOR
// ══════════════════════════════════════════════════════════════
async function runTest() {
  const btn   = document.getElementById('btn');
  const sBtn  = document.getElementById('share-btn');
  const rBtn  = document.getElementById('report-btn');

  btn.disabled    = true;
  btn.textContent = 'Running...';
  sBtn.style.display = 'none';
  rBtn.style.display = 'none';

  totalBytesUsed = 0;
  lastResult     = { shareLink: null, testId: null, dl: '–', ul: '–', ping: '–' };

  resetUI();
  document.getElementById('test-id-box').style.display = 'none';
  document.getElementById('mbv').textContent = '0.00';

  let dlSpeed = 0, ulSpeed = 0, ping = 0;

  try {
    // PING
    document.getElementById('phase').textContent = 'PING';
    ping = await measurePing();
    document.getElementById('rp').textContent = Math.round(ping);

    // DOWNLOAD
    dlSpeed = await measureDownload(() => {});
    document.getElementById('rd').textContent = fmtSpeed(dlSpeed);
    updateGauge(0, 'url(#gUL)');

    // UPLOAD
    ulSpeed = await measureUpload(() => {});
    document.getElementById('ru').textContent = fmtSpeed(ulSpeed);

    // SAVE
    document.getElementById('phase').textContent = 'SAVING...';
    lastResult.dl   = fmtSpeed(dlSpeed);
    lastResult.ul   = fmtSpeed(ulSpeed);
    lastResult.ping = Math.round(ping).toString();

    try {
      const sr = await fetch('save_result.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ isp: document.getElementById('v-isp').textContent, dl: dlSpeed.toFixed(1), ul: ulSpeed.toFixed(1), ping: Math.round(ping), device: detectDevice() })
      });
      const sd = await sr.json();
      if (sd.status === 'success') {
        lastResult.shareLink = sd.share_url;
        lastResult.testId    = sd.test_id;
        sBtn.style.display   = 'block';
        document.getElementById('test-id-value').textContent  = sd.test_id;
        document.getElementById('test-id-box').style.display  = 'block';
      }
    } catch(e) { console.error('Save error:', e); }

    showResults();
    rBtn.style.display = 'block';

    history.unshift({ time: new Date().toLocaleTimeString(), dl: dlSpeed.toFixed(1), ul: ulSpeed.toFixed(1) });
    history = history.slice(0, 5);
    localStorage.setItem('aura_history', JSON.stringify(history));
    renderHistory();

  } catch(e) {
    console.error('Test error:', e);
    document.getElementById('phase').textContent = 'ERROR';
  } finally {
    btn.disabled    = false;
    btn.textContent = 'Start Speed Test';
  }
}

// ══════════════════════════════════════════════════════════════
// SHARE & HISTORY
// ══════════════════════════════════════════════════════════════
function shareResults() {
  if (!lastResult.shareLink) return;
  navigator.clipboard.writeText(lastResult.shareLink)
    .then(() => { const b = document.getElementById('share-btn'); b.textContent = 'Link Copied!'; setTimeout(() => b.textContent = 'Share Link', 2000); })
    .catch(() => alert('Share link: ' + lastResult.shareLink));
}

function renderHistory() {
  document.getElementById('hist-list').innerHTML = history.map(h => `
    <div class="hist-item">
      <span>${h.time}</span>
      <span style="color:#6366f1">↓ ${h.dl}</span>
      <span style="color:#ec4899">↑ ${h.ul}</span>
    </div>`).join('');
}

// ══════════════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════════════
document.getElementById('btn').addEventListener('click', runTest);
detectBrave();
fetchInfo();
renderHistory();
</script>
</body>
</html>