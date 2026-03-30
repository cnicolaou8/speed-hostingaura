<?php
header("Content-Type: application/json");
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$data = json_decode(file_get_contents('php://input'), true);

$contact = trim($data['contact'] ?? '');
$type    = $data['type'] ?? 'email';

if (!$contact) {
    echo json_encode(["status" => "error", "message" => "Invalid contact"]);
    exit;
}

// ── CHECK IF ALREADY REGISTERED ──
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt->bind_param("ss", $contact, $contact);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "This " . ($type === 'sms' ? "phone number" : "email address") . " is already registered. Please log in instead."
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// ── GENERATE & STORE OTP ──
$otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt2 = $conn->prepare("INSERT INTO otp_verifications (contact, otp_code, expires_at) VALUES (?, ?, ?)");
$stmt2->bind_param("sss", $contact, $otp, $expires);
$stmt2->execute();
$stmt2->close();

// ── SEND VIA CLICKSEND SMS ──
if ($type === 'sms') {
    $payload = json_encode([
        "messages" => [[
            "source" => "php",
            "from"   => OTP_SMS_SENDER,
            "body"   => "Your " . SITE_NAME . " verification code is: $otp. Valid for 10 minutes. Do not share this code.",
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
    curl_close($ch);

    $result = json_decode($response, true);
    if (!isset($result['response_code']) || $result['response_code'] !== 'SUCCESS') {
        echo json_encode(["status" => "error", "message" => "Failed to send SMS. Please check your number and try again."]);
        exit;
    }

// ── SEND VIA PHP MAIL (EMAIL) ──
} else {
    $subject = "Your " . SITE_NAME . " Verification Code";
    $message = "
    <html>
    <body style='font-family:sans-serif;background:#0f0f1a;color:#e2e8f0;padding:30px'>
      <div style='max-width:420px;margin:0 auto;background:#0c0c1c;border:1px solid #1e1e3a;border-radius:16px;padding:30px;text-align:center'>
        <h2 style='background:linear-gradient(90deg,#6366f1,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:1.8rem'>HostingAura</h2>
        <p style='color:#94a3b8'>Your verification code for <strong style='color:#e2e8f0'>" . SITE_NAME . "</strong> is:</p>
        <h1 style='letter-spacing:10px;color:#6366f1;font-size:2.8rem;margin:20px 0'>" . $otp . "</h1>
        <p style='color:#475569;font-size:0.85rem'>This code expires in <strong>10 minutes</strong>.</p>
        <p style='color:#475569;font-size:0.85rem'>If you did not request this, please ignore this email.</p>
        <hr style='border:none;border-top:1px solid #1e1e3a;margin:20px 0'>
        <p style='font-size:0.75rem;color:#334155'>— The HostingAura Team</p>
      </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: HostingAura <" . OTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . OTP_FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $sent = mail($contact, $subject, $message, $headers);

    if (!$sent) {
        echo json_encode(["status" => "error", "message" => "Failed to send email. Please try again."]);
        exit;
    }
}

echo json_encode(["status" => "success", "message" => "OTP sent"]);
$conn->close();
?>