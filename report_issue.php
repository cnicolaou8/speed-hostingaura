<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/vhosts/hostingaura.com/speed.hostingaura.com/report_debug.log');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

// ── INPUTS ────────────────────────────────────────────────────
$testId          = isset($body['test_id'])       ? trim($body['test_id'])       : '';
$issue           = isset($body['description'])   ? trim($body['description'])   : '';
$category        = isset($body['category'])      ? trim($body['category'])      : 'other';
$reporterContact = isset($body['contact'])       ? trim($body['contact'])       : '';
$wantsContact    = isset($body['wants_contact']) ? (bool)$body['wants_contact'] : false;
$dl              = isset($body['dl'])            ? $body['dl']                  : 0;
$ul              = isset($body['ul'])            ? $body['ul']                  : 0;
$ping            = isset($body['ping'])          ? $body['ping']                : 0;
$isp             = isset($body['isp'])           ? trim($body['isp'])           : '';
$device          = isset($body['device'])        ? trim($body['device'])        : '';

// ── VALIDATION ────────────────────────────────────────────────
if (empty($issue)) {
    die(json_encode(['status' => 'error', 'message' => 'Please describe the issue']));
}

if (mb_strlen($issue) > 1000) {
    die(json_encode(['status' => 'error', 'message' => 'Description too long']));
}

$allowedCategories = ['wrong_speed','test_failed','wrong_location','save_failed','other'];
if (!in_array($category, $allowedCategories)) {
    $category = 'other';
}

// ── PARSE CONTACT TYPE ────────────────────────────────────────
$reporterEmail = '';
$reporterPhone = '';
if (!empty($reporterContact)) {
    if (filter_var($reporterContact, FILTER_VALIDATE_EMAIL)) {
        $reporterEmail = $reporterContact;
    } elseif (preg_match('/^\+?[0-9\s\-]{7,20}$/', $reporterContact)) {
        $reporterPhone = preg_replace('/[\s\-]/', '', $reporterContact);
    }
}

// ── OVERRIDE WITH VERIFIED SESSION CONTACT ────────────────────
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
if ($userId) {
    $c = getDBConnection();
    if ($c) {
        $s = $c->prepare("SELECT email, phone FROM users WHERE id = ?");
        $s->bind_param("i", $userId);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
        $s->close();
        $c->close();
        if (!empty($row['email'])) $reporterEmail = $row['email'];
        if (!empty($row['phone'])) $reporterPhone = $row['phone'];
    }
}

// ── GET IP ADDRESS ────────────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}
$ip = trim($ip);

// ── SAVE TO DB ────────────────────────────────────────────────
$conn = getDBConnection();
if (!$conn) {
    die(json_encode(['status' => 'error', 'message' => 'Database error']));
}

$stmt = $conn->prepare("
    INSERT INTO report_issues
        (test_id, user_id, reporter_email, reporter_phone, category,
         description, wants_contact, dl_speed, ul_speed, ping,
         isp, device, reporter_ip, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    $conn->close();
    die(json_encode(['status' => 'error', 'message' => 'Database prepare failed']));
}

$wci = $wantsContact ? 1 : 0;
$stmt->bind_param(
    'siisssiddiiss',
    $testId, $userId, $reporterEmail, $reporterPhone,
    $category, $issue, $wci,
    $dl, $ul, $ping, $isp, $device, $ip
);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    die(json_encode(['status' => 'error', 'message' => 'Failed to save']));
}

$reportId = $conn->insert_id;
$stmt->close();
$conn->close();

error_log("Report #{$reportId} saved successfully");

// ── LABELS ────────────────────────────────────────────────────
$categoryLabel = [
    'wrong_speed'    => '📉 Speed results seem wrong',
    'test_failed'    => '❌ Test failed / crashed',
    'wrong_location' => '📍 Wrong location or ISP detected',
    'save_failed'    => '💾 Result did not save',
    'other'          => '🔧 Other issue',
][$category] ?? 'Other';

$categoryShort = [
    'wrong_speed'    => 'Wrong speed result',
    'test_failed'    => 'Test failed/crashed',
    'wrong_location' => 'Wrong location/ISP',
    'save_failed'    => 'Result not saved',
    'other'          => 'Other issue',
][$category] ?? 'Other';

$siteUrl    = defined('SITE_URL') ? SITE_URL : 'https://speed.hostingaura.com';
$adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'c.nicolaou8@proton.me';
$adminPhone = defined('ADMIN_PHONE') ? ADMIN_PHONE : '+35796662666';

error_log("Config check - Admin Email: {$adminEmail}, Admin Phone: {$adminPhone}");

// ══════════════════════════════════════════════════════════════
// CLICKSEND SMS FUNCTION
// ══════════════════════════════════════════════════════════════
function sendClickSendSms($to, $message)
{
    error_log("Attempting to send SMS to: {$to}");
    
    if (!defined('CLICKSEND_USERNAME')) {
        error_log("ERROR: CLICKSEND_USERNAME not defined");
        return false;
    }
    if (!defined('CLICKSEND_API_KEY')) {
        error_log("ERROR: CLICKSEND_API_KEY not defined");
        return false;
    }
    if (!defined('CLICKSEND_FROM')) {
        error_log("ERROR: CLICKSEND_FROM not defined");
        return false;
    }
    
    $username = CLICKSEND_USERNAME;
    $apiKey   = CLICKSEND_API_KEY;
    $from     = CLICKSEND_FROM;

    error_log("ClickSend config - Username: {$username}, From: {$from}");

    if (empty($username) || empty($apiKey) || empty($from)) {
        error_log("ERROR: ClickSend credentials are empty");
        return false;
    }
    
    if (strpos($to, '+') !== 0) {
        error_log("ERROR: Phone must start with + (E.164 format): {$to}");
        return false;
    }

    $payload = json_encode([
        'messages' => [[
            'source' => 'php',
            'body'   => $message,
            'to'     => $to,
            'from'   => $from,
        ]]
    ]);

    error_log("ClickSend payload: " . $payload);

    $ch = curl_init('https://rest.clicksend.com/v3/sms/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_USERPWD        => "{$username}:{$apiKey}",
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    error_log("ClickSend response - HTTP {$httpCode}: " . $result);

    if ($curlErr) {
        error_log("ERROR: ClickSend cURL error: {$curlErr}");
        return false;
    }
    
    $resp = json_decode($result, true);
    if ($httpCode === 200 && isset($resp['response_code']) && $resp['response_code'] === 'SUCCESS') {
        error_log("SUCCESS: SMS sent to {$to}");
        return true;
    }
    
    error_log("ERROR: ClickSend failed - HTTP {$httpCode}: " . $result);
    return false;
}

// ══════════════════════════════════════════════════════════════
// 1. ADMIN SMS NOTIFICATION
// ══════════════════════════════════════════════════════════════
$adminSms = "HostingAura Report #{$reportId}\n"
  . "Issue: {$categoryShort}\n"
  . "Test: " . ($testId ?: 'N/A') . "\n"
  . "Speed: ↓{$dl} ↑{$ul} Ping:{$ping}ms\n"
  . "ISP: " . mb_substr($isp, 0, 25) . "\n"
  . "Contact: " . ($reporterEmail ?: $reporterPhone ?: 'Anonymous') . "\n"
  . "Follow-up: " . ($wantsContact ? 'YES' : 'No') . "\n"
  . "Msg: " . mb_substr($issue, 0, 80);

error_log("Sending admin SMS notification");
$smsResult = sendClickSendSms($adminPhone, $adminSms);
error_log("Admin SMS result: " . ($smsResult ? 'SUCCESS' : 'FAILED'));

// ══════════════════════════════════════════════════════════════
// 2. ADMIN EMAIL NOTIFICATION
// ══════════════════════════════════════════════════════════════
error_log("Sending admin email to: {$adminEmail}");

$contactRows = '';
if ($reporterEmail) {
    $contactRows .= "<tr><td style='padding:9px 12px;color:#6b7280'>Reporter Email</td>"
                  . "<td style='padding:9px 12px;color:#111'>" . htmlspecialchars($reporterEmail) . "</td></tr>";
}
if ($reporterPhone) {
    $contactRows .= "<tr style='background:#f9fafb'><td style='padding:9px 12px;color:#6b7280'>Reporter Phone</td>"
                  . "<td style='padding:9px 12px;color:#111'>" . htmlspecialchars($reporterPhone) . "</td></tr>";
}

$adminEmailBody = "<!DOCTYPE html><html><head><meta charset='UTF-8'/></head>
<body style='margin:0;padding:24px;background:#f3f4f6;font-family:Arial,sans-serif'>
<div style='max-width:600px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)'>
  <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:28px 32px'>
    <p style='margin:0 0 4px;color:rgba(255,255,255,.7);font-size:.78rem;letter-spacing:.1em;text-transform:uppercase'>HostingAura Speed Test</p>
    <h1 style='margin:0;color:#fff;font-size:1.45rem'>⚠️ Issue Report #{$reportId}</h1>
  </div>
  <div style='padding:28px 32px'>
    <table style='width:100%;border-collapse:collapse;font-size:.9rem'>
      <tr style='background:#f9fafb'>
        <td style='padding:9px 12px;color:#6b7280;width:160px'>Report ID</td>
        <td style='padding:9px 12px;font-weight:700;color:#111'>#{$reportId}</td>
      </tr>
      <tr>
        <td style='padding:9px 12px;color:#6b7280'>Category</td>
        <td style='padding:9px 12px;font-weight:600;color:#6366f1'>" . htmlspecialchars($categoryLabel) . "</td>
      </tr>
      <tr style='background:#f9fafb'>
        <td style='padding:9px 12px;color:#6b7280'>Test ID</td>
        <td style='padding:9px 12px;font-family:monospace;color:#111'>" . htmlspecialchars($testId ?: '—') . "</td>
      </tr>
      {$contactRows}
      <tr>
        <td style='padding:9px 12px;color:#6b7280'>Wants Contact</td>
        <td style='padding:9px 12px;color:#111'>" . ($wantsContact ? '✅ Yes' : 'No') . "</td>
      </tr>
      <tr style='background:#f9fafb'>
        <td style='padding:9px 12px;color:#6b7280'>Speed</td>
        <td style='padding:9px 12px;color:#111'>↓ <strong>{$dl}</strong> Mbps | ↑ <strong>{$ul}</strong> Mbps | Ping: <strong>{$ping}</strong> ms</td>
      </tr>
      <tr>
        <td style='padding:9px 12px;color:#6b7280'>ISP</td>
        <td style='padding:9px 12px;color:#111'>" . htmlspecialchars($isp ?: '—') . "</td>
      </tr>
      <tr style='background:#f9fafb'>
        <td style='padding:9px 12px;color:#6b7280'>Device</td>
        <td style='padding:9px 12px;color:#111'>" . htmlspecialchars($device ?: '—') . "</td>
      </tr>
      <tr>
        <td style='padding:9px 12px;color:#6b7280'>Reporter IP</td>
        <td style='padding:9px 12px;font-family:monospace;color:#111'>" . htmlspecialchars($ip) . "</td>
      </tr>
      <tr style='background:#f9fafb'>
        <td style='padding:9px 12px;color:#6b7280'>Submitted</td>
        <td style='padding:9px 12px;color:#111'>" . date('d M Y, H:i:s T') . "</td>
      </tr>
    </table>
    <div style='margin-top:24px;padding:18px 20px;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px'>
      <p style='margin:0 0 8px;font-size:.75rem;font-weight:700;color:#92400e;text-transform:uppercase'>Issue Description</p>
      <p style='margin:0;color:#1f2937;font-size:.95rem;line-height:1.65'>" . nl2br(htmlspecialchars($issue)) . "</p>
    </div>
  </div>
  <div style='padding:16px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:.72rem;color:#9ca3af;text-align:center'>
    HostingAura — <a href='{$siteUrl}' style='color:#6366f1'>{$siteUrl}</a>
  </div>
</div>
</body></html>";

$adminHeaders  = "MIME-Version: 1.0\r\n";
$adminHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
$adminHeaders .= "From: HostingAura Reports <noreply@hostingaura.net>\r\n";
if ($reporterEmail) $adminHeaders .= "Reply-To: {$reporterEmail}\r\n";

$emailResult = mail($adminEmail, "[HostingAura] Issue Report #{$reportId} — {$categoryShort}", $adminEmailBody, $adminHeaders);
error_log("Admin email result: " . ($emailResult ? 'SUCCESS' : 'FAILED'));

// ══════════════════════════════════════════════════════════════
// 3. USER CONFIRMATION (Email preferred, SMS fallback)
// ══════════════════════════════════════════════════════════════
$followUpNote = $wantsContact
    ? "You mentioned we can contact you if we need more info — we'll reach out if needed."
    : "No further action is needed from your side.";

if (!empty($reporterEmail)) {
    error_log("Sending confirmation email to user: {$reporterEmail}");
    
    $userEmailBody = "<!DOCTYPE html><html><head><meta charset='UTF-8'/></head>
<body style='margin:0;padding:24px;background:#f3f4f6;font-family:Arial,sans-serif'>
<div style='max-width:520px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)'>
  <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:28px 32px'>
    <p style='margin:0 0 4px;color:rgba(255,255,255,.7);font-size:.78rem;letter-spacing:.1em;text-transform:uppercase'>HostingAura Speed Test</p>
    <h1 style='margin:0;color:#fff;font-size:1.3rem'>Thanks for your report 🙏</h1>
  </div>
  <div style='padding:28px 32px;color:#374151;font-size:.9rem;line-height:1.75'>
    <p>Hi there,</p>
    <p>We've received your issue report and our team is looking into it.</p>
    <div style='background:#f3f4f6;border-radius:10px;padding:16px 20px;margin:20px 0;text-align:center'>
      <p style='margin:0 0 6px;font-size:.72rem;color:#6b7280;text-transform:uppercase'>Your Report Reference</p>
      <p style='margin:0;font-size:1.6rem;font-weight:800;color:#6366f1;font-family:monospace'>#{$reportId}</p>
    </div>
    <div style='background:#fffbeb;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:0 8px 8px 0;margin-bottom:18px'>
      <strong>" . htmlspecialchars($categoryLabel) . "</strong><br>
      <span style='color:#6b7280;font-size:.88rem'>" . htmlspecialchars(mb_substr($issue, 0, 200)) . (mb_strlen($issue) > 200 ? '...' : '') . "</span>
    </div>
    <p>{$followUpNote}</p>
    <p>Thank you for helping us improve HostingAura.</p>
    <p style='margin-top:24px'>— The HostingAura Team</p>
  </div>
  <div style='padding:14px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:.72rem;color:#9ca3af;text-align:center'>
    <a href='{$siteUrl}' style='color:#6366f1'>{$siteUrl}</a>
  </div>
</div>
</body></html>";

    $userHeaders  = "MIME-Version: 1.0\r\n";
    $userHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $userHeaders .= "From: HostingAura <noreply@hostingaura.net>\r\n";
    
    $userEmailResult = mail($reporterEmail, "We received your report — HostingAura #{$reportId}", $userEmailBody, $userHeaders);
    error_log("User email result: " . ($userEmailResult ? 'SUCCESS' : 'FAILED'));

} elseif (!empty($reporterPhone)) {
    error_log("Sending confirmation SMS to user: {$reporterPhone}");
    
    $userSms = "HostingAura: We received your issue report #{$reportId} and our team is on it! "
             . ($wantsContact ? "We may contact you if we need more info." : "Thank you!");
    $userSmsResult = sendClickSendSms($reporterPhone, $userSms);
    error_log("User SMS result: " . ($userSmsResult ? 'SUCCESS' : 'FAILED'));
}

// ══════════════════════════════════════════════════════════════
// SUCCESS RESPONSE
// ══════════════════════════════════════════════════════════════
error_log("Report #{$reportId} completed - returning success response");

die(json_encode([
    'status'    => 'success',
    'message'   => 'Report submitted',
    'report_id' => $reportId,
]));