# 🎛️ Admin Dashboard - Complete Deployment Guide

## 🎯 **What You Get:**

A **password-protected admin dashboard** where you can monitor EVERYTHING:

✅ **Total Users** - Count + full details (email, phone, registration date)  
✅ **Speed Tests** - Count + all test results (download, upload, ping, ISP)  
✅ **SMS Sent** - Count + content + cost tracking  
✅ **Email Sent** - Count + subjects + content  
✅ **Export to CSV** - For all data types  
✅ **Beautiful UI** - Matches your site design  

---

## 📥 **Files Created:**

### **1. admin-dashboard.php** ⭐⭐⭐
- Main admin panel
- Password protected
- View all data
- Export functionality

### **2. create-admin-logging-tables.sql**
- Creates `sms_logs` table
- Creates `email_logs` table
- Stores all SMS/email history

---

## 🚀 **Quick Deployment (3 Steps):**

### **STEP 1: Create Database Tables (2 minutes)**

1. **Open phpMyAdmin**
   ```
   https://linux57.name-servers.gr:8443/phpmyadmin
   ```

2. **Select database:** `speed_db`

3. **Click "SQL" tab**

4. **Copy entire contents** of `create-admin-logging-tables.sql`

5. **Paste and click "Go"**

6. **Verify:** You should see 2 new tables:
   - `sms_logs`
   - `email_logs`

**✅ Done! Database ready.**

---

### **STEP 2: Upload Admin Dashboard (1 minute)**

1. **Upload file:** `admin-dashboard.php`

2. **Location:**
   ```
   /var/www/vhosts/hostingaura.com/speed.hostingaura.com/admin-dashboard.php
   ```

3. **Set permissions:** `644`

4. **Access URL:**
   ```
   https://speed.hostingaura.com/admin-dashboard.php
   ```

5. **Default password:** ``

   **⚠️ CHANGE THIS PASSWORD!**
   
   Edit line 15 in `admin-dashboard.php`:
   ```php
   $admin_password = 'YOUR_SECURE_PASSWORD_HERE';
   ```

**✅ Done! Dashboard accessible.**

---

### **STEP 3: Add Logging to Your Existing Code (10 minutes)**

Now you need to update your existing code to **log SMS and emails** to the database.

---

## 📱 **How to Log SMS Messages:**

### **Find Your ClickSend Code:**

Look for where you send SMS (probably in your OTP registration code).

### **Current Code (Before):**

```php
// Example: Your current SMS sending code
$response = $clicksend->sendSMS($phone, $message);
```

### **Updated Code (After):**

```php
// Send SMS via ClickSend
$response = $clicksend->sendSMS($phone, $message);

// LOG TO DATABASE
$db = getDBConnection();
$stmt = $db->prepare("
    INSERT INTO sms_logs 
    (recipient_phone, message_text, message_type, user_id, status, cost, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$message_type = 'otp'; // or 'notification', 'other'
$user_id = $_SESSION['user_id'] ?? null; // if logged in
$status = $response['success'] ? 'sent' : 'failed';
$cost = $response['cost'] ?? 0; // from ClickSend response

$stmt->bind_param("sssisi", 
    $phone, 
    $message, 
    $message_type, 
    $user_id, 
    $status, 
    $cost
);
$stmt->execute();
$stmt->close();
```

---

## 📧 **How to Log Emails:**

### **Find Your Email Code:**

Look for where you send emails (welcome emails, notifications, etc.).

### **Current Code (Before):**

```php
// Example: Your current email sending code
mail($to, $subject, $message, $headers);
```

### **Updated Code (After):**

```php
// Send email
$sent = mail($to, $subject, $message, $headers);

// LOG TO DATABASE
$db = getDBConnection();
$stmt = $db->prepare("
    INSERT INTO email_logs 
    (recipient_email, subject, message_text, message_html, email_type, user_id, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");

$email_type = 'welcome'; // or 'notification', 'app_launch', 'other'
$user_id = $_SESSION['user_id'] ?? null;
$status = $sent ? 'sent' : 'failed';
$message_html = null; // if you have HTML version

$stmt->bind_param("sssssis", 
    $to, 
    $subject, 
    $message, 
    $message_html, 
    $email_type, 
    $user_id, 
    $status
);
$stmt->execute();
$stmt->close();
```

---

## 🔧 **Helper Function (Optional):**

Add this to your `config.php` for easy logging:

```php
<?php
/**
 * Log SMS to database
 */
function logSMS($phone, $message, $type = 'other', $user_id = null, $status = 'sent', $cost = 0) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        INSERT INTO sms_logs 
        (recipient_phone, message_text, message_type, user_id, status, cost, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssisi", $phone, $message, $type, $user_id, $status, $cost);
    $stmt->execute();
    $stmt->close();
}

/**
 * Log Email to database
 */
function logEmail($to, $subject, $message, $type = 'other', $user_id = null, $status = 'sent') {
    $db = getDBConnection();
    $stmt = $db->prepare("
        INSERT INTO email_logs 
        (recipient_email, subject, message_text, email_type, user_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssssis", $to, $subject, $message, $type, $user_id, $status);
    $stmt->execute();
    $stmt->close();
}
?>
```

**Then use it like this:**

```php
// After sending SMS
logSMS($phone, 'Your OTP is: 1234', 'otp', $user_id, 'sent', 0.05);

// After sending email
logEmail($email, 'Welcome!', 'Welcome to HostingAura', 'welcome', $user_id, 'sent');
```

---

## 🧪 **Testing:**

### **Test 1: Access Dashboard**

1. **Open browser:**
   ```
   https://speed.hostingaura.com/admin-dashboard.php
   ```

2. **Enter password:** `Cy96662666!`

3. **You should see:**
   - Login successful
   - Stats showing 0 or current counts
   - Navigation tabs

---

### **Test 2: View Data**

**Click each tab:**

- **👥 Users** - See all registered users
- **🚀 Speed Tests** - See all speed tests
- **📱 SMS Logs** - See SMS sent (will be empty until you add logging)
- **📧 Email Logs** - See emails sent (will be empty until you add logging)

---

### **Test 3: Export Data**

1. **Go to "Users" tab**
2. **Click "📥 Export CSV"**
3. **File downloads** with all user data
4. **Open in Excel** - verify data looks correct

---

## 📊 **What the Dashboard Shows:**

### **Overview Page:**

```
┌────────────────────────────────────────┐
│  🎛️ Admin Dashboard                   │
├────────────────────────────────────────┤
│                                        │
│  ┌──────────┐  ┌──────────┐           │
│  │    👥    │  │    🚀    │           │
│  │   142    │  │  1,847   │           │
│  │  Users   │  │  Tests   │           │
│  │ +12 7d   │  │ +45 24h  │           │
│  └──────────┘  └──────────┘           │
│                                        │
│  ┌──────────┐  ┌──────────┐           │
│  │    📱    │  │    📧    │           │
│  │   523    │  │   289    │           │
│  │   SMS    │  │  Emails  │           │
│  │ €26.15   │  │          │           │
│  └──────────┘  └──────────┘           │
│                                        │
└────────────────────────────────────────┘
```

---

### **Users Tab:**

```
┌────────────────────────────────────────┐
│  👥 Registered Users    [📥 Export]   │
├────────────────────────────────────────┤
│ ID | Email/Phone      | Tests | Date  │
├────────────────────────────────────────┤
│ 1  | user@email.com   |  12   | Jan 5 │
│ 2  | +35799123456     |   3   | Jan 6 │
│ 3  | test@test.com    |   8   | Jan 7 │
└────────────────────────────────────────┘
```

---

### **SMS Logs Tab:**

```
┌────────────────────────────────────────────────┐
│  📱 SMS Logs              [📥 Export]         │
├────────────────────────────────────────────────┤
│ Phone        | Message      | Type | Cost     │
├────────────────────────────────────────────────┤
│ +35799...    | Your OTP...  | OTP  | €0.05    │
│ +35799...    | Welcome!     | Noti | €0.05    │
└────────────────────────────────────────────────┘
```

---

### **Email Logs Tab:**

```
┌─────────────────────────────────────────────┐
│  📧 Email Logs           [📥 Export]       │
├─────────────────────────────────────────────┤
│ Email         | Subject        | Type      │
├─────────────────────────────────────────────┤
│ user@test.com | Welcome!       | Welcome   │
│ test@test.com | App Launch!    | App       │
└─────────────────────────────────────────────┘
```

---

### **Speed Tests Tab:**

```
┌──────────────────────────────────────────────────┐
│  🚀 Speed Tests            [📥 Export]          │
├──────────────────────────────────────────────────┤
│ User          | ↓Down | ↑Up  | Ping | ISP      │
├──────────────────────────────────────────────────┤
│ user@test.com | 150.2 | 45.3 |  12  | Cyta     │
│ test@test.com | 98.5  | 32.1 |  25  | MTN      │
└──────────────────────────────────────────────────┘
```

---

## 🔐 **Security Features:**

### **Built-in Protection:**

✅ **Password authentication**  
✅ **Session-based login**  
✅ **SQL injection prevention** (prepared statements)  
✅ **No direct database credentials exposed**  

### **Recommended Additional Security:**

1. **Change default password** (line 15)
2. **Add IP whitelist** (only your IP can access)
3. **Use `.htpasswd`** for double authentication
4. **Enable HTTPS** (already done on your site)

---

## 📋 **Where to Add Logging:**

### **Files You Need to Update:**

1. **OTP Registration File** (where you send OTP codes)
   - Add SMS logging after ClickSend call

2. **Email Sending Functions** (welcome emails, etc.)
   - Add email logging after mail() call

3. **Password Reset** (if you send reset codes)
   - Add SMS/email logging

4. **App Launch Emails** (send-app-launch-emails.php)
   - Already has email sending, add logging there

---

## 📊 **Example: Complete OTP Flow with Logging:**

```php
<?php
// User requests OTP
session_start();
require_once 'config.php';

$phone = $_POST['phone'];
$otp_code = rand(100000, 999999);

// Save OTP to session/database
$_SESSION['otp_code'] = $otp_code;

// Send via ClickSend
$message = "Your HostingAura verification code: $otp_code";
$response = sendClickSendSMS($phone, $message); // your existing function

// ✅ LOG SMS TO DATABASE
$db = getDBConnection();
$stmt = $db->prepare("
    INSERT INTO sms_logs 
    (recipient_phone, message_text, message_type, status, cost, created_at) 
    VALUES (?, ?, 'otp', ?, ?, NOW())
");

$status = $response['success'] ? 'sent' : 'failed';
$cost = $response['cost'] ?? 0.05;

$stmt->bind_param("sssi", $phone, $message, $status, $cost);
$stmt->execute();
$stmt->close();

// Return success
echo json_encode(['success' => true]);
?>
```

---

## 🛠️ **Troubleshooting:**

### **Problem: "Password incorrect"**

**Solution:** Check line 15 in `admin-dashboard.php`, password is case-sensitive

---

### **Problem: "Table doesn't exist"**

**Solution:** Run the SQL file in phpMyAdmin to create tables

---

### **Problem: "SMS/Email tabs are empty"**

**Solution:** Tables exist but no data logged yet. Add logging code to your SMS/email sending functions.

---

### **Problem: "Can't access admin-dashboard.php"**

**Check:**
1. File uploaded to correct location?
2. File permissions correct (644)?
3. URL correct? (with .php extension)

---

## 📈 **Database Schema Reference:**

### **sms_logs Table:**

```sql
id               INT (auto increment)
recipient_phone  VARCHAR(20)
message_text     TEXT
message_type     ENUM('otp', 'notification', 'other')
user_id          INT (nullable)
status           ENUM('sent', 'failed', 'pending')
clicksend_message_id  VARCHAR(100)
error_message    TEXT (nullable)
cost             DECIMAL(10,4)
created_at       DATETIME
```

### **email_logs Table:**

```sql
id               INT (auto increment)
recipient_email  VARCHAR(255)
subject          VARCHAR(500)
message_text     TEXT
message_html     TEXT (nullable)
email_type       ENUM('welcome', 'notification', 'app_launch', 'other')
user_id          INT (nullable)
status           ENUM('sent', 'failed', 'pending')
error_message    TEXT (nullable)
created_at       DATETIME
```

---

## ✅ **Final Checklist:**

### **Deployment:**
- [ ] Database tables created (run SQL file)
- [ ] Admin dashboard uploaded
- [ ] Password changed from default
- [ ] Admin panel accessible via URL

### **Testing:**
- [ ] Can login with password
- [ ] Can see users tab (shows existing users)
- [ ] Can see speed tests tab (shows existing tests)
- [ ] Can export CSV files

### **Integration:**
- [ ] Added SMS logging to OTP code
- [ ] Added email logging to email sends
- [ ] Tested that logs appear in dashboard

---

## 🎉 **You're Done!**

**Access your dashboard:**
```
https://speed.hostingaura.com/admin-dashboard.php
```

**Default password:** `Cy96662666!` (CHANGE THIS!)

**Features you can now use:**

✅ Monitor total users and activity  
✅ See all speed tests run  
✅ Track every SMS sent + cost  
✅ Track every email sent  
✅ Export everything to CSV  
✅ Beautiful dark-themed interface  
✅ Real-time refresh  

---

## 📞 **Quick Reference:**

**Admin Dashboard URL:**
```
https://speed.hostingaura.com/admin-dashboard.php
```

**phpMyAdmin:**
```
https://linux57.name-servers.gr:8443/phpmyadmin
```

**Database:** `speed_db`

**Tables:**
- `users` (already exists)
- `speed_results` (already exists)
- `sms_logs` (new - create with SQL file)
- `email_logs` (new - create with SQL file)

---

**Need help? Just ask!** 🚀