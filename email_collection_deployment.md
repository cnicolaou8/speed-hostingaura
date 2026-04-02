# 📧 Email Collection System - Complete Deployment Guide

## 🎯 **What This Does:**

Saves emails of users who want to be notified when your iOS app launches, so **YOU can access them** and email everyone on launch day!

---

## 📥 **5 Files Created:**

### **1. dashboard-v1.1-platform-aware.php** (Updated)
- Main dashboard with Coming Soon banner
- Email signup form
- **NOW sends emails to your database!**

### **2. api/notify-app-launch.php** (NEW)
- Backend API endpoint
- Saves emails to database
- Validates and prevents duplicates

### **3. create-app-launch-notifications-table.sql** (NEW)
- Creates database table
- Stores all signup emails

### **4. admin-app-launch-signups.php** (NEW)
- **Admin page to VIEW all emails!**
- Export to CSV
- Copy all emails button
- See who's been notified

### **5. send-app-launch-emails.php** (NEW)
- **Send emails to everyone on launch day!**
- Beautiful HTML email template
- Tracks who's been notified

---

## 🚀 **Step-by-Step Deployment:**

### **STEP 1: Create Database Table (2 minutes)**

1. **Go to Plesk** → phpMyAdmin
2. **Select database:** `speed_db`
3. **Click "SQL" tab**
4. **Copy-paste** entire contents of `create-app-launch-notifications-table.sql`
5. **Click "Go"**
6. **Verify:** You should see new table `app_launch_notifications`

**✅ Done! Database ready to collect emails.**

---

### **STEP 2: Create API Folder (1 minute)**

1. **In Plesk File Manager**, navigate to:
   ```
   /var/www/vhosts/hostingaura.com/speed.hostingaura.com/
   ```

2. **Create new folder:** `api`

3. **Upload file:** `api-notify-app-launch.php`

4. **Rename it to:** `notify-app-launch.php`

**Final path:**
```
/var/www/vhosts/hostingaura.com/speed.hostingaura.com/api/notify-app-launch.php
```

**✅ Done! API endpoint ready.**

---

### **STEP 3: Upload Dashboard (1 minute)**

1. **Backup old dashboard:**
   - Rename: `dashboard.php` → `dashboard-old.php`

2. **Upload new dashboard:**
   - `dashboard-v1.1-platform-aware.php` → `dashboard.php`

**✅ Done! Dashboard now connects to API.**

---

### **STEP 4: Upload Admin Page (1 minute)**

1. **Upload file:** `admin-app-launch-signups.php`

2. **Access it at:**
   ```
   https://speed.hostingaura.com/admin-app-launch-signups.php
   ```

3. **Default password:** `Cy96662666!`

   **⚠️ CHANGE THIS PASSWORD!**
   
   Edit line 15 in the file:
   ```php
   $admin_password = 'YOUR_NEW_PASSWORD_HERE';
   ```

**✅ Done! Admin panel ready.**

---

### **STEP 5: Upload Email Sender (1 minute)**

1. **Upload file:** `send-app-launch-emails.php`

2. **Don't run it yet!** (Only run on launch day)

**✅ Done! Launch email script ready.**

---

## 🧪 **Testing the System:**

### **Test 1: User Signup (Web Browser)**

1. **Open dashboard:**
   ```
   https://speed.hostingaura.com/dashboard.php
   ```

2. **Login → Profile → Settings**

3. **Scroll to "iOS App Coming Soon!" section**

4. **Enter test email:** `test@example.com`

5. **Click "Notify Me on Launch"**

6. **Expected:**
   - Button shows "Saving..."
   - Success message: "✅ You're on the list!"
   - Email field clears

---

### **Test 2: Check Database (phpMyAdmin)**

1. **Go to phpMyAdmin**

2. **Browse table:** `app_launch_notifications`

3. **You should see:**
   - Your test email
   - IP address
   - Timestamp
   - `notified = 0` (not yet notified)

---

### **Test 3: View in Admin Panel**

1. **Open admin page:**
   ```
   https://speed.hostingaura.com/admin-app-launch-signups.php
   ```

2. **Enter password:** `Cy96662666!`

3. **You should see:**
   - Total Signups: 1
   - Table showing your test email
   - "Notified: ✗ No" badge

4. **Test features:**
   - Click "📋 Copy" → Email copied to clipboard
   - Click "📋 Copy All Emails" → All emails copied
   - Click "📥 Export CSV" → Downloads CSV file

**✅ Everything working!**

---

## 📊 **How to Use the System:**

### **Daily/Weekly Monitoring:**

**Check admin page to see signups:**
```
https://speed.hostingaura.com/admin-app-launch-signups.php
```

**You'll see:**
- 📊 Total signups count
- 📊 Pending notifications
- 📊 Already notified count
- 📧 Full email list

---

### **Export Email List:**

**Option 1: CSV Export**
- Click "📥 Export CSV" in admin page
- Opens in Excel/Numbers
- Use for email marketing tools

**Option 2: Copy All**
- Click "📋 Copy All Emails"
- Emails copied as: `email1@example.com, email2@example.com, ...`
- Paste into email BCC field

---

### **On Launch Day (When App is Approved):**

#### **Step 1: Update send-app-launch-emails.php**

Edit line 17:
```php
$app_store_url = 'https://apps.apple.com/app/your-actual-app-id';
```

#### **Step 2: Open in Browser**

```
https://speed.hostingaura.com/send-app-launch-emails.php
```

#### **Step 3: Review Confirmation Page**

You'll see:
- ⚠️ Warning about sending emails
- Your App Store URL
- Number of emails to send
- Confirmation required

#### **Step 4: Click "Yes, Send Emails"**

- Emails sent to everyone!
- Progress shown
- Success/failure count
- All marked as "notified"

#### **Step 5: Verify in Admin**

- Check admin page
- All should show "✓ Yes" for notified
- Pending notifications: 0

**✅ Launch complete!**

---

## 📧 **Email Template Preview:**

Users receive this beautiful HTML email:

```
┌────────────────────────────────────┐
│     🎉 It's Finally Here!         │  ← Purple gradient header
├────────────────────────────────────┤
│                                    │
│ You asked to be notified when our │
│ iOS app launched – and it's LIVE  │
│ right now!                         │
│                                    │
│ Download the HostingAura Speed     │
│ Test app and get:                  │
│                                    │
│ • Push Notifications               │
│ • Scheduled Tests                  │
│ • Face ID Login                    │
│ • Native Performance               │
│                                    │
│    ┌─────────────────┐             │
│    │  Download Now   │  ← Big button
│    └─────────────────┘             │
│                                    │
│ Thank you for your patience!       │
│ - The HostingAura Team             │
└────────────────────────────────────┘
```

---

## 🔒 **Security Notes:**

### **Admin Page Protection:**

**Current:** Simple password protection

**Recommended Improvements:**
1. Change default password
2. Use `.htpasswd` for basic auth
3. Add IP whitelist
4. Use stronger authentication

### **API Protection:**

**Current:** 
- Email validation
- Duplicate prevention
- Rate limiting via web server

**Already Included:**
- SQL injection prevention (prepared statements)
- XSS prevention (input validation)
- CORS headers

---

## 📊 **Database Schema:**

```sql
app_launch_notifications
├─ id (INT, Primary Key)
├─ email (VARCHAR, UNIQUE)
├─ ip_address (VARCHAR)
├─ user_agent (TEXT)
├─ notified (BOOLEAN, default FALSE)
├─ notified_at (DATETIME)
└─ created_at (DATETIME)
```

---

## 🛠️ **Troubleshooting:**

### **Problem: "Failed to save email"**

**Check:**
1. Database table exists?
2. API file uploaded correctly?
3. File permissions correct (644)?

**Test API directly:**
```bash
curl -X POST https://speed.hostingaura.com/api/notify-app-launch.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

**Expected response:**
```json
{"success":true,"message":"You're on the list!..."}
```

---

### **Problem: Admin page 404**

**Check:**
1. File uploaded to root (not in subfolder)?
2. Named exactly `admin-app-launch-signups.php`?
3. URL correct?

---

### **Problem: Emails not sending**

**Check:**
1. PHP `mail()` function enabled on server?
2. SPF/DKIM records configured?
3. Check spam folder?

**Alternative:** Use PHPMailer or SendGrid instead of `mail()`

---

## 📈 **Analytics Ideas:**

### **Track Conversion:**

Add to database:
```sql
ALTER TABLE app_launch_notifications 
ADD COLUMN downloaded BOOLEAN DEFAULT FALSE,
ADD COLUMN downloaded_at DATETIME;
```

Then track when users actually download the app!

---

## ✅ **Final Checklist:**

### **Deployment:**
- [ ] Database table created
- [ ] API folder created
- [ ] API file uploaded
- [ ] Dashboard uploaded
- [ ] Admin page uploaded
- [ ] Email sender uploaded
- [ ] Admin password changed

### **Testing:**
- [ ] User can submit email
- [ ] Email saved to database
- [ ] Email visible in admin panel
- [ ] CSV export works
- [ ] Copy all emails works

### **Launch Day:**
- [ ] App Store URL updated
- [ ] Email template reviewed
- [ ] Test email sent (to yourself)
- [ ] Mass email sent
- [ ] All marked as notified

---

## 🎉 **Summary:**

**Before:**
- ❌ Emails only in localStorage (you can't see them)
- ❌ No way to contact users
- ❌ Lost if browser cleared

**After:**
- ✅ All emails saved in YOUR database
- ✅ Admin panel to view/export emails
- ✅ One-click launch email sender
- ✅ Beautiful HTML email template
- ✅ Track who's been notified

---

## 📞 **Need Help?**

**Common URLs:**

**Admin Panel:**
```
https://speed.hostingaura.com/admin-app-launch-signups.php
```

**API Endpoint:**
```
https://speed.hostingaura.com/api/notify-app-launch.php
```

**Email Sender:**
```
https://speed.hostingaura.com/send-app-launch-emails.php
```

**phpMyAdmin:**
```
https://linux57.name-servers.gr:8443/phpmyadmin
```

---

**You're all set! Users sign up → Emails saved to database → You send launch emails → Profit! 🚀**