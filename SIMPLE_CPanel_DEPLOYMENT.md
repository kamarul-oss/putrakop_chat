# Simple cPanel Deployment Guide — No Local Setup Required

**Just Copy and Paste to cPanel!**

---

## What You Need

1. ✅ Your project files (already have them)
2. ✅ cPanel login credentials
3. ✅ Gemini API key

---

## Step 1: Prepare Files for Upload

### Option A: Create ZIP File (Recommended)

1. Open File Explorer
2. Go to: `C:\KAMARUL\1.PROJECT\OPENCODE\putrakop_live_chat`
3. Select ALL files and folders:
   - Press `Ctrl + A` to select all
   - OR hold `Ctrl` and click each file/folder
4. Right-click on the selected files
5. Click **"Send to"** → **"Compressed (zipped) folder"**
6. Wait for the ZIP file to be created
7. Name it: `putrakop_live_chat.zip`

### Option B: Upload Individual Files

If ZIP doesn't work, you can upload files individually (slower).

---

## Step 2: Login to cPanel

1. Open your web browser
2. Go to: `https://yourdomain.com:2083`
   - Replace `yourdomain.com` with your actual domain
3. Enter your cPanel username and password
4. Click **"Login"**

---

## Step 3: Upload ZIP File

1. In cPanel, find **"File Manager"** (under Files section)
2. Click on **"File Manager"**
3. In the left sidebar, click on **"public_html"**
4. Click **"Upload"** button (top menu)
5. Click **"Select File"** or drag and drop your `putrakop_live_chat.zip`
6. Wait for upload to complete (may take a few minutes)
7. Click **"Go Back"** when done

---

## Step 4: Extract ZIP File

1. Find your uploaded `putrakop_live_chat.zip`
2. Right-click on it
3. Click **"Extract"**
4. Confirm the path: `/public_html/`
5. Click **"Extract File(s)"**
6. Wait for extraction to complete
7. Click **"Close"**

---

## Step 5: Run Commands on Server

### Option A: Use cPanel Terminal (Easiest)

1. In cPanel, find **"Terminal"** (under Advanced section)
2. Click on **"Terminal"**
3. A black window will open
4. Type these commands one by one, press Enter after each:

```bash
cd ~/public_html
```

```bash
composer install --no-dev --optimize-autoloader
```

```bash
php artisan key:generate
```

```bash
php artisan migrate --force
```

```bash
php artisan db:seed --force
```

```bash
npm install
```

```bash
npm run build
```

### Option B: Use SSH (If Terminal Not Available)

If cPanel Terminal doesn't work, you may need SSH access. Contact your hosting provider to enable SSH.

---

## Step 6: Create .env File

1. In File Manager, go to `public_html/`
2. Click **"+ File"** button (top menu)
3. Name it: `.env`
4. Click **"Create New File"**
5. Right-click on `.env` → Click **"Edit"**
6. Copy and paste this content:

```env
APP_NAME="PutraKop Live Chat"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Kuala_Lumpur
APP_URL=https://yourdomain.com

APP_LOCALE=ms
APP_FALLBACK_LOCALE=en
APP_MAINTENANCE_DRIVER=file

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Redis (if available)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session & Cache
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# Queue
QUEUE_CONNECTION=database

# Google Gemini AI (Free Tier)
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_MODEL=gemini-2.0-flash
GEMINI_DAILY_LIMIT=1500

# File Storage
FILESYSTEM_DISK=local

# Sanctum
SANCTUM_STATEFUL_DOMAINS=yourdomain.com

# Device Authentication
DEVICE_FINGERPRINT_ENABLED=true
DEVICE_TRUST_DURATION_DAYS=90

# AI Assistant
AI_ASSISTANT_ENABLED=true
AI_FALLBACK_TO_CANNED=true

# Business Hours
BUSINESS_HOURS_START="09:00"
BUSINESS_HOURS_END="17:00"
BUSINESS_DAYS="mon,tue,wed,thu,fri"

# Rating
RATING_ENABLED=true

# Security Configuration
CSP_ENABLED=true
CSP_REPORT_ONLY=false

# Audit Logging
AUDIT_ENABLED=true
AUDIT_LOG_LEVEL=info

# AI Rate Limits
AI_DAILY_LIMIT=1500
AI_RPM_LIMIT=15
AI_TIMEOUT=30
AI_MAX_TOKENS=500

# Logging
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=info
```

7. **Replace these values:**
   - `yourdomain.com` → Your actual domain
   - `your_database_name` → Your database name from Step 7
   - `your_database_user` → Your database username from Step 7
   - `your_database_password` → Your database password from Step 7
   - `your_gemini_api_key_here` → Your Gemini API key

---

## Step 7: Setup Database

### Option A: Create New Database

1. In cPanel, find **"MySQL Databases"** (under Databases section)
2. **Create Database:**
   - Enter name: `putrakop_live_chat`
   - Click **"Create Database"**
   - Note the full name (e.g., `username_putrakop_live_chat`)

3. **Create User:**
   - Scroll to **"MySQL Users"** section
   - Enter username: `putrakop_user`
   - Enter password (create a strong password)
   - Click **"Create User"**
   - Note the full username (e.g., `username_putrakop_user`)

4. **Assign User to Database:**
   - Scroll to **"Add User To Database"** section
   - Select your user
   - Select your database
   - Click **"Add"**
   - Check **"ALL PRIVILEGES"**
   - Click **"Make Changes"**

### Option B: Use Existing Database

If you already have a database, just note the:
- Database name
- Database username
- Database password

---

## Step 8: Update .env with Database Info

1. In File Manager, go to `public_html/`
2. Right-click on `.env` → Click **"Edit"**
3. Update these lines:

```
DB_DATABASE=username_putrakop_live_chat
DB_USERNAME=username_putrakop_user
DB_PASSWORD=your_database_password
```

4. Click **"Save Changes"**

---

## Step 9: Run Database Setup

1. In cPanel, find **"Terminal"**
2. Type these commands:

```bash
cd ~/public_html
php artisan migrate --force
php artisan db:seed --force
```

---

## Step 10: Setup Cron Jobs

1. In cPanel, find **"Cron Jobs"** (under Advanced section)
2. **Add Queue Worker:**
   - Common Settings: **Every minute**
   - Command: `cd ~/public_html && php artisan schedule:run >> /dev/null 2>&1`
   - Click **"Add New Cron Job"**

3. **Add Scheduler:**
   - Common Settings: **Every 5 minutes**
   - Command: `cd ~/public_html && php artisan queue:work --stop-when-empty >> /dev/null 2>&1`
   - Click **"Add New Cron Job"**

---

## Step 11: Set Folder Permissions

1. In File Manager, go to `public_html/`
2. Right-click on **"storage"** folder → Click **"Change Permissions"**
3. Set to: **775** (or **777** if 775 doesn't work)
4. Do the same for:
   - `bootstrap/cache/` folder

---

## Step 12: Test Your Application

### Test Health Check

1. Open your browser
2. Go to: `https://yourdomain.com/api/v1/health`
3. You should see:
   ```json
   {
     "status": "healthy",
     "services": {
       "database": {"healthy": true},
       "cache": {"healthy": true}
     }
   }
   ```

### Test Customer Chat

1. Go to: `https://yourdomain.com/chat`
2. Select a department
3. Send a message
4. Check if you get a response

### Test Admin Panel

1. Go to: `https://yourdomain.com/admin`
2. Login with test credentials:
   - Email: `admin@putrakop.org`
   - Password: `password`

---

## Troubleshooting

### Problem: "composer is not recognized"

**Solution:** You don't need Composer on your computer! The commands are run on the server using cPanel Terminal.

### Problem: Terminal command fails

**Solution:** Try running commands one at a time. If one fails, check the error message.

### Problem: Database connection error

**Solution:** Check your `.env` file has correct database credentials.

### Problem: 500 Internal Server Error

**Solution:** Check the error log in cPanel → Error Log.

### Problem: Files not uploading

**Solution:** Check PHP upload limits in cPanel → PHP Selector.

---

## Quick Reference

### Important URLs

| Page | URL |
|------|-----|
| Customer Chat | https://yourdomain.com/chat |
| Admin Panel | https://yourdomain.com/admin |
| Health Check | https://yourdomain.com/api/v1/health |

### Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@putrakop.org | password |
| Manager | manager@putrakop.org | password |
| Agent | agent@putrakop.org | password |

### Folder Permissions

| Folder | Permission |
|--------|------------|
| `storage/` | 775 or 777 |
| `bootstrap/cache/` | 775 or 777 |

---

## Summary

| Step | What to Do |
|------|------------|
| 1 | Create ZIP file |
| 2 | Login to cPanel |
| 3 | Upload ZIP file |
| 4 | Extract ZIP file |
| 5 | Run commands on server |
| 6 | Create .env file |
| 7 | Setup database |
| 8 | Update .env with database info |
| 9 | Run database setup |
| 10 | Setup cron jobs |
| 11 | Set folder permissions |
| 12 | Test your application |

---

**That's it! No local installation needed! 🎉**

---

*Last Updated: July 8, 2026*
*Version: 1.0*
