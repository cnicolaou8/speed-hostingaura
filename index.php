<?php
// ══════════════════════════════════════════════════════════════
// HostingAura Speed Test - COMPLETE VERSION
// Speed Test: LUMO's optimizations
// Auth: All features restored
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
.wrap{position:relative;width:260px;height:260px;margin-bottom:22px}
.wrap svg{width:100%;height:100%;overflow:visible}
.ci{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}
#val{font-size:3.6rem;font-weight:800;letter-spacing:-3px;line-height:1;font-variant-numeric:tabular-nums}
#unit{font-size:.65rem;color:#475569;letter-spacing:.18em;text-transform:uppercase;margin-top:4px}
#phase{font-size:.58rem;letter-spacing:.28em;text-transform:uppercase;margin-top:10px;color:#6366f1}
.cards{display:flex;gap:10px;margin-bottom:14px}
.card{background:#0c0c1c;border:1px solid #181830;border-radius:14px;padding:12px;text-align:center;min-width:82px}
.cv{font-size:1.3rem;font-weight:700;color:#e2e8f0;min-height:1.5rem}
.cn{font-size:.52rem;letter-spacing:.16em;color:#475569;text-transform:uppercase}
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

/* MODAL STYLES */
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
      <div class="turnstile-widget" id="register-turnstile"></div>
      <button type="submit" class="form-submit">Sign Up</button>
      <div class="modal-msg" id="register-msg"></div>
    </form>
    
    <div class="modal-switch">
      Already have an account? <a onclick="switchModal('modal-register','modal-login')">Log In</a>
    </div>
  </div>
</div>

<!-- VERIFY OTP MODAL -->
<div class="modal-overlay" id="modal-verify">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-verify')">×</button>
    <h2>Verify Code</h2>
    <p class="sub">Enter the 6-digit code sent to <span id="verify-contact-display"></span></p>
    
    <form id="form-verify" onsubmit="doVerify(event)">
      <div class="form-group">
        <label>Verification Code</label>
        <input type="text" id="verify-code" maxlength="6" pattern="\d{6}" required/>
      </div>
      <button type="submit" class="form-submit">Verify</button>
      <div class="modal-msg" id="verify-msg"></div>
    </form>
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

<script>
'use strict';

console.log('🚀 HostingAura Speed Test - COMPLETE VERSION');

// ══════════════════════════════════════════════════════════════
// CONFIG
// ══════════════════════════════════════════════════════════════
const TURNSTILE_SITE_KEY = '<?= TURNSTILE_SITE_KEY ?>';
const TEST_FILES = [
    '/test-files/100mb.bin',
    '/test-files/50mb.bin',
    '/test-files/25mb.bin',
    '/test-files/10mb.bin'
];
const UPLOAD_ENDPOINT = 'empty.php';

const ARC_LEN = 518.36;
const MAX_SPEED = 1000;

let totalBytesUsed = 0;
let lastResult = { shareLink: null, testId: null, dl: '–', ul: '–', ping: '–' };
let history = [];
try { history = JSON.parse(localStorage.getItem('aura_history') || '[]'); } catch(e) {}

// Auth state
let otpType = 'email';
let forgotType = 'email';
let loginType = 'email';
let registerType = 'email';
let loginTurnstileToken = null;
let registerTurnstileToken = null;
let forgotTurnstileToken = null;
let verifyingContact = '';

// ══════════════════════════════════════════════════════════════
// UI HELPERS
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
// MODAL FUNCTIONS
// ══════════════════════════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add('active');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

function switchModal(from, to) {
  closeModal(from);
  openModal(to);
}

// ══════════════════════════════════════════════════════════════
// TYPE TOGGLES
// ══════════════════════════════════════════════════════════════
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
// TURNSTILE
// ══════════════════════════════════════════════════════════════
window.onTurnstileLoad = function() {
  if (window.turnstile) {
    turnstile.render('#login-turnstile', {
      sitekey: TURNSTILE_SITE_KEY,
      callback: (token) => { loginTurnstileToken = token; }
    });
    turnstile.render('#register-turnstile', {
      sitekey: TURNSTILE_SITE_KEY,
      callback: (token) => { registerTurnstileToken = token; }
    });
    turnstile.render('#forgot-turnstile', {
      sitekey: TURNSTILE_SITE_KEY,
      callback: (token) => { forgotTurnstileToken = token; }
    });
  }
};

// ══════════════════════════════════════════════════════════════
// AUTH FUNCTIONS
// ══════════════════════════════════════════════════════════════
async function doLogin(e) {
  e.preventDefault();
  const contact = document.getElementById('login-contact').value;
  const msg = document.getElementById('login-msg');
  const btn = document.getElementById('login-btn');
  
  msg.textContent = '';
  btn.disabled = true;
  
  try {
    const res = await fetch('send_otp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        type: loginType,
        contact: contact,
        purpose: 'login',
        turnstile: loginTurnstileToken
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      verifyingContact = contact;
      document.getElementById('verify-contact-display').textContent = contact;
      switchModal('modal-login', 'modal-verify');
    } else {
      msg.textContent = data.message || 'Error sending code';
      msg.className = 'modal-msg error';
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
  const msg = document.getElementById('register-msg');
  
  msg.textContent = '';
  
  try {
    const res = await fetch('send_otp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        type: registerType,
        contact: contact,
        purpose: 'register',
        turnstile: registerTurnstileToken
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      verifyingContact = contact;
      document.getElementById('verify-contact-display').textContent = contact;
      switchModal('modal-register', 'modal-verify');
    } else {
      msg.textContent = data.message || 'Error';
      msg.className = 'modal-msg error';
    }
  } catch(e) {
    msg.textContent = 'Network error';
    msg.className = 'modal-msg error';
  }
}

async function doVerify(e) {
  e.preventDefault();
  const code = document.getElementById('verify-code').value;
  const msg = document.getElementById('verify-msg');
  
  msg.textContent = '';
  
  try {
    const res = await fetch('verify_otp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        contact: verifyingContact,
        code: code
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      location.reload();
    } else {
      msg.textContent = data.message || 'Invalid code';
      msg.className = 'modal-msg error';
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
  
  msg.textContent = '';
  
  try {
    const res = await fetch('send_otp.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        type: forgotType,
        contact: contact,
        purpose: 'reset',
        turnstile: forgotTurnstileToken
      })
    });
    const data = await res.json();
    
    if (data.status === 'success') {
      verifyingContact = contact;
      document.getElementById('verify-contact-display').textContent = contact;
      switchModal('modal-forgot', 'modal-verify');
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

function openReportModal() {
  alert('Report feature - modal to be added');
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
// PING TEST
// ══════════════════════════════════════════════════════════════
async function measurePing() {
  let pings = [];
  for (let i = 0; i < 5; i++) {
    const t0 = performance.now();
    try {
      await fetch(TEST_FILES[0], { method: 'HEAD', cache: 'no-store' });
      pings.push(performance.now() - t0);
    } catch(e) {}
  }
  return pings.length ? Math.min(...pings) : 0;
}

// ══════════════════════════════════════════════════════════════
// DOWNLOAD TEST - LUMO's optimizations
// ══════════════════════════════════════════════════════════════
async function measureDownload(updateCallback) {
  console.log('📥 Download test: Multi-file parallel strategy');
  
  const WORKERS_PER_FILE = 2;
  const DURATION = 30000;
  
  let totalBytes = 0;
  let startTime = performance.now();
  let running = true;
  
  document.getElementById('phase').textContent = 'DOWNLOAD';
  
  setTimeout(() => { running = false; }, DURATION);
  
  async function dlWorker(fileUrl) {
    while (running) {
      const xhr = new XMLHttpRequest();
      xhr.open('GET', fileUrl + '?r=' + Math.random(), true);
      xhr.responseType = 'arraybuffer';
      
      let prevLoaded = 0;
      xhr.onprogress = (e) => {
        if (!running) {
          xhr.abort();
          return;
        }
        
        const chunk = e.loaded - prevLoaded;
        prevLoaded = e.loaded;
        totalBytes += chunk;
        totalBytesUsed += chunk;
        
        document.getElementById('mbv').textContent = (totalBytesUsed / 1048576).toFixed(2);
        
        const elapsed = (performance.now() - startTime) / 1000;
        if (elapsed > 1) {
          const speed = (totalBytes * 8) / (elapsed * 1000000);
          updateCallback(speed);
          updateGauge(speed, 'url(#gDL)');
        }
      };
      
      xhr.send();
      
      setTimeout(() => {
        if (running && xhr.readyState !== 4) {
          xhr.abort();
        }
      }, 2000);
      
      await new Promise(resolve => {
        xhr.onloadend = resolve;
        xhr.onerror = resolve;
      });
      
      if (!running) break;
    }
  }
  
  const workers = [];
  for (const file of TEST_FILES) {
    for (let i = 0; i < WORKERS_PER_FILE; i++) {
      workers.push(dlWorker(file));
    }
  }
  
  await Promise.all(workers);
  
  const finalElapsed = (performance.now() - startTime) / 1000;
  const finalSpeed = finalElapsed > 0 ? (totalBytes * 8) / (finalElapsed * 1000000) : 0;
  
  console.log('📥 Download complete:', fmtSpeed(finalSpeed), 'Mbps');
  return finalSpeed;
}

// ══════════════════════════════════════════════════════════════
// UPLOAD TEST - LUMO's optimizations
// ══════════════════════════════════════════════════════════════
async function measureUpload(updateCallback) {
  console.log('📤 Upload test starting...');
  
  const WORKERS = 8;
  const DURATION = 30000;
  const CHUNK_SIZE = 1048576;
  
  let totalBytes = 0;
  let startTime = performance.now();
  let running = true;
  
  document.getElementById('phase').textContent = 'UPLOAD';
  updateGauge(0, 'url(#gUL)');
  
  const uploadData = new ArrayBuffer(CHUNK_SIZE);
  const view = new Uint8Array(uploadData);
  for (let i = 0; i < CHUNK_SIZE; i++) {
    view[i] = Math.random() * 256 | 0;
  }
  
  setTimeout(() => { running = false; }, DURATION);
  
  async function ulWorker() {
    while (running) {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', UPLOAD_ENDPOINT, true);
      
      let prevLoaded = 0;
      xhr.upload.onprogress = (e) => {
        if (!running) {
          xhr.abort();
          return;
        }
        
        const chunk = e.loaded - prevLoaded;
        prevLoaded = e.loaded;
        totalBytes += chunk;
        totalBytesUsed += chunk;
        
        document.getElementById('mbv').textContent = (totalBytesUsed / 1048576).toFixed(2);
        
        const elapsed = (performance.now() - startTime) / 1000;
        if (elapsed > 1) {
          const speed = (totalBytes * 8) / (elapsed * 1000000);
          updateCallback(speed);
          updateGauge(speed, 'url(#gUL)');
        }
      };
      
      xhr.onerror = () => {};
      xhr.onload = () => {};
      
      xhr.send(uploadData);
      
      setTimeout(() => {
        if (running && xhr.readyState !== 4) {
          xhr.abort();
        }
      }, 2000);
      
      await new Promise(resolve => {
        xhr.onloadend = resolve;
        xhr.onerror = resolve;
      });
      
      if (!running) break;
    }
  }
  
  const workers = [];
  for (let i = 0; i < WORKERS; i++) {
    workers.push(ulWorker());
  }
  
  await Promise.all(workers);
  
  const finalElapsed = (performance.now() - startTime) / 1000;
  const finalSpeed = finalElapsed > 0 ? (totalBytes * 8) / (finalElapsed * 1000000) : 0;
  
  console.log('📤 Upload complete:', fmtSpeed(finalSpeed), 'Mbps');
  return finalSpeed;
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
  updateGauge(0, 'url(#gDL)');
  
  let dlSpeed = 0, ulSpeed = 0, ping = 0;
  
  try {
    document.getElementById('phase').textContent = 'PING';
    ping = await measurePing();
    document.getElementById('rp').textContent = Math.round(ping);
    
    dlSpeed = await measureDownload((s) => {
      document.getElementById('rd').textContent = fmtSpeed(s);
    });
    document.getElementById('rd').textContent = fmtSpeed(dlSpeed);
    
    ulSpeed = await measureUpload((s) => {
      document.getElementById('ru').textContent = fmtSpeed(s);
    });
    document.getElementById('ru').textContent = fmtSpeed(ulSpeed);
    
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
              document.getElementById('share-btn').style.display = 'block';
              document.getElementById('test-id-value').textContent = data.test_id;
              document.getElementById('test-id-box').style.display = 'block';
          }
      })
      .catch(e => console.error("Save failed (non-fatal):", e));
    
    document.getElementById('phase').textContent = 'COMPLETE';
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
    btn.textContent = 'Start Speed Test';
  }
}

// ══════════════════════════════════════════════════════════════
// SHARE & HISTORY
// ══════════════════════════════════════════════════════════════
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

// ══════════════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════════════
document.getElementById('btn').addEventListener('click', runTest);
fetchInfo();
renderHistory();

console.log('✅ Complete version loaded - Auth + LUMO speed test optimizations!');
</script>
</body>
</html>