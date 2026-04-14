<?php
// ══════════════════════════════════════════════════════════════
// HostingAura Speed Test - v3 COMPLETE FIX
// ✅ Turnstile renders on modal OPEN (not on page load while hidden)
// ✅ Ping uses dedicated tiny endpoint (ping.php)
// ✅ Clear completion UI with large result cards
// ✅ Sliding window measurement + warm-up discard
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
.test-id-box{background:#0c0c1c;border:1px solid #181830;border-radius:8px;padding:8px 12px;font-size:.65rem;color:#94a3b8;margin-bottom:14px;display:none;text-align:center;max-width:360px}
.test-id-box .lbl{font-size:.55rem;letter-spacing:.14em;text-transform:uppercase;color:#475569;margin-right:4px}
.test-id-box .tid{color:#6366f1;font-weight:700;font-family:monospace}

/* ── GAUGE (during test) ── */
.test-area{transition:opacity .4s ease}
.wrap{position:relative;width:260px;height:260px;margin-bottom:22px}
.wrap svg{width:100%;height:100%;overflow:visible}
.ci{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}
#val{font-size:3.6rem;font-weight:800;letter-spacing:-3px;line-height:1;font-variant-numeric:tabular-nums}
#unit{font-size:.65rem;color:#475569;letter-spacing:.18em;text-transform:uppercase;margin-top:4px}
#phase{font-size:.58rem;letter-spacing:.28em;text-transform:uppercase;margin-top:10px;color:#6366f1}

/* ── LIVE CARDS (during test) ── */
.cards{display:flex;gap:10px;margin-bottom:14px}
.card{background:#0c0c1c;border:1px solid #181830;border-radius:14px;padding:12px;text-align:center;min-width:82px}
.cv{font-size:1.3rem;font-weight:700;color:#e2e8f0;min-height:1.5rem}
.cn{font-size:.52rem;letter-spacing:.16em;color:#475569;text-transform:uppercase}

/* ── RESULTS PANEL (after test completes) ── */
.results-panel{
  display:none;
  width:100%;
  max-width:440px;
  margin-bottom:20px;
  animation:resultsFadeIn .6s ease-out;
}
.results-panel.visible{display:block}
@keyframes resultsFadeIn{
  from{opacity:0;transform:translateY(16px)}
  to{opacity:1;transform:translateY(0)}
}
.results-header{
  text-align:center;
  margin-bottom:16px;
}
.results-header .check-icon{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:36px;height:36px;
  border-radius:50%;
  background:rgba(34,197,94,.12);
  margin-bottom:8px;
}
.results-header .check-icon svg{width:20px;height:20px;color:#22c55e}
.results-header h3{
  font-size:.7rem;
  letter-spacing:.28em;
  text-transform:uppercase;
  color:#22c55e;
  font-weight:600;
}
.results-grid{
  display:grid;
  grid-template-columns:1fr 1fr 1fr;
  gap:10px;
  margin-bottom:14px;
}
.result-card{
  background:#0c0c1c;
  border:1px solid #181830;
  border-radius:16px;
  padding:20px 12px;
  text-align:center;
  position:relative;
  overflow:hidden;
}
.result-card::before{
  content:'';
  position:absolute;
  top:0;left:0;right:0;
  height:3px;
  border-radius:16px 16px 0 0;
}
.result-card.dl::before{background:linear-gradient(90deg,#6366f1,#06b6d4)}
.result-card.ul::before{background:linear-gradient(90deg,#7c3aed,#ec4899)}
.result-card.pg::before{background:linear-gradient(90deg,#f59e0b,#ef4444)}
.result-card .r-value{
  font-size:2.4rem;
  font-weight:800;
  letter-spacing:-2px;
  line-height:1;
  color:#e2e8f0;
  font-variant-numeric:tabular-nums;
}
.result-card.dl .r-value{
  background:linear-gradient(135deg,#6366f1,#06b6d4);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.result-card.ul .r-value{
  background:linear-gradient(135deg,#7c3aed,#ec4899);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.result-card.pg .r-value{
  background:linear-gradient(135deg,#f59e0b,#ef4444);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.result-card .r-unit{
  font-size:.6rem;
  color:#64748b;
  letter-spacing:.12em;
  text-transform:uppercase;
  margin-top:4px;
}
.result-card .r-label{
  font-size:.55rem;
  letter-spacing:.18em;
  text-transform:uppercase;
  color:#475569;
  margin-top:10px;
  font-weight:600;
}

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
.hist-wrap{width:100%;max-width:440px;margin-top:25px}
.hist-item{background:#0c0c1c;border:1px solid #181830;border-radius:10px;display:flex;padding:10px;margin-bottom:7px;font-size:0.75rem;justify-content:space-between;align-items:center}

/* ── MODALS ── */
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
.form-group textarea{resize:vertical;min-height:80px}
.form-submit{width:100%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:999px;padding:11px;font-size:.9rem;font-weight:600;cursor:pointer;margin-top:6px;transition:.2s}
.form-submit:hover{opacity:.85}
.form-submit:disabled{opacity:.5;cursor:not-allowed}
.modal-msg{font-size:.72rem;text-align:center;margin-top:10px;min-height:1rem}
.modal-msg.error{color:#ff4d4d}
.modal-msg.success{color:#22c55e}
.modal-switch{font-size:.68rem;color:#475569;text-align:center;margin-top:14px}
.modal-switch a{color:#6366f1;cursor:pointer;text-decoration:none;font-weight:600}
.modal-switch a:hover{text-decoration:underline}
.type-toggle{display:flex;gap:8px;margin-bottom:16px}
.type-toggle button{flex:1;background:#0c0c1c;border:1px solid #1e1e3a;border-radius:8px;padding:7px;font-size:.68rem;color:#94a3b8;cursor:pointer;transition:.2s}
.type-toggle button.active{border-color:#6366f1;color:#6366f1;background:#1a1a2e}
.turnstile-widget{margin:14px 0;display:flex;justify-content:center;min-height:65px}
.checkbox-group{display:flex;align-items:center;gap:8px;margin:12px 0}
.checkbox-group input[type="checkbox"]{width:auto;margin:0}
.checkbox-group label{margin:0;font-size:.75rem;color:#94a3b8;text-transform:none;letter-spacing:normal}
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

<div class="auth-bar">
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

<div class="test-id-box" id="test-id-box">
  <span class="lbl">TEST ID</span><span class="tid" id="test-id-value">–</span>
</div>

<!-- GAUGE AREA (visible during test, fades after) -->
<div class="test-area" id="test-area">
  <div class="wrap">
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

  <div class="cards">
    <div class="card"><div class="cv" id="rd">–</div><div class="cn">Down</div></div>
    <div class="card"><div class="cv" id="ru">–</div><div class="cn">Up</div></div>
    <div class="card"><div class="cv" id="rp">–</div><div class="cn">Ping</div></div>
  </div>
</div>

<!-- RESULTS PANEL (shown after test completes) -->
<div class="results-panel" id="results-panel">
  <div class="results-header">
    <div class="check-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </div>
    <h3>Test Complete</h3>
  </div>
  <div class="results-grid">
    <div class="result-card dl">
      <div class="r-value" id="final-dl">–</div>
      <div class="r-unit">Mbps</div>
      <div class="r-label">Download</div>
    </div>
    <div class="result-card ul">
      <div class="r-value" id="final-ul">–</div>
      <div class="r-unit">Mbps</div>
      <div class="r-label">Upload</div>
    </div>
    <div class="result-card pg">
      <div class="r-value" id="final-ping">–</div>
      <div class="r-unit">ms</div>
      <div class="r-label">Ping</div>
    </div>
  </div>
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

<!-- LOGIN MODAL -->
<div class="modal-overlay" id="modal-login">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-login')">×</button>
    <h2>Welcome Back</h2>
    <p class="sub">Log in to access your speed test history</p>
    
    <div class="type-toggle">
      <button class="active" onclick="setLoginType('email')">Email</button>
      <button onclick="setLoginType('phone')">Phone</button>
    </div>
    
    <form id="form-login" onsubmit="doLogin(event)">
      <div class="form-group">
        <label id="login-label">Email</label>
        <input type="text" id="login-contact" required/>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="login-password" required minlength="8"/>
      </div>
      <div class="turnstile-widget" id="login-turnstile"></div>
      <button type="submit" class="form-submit" id="login-btn">Log In</button>
      <div class="modal-msg" id="login-msg"></div>
    </form>
    
    <div class="modal-switch">
      Don't have an account? <a onclick="switchModal('modal-login','modal-register')">Sign Up</a>
    </div>
    <div class="modal-switch">
      <a onclick="switchModal('modal-login','modal-forgot')">Forgot Password?</a>
    </div>
  </div>
</div>

<!-- REGISTER MODAL -->
<div class="modal-overlay" id="modal-register">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-register')">×</button>
    <h2>Create Account</h2>
    <p class="sub">Join HostingAura to track your speed tests</p>
    
    <div class="type-toggle">
      <button class="active" onclick="setRegisterType('email')">Email</button>
      <button onclick="setRegisterType('phone')">Phone</button>
    </div>
    
    <form id="form-register" onsubmit="doRegister(event)">
      <div class="form-group">
        <label id="register-label">Email</label>
        <input type="text" id="register-contact" required/>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="register-password" required minlength="8"/>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" id="register-password-confirm" required minlength="8"/>
      </div>
      <div class="turnstile-widget" id="register-turnstile"></div>
      <button type="submit" class="form-submit">Sign Up</button>
      <div class="modal-msg" id="register-msg"></div>
    </form>
    
    <div class="modal-switch">
      Already have an account? <a onclick="switchModal('modal-register','modal-login')">Log In</a>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD MODAL -->
<div class="modal-overlay" id="modal-forgot">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-forgot')">×</button>
    <h2>Reset Password</h2>
    <p class="sub">We'll send you a verification code</p>
    
    <div class="type-toggle">
      <button class="active" onclick="setForgotType('email')">Email</button>
      <button onclick="setForgotType('phone')">Phone</button>
    </div>
    
    <form id="form-forgot" onsubmit="doForgot(event)">
      <div class="form-group">
        <label id="forgot-label">Email</label>
        <input type="text" id="forgot-contact" required/>
      </div>
      <div class="turnstile-widget" id="forgot-turnstile"></div>
      <button type="submit" class="form-submit">Send Code</button>
      <div class="modal-msg" id="forgot-msg"></div>
    </form>
    
    <div class="modal-switch">
      <a onclick="closeModal('modal-forgot'); openModal('modal-login')">Back to Log In</a>
    </div>
  </div>
</div>

<!-- REPORT ISSUE MODAL -->
<div class="modal-overlay" id="modal-report">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-report')">×</button>
    <h2>Report an Issue</h2>
    <p class="sub">Help us improve by reporting problems with your test</p>
    
    <form id="form-report" onsubmit="doReport(event)">
      <div class="form-group">
        <label>Issue Category</label>
        <select id="report-category" required>
          <option value="">Select issue type...</option>
          <option value="wrong_speed">📉 Speed results seem wrong</option>
          <option value="test_failed">❌ Test failed / crashed</option>
          <option value="wrong_location">📍 Wrong location or ISP detected</option>
          <option value="save_failed">💾 Result did not save</option>
          <option value="other">🔧 Other issue</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Description</label>
        <textarea id="report-description" required placeholder="Please describe the issue in detail..." rows="5"></textarea>
      </div>
      
      <div class="form-group">
        <label>Your Contact (Optional)</label>
        <input type="text" id="report-contact" placeholder="Email or phone (if you want us to follow up)"/>
      </div>
      
      <div class="checkbox-group">
        <input type="checkbox" id="report-wants-contact"/>
        <label for="report-wants-contact">You can contact me about this issue</label>
      </div>
      
      <div class="turnstile-widget" id="report-turnstile"></div>
      <button type="submit" class="form-submit">Submit Report</button>
      <div class="modal-msg" id="report-msg"></div>
    </form>
  </div>
</div>

<script>
'use strict';
console.log('🚀 HostingAura Speed Test v3 - Complete Fix');

const TURNSTILE_SITE_KEY = '<?= TURNSTILE_SITE_KEY ?? "0x4AAAAAAC1BmAZPlmbVHYDb" ?>';
const TEST_FILES = ['/test-files/100mb.bin','/test-files/50mb.bin','/test-files/25mb.bin','/test-files/10mb.bin'];
const UPLOAD_ENDPOINT = 'empty.php';
const PING_ENDPOINT = 'ping.php';     // ← NEW: tiny dedicated ping endpoint
const ARC_LEN = 518.36;
const MAX_SPEED = 1000;

// ══════════════════════════════════════════════════════════════
// SPEED TEST TUNING
// ══════════════════════════════════════════════════════════════
const DL_WORKERS       = 6;
const DL_DURATION      = 20000;
const DL_WARMUP        = 3000;
const DL_WINDOW        = 5000;
const DL_ABORT_TIMEOUT = 12000;

const UL_WORKERS       = 6;
const UL_DURATION      = 20000;
const UL_WARMUP        = 3000;
const UL_WINDOW        = 5000;
const UL_CHUNK_SIZE    = 4 * 1048576;
const UL_ABORT_TIMEOUT = 12000;

let totalBytesUsed = 0;
let lastResult = { shareLink: null, testId: null, dl: '–', ul: '–', ping: '–' };
let history = [];
try { history = JSON.parse(localStorage.getItem('aura_history') || '[]'); } catch(e) {}

let loginType = 'email', registerType = 'email', forgotType = 'email';

// ══════════════════════════════════════════════════════════════
// TURNSTILE - render on modal open, not on page load
// Tokens stored per-modal, reset on close
// ══════════════════════════════════════════════════════════════
let turnstileReady = false;
const turnstileTokens = {};    // { 'login': token, 'register': token, ... }
const turnstileWidgets = {};   // { 'login': widgetId, ... }

window.onTurnstileLoad = function() {
  turnstileReady = true;
  console.log('✅ Turnstile API ready');
};

function renderTurnstile(modalName, containerId) {
  if (!turnstileReady || !window.turnstile) {
    console.warn('Turnstile not ready yet');
    return;
  }
  // If already rendered for this modal, reset it to get a fresh token
  if (turnstileWidgets[modalName] !== undefined) {
    try { turnstile.reset(turnstileWidgets[modalName]); } catch(e) {}
    return;
  }
  const el = document.getElementById(containerId);
  if (!el) return;
  turnstileWidgets[modalName] = turnstile.render('#' + containerId, {
    sitekey: TURNSTILE_SITE_KEY,
    callback: (token) => {
      turnstileTokens[modalName] = token;
      console.log('🔑 Turnstile token received for:', modalName);
    },
    'expired-callback': () => {
      turnstileTokens[modalName] = null;
      console.log('⚠️ Turnstile token expired for:', modalName);
    }
  });
}

function getTurnstileToken(modalName) {
  return turnstileTokens[modalName] || null;
}

// ══════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════
const flag = cc => cc ? cc.toUpperCase().replace(/./g, c => String.fromCodePoint(c.charCodeAt(0)+127397)) : '🌍';
function fmtSpeed(s) { return s > 100 ? Math.round(s).toString() : s.toFixed(1); }

function updateGauge(val, gradient) {
  document.getElementById('arc').style.strokeDashoffset = ARC_LEN * (1 - Math.min(val / MAX_SPEED, 1));
  document.getElementById('arc').setAttribute('stroke', gradient);
  document.getElementById('val').textContent = fmtSpeed(val);
}

function detectDevice() {
  const ua = navigator.userAgent;
  let browser = 'Browser';
  if (ua.indexOf('Chrome') > -1) browser = 'Chrome';
  else if (ua.indexOf('Safari') > -1) browser = 'Safari';
  else if (ua.indexOf('Firefox') > -1) browser = 'Firefox';
  if (/Mac OS X/.test(ua)) return `${browser} on Mac`;
  if (/Windows/.test(ua)) return `${browser} on Windows`;
  if (/Android/.test(ua)) return `${browser} on Android`;
  if (/iPhone/.test(ua)) return `${browser} on iPhone`;
  return browser;
}

// ══════════════════════════════════════════════════════════════
// MODAL MANAGEMENT — renders Turnstile when modal opens
// ══════════════════════════════════════════════════════════════
const modalTurnstileMap = {
  'modal-login': { name: 'login', container: 'login-turnstile' },
  'modal-register': { name: 'register', container: 'register-turnstile' },
  'modal-forgot': { name: 'forgot', container: 'forgot-turnstile' },
  'modal-report': { name: 'report', container: 'report-turnstile' },
};

function openModal(id) {
  document.getElementById(id).classList.add('active');
  // Render Turnstile now that the modal is visible
  const cfg = modalTurnstileMap[id];
  if (cfg) {
    // Small delay to ensure modal is fully visible for Turnstile
    setTimeout(() => renderTurnstile(cfg.name, cfg.container), 150);
  }
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

function switchModal(from, to) {
  closeModal(from);
  openModal(to);
}

function setLoginType(type) {
  loginType = type;
  document.querySelectorAll('#modal-login .type-toggle button').forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('login-label').textContent = type === 'email' ? 'Email' : 'Phone Number';
  document.getElementById('login-contact').placeholder = type === 'email' ? 'you@example.com' : '+357XXXXXXXX';
}

function setRegisterType(type) {
  registerType = type;
  document.querySelectorAll('#modal-register .type-toggle button').forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('register-label').textContent = type === 'email' ? 'Email' : 'Phone Number';
  document.getElementById('register-contact').placeholder = type === 'email' ? 'you@example.com' : '+357XXXXXXXX';
}

function setForgotType(type) {
  forgotType = type;
  document.querySelectorAll('#modal-forgot .type-toggle button').forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('forgot-label').textContent = type === 'email' ? 'Email' : 'Phone Number';
  document.getElementById('forgot-contact').placeholder = type === 'email' ? 'you@example.com' : '+357XXXXXXXX';
}

// ══════════════════════════════════════════════════════════════
// AUTHENTICATION — now uses getTurnstileToken()
// Sends BOTH 'turnstile' and 'turnstile_token' keys to handle
// whichever the backend expects
// ══════════════════════════════════════════════════════════════
async function doLogin(e) {
  e.preventDefault();
  const contact = document.getElementById('login-contact').value;
  const password = document.getElementById('login-password').value;
  const msg = document.getElementById('login-msg');
  const btn = document.getElementById('login-btn');
  const token = getTurnstileToken('login');
  
  msg.textContent = '';

  if (!token) {
    msg.textContent = 'Please complete the security check first';
    msg.className = 'modal-msg error';
    return;
  }

  btn.disabled = true;
  
  try {
    const res = await fetch('login.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        type: loginType,
        contact: contact,
        password: password,
        turnstile: token,
        turnstile_token: token   // send both keys
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      location.reload();
    } else {
      msg.textContent = data.message || 'Login failed';
      msg.className = 'modal-msg error';
      // Reset turnstile for retry
      renderTurnstile('login', 'login-turnstile');
    }
  } catch(e) {
    msg.textContent = 'Network error';
    msg.className = 'modal-msg error';
  } finally {
    btn.disabled = false;
  }
}

async function doRegister(e) {
  e.preventDefault();
  const contact = document.getElementById('register-contact').value;
  const password = document.getElementById('register-password').value;
  const passwordConfirm = document.getElementById('register-password-confirm').value;
  const msg = document.getElementById('register-msg');
  const token = getTurnstileToken('register');
  
  msg.textContent = '';
  
  if (password !== passwordConfirm) {
    msg.textContent = 'Passwords do not match';
    msg.className = 'modal-msg error';
    return;
  }

  if (!token) {
    msg.textContent = 'Please complete the security check first';
    msg.className = 'modal-msg error';
    return;
  }
  
  try {
    const res = await fetch('register.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        type: registerType,
        contact: contact,
        password: password,
        turnstile: token,
        turnstile_token: token
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      location.reload();
    } else {
      msg.textContent = data.message || 'Registration failed';
      msg.className = 'modal-msg error';
      renderTurnstile('register', 'register-turnstile');
    }
  } catch(e) {
    msg.textContent = 'Network error';
    msg.className = 'modal-msg error';
  }
}

async function doForgot(e) {
  e.preventDefault();
  const contact = document.getElementById('forgot-contact').value;
  const msg = document.getElementById('forgot-msg');
  const token = getTurnstileToken('forgot');
  
  msg.textContent = '';

  if (!token) {
    msg.textContent = 'Please complete the security check first';
    msg.className = 'modal-msg error';
    return;
  }
  
  try {
    const res = await fetch('send_otp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        type: forgotType,
        contact: contact,
        purpose: 'reset',
        turnstile: token,
        turnstile_token: token
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      msg.textContent = 'Reset code sent! Check your ' + forgotType;
      msg.className = 'modal-msg success';
    } else {
      msg.textContent = data.message || 'Error';
      msg.className = 'modal-msg error';
    }
  } catch(e) {
    msg.textContent = 'Network error';
    msg.className = 'modal-msg error';
  }
}

async function doLogout() {
  await fetch('logout.php');
  location.reload();
}

// ══════════════════════════════════════════════════════════════
// REPORT ISSUE
// ══════════════════════════════════════════════════════════════
function openReportModal() {
  if (!lastResult.testId) {
    alert('Please run a speed test first before reporting an issue.');
    return;
  }
  openModal('modal-report');
}

async function doReport(e) {
  e.preventDefault();
  const category = document.getElementById('report-category').value;
  const description = document.getElementById('report-description').value;
  const contact = document.getElementById('report-contact').value;
  const wantsContact = document.getElementById('report-wants-contact').checked;
  const msg = document.getElementById('report-msg');
  const token = getTurnstileToken('report');
  
  msg.textContent = '';

  if (!token) {
    msg.textContent = 'Please complete the security check first';
    msg.className = 'modal-msg error';
    return;
  }
  
  try {
    const res = await fetch('report_issue.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        test_id: lastResult.testId,
        category: category,
        description: description,
        contact: contact,
        wants_contact: wantsContact,
        dl: lastResult.dl,
        ul: lastResult.ul,
        ping: lastResult.ping,
        isp: document.getElementById('v-isp').textContent,
        device: detectDevice(),
        turnstile_token: token
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      msg.textContent = '✅ Report submitted successfully! Thank you for your feedback.';
      msg.className = 'modal-msg success';
      setTimeout(() => {
        closeModal('modal-report');
        document.getElementById('form-report').reset();
        msg.textContent = '';
      }, 2500);
    } else {
      msg.textContent = data.message || 'Failed to submit report';
      msg.className = 'modal-msg error';
    }
  } catch(e) {
    msg.textContent = 'Network error. Please try again.';
    msg.className = 'modal-msg error';
  }
}

// ══════════════════════════════════════════════════════════════
// INFO DETECTION
// ══════════════════════════════════════════════════════════════
async function fetchInfo() {
  try {
    const r = await fetch('https://www.cloudflare.com/cdn-cgi/trace');
    const t = await r.text();
    const d = Object.fromEntries(t.split('\n').filter(v=>v).map(v=>v.split('=')));
    document.getElementById('v-ip').textContent = d.ip || 'Unknown';
    document.getElementById('v-co').textContent = d.loc ? flag(d.loc) + ' ' + d.loc : 'Unknown';
  } catch(e) {}
  try {
    const r2 = await fetch('https://ipapi.co/json/');
    const d2 = await r2.json();
    if (d2 && d2.org) document.getElementById('v-isp').textContent = d2.org;
  } catch(e) {}
}

// ══════════════════════════════════════════════════════════════
// SLIDING WINDOW SPEED CALCULATOR
// ══════════════════════════════════════════════════════════════
class SpeedMeter {
  constructor(windowMs, warmupMs) {
    this.windowMs = windowMs;
    this.warmupMs = warmupMs;
    this.samples = [];
    this.totalBytes = 0;
    this.startTime = 0;
    this.peakSpeed = 0;
    this.currentSpeed = 0;
  }

  start() {
    this.startTime = performance.now();
    this.samples = [];
    this.totalBytes = 0;
    this.peakSpeed = 0;
    this.currentSpeed = 0;
  }

  addBytes(bytes) {
    const now = performance.now();
    this.totalBytes += bytes;
    this.samples.push({ time: now, bytes });

    const elapsed = now - this.startTime;
    if (elapsed < this.warmupMs) return 0;

    const windowStart = now - this.windowMs;
    const effectiveStart = Math.max(windowStart, this.startTime + this.warmupMs);

    let windowBytes = 0;
    let oldestInWindow = now;
    for (let i = this.samples.length - 1; i >= 0; i--) {
      const s = this.samples[i];
      if (s.time < effectiveStart) break;
      windowBytes += s.bytes;
      oldestInWindow = s.time;
    }

    const windowDuration = (now - Math.max(oldestInWindow, effectiveStart)) / 1000;
    if (windowDuration > 0.5) {
      this.currentSpeed = (windowBytes * 8) / (windowDuration * 1000000);
      if (this.currentSpeed > this.peakSpeed) {
        this.peakSpeed = this.currentSpeed;
      }
    }

    // Prune old samples
    const pruneTime = now - (this.windowMs * 2);
    while (this.samples.length > 0 && this.samples[0].time < pruneTime) {
      this.samples.shift();
    }

    return this.currentSpeed;
  }

  getFinalSpeed() {
    if (this.peakSpeed > 0) {
      return this.peakSpeed * 0.7 + this.currentSpeed * 0.3;
    }
    return this.currentSpeed;
  }
}

// ══════════════════════════════════════════════════════════════
// SPEED TEST ENGINE
// ══════════════════════════════════════════════════════════════
async function measurePing() {
  // Use dedicated ping.php (tiny response) for accurate RTT
  // Falls back to Cloudflare trace if ping.php fails
  let endpoint = PING_ENDPOINT;
  let pings = [];

  // Warm-up request (establish connection, DNS, TLS)
  try { await fetch(endpoint + '?warmup=1', { cache: 'no-store' }); } catch(e) {
    endpoint = 'https://www.cloudflare.com/cdn-cgi/trace';
    try { await fetch(endpoint, { cache: 'no-store' }); } catch(e2) {}
  }

  // Actual measurements
  for (let i = 0; i < 10; i++) {
    const t0 = performance.now();
    try {
      await fetch(endpoint + '?p=' + i + '&r=' + Math.random(), { cache: 'no-store' });
      pings.push(performance.now() - t0);
    } catch(e) {}
  }
  
  if (!pings.length) return 0;
  // Sort and take median for stability
  pings.sort((a, b) => a - b);
  // Remove worst 20% (outliers)
  const trimCount = Math.floor(pings.length * 0.2);
  const trimmed = pings.slice(0, pings.length - trimCount);
  // Return median of trimmed set
  return trimmed[Math.floor(trimmed.length / 2)];
}

async function measureDownload(updateCallback) {
  console.log('📥 Download: %d workers, %ds, %ds warmup', DL_WORKERS, DL_DURATION/1000, DL_WARMUP/1000);
  const meter = new SpeedMeter(DL_WINDOW, DL_WARMUP);
  meter.start();
  let running = true;
  document.getElementById('phase').textContent = 'WARMING UP';

  setTimeout(() => { running = false; }, DL_DURATION);
  setTimeout(() => { if (running) document.getElementById('phase').textContent = 'DOWNLOAD'; }, DL_WARMUP);

  const primaryFile = TEST_FILES[0];

  async function dlWorker(id) {
    while (running) {
      try {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', primaryFile + '?w=' + id + '&r=' + Math.random(), true);
        xhr.responseType = 'arraybuffer';
        let prevLoaded = 0;

        xhr.onprogress = (e) => {
          if (!running) { xhr.abort(); return; }
          const chunk = e.loaded - prevLoaded;
          prevLoaded = e.loaded;
          totalBytesUsed += chunk;
          document.getElementById('mbv').textContent = (totalBytesUsed / 1048576).toFixed(2);
          const speed = meter.addBytes(chunk);
          if (speed > 0) {
            updateCallback(speed);
            updateGauge(speed, 'url(#gDL)');
          }
        };

        xhr.send();
        const abortTimer = setTimeout(() => {
          if (running && xhr.readyState !== 4) xhr.abort();
        }, DL_ABORT_TIMEOUT);

        await new Promise(resolve => {
          xhr.onloadend = () => { clearTimeout(abortTimer); resolve(); };
          xhr.onerror = () => { clearTimeout(abortTimer); resolve(); };
        });
      } catch(e) {
        await new Promise(r => setTimeout(r, 100));
      }
      if (!running) break;
    }
  }

  const workers = [];
  for (let i = 0; i < DL_WORKERS; i++) workers.push(dlWorker(i));
  await Promise.all(workers);

  return meter.getFinalSpeed();
}

async function measureUpload(updateCallback) {
  console.log('📤 Upload: %d workers, %dMB chunks, %ds', UL_WORKERS, UL_CHUNK_SIZE/1048576, UL_DURATION/1000);
  const meter = new SpeedMeter(UL_WINDOW, UL_WARMUP);
  meter.start();
  let running = true;
  document.getElementById('phase').textContent = 'WARMING UP';
  updateGauge(0, 'url(#gUL)');

  setTimeout(() => { running = false; }, UL_DURATION);
  setTimeout(() => { if (running) document.getElementById('phase').textContent = 'UPLOAD'; }, UL_WARMUP);

  const uploadData = new ArrayBuffer(UL_CHUNK_SIZE);
  if (window.crypto && crypto.getRandomValues) {
    for (let offset = 0; offset < UL_CHUNK_SIZE; offset += 65536) {
      crypto.getRandomValues(new Uint8Array(uploadData, offset, Math.min(65536, UL_CHUNK_SIZE - offset)));
    }
  } else {
    const v = new Uint8Array(uploadData);
    for (let i = 0; i < UL_CHUNK_SIZE; i++) v[i] = Math.random() * 256 | 0;
  }

  async function ulWorker(id) {
    while (running) {
      try {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', UPLOAD_ENDPOINT + '?w=' + id + '&r=' + Math.random(), true);
        let prevLoaded = 0;

        xhr.upload.onprogress = (e) => {
          if (!running) { xhr.abort(); return; }
          const chunk = e.loaded - prevLoaded;
          prevLoaded = e.loaded;
          totalBytesUsed += chunk;
          document.getElementById('mbv').textContent = (totalBytesUsed / 1048576).toFixed(2);
          const speed = meter.addBytes(chunk);
          if (speed > 0) {
            updateCallback(speed);
            updateGauge(speed, 'url(#gUL)');
          }
        };

        xhr.send(uploadData);
        const abortTimer = setTimeout(() => {
          if (running && xhr.readyState !== 4) xhr.abort();
        }, UL_ABORT_TIMEOUT);

        await new Promise(resolve => {
          xhr.onloadend = () => { clearTimeout(abortTimer); resolve(); };
          xhr.onerror = () => { clearTimeout(abortTimer); resolve(); };
        });
      } catch(e) {
        await new Promise(r => setTimeout(r, 100));
      }
      if (!running) break;
    }
  }

  const workers = [];
  for (let i = 0; i < UL_WORKERS; i++) workers.push(ulWorker(i));
  await Promise.all(workers);

  return meter.getFinalSpeed();
}

// ══════════════════════════════════════════════════════════════
// UI STATE MANAGEMENT
// ══════════════════════════════════════════════════════════════
function showTestUI() {
  document.getElementById('test-area').style.opacity = '1';
  document.getElementById('test-area').style.display = '';
  document.getElementById('results-panel').classList.remove('visible');
}

function showResultsUI(dl, ul, ping) {
  // Hide gauge, show results panel
  document.getElementById('test-area').style.display = 'none';
  
  const panel = document.getElementById('results-panel');
  document.getElementById('final-dl').textContent = fmtSpeed(dl);
  document.getElementById('final-ul').textContent = fmtSpeed(ul);
  document.getElementById('final-ping').textContent = Math.round(ping);
  panel.classList.add('visible');
}

// ══════════════════════════════════════════════════════════════
// RUN TEST
// ══════════════════════════════════════════════════════════════
async function runTest() {
  const btn = document.getElementById('btn');
  btn.disabled = true;
  btn.textContent = 'Running...';
  totalBytesUsed = 0;
  document.getElementById('mbv').textContent = '0.00';
  
  // Reset UI to test mode
  showTestUI();
  updateGauge(0, 'url(#gDL)');
  document.getElementById('rd').textContent = '–';
  document.getElementById('ru').textContent = '–';
  document.getElementById('rp').textContent = '–';
  document.getElementById('share-btn').style.display = 'none';
  document.getElementById('report-btn').style.display = 'none';
  
  let dlSpeed = 0, ulSpeed = 0, ping = 0;
  
  try {
    // PING
    document.getElementById('phase').textContent = 'PING';
    ping = await measurePing();
    document.getElementById('rp').textContent = Math.round(ping);

    // DOWNLOAD
    dlSpeed = await measureDownload((s) => {
      document.getElementById('rd').textContent = fmtSpeed(s);
    });
    document.getElementById('rd').textContent = fmtSpeed(dlSpeed);

    // UPLOAD
    ulSpeed = await measureUpload((s) => {
      document.getElementById('ru').textContent = fmtSpeed(s);
    });
    document.getElementById('ru').textContent = fmtSpeed(ulSpeed);
    
    // Save result
    fetch('save_result.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            isp: document.getElementById('v-isp').textContent,
            dl: dlSpeed.toFixed(1),
            ul: ulSpeed.toFixed(1),
            ping: Math.round(ping),
            device: detectDevice()
        })
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              lastResult.shareLink = data.share_url;
              lastResult.testId = data.test_id;
              lastResult.dl = dlSpeed.toFixed(1);
              lastResult.ul = ulSpeed.toFixed(1);
              lastResult.ping = Math.round(ping).toString();
              document.getElementById('share-btn').style.display = 'block';
              document.getElementById('test-id-value').textContent = data.test_id;
              document.getElementById('test-id-box').style.display = 'block';
          }
      })
      .catch(e => console.error("Save failed:", e));
    
    // ✨ SWITCH TO RESULTS UI
    showResultsUI(dlSpeed, ulSpeed, ping);
    document.getElementById('report-btn').style.display = 'block';
    
    history.unshift({ time: new Date().toLocaleTimeString(), dl: dlSpeed.toFixed(1), ul: ulSpeed.toFixed(1) });
    history = history.slice(0, 5);
    localStorage.setItem('aura_history', JSON.stringify(history));
    renderHistory();
  } catch(e) {
    console.error('Test error:', e);
    document.getElementById('phase').textContent = 'ERROR';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Run Again';
  }
}

function shareResults() {
  if (!lastResult.shareLink) return;
  navigator.clipboard.writeText(lastResult.shareLink)
    .then(() => {
      const b = document.getElementById('share-btn');
      b.textContent = 'Link Copied!';
      setTimeout(() => b.textContent = 'Share Link', 2000);
    })
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

document.getElementById('btn').addEventListener('click', runTest);
fetchInfo();
renderHistory();
console.log('✅ v3 loaded — Turnstile fix, ping fix, results UI');
</script>
</body>
</html>