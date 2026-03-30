# ⚡ HostingAura Speed Test

A self-hosted, full-stack internet speed test application built for [speed.hostingaura.com](https://speed.hostingaura.com).

![Status](https://img.shields.io/badge/status-live-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.x-blue)
![License](https://img.shields.io/badge/license-MIT-purple)

---

## 🚀 Features

- **Real-time speed test** — Download, Upload, and Ping measured against your own server
- **User accounts** — Register and log in to track your full test history
- **OTP Verification** — Email and SMS one-time password verification via ClickSend
- **Guest mode** — Run tests without an account, results saved to browser localStorage
- **Shareable results** — Every test gets a unique shareable URL
- **Dashboard** — Logged-in users can view all their past results
- **Privacy first** — Passwords hashed with bcrypt, credentials never stored in code

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, Vanilla JavaScript |
| Backend | PHP 8.x |
| Database | MySQL (MariaDB) |
| Hosting | Plesk + Cloudflare |
| OTP Delivery | ClickSend API (Email & SMS) |
| Version Control | GitHub |

---

## 📁 Project Structure
speed/
├── database/
│ └── schema.sql # Full database schema
├── index.html # Main speed test UI
├── dashboard.php # User history dashboard
├── results.php # Shareable result page
├── save_result.php # Saves test result to DB
├── login.php # User login (JSON API)
├── logout.php # Session destroy
├── check_session.php # Returns current session info
├── sent_otp.php # Sends OTP via ClickSend
├── verify_otp.php # Verifies OTP & creates account
├── download.php # Download test endpoint
├── upload.php # Upload test endpoint
├── empty.php # Upload target (empty response)
├── privacy.html # Privacy Policy
├── config.php # 🔒 NOT in repo — see setup below
└── .gitignore



---

## ⚙️ Setup & Installation

### 1. Clone the Repository
```bash
git clone https://github.com/cnicolaou8/hostingaura.git
cd hostingaura/speed
```

### 2. Create the Database
Run the SQL schema in your MySQL/phpMyAdmin:
```bash
mysql -u your_user -p < database/schema.sql
```

### 3. Create `config.php`
Create this file manually in the project root. **Never commit this file.**
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'speed_db');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

define('CLICKSEND_USERNAME', 'your@email.com');
define('CLICKSEND_API_KEY',  'your_clicksend_api_key');

define('OTP_FROM_EMAIL',  'noreply@yourdomain.com');
define('OTP_FROM_NAME',   'YourBrand');
define('OTP_SMS_SENDER',  'YourBrand');
define('SITE_NAME',       'speed.yourdomain.com');
?>
```

### 4. Configure Cloudflare (Recommended)
- Enable **Full (Strict) SSL**
- Disable **Rocket Loader** for the speed subdomain
- Disable **Web Analytics beacon injection** for accurate results

---

## 🔒 Security

- `config.php` is listed in `.gitignore` and never pushed to GitHub
- Passwords are hashed using `PASSWORD_BCRYPT` via PHP's `password_hash()`
- OTP codes expire after **10 minutes** and are invalidated after use
- All DB queries use prepared statements or escaped inputs

---

## 📬 OTP Delivery

OTP verification is powered by [ClickSend](https://clicksend.com):
- **Email OTP** — sent from `noreply@hostingaura.com`
- **SMS OTP** — sent from `HostingAura`

---

## 📄 License

MIT License — feel free to use, modify, and distribute.

---

## 👨‍💻 Built By

**HostingAura** — Web Hosting & Design Services  
🌐 [hostingaura.com](https://hostingaura.com)  
📧 [privacy@hostingaura.com](mailto:privacy@hostingaura.com)