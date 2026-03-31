<?php
session_start();
require_once 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userId = $isLoggedIn ? intval($_SESSION['user_id']) : null;

// Get user info if logged in
$userContact = '';
if ($isLoggedIn) {
    $conn = getDBConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();
        
        $userContact = $result['email'] ?? $result['phone'] ?? '';
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

<!-- CLOUDFLARE TURNSTILE -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad" async defer></script>

<style>
/* ══════════════════════════════════════════════════════════════
   RESET & BASE STYLES
   ══════════════════════════════════════════════════════════════ */
*{margin:0;padding:0;box-sizing:border-box}

body{
  min-height:100vh;
  background:#070711;
  background-image:radial-gradient(ellipse 90% 55% at 50% 0%,rgba(99,102,241,.15) 0%,transparent 65%);
  color:#e2e8f0;
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:24px 16px;
}

/* ══════════════════════════════════════════════════════════════
   LOGO & BRANDING
   ══════════════════════════════════════════════════════════════ */
.logo{margin-bottom:18px;text-align:center;line-height:1}
.logo-text{font-size:1.7rem;font-weight:800;letter-spacing:-.5px}
.logo-hosting{
  background:linear-gradient(90deg,#38bdf8,#6366f1);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}
.logo-aura{
  background:linear-gradient(90deg,#a855f7,#ec4899);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}
.beta-badge{
  display:inline-block;
  background:rgba(236,72,153,0.15);
  border:1px solid rgba(236,72,153,0.3);
  color:#ec4899;
  font-size:0.5rem;font-weight:700;
  letter-spacing:0.1em;padding:3px 8px;
  border-radius:4px;margin-left:8px;
  vertical-align:middle;position:relative;top:-2px;
}
.logo-sub{
  font-size:.58rem;
  letter-spacing:.38em;
  color:#475569;
  text-transform:uppercase;
  margin-top:4px;
}

/* ══════════════════════════════════════════════════════════════
   AUTH BAR
   ══════════════════════════════════════════════════════════════ */
.auth-bar{
  display:flex;gap:8px;align-items:center;
  margin-bottom:16px;flex-wrap:wrap;
  justify-content:center;
}
.auth-bar .user-info{font-size:.65rem;color:#94a3b8}
.auth-bar .user-info span{color:#6366f1;font-weight:700}

.auth-btn{
  background:none;border:1px solid #334155;
  border-radius:999px;padding:5px 16px;
  font-size:.65rem;color:#94a3b8;cursor:pointer;
  transition:.2s;
}
.auth-btn:hover{border-color:#6366f1;color:#6366f1}
.auth-btn.primary{
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  border:none;color:#fff;font-weight:600;
}
.auth-btn.primary:hover{opacity:.85}
.auth-btn.danger{border-color:#ff4d4d;color:#ff4d4d}
.auth-btn.danger:hover{background:#ff4d4d;color:#fff}

.dashboard-btn{
  background:#1a1a2e;border:1px solid #6366f1;
  border-radius:999px;padding:5px 16px;
  font-size:.65rem;color:#6366f1;cursor:pointer;
  text-decoration:none;transition:.2s;
}
.dashboard-btn:hover{background:#6366f1;color:#fff}

/* ══════════════════════════════════════════════════════════════
   INFO BADGES
   ══════════════════════════════════════════════════════════════ */
.infobar{
  display:flex;gap:6px;flex-wrap:wrap;
  justify-content:center;margin-bottom:18px;
}
.badge{
  background:#0c0c1c;border:1px solid #181830;
  border-radius:8px;padding:5px 12px;font-size:.65rem;
  color:#94a3b8;display:flex;align-items:center;gap:5px;
}
.badge .lbl{
  font-size:.55rem;letter-spacing:.14em;
  text-transform:uppercase;color:#475569;margin-right:2px;
}

/* ══════════════════════════════════════════════════════════════
   NOTICES & TEST ID
   ══════════════════════════════════════════════════════════════ */
.brave-notice{
  background:#fef3c7;border:1px solid #f59e0b;
  border-radius:8px;padding:8px 12px;font-size:.65rem;
  color:#92400e;margin-bottom:12px;display:none;
  text-align:center;max-width:360px;
}

.test-id-box{
  background:#0c0c1c;border:1px solid #181830;
  border-radius:8px;padding:8px 12px;font-size:.65rem;
  color:#94a3b8;margin-bottom:14px;display:none;
  text-align:center;max-width:360px;
}
.test-id-box .lbl{
  font-size:.55rem;letter-spacing:.14em;
  text-transform:uppercase;color:#475569;margin-right:4px;
}
.test-id-box .tid{
  color:#6366f1;font-weight:700;font-family:monospace;
}

/* ══════════════════════════════════════════════════════════════
   SPEED GAUGE
   ══════════════════════════════════════════════════════════════ */
.wrap{
  position:relative;width:260px;height:260px;
  margin-bottom:22px;transition:all 0.5s ease;overflow:hidden;
}
.wrap.hidden{height:0;margin-bottom:0;opacity:0}
.wrap svg{width:100%;height:100%;overflow:visible}

.ci{
  position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  text-align:center;pointer-events:none;
}
#val{
  font-size:3.6rem;font-weight:800;
  letter-spacing:-3px;line-height:1;
  font-variant-numeric:tabular-nums;
}
#unit{
  font-size:.65rem;color:#475569;
  letter-spacing:.18em;text-transform:uppercase;margin-top:4px;
}
#phase{
  font-size:.58rem;letter-spacing:.28em;
  text-transform:uppercase;margin-top:10px;color:#6366f1;
}

/* ══════════════════════════════════════════════════════════════
   RESULT CARDS
   ══════════════════════════════════════════════════════════════ */
.cards{
  display:flex;gap:10px;margin-bottom:14px;
  transition:all 0.5s ease;
}
.card{
  background:#0c0c1c;border:1px solid #181830;
  border-radius:14px;padding:12px;text-align:center;
  min-width:82px;transition:all .5s;
}
.cards.expanded .card{min-width:120px;padding:20px}

.cv{
  font-size:1.3rem;font-weight:700;color:#e2e8f0;
  min-height:1.5rem;transition:all 0.5s ease;
}
.cards.expanded .cv{font-size:2.8rem}

.cn{
  font-size:.52rem;letter-spacing:.16em;
  color:#475569;text-transform:uppercase;
  transition:all 0.5s ease;
}
.cards.expanded .cn{font-size:.65rem;margin-top:8px}

/* ══════════════════════════════════════════════════════════════
   DATA USAGE & PRIVACY
   ══════════════════════════════════════════════════════════════ */
.mbbar{
  background:#0c0c1c;border:1px solid #181830;
  border-radius:10px;padding:6px 15px;font-size:.64rem;
  margin-bottom:16px;color:#475569;
}
.mbv{font-weight:700;color:#6366f1}

.privacy-notice{
  background:#0c0c1c;border:1px solid #181830;
  border-radius:12px;padding:12px 16px;font-size:.7rem;
  color:#94a3b8;line-height:1.5;max-width:360px;
  margin-bottom:16px;text-align:center;
}
.privacy-notice a{
  color:#6366f1;text-decoration:none;font-weight:600;
}
.privacy-notice a:hover{text-decoration:underline}

/* ══════════════════════════════════════════════════════════════
   BUTTONS
   ══════════════════════════════════════════════════════════════ */
.btn-group{display:flex;gap:10px;margin-bottom:20px}

#btn{
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  color:#fff;border:none;border-radius:999px;
  padding:12px 42px;font-size:.9rem;font-weight:600;
  cursor:pointer;box-shadow:0 4px 20px rgba(99,102,241,0.3);
  transition:0.2s;
}
#btn:disabled{opacity:0.5;cursor:not-allowed}

#share-btn{
  background:#1e1e3a;color:#94a3b8;
  border:1px solid #334155;border-radius:999px;
  padding:12px 24px;font-size:.9rem;cursor:pointer;
  display:none;
}

/* ══════════════════════════════════════════════════════════════
   HISTORY
   ══════════════════════════════════════════════════════════════ */
.hist-wrap{width:100%;max-width:360px;margin-top:25px}
.hist-item{
  background:#0c0c1c;border:1px solid #181830;
  border-radius:10px;display:flex;padding:10px;
  margin-bottom:7px;font-size:0.75rem;
  justify-content:space-between;align-items:center;
}

/* ══════════════════════════════════════════════════════════════
   MODAL SYSTEM
   ══════════════════════════════════════════════════════════════ */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.7);backdrop-filter:blur(4px);
  z-index:1000;align-items:center;justify-content:center;
}
.modal-overlay.active{display:flex}

.modal{
  background:#0f0f1a;border:1px solid #1e1e3a;
  border-radius:18px;padding:28px 24px;
  width:100%;max-width:360px;position:relative;
}
.modal h2{
  font-size:1.1rem;font-weight:700;
  margin-bottom:4px;color:#e2e8f0;
}
.modal p.sub{
  font-size:.7rem;color:#475569;margin-bottom:20px;
}

.modal-close{
  position:absolute;top:14px;right:16px;
  background:none;border:none;color:#475569;
  font-size:1.2rem;cursor:pointer;
}
.modal-close:hover{color:#e2e8f0}

/* ══════════════════════════════════════════════════════════════
   FORM ELEMENTS
   ══════════════════════════════════════════════════════════════ */
.form-group{margin-bottom:14px}
.form-group label{
  display:block;font-size:.65rem;letter-spacing:.1em;
  text-transform:uppercase;color:#475569;margin-bottom:6px;
}
.form-group input{
  width:100%;background:#0c0c1c;border:1px solid #1e1e3a;
  border-radius:8px;padding:10px 12px;font-size:.85rem;
  color:#e2e8f0;outline:none;transition:.2s;
}
.form-group input:focus{border-color:#6366f1}

.form-submit{
  width:100%;background:linear-gradient(135deg,#6366f1,#8b5cf6);
  color:#fff;border:none;border-radius:999px;
  padding:11px;font-size:.9rem;font-weight:600;
  cursor:pointer;margin-top:6px;transition:.2s;
}
.form-submit:hover{opacity:.85}
.form-submit:disabled{opacity:.5;cursor:not-allowed}

.modal-switch{
  font-size:.68rem;color:#475569;
  text-align:center;margin-top:14px;
}
.modal-switch a{
  color:#6366f1;cursor:pointer;
  text-decoration:none;font-weight:600;
}
.modal-switch a:hover{text-decoration:underline}

.modal-msg{
  font-size:.72rem;text-align:center;
  margin-top:10px;min-height:1rem;
}
.modal-msg.error{color:#ff4d4d}
.modal-msg.success{color:#22c55e}

.otp-note{
  font-size:.65rem;color:#475569;
  text-align:center;margin-bottom:12px;
}

.type-toggle{display:flex;gap:8px;margin-bottom:16px}
.type-toggle button{
  flex:1;background:#0c0c1c;border:1px solid #1e1e3a;
  border-radius:8px;padding:7px;font-size:.68rem;
  color:#94a3b8;cursor:pointer;transition:.2s;
}
.type-toggle button.active{
  border-color:#6366f1;color:#6366f1;background:#1a1a2e;
}

/* ══════════════════════════════════════════════════════════════
   TURNSTILE WIDGET
   ══════════════════════════════════════════════════════════════ */
.turnstile-widget{
  margin:14px 0;
  display:flex;
  justify-content:center;
  min-height:65px;
}

/* ══════════════════════════════════════════════════════════════
   LOCKOUT TIMER
   ══════════════════════════════════════════════════════════════ */
.lockout-timer{
  font-size:2rem;font-weight:800;color:#ff4d4d;
  letter-spacing:2px;text-align:center;margin:8px 0;
  font-variant-numeric:tabular-nums;
}
.lockout-msg{
  font-size:.72rem;color:#ff4d4d;
  text-align:center;margin-bottom:4px;
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════════
     LOGO & BRANDING
     ══════════════════════════════════════════════════════════════ -->
<div class="logo">
  <div class="logo-text">
    <span class="logo-hosting">hosting</span><span class="logo-aura">aura</span>
    <span class="beta-badge">BETA</span>
  </div>
  <div class="logo-sub">Enterprise Speed Test</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     AUTH BAR
     ══════════════════════════════════════════════════════════════ -->
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

<!-- ══════════════════════════════════════════════════════════════
     INFO BADGES
     ══════════════════════════════════════════════════════════════ -->
<div class="infobar">
  <div class="badge"><span class="lbl">IP</span><span id="v-ip">...</span></div>
  <div class="badge"><span class="lbl">LOC</span><span id="v-co">...</span></div>
  <div class="badge"><span class="lbl">ISP</span><span id="v-isp">...</span></div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     NOTICES
     ══════════════════════════════════════════════════════════════ -->
<div class="brave-notice" id="brave-notice">
  ⚠️ Using Brave? Disable shields for accurate ISP/location detection
</div>

<div class="test-id-box" id="test-id-box">
  <span class="lbl">TEST ID</span><span class="tid" id="test-id-value">–</span>
</div>

<!-- ══════════════════════════════════════════════════════════════
     SPEED GAUGE
     ══════════════════════════════════════════════════════════════ -->
<div class="wrap" id="gauge-wrap">
  <svg viewBox="0 0 300 300">
    <defs>
      <linearGradient id="gDL" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#6366f1"/>
        <stop offset="100%" stop-color="#06b6d4"/>
      </linearGradient>
      <linearGradient id="gUL" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#7c3aed"/>
        <stop offset="100%" stop-color="#ec4899"/>
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

<!-- ══════════════════════════════════════════════════════════════
     RESULT CARDS
     ══════════════════════════════════════════════════════════════ -->
<div class="cards" id="cards-wrap">
  <div class="card">
    <div class="cv" id="rd">–</div>
    <div class="cn">Down</div>
  </div>
  <div class="card">
    <div class="cv" id="ru">–</div>
    <div class="cn">Up</div>
  </div>
  <div class="card">
    <div class="cv" id="rp">–</div>
    <div class="cn">Ping</div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     DATA USAGE
     ══════════════════════════════════════════════════════════════ -->
<div class="mbbar">
  📦 Data Used: <span class="mbv" id="mbv">0.00</span> MB
</div>

<!-- ══════════════════════════════════════════════════════════════
     PRIVACY NOTICE
     ══════════════════════════════════════════════════════════════ -->
<div class="privacy-notice">
  By running this test, you agree that we collect your IP address, ISP, location, 
  and test results for service improvement. <a href="privacy.html">Privacy Policy</a>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ACTION BUTTONS
     ══════════════════════════════════════════════════════════════ -->
<div class="btn-group">
  <button id="btn">Start Speed Test</button>
  <button id="share-btn" onclick="shareResults()">Share Link</button>
</div>

<!-- ══════════════════════════════════════════════════════════════
     LOCAL HISTORY
     ══════════════════════════════════════════════════════════════ -->
<div class="hist-wrap" id="hist-list"></div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: LOGIN
     ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-login">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-login')">✕</button>
    <h2>Welcome back</h2>
    <p class="sub">Log in to view your full speed test history</p>
    <div class="form-group">
      <label>Email or Phone Number</label>
      <input type="text" id="login-contact" placeholder="you@example.com or +357 99 000000"/>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" id="login-pass" placeholder="••••••••"/>
    </div>
    
    <!-- TURNSTILE WIDGET -->
    <div class="turnstile-widget" id="login-turnstile-container"></div>
    
    <button class="form-submit" id="login-submit" onclick="submitLogin()" disabled>Log In</button>
    <div class="modal-msg" id="login-msg"></div>
    
    <div style="text-align:center;margin-top:10px;">
      <a onclick="switchModal('modal-login','modal-forgot')" 
         style="color:#6366f1;font-size:0.7rem;cursor:pointer;text-decoration:none;">
        Forgot Password?
      </a>
    </div>
    
    <div class="modal-switch">
      Don't have an account? 
      <a onclick="switchModal('modal-login','modal-register')">Sign up</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: REGISTER STEP 1
     ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-register">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-register')">✕</button>
    <h2>Create Account</h2>
    <p class="sub">Verify your contact to get started</p>
    <div class="type-toggle">
      <button id="tog-email" class="active" onclick="setOtpType('email')">📧 Email</button>
      <button id="tog-sms" onclick="setOtpType('sms')">📱 SMS</button>
    </div>
    <div class="form-group">
      <label id="contact-label">Email Address</label>
      <input type="email" id="reg-contact" placeholder="you@example.com"/>
    </div>
    
    <!-- TURNSTILE WIDGET -->
    <div class="turnstile-widget" id="register-turnstile-container"></div>
    
    <button class="form-submit" id="reg-send-btn" onclick="sendOtp()" disabled>
      Send Verification Code
    </button>
    <div class="modal-msg" id="reg-msg"></div>
    <div class="modal-switch">
      Already have an account? 
      <a onclick="switchModal('modal-register','modal-login')">Log in</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: REGISTER STEP 2
     ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-verify">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-verify')">✕</button>
    <h2>Verify & Set Password</h2>
    <p class="sub">
      Enter the code sent to 
      <span id="verify-contact-display" style="color:#6366f1"></span>
    </p>
    <div class="otp-note">⏱ Code expires in 10 minutes</div>
    <div class="form-group">
      <label>Verification Code</label>
      <input type="text" id="otp-input" placeholder="6-digit code" maxlength="6"/>
    </div>
    <div class="form-group">
      <label>Create Password</label>
      <input type="password" id="reg-pass" placeholder="Min. 8 characters"/>
    </div>
    <button class="form-submit" id="verify-submit" onclick="submitVerify()">
      Create Account
    </button>
    <div class="modal-msg" id="verify-msg"></div>
    <div class="modal-switch">
      <a onclick="switchModal('modal-verify','modal-register')">← Change contact</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: FORGOT PASSWORD
     ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-forgot">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-forgot')">✕</button>
    <h2>Reset Password</h2>
    <p class="sub">Enter your contact info to receive a reset code</p>
    <div class="type-toggle">
      <button id="forgot-tog-email" class="active" onclick="setForgotType('email')">
        📧 Email
      </button>
      <button id="forgot-tog-sms" onclick="setForgotType('sms')">
        📱 SMS
      </button>
    </div>
    <div class="form-group">
      <label id="forgot-contact-label">Email Address</label>
      <input type="email" id="forgot-contact" placeholder="you@example.com"/>
    </div>
    
    <!-- TURNSTILE WIDGET -->
    <div class="turnstile-widget" id="forgot-turnstile-container"></div>
    
    <button class="form-submit" id="forgot-submit" onclick="submitForgotPassword()" disabled>
      Send Reset Code
    </button>
    <div class="modal-msg" id="forgot-msg"></div>
    <div class="modal-switch">
      <a onclick="switchModal('modal-forgot','modal-login')">← Back to login</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: RESET PASSWORD
     ══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-reset">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-reset')">✕</button>
    <h2>Enter Reset Code</h2>
    <p class="sub">
      Code sent to 
      <span id="reset-contact-display" style="color:#6366f1"></span>
    </p>
    <div class="otp-note">⏱ Code expires in 10 minutes</div>
    <div class="form-group">
      <label>Reset Code</label>
      <input type="text" id="reset-otp" placeholder="6-digit code" maxlength="6"/>
    </div>
    <div class="form-group">
      <label>New Password</label>
      <input type="password" id="reset-pass" placeholder="Min. 8 characters"/>
    </div>
    <button class="form-submit" id="reset-submit" onclick="submitResetPassword()">
      Reset Password
    </button>
    <div class="modal-msg" id="reset-msg"></div>
    <div class="modal-switch">
      <a onclick="switchModal('modal-reset','modal-forgot')">← Request new code</a>
    </div>
  </div>
</div>

<script>
// ══════════════════════════════════════════════════════════════
// GLOBAL VARIABLES
// ══════════════════════════════════════════════════════════════
const TURNSTILE_SITE_KEY = '<?= TURNSTILE_SITE_KEY ?>';
let currentUser = <?= $isLoggedIn ? '"' . htmlspecialchars($userContact, ENT_QUOTES) . '"' : 'null' ?>;
let otpType = 'email';
let forgotType = 'email';

// Turnstile state
let loginTurnstileToken = null;
let registerTurnstileToken = null;
let forgotTurnstileToken = null;
let loginWidgetId = null;
let registerWidgetId = null;
let forgotWidgetId = null;

// ══════════════════════════════════════════════════════════════
// TURNSTILE INITIALIZATION
// ══════════════════════════════════════════════════════════════
window.onTurnstileLoad = function() {
  console.log('✅ Turnstile script loaded');
};

function renderTurnstileWidget(containerId, callbackName) {
  const container = document.getElementById(containerId);
  if (!container) {
    console.error('Container not found:', containerId);
    return null;
  }
  
  if (typeof turnstile === 'undefined') {
    console.error('Turnstile not loaded yet');
    return null;
  }
  
  try {
    const widgetId = turnstile.render(container, {
      sitekey: TURNSTILE_SITE_KEY,
      theme: 'dark',
      callback: window[callbackName]
    });
    console.log(`✅ Rendered ${containerId} widget:`, widgetId);
    return widgetId;
  } catch (e) {
    console.error(`❌ Failed to render ${containerId}:`, e);
    return null;
  }
}

// Turnstile success callbacks
window.onLoginTurnstileSuccess = function(token) {
  console.log('✅ Login Turnstile verified');
  loginTurnstileToken = token;
  document.getElementById('login-submit').disabled = false;
};

window.onRegisterTurnstileSuccess = function(token) {
  console.log('✅ Register Turnstile verified');
  registerTurnstileToken = token;
  document.getElementById('reg-send-btn').disabled = false;
};

window.onForgotTurnstileSuccess = function(token) {
  console.log('✅ Forgot Turnstile verified');
  forgotTurnstileToken = token;
  document.getElementById('forgot-submit').disabled = false;
};

// ══════════════════════════════════════════════════════════════
// MODAL MANAGEMENT
// ══════════════════════════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add('active');
  
  // Render Turnstile widget when modal opens
  setTimeout(() => {
    if (typeof turnstile !== 'undefined') {
      if (id === 'modal-login' && !loginWidgetId) {
        loginWidgetId = renderTurnstileWidget('login-turnstile-container', 'onLoginTurnstileSuccess');
      } else if (id === 'modal-register' && !registerWidgetId) {
        registerWidgetId = renderTurnstileWidget('register-turnstile-container', 'onRegisterTurnstileSuccess');
      } else if (id === 'modal-forgot' && !forgotWidgetId) {
        forgotWidgetId = renderTurnstileWidget('forgot-turnstile-container', 'onForgotTurnstileSuccess');
      }
    }
  }, 100);
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
  clearMessages();
  
  // Reset Turnstile widgets
  if (typeof turnstile !== 'undefined') {
    try {
      if (id === 'modal-login' && loginWidgetId !== null) {
        turnstile.reset(loginWidgetId);
        loginTurnstileToken = null;
        document.getElementById('login-submit').disabled = true;
      } else if (id === 'modal-register' && registerWidgetId !== null) {
        turnstile.reset(registerWidgetId);
        registerTurnstileToken = null;
        document.getElementById('reg-send-btn').disabled = true;
      } else if (id === 'modal-forgot' && forgotWidgetId !== null) {
        turnstile.reset(forgotWidgetId);
        forgotTurnstileToken = null;
        document.getElementById('forgot-submit').disabled = true;
      }
    } catch(e) {
      console.error('Turnstile reset error:', e);
    }
  }
}

function switchModal(from, to) {
  closeModal(from);
  setTimeout(() => openModal(to), 150);
}

function clearMessages() {
  ['login-msg', 'reg-msg', 'verify-msg', 'forgot-msg', 'reset-msg'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = '';
      el.className = 'modal-msg';
    }
  });
}

function setMsg(id, text, type) {
  const el = document.getElementById(id);
  el.textContent = text;
  el.className = 'modal-msg ' + type;
}

// ══════════════════════════════════════════════════════════════
// SIGNUP OTP TYPE TOGGLE
// ══════════════════════════════════════════════════════════════
function setOtpType(type) {
  otpType = type;
  document.getElementById('tog-email').classList.toggle('active', type === 'email');
  document.getElementById('tog-sms').classList.toggle('active', type === 'sms');
  
  const input = document.getElementById('reg-contact');
  const label = document.getElementById('contact-label');
  
  if (type === 'email') {
    input.type = 'email';
    input.placeholder = 'you@example.com';
    label.textContent = 'Email Address';
  } else {
    input.type = 'tel';
    input.placeholder = '+357 99 000000';
    label.textContent = 'Phone Number';
  }
}

// ══════════════════════════════════════════════════════════════
// SEND OTP (REGISTRATION)
// ══════════════════════════════════════════════════════════════
async function sendOtp() {
  const contact = document.getElementById('reg-contact').value.trim();
  
  if (!contact) {
    setMsg('reg-msg', 'Please enter your ' + (otpType === 'email' ? 'email' : 'phone number'), 'error');
    return;
  }
  
  if (!registerTurnstileToken) {
    setMsg('reg-msg', 'Please complete the security check', 'error');
    return;
  }
  
  const btn = document.getElementById('reg-send-btn');
  btn.disabled = true;
  btn.textContent = 'Sending...';
  
  try {
    const r = await fetch('sent_otp.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        contact, 
        type: otpType,
        turnstile_token: registerTurnstileToken
      })
    });
    const d = await r.json();
    
    if (d.status === 'success') {
      document.getElementById('verify-contact-display').textContent = contact;
      switchModal('modal-register', 'modal-verify');
    } else {
      setMsg('reg-msg', d.message || 'Failed to send code', 'error');
      if (typeof turnstile !== 'undefined' && registerWidgetId !== null) {
        turnstile.reset(registerWidgetId);
        registerTurnstileToken = null;
        btn.disabled = true;
      }
    }
  } catch (e) {
    setMsg('reg-msg', 'Network error. Please try again.', 'error');
    if (typeof turnstile !== 'undefined' && registerWidgetId !== null) {
      turnstile.reset(registerWidgetId);
      registerTurnstileToken = null;
      btn.disabled = true;
    }
  }
  
  btn.textContent = 'Send Verification Code';
}

// ══════════════════════════════════════════════════════════════
// VERIFY OTP (REGISTRATION)
// ══════════════════════════════════════════════════════════════
async function submitVerify() {
  const contact = document.getElementById('reg-contact').value.trim();
  const otp = document.getElementById('otp-input').value.trim();
  const pass = document.getElementById('reg-pass').value;
  
  if (!otp || otp.length !== 6) {
    setMsg('verify-msg', 'Enter the 6-digit code', 'error');
    return;
  }
  if (!pass || pass.length < 8) {
    setMsg('verify-msg', 'Password must be at least 8 characters', 'error');
    return;
  }
  
  const btn = document.getElementById('verify-submit');
  btn.disabled = true;
  btn.textContent = 'Verifying...';
  
  try {
    const r = await fetch('verify_otp.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ contact, otp, password: pass })
    });
    const d = await r.json();
    
    if (d.status === 'success') {
      setMsg('verify-msg', '✅ Account created! Redirecting...', 'success');
      setTimeout(() => { window.location.reload(); }, 1500);
    } else {
      setMsg('verify-msg', d.message || 'Verification failed', 'error');
    }
  } catch (e) {
    setMsg('verify-msg', 'Network error. Please try again.', 'error');
  }
  
  btn.disabled = false;
  btn.textContent = 'Create Account';
}

// ══════════════════════════════════════════════════════════════
// LOGIN
// ══════════════════════════════════════════════════════════════
let lockoutInterval = null;

async function submitLogin() {
  const contact = document.getElementById('login-contact').value.trim();
  const pass = document.getElementById('login-pass').value;
  
  if (!contact || !pass) {
    setMsg('login-msg', 'Please fill in all fields', 'error');
    return;
  }
  
  if (!loginTurnstileToken) {
    setMsg('login-msg', 'Please complete the security check', 'error');
    return;
  }
  
  const btn = document.getElementById('login-submit');
  btn.disabled = true;
  btn.textContent = 'Logging in...';
  
  try {
    const r = await fetch('login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        contact, 
        password: pass,
        turnstile_token: loginTurnstileToken
      })
    });
    const d = await r.json();
    
    if (d.status === 'success') {
      setMsg('login-msg', '✅ Logged in! Redirecting...', 'success');
      setTimeout(() => { window.location.reload(); }, 1000);
      
    } else if (d.status === 'locked') {
      startLockoutTimer(d.seconds_left, d.message);
      btn.disabled = true;
      
    } else {
      setMsg('login-msg', d.message || 'Login failed', 'error');
      if (typeof turnstile !== 'undefined' && loginWidgetId !== null) {
        turnstile.reset(loginWidgetId);
        loginTurnstileToken = null;
        btn.disabled = true;
      }
    }
  } catch (e) {
    setMsg('login-msg', 'Network error. Please try again.', 'error');
    if (typeof turnstile !== 'undefined' && loginWidgetId !== null) {
      turnstile.reset(loginWidgetId);
      loginTurnstileToken = null;
      btn.disabled = true;
    }
  }
}

function startLockoutTimer(seconds, message) {
  if (lockoutInterval) clearInterval(lockoutInterval);
  
  const btn = document.getElementById('login-submit');
  const msgEl = document.getElementById('login-msg');
  
  function updateDisplay(secs) {
    const mins = Math.floor(secs / 60);
    const s = secs % 60;
    const timeStr = mins + ':' + String(s).padStart(2, '0');
    msgEl.innerHTML = `
      <div class="lockout-msg">${message}</div>
      <div class="lockout-timer">${timeStr}</div>
    `;
    msgEl.className = 'modal-msg';
  }
  
  updateDisplay(seconds);
  let remaining = seconds;
  
  lockoutInterval = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
      clearInterval(lockoutInterval);
      lockoutInterval = null;
      msgEl.textContent = '';
      msgEl.className = 'modal-msg';
      if (typeof turnstile !== 'undefined' && loginWidgetId !== null) {
        turnstile.reset(loginWidgetId);
        loginTurnstileToken = null;
      }
    } else {
      updateDisplay(remaining);
    }
  }, 1000);
}

// ══════════════════════════════════════════════════════════════
// FORGOT PASSWORD TYPE TOGGLE
// ══════════════════════════════════════════════════════════════
function setForgotType(type) {
  forgotType = type;
  document.getElementById('forgot-tog-email').classList.toggle('active', type === 'email');
  document.getElementById('forgot-tog-sms').classList.toggle('active', type === 'sms');
  
  const input = document.getElementById('forgot-contact');
  const label = document.getElementById('forgot-contact-label');
  
  if (type === 'email') {
    input.type = 'email';
    input.placeholder = 'you@example.com';
    label.textContent = 'Email Address';
  } else {
    input.type = 'tel';
    input.placeholder = '+357 99 000000';
    label.textContent = 'Phone Number';
  }
}

// ══════════════════════════════════════════════════════════════
// SUBMIT FORGOT PASSWORD
// ══════════════════════════════════════════════════════════════
async function submitForgotPassword() {
  const contact = document.getElementById('forgot-contact').value.trim();
  
  if (!contact) {
    setMsg('forgot-msg', 'Please enter your ' + (forgotType === 'email' ? 'email' : 'phone number'), 'error');
    return;
  }
  
  if (!forgotTurnstileToken) {
    setMsg('forgot-msg', 'Please complete the security check', 'error');
    return;
  }
  
  const btn = document.getElementById('forgot-submit');
  btn.disabled = true;
  btn.textContent = 'Sending...';
  
  try {
    const r = await fetch('forgot_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        contact, 
        type: forgotType,
        turnstile_token: forgotTurnstileToken
      })
    });
    const d = await r.json();
    
    if (d.status === 'success') {
      document.getElementById('reset-contact-display').textContent = contact;
      switchModal('modal-forgot', 'modal-reset');
    } else {
      setMsg('forgot-msg', d.message || 'Failed to send reset code', 'error');
      if (typeof turnstile !== 'undefined' && forgotWidgetId !== null) {
        turnstile.reset(forgotWidgetId);
        forgotTurnstileToken = null;
        btn.disabled = true;
      }
    }
  } catch (e) {
    setMsg('forgot-msg', 'Network error. Please try again.', 'error');
    if (typeof turnstile !== 'undefined' && forgotWidgetId !== null) {
      turnstile.reset(forgotWidgetId);
      forgotTurnstileToken = null;
      btn.disabled = true;
    }
  }
  
  btn.textContent = 'Send Reset Code';
}

// ══════════════════════════════════════════════════════════════
// SUBMIT RESET PASSWORD
// ══════════════════════════════════════════════════════════════
async function submitResetPassword() {
  const contact = document.getElementById('forgot-contact').value.trim();
  const otp = document.getElementById('reset-otp').value.trim();
  const pass = document.getElementById('reset-pass').value;
  
  if (!otp || otp.length !== 6) {
    setMsg('reset-msg', 'Enter the 6-digit code', 'error');
    return;
  }
  if (!pass || pass.length < 8) {
    setMsg('reset-msg', 'Password must be at least 8 characters', 'error');
    return;
  }
  
  const btn = document.getElementById('reset-submit');
  btn.disabled = true;
  btn.textContent = 'Resetting...';
  
  try {
    const r = await fetch('reset_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ contact, otp, password: pass })
    });
    const d = await r.json();
    
    if (d.status === 'success') {
      setMsg('reset-msg', '✅ Password reset! Logging you in...', 'success');
      setTimeout(() => { window.location.reload(); }, 1500);
    } else {
      setMsg('reset-msg', d.message || 'Reset failed', 'error');
    }
  } catch (e) {
    setMsg('reset-msg', 'Network error. Please try again.', 'error');
  }
  
  btn.disabled = false;
  btn.textContent = 'Reset Password';
}

// ══════════════════════════════════════════════════════════════
// LOGOUT
// ══════════════════════════════════════════════════════════════
async function doLogout() {
  await fetch('logout.php');
  window.location.reload();
}

// ══════════════════════════════════════════════════════════════
// SPEED TEST ENGINE
// ══════════════════════════════════════════════════════════════
const ARC_LEN = 518.36;
const MAX_SPEED = 1000;
const DL_HOST = 'download.php';
const UL_HOST = 'empty.php';

let totalBytesUsed = 0;
let history = JSON.parse(localStorage.getItem('aura_history') || '[]');
let lastResult = { shareLink: null, testId: null };

const flag = cc => cc ? cc.toUpperCase().replace(/./g, c => 
  String.fromCodePoint(c.charCodeAt(0) + 127397)
) : '🌍';

function updateGauge(val, color) {
  const offset = ARC_LEN * (1 - Math.min(val / MAX_SPEED, 1));
  document.getElementById('arc').style.strokeDashoffset = offset;
  if (color) document.getElementById('arc').setAttribute('stroke', color);
  document.getElementById('val').textContent = val > 100 ? Math.round(val) : val.toFixed(1);
}

function hideGaugeShowResults() {
  document.getElementById('gauge-wrap').classList.add('hidden');
  document.getElementById('cards-wrap').classList.add('expanded');
}

function showGaugeResetResults() {
  document.getElementById('gauge-wrap').classList.remove('hidden');
  document.getElementById('cards-wrap').classList.remove('expanded');
}

function showTestId(testId) {
  document.getElementById('test-id-value').textContent = testId;
  document.getElementById('test-id-box').style.display = 'block';
}

function hideTestId() {
  document.getElementById('test-id-box').style.display = 'none';
}

function fetchWithTimeout(url, timeout = 5000) {
  return Promise.race([
    fetch(url),
    new Promise((_, reject) => setTimeout(() => reject(new Error('Timeout')), timeout))
  ]);
}

async function detectBrave() {
  if (navigator.brave && await navigator.brave.isBrave()) {
    document.getElementById('brave-notice').style.display = 'block';
  }
}

async function fetchInfo() {
  try {
    const r = await fetchWithTimeout('https://www.cloudflare.com/cdn-cgi/trace', 3000);
    const t = await r.text();
    const d = Object.fromEntries(t.split('\n').filter(v => v).map(v => v.split('=')));
    
    document.getElementById('v-ip').textContent = d.ip || 'Unknown';
    document.getElementById('v-co').textContent = d.loc ? flag(d.loc) + ' ' + d.loc : 'Unknown';
    
    let ispFound = false;
    
    try {
      const r2 = await fetchWithTimeout('https://ipapi.co/json/', 5000);
      const ipD = await r2.json();
      if (ipD && ipD.org) {
        document.getElementById('v-isp').textContent = ipD.org;
        ispFound = true;
      }
    } catch (e) {}
    
    if (!ispFound) {
      try {
        const r3 = await fetchWithTimeout('https://ip-api.com/json/', 5000);
        const ipD2 = await r3.json();
        if (ipD2 && ipD2.isp) {
          document.getElementById('v-isp').textContent = ipD2.isp;
          ispFound = true;
        }
      } catch (e) {}
    }
    
    if (!ispFound) {
      document.getElementById('v-isp').textContent = 'Unknown ISP';
    }
  } catch (e) {
    document.getElementById('v-ip').textContent = 'Error';
    document.getElementById('v-co').textContent = 'Error';
    document.getElementById('v-isp').textContent = 'Error';
  }
}

async function measurePing() {
  let pings = [];
  for (let i = 0; i < 6; i++) {
    const t0 = performance.now();
    try {
      await fetch(DL_HOST + '?size=0.01&t=' + t0, { cache: 'no-store' });
      if (i > 0) pings.push(performance.now() - t0);
    } catch (e) {
      if (i > 0) pings.push(performance.now() - t0);
    }
  }
  return pings.length > 0 ? Math.min(...pings) : 0;
}

async function measureDownload(cb) {
  const duration = 8000;
  const start = performance.now();
  let downloaded = 0;
  let stop = false;
  let lastUpdate = performance.now();
  
  setTimeout(() => stop = true, duration);
  
  async function worker() {
    while (!stop) {
      try {
        const r = await fetch(DL_HOST + '?size=5&p=' + Math.random(), { cache: 'no-store' });
        const reader = r.body.getReader();
        
        while (true) {
          const { done, value } = await reader.read();
          if (done || stop) break;
          
          downloaded += value.length;
          totalBytesUsed += value.length;
          
          const now = performance.now();
          if (now - lastUpdate > 200) {
            document.getElementById('mbv').textContent = (totalBytesUsed / 1048576).toFixed(2);
            const elapsed = (now - start) / 1000;
            if (elapsed > 0.5) {
              cb((downloaded * 8) / (elapsed * 1000000));
            }
            lastUpdate = now;
          }
        }
      } catch (e) {
        break;
      }
    }
  }
  
  await Promise.all([worker(), worker(), worker(), worker()]);
  const elapsed = (performance.now() - start) / 1000;
  return (downloaded * 8) / (elapsed * 1000000);
}

async function measureUpload(cb) {
  const duration = 8000;
  const start = performance.now();
  const payload = new Uint8Array(1024 * 1024 * 2);
  let uploaded = 0;
  let stop = false;
  let lastUpdate = performance.now();
  let speedSamples = [];
  
  setTimeout(() => stop = true, duration);
  
  async function worker() {
    while (!stop) {
      try {
        await new Promise((res) => {
          const xhr = new XMLHttpRequest();
          xhr.open("POST", UL_HOST, true);
          let last = 0;
          
          xhr.upload.onprogress = e => {
            const chunk = e.loaded - last;
            last = e.loaded;
            uploaded += chunk;
            totalBytesUsed += chunk;
            
            const now = performance.now();
            if (now - lastUpdate > 200) {
              document.getElementById('mbv').textContent = (totalBytesUsed / 1048576).toFixed(2);
              const elapsed = (now - start) / 1000;
              if (elapsed > 0.5) {
                const s = (uploaded * 8) / (elapsed * 1000000);
                speedSamples.push(s);
                if (speedSamples.length > 5) speedSamples.shift();
                cb(speedSamples.reduce((a, b) => a + b, 0) / speedSamples.length);
              }
              lastUpdate = now;
            }
          };
          
          xhr.onload = () => res();
          xhr.onerror = () => res();
          xhr.ontimeout = () => res();
          xhr.send(payload);
        });
      } catch (e) {
        break;
      }
    }
  }
  
  await Promise.all([worker(), worker(), worker(), worker(), worker(), worker()]);
  const elapsed = (performance.now() - start) / 1000;
  return (uploaded * 8) / (elapsed * 1000000);
}

async function runTest() {
  const b = document.getElementById('btn');
  const sBtn = document.getElementById('share-btn');
  
  b.disabled = true;
  b.textContent = "Running...";
  sBtn.style.display = "none";
  totalBytesUsed = 0;
  
  showGaugeResetResults();
  hideTestId();
  
  document.getElementById('rd').textContent = '–';
  document.getElementById('ru').textContent = '–';
  document.getElementById('rp').textContent = '–';
  
  updateGauge(0, "url(#gDL)");
  
  let dlSpeed = 0;
  let ulSpeed = 0;
  
  try {
    document.getElementById('phase').textContent = "PING";
    const ping = await measurePing();
    document.getElementById('rp').textContent = Math.round(ping);
    
    document.getElementById('phase').textContent = "DOWNLOAD";
    dlSpeed = await measureDownload(s => {
      updateGauge(s, "url(#gDL)");
      document.getElementById('rd').textContent = s > 100 ? Math.round(s) : s.toFixed(1);
    });
    document.getElementById('rd').textContent = dlSpeed > 100 ? Math.round(dlSpeed) : dlSpeed.toFixed(1);
    
    document.getElementById('phase').textContent = "UPLOAD";
    ulSpeed = await measureUpload(s => {
      updateGauge(s, "url(#gUL)");
      document.getElementById('ru').textContent = s > 100 ? Math.round(s) : s.toFixed(1);
    });
    document.getElementById('ru').textContent = ulSpeed > 100 ? Math.round(ulSpeed) : ulSpeed.toFixed(1);
    
    document.getElementById('phase').textContent = "SAVING...";
    
    try {
      const saveResponse = await fetch('save_result.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          isp: document.getElementById('v-isp').textContent,
          dl: dlSpeed.toFixed(1),
          ul: ulSpeed.toFixed(1),
          ping: Math.round(ping)
        })
      });
      
      const serverResult = await saveResponse.json();
      
      if (serverResult.status === "success") {
        lastResult.shareLink = serverResult.share_url;
        lastResult.testId = serverResult.test_id;
        sBtn.style.display = "block";
        showTestId(serverResult.test_id);
      }
    } catch (saveError) {
      console.error('Save error:', saveError);
    }
    
    hideGaugeShowResults();
    
    history.unshift({
      time: new Date().toLocaleTimeString(),
      dl: dlSpeed.toFixed(1),
      ul: ulSpeed.toFixed(1)
    });
    history = history.slice(0, 5);
    localStorage.setItem('aura_history', JSON.stringify(history));
    renderHistory();
    
  } catch (e) {
    console.error('Test error:', e);
    document.getElementById('phase').textContent = "ERROR";
  } finally {
    b.disabled = false;
    b.textContent = "Start Speed Test";
  }
}

function shareResults() {
  if (!lastResult.shareLink) return;
  
  navigator.clipboard.writeText(lastResult.shareLink).then(() => {
    const sBtn = document.getElementById('share-btn');
    sBtn.textContent = "Link Copied!";
    setTimeout(() => sBtn.textContent = "Share Link", 2000);
  }).catch(() => {
    alert('Share link: ' + lastResult.shareLink);
  });
}

function renderHistory() {
  document.getElementById('hist-list').innerHTML = history.map(h => `
    <div class="hist-item">
      <span>${h.time}</span>
      <span style="color:#6366f1">↓ ${h.dl}</span>
      <span style="color:#ec4899">↑ ${h.ul}</span>
    </div>
  `).join('');
}

// ══════════════════════════════════════════════════════════════
// EVENT LISTENERS
// ══════════════════════════════════════════════════════════════
document.getElementById('btn').addEventListener('click', runTest);

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) {
      overlay.classList.remove('active');
    }
  });
});

// ══════════════════════════════════════════════════════════════
// INITIALIZATION
// ══════════════════════════════════════════════════════════════
detectBrave();
fetchInfo();
renderHistory();
</script>
</body>
</html>