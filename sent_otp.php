<?php
// ══════════════════════════════════════════════════════════════
// sent_otp.php — Generates and sends a 6-digit OTP
// Called via AJAX from index.html registration form
// Supports: Email (PHP mail) and SMS (ClickSend API)
// ══════════════════════════════════════════════════════════════
header("Content-Type: application/json");
require_once 'config.php';

// ── DATABASE CONNECTION ───────────────────────────────────────
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Server error. Please try again."]);
    exit;
}

// ── READ JSON INPUT ───────────────────────────────────────────
$data    = json_decode(file_get_contents('php://input'), true);
$contact = trim($data['contact'] ?? '');
$type    = $data['type'] ?? 'email'; // 'email' or 'sms'

// ══════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ══════════════════════════════════════════════════════════════
if (empty($contact)) {
    echo json_encode(["status" => "error", "message" => "Contact information is required"]);
    exit;
}

if ($type === 'email') {
    if (!isValidEmail($contact)) {
        echo json_encode(["status" => "error", "message" => "Invalid email address"]);
        exit;
    }
} elseif ($type === 'sms') {
    $contact = cleanPhone($contact);
    if (!isValidPhone($contact)) {
        echo json_encode(["status" => "error", "message" => "Invalid phone number. Use format: +357XXXXXXXX"]);
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid contact type"]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// RATE LIMITING — Max 3 OTP requests per hour per contact
// ══════════════════════════════════════════════════════════════
$oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
$stmt_rate  = $conn->prepare("SELECT COUNT(*) as count FROM otp_verifications 
                               WHERE contact = ? AND created_at > ?");
$stmt_rate->bind_param("ss", $contact, $oneHourAgo);
$stmt_rate->execute();
$rateCount = $stmt_rate->get_result()->fetch_assoc()['count'];
$stmt_rate->close();

if ($rateCount >= 3) {
    echo json_encode(["status" => "error", "message" => "Too many OTP requests. Please try again in 1 hour."]);
    $conn->close();
    exit;
}

// ══════════════════════════════════════════════════════════════
// CHECK IF CONTACT ALREADY REGISTERED
// ══════════════════════════════════════════════════════════════
$stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt_check->bind_param("ss", $contact, $contact);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Already registered. Please log in instead."]);
    $stmt_check->close();
    $conn->close();
    exit;
}
$stmt_check->close();

// ══════════════════════════════════════════════════════════════
// DELETE OLD OTPs FOR THIS CONTACT (keep table clean)
// ══════════════════════════════════════════════════════════════
$stmt_del = $conn->prepare("DELETE FROM otp_verifications WHERE contact = ?");
$stmt_del->bind_param("s", $contact);
$stmt_del->execute();
$stmt_del->close();

// ══════════════════════════════════════════════════════════════
// GENERATE & STORE NEW OTP
// ══════════════════════════════════════════════════════════════
$otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

$stmt_insert = $conn->prepare("INSERT INTO otp_verifications (contact, otp_code, expires_at, created_at) 
                                VALUES (?, ?, ?, NOW())");
$stmt_insert->bind_param("sss", $contact, $otp, $expires);
$stmt_insert->execute();
$stmt_insert->close();

// ══════════════════════════════════════════════════════════════
// SEND OTP VIA SMS (ClickSend API)
// ══════════════════════════════════════════════════════════════
if ($type === 'sms') {

    $payload = json_encode([
        "messages" => [[
            "source" => "php",
            "from"   => OTP_SMS_SENDER,
            "body"   => "Your " . SITE_NAME . " code is: $otp. Valid for " . OTP_EXPIRY_MINUTES . " minutes. Do not share this.",
            "to"     => $contact
        ]]
    ]);

    $ch = curl_init("https://rest.clicksend.com/v3/sms/send");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(CLICKSEND_USERNAME . ':' . CLICKSEND_API_KEY)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("ClickSend HTTP error: $httpCode — $response");
        echo json_encode(["status" => "error", "message" => "Failed to send SMS. Please try again."]);
        $conn->close();
        exit;
    }

    $result = json_decode($response, true);
    if (!isset($result['response_code']) || $result['response_code'] !== 'SUCCESS') {
        error_log("ClickSend error: " . json_encode($result));
        echo json_encode(["status" => "error", "message" => "Failed to send SMS. Please check your number."]);
        $conn->close();
        exit;
    }

// ══════════════════════════════════════════════════════════════
// SEND OTP VIA EMAIL (PHP mail)
// ══════════════════════════════════════════════════════════════
} else {

    $subject = "Your " . SITE_NAME . " Verification Code";
    $message = "
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family:-apple-system,sans-serif;background:#0f0f1a;color:#e2e8f0;padding:30px;margin:0'>
      <div style='max-width:500px;margin:0 auto;background:#0c0c1c;border:1px solid #1e1e3a;border-radius:16px;padding:40px;text-align:center'>
        <h2 style='margin:0 0 10px'>
          <span style='background:linear-gradient(90deg,#6366f1,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent'>HostingAura</span>
        </h2>
        <p style='color:#94a3b8;margin:0 0 30px'>Your verification code is:</p>
        <h1 style='letter-spacing:10px;color:#6366f1;font-size:3rem;margin:20px 0;font-weight:700'>$otp</h1>
        <p style='color:#64748b;font-size:0.85rem'>Expires in <strong style='color:#94a3b8'>" . OTP_EXPIRY_MINUTES . " minutes</strong>.</p>
        <p style='color:#475569;font-size:0.8rem;margin-top:10px'>If you didn't request this, ignore this email.</p>
        <hr style='border:none;border-top:1px solid #1e1e3a;margin:30px 0'>
        <p style='font-size:0.75rem;color:#334155'>— The HostingAura Team</p>
      </div>
    </body>
    </html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . OTP_FROM_NAME . " <" . OTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . OTP_FROM_EMAIL . "\r\n";

    if (!mail($contact, $subject, $message, $headers)) {
        error_log("Failed to send OTP email to: $contact");
        echo json_encode(["status" => "error", "message" => "Failed to send email. Please try again."]);
        $conn->close();
        exit;
    }
}

// ══════════════════════════════════════════════════════════════
// SUCCESS
// ══════════════════════════════════════════════════════════════
error_log("OTP sent to $contact via $type");
echo json_encode([
    "status"  => "success",
    "message" => "OTP sent to your " . ($type === 'sms' ? 'phone' : 'email')
]);

$conn->close();
?>