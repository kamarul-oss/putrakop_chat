# cPanel Deployment Guide — PutraKop Live Chat System

**Step-by-Step Guide for cPanel File Manager**

---

## Table of Contents

1. [Before You Start](#1-before-you-start)
2. [Prepare Your Files](#2-prepare-your-files)
3. [Upload Files to cPanel](#3-upload-files-to-cpanel)
4. [Setup Database in cPanel](#4-setup-database-in-cpanel)
5. [Configure the Application](#5-configure-the-application)
6. [Run Migrations & Seed Data](#6-run-migrations--seed-data)
7. [Setup Cron Jobs](#7-setup-cron-jobs)
8. [Test Your Deployment](#8-test-your-deployment)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Before You Start

### What You Need

| Item | Where to Get It |
|------|-----------------|
| cPanel Login | Your hosting provider |
| SSH Access (optional) | Request from hosting provider |
| FileZilla or similar FTP client | Optional, but helpful |
| Gemini API Key | aistudio.google.com |

### Check Your Hosting Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP Version | 8.1 | 8.3 |
| MySQL Version | 5.7 | 8.0 |
| PHP Memory Limit | 256MB | 512MB |
| PHP Execution Time | 60s | 120s |
| PHP Post Max Size | 64MB | 128MB |
| PHP Upload Max Filesize | 64MB | 128MB |

---

## 2. Prepare Your Files

### Step 2.1: Build for Production

On your local computer, before uploading:

1. Open Command Prompt in your project folder
2. Run these commands:

```bash
# Install dependencies (if not already done)
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Step 2.2: Create Upload ZIP File

1. Open File Explorer
2. Navigate to your project folder: `C:\Projects\putrakop_live_chat`
3. Select ALL files and folders
4. Right-click → Send to → Compressed (zipped) folder
5. Name it: `putrakop_live_chat.zip`

**Important:** Make sure the ZIP contains:
- `app/` folder
- `bootstrap/` folder
- `config/` folder
- `database/` folder
- `public/` folder
- `resources/` folder
- `routes/` folder
- `storage/` folder
- `vendor/` folder
- `composer.json`
- `artisan`
- `package.json`
- `vite.config.js`

**Do NOT include:**
- `.env` file (we'll create this on the server)
- `node_modules/` folder
- `.git/` folder

---

## 3. Upload Files to cPanel

### Step 3.1: Login to cPanel

1. Go to: `https://yourdomain.com:2083`
2. Enter your cPanel username and password
3. Click "Login"

### Step 3.2: Open File Manager

1. In cPanel, find "File Manager" (under Files section)
2. Click on "File Manager"

### Step 3.3: Navigate to Public HTML

1. In the left sidebar, click on "public_html"
2. This is your website's root folder

### Step 3.4: Create Project Folder

1. Click "+ Folder" button (top menu)
2. Name it: `putrakop_live_chat`
3. Click "Create New Folder"
4. Double-click to enter the folder

### Step 3.5: Upload ZIP File

1. Click "Upload" button (top menu)
2. Click "Select File" or drag and drop your ZIP file
3. Wait for upload to complete
4. Click "Go Back"

### Step 3.6: Extract ZIP File

1. Find your uploaded ZIP file
2. Right-click on it → Click "Extract"
3. Confirm the extraction path
4. Click "Extract File(s)"
5. Wait for extraction to complete

### Step 3.7: Move Files to Correct Location

**Important:** The files should be directly in `public_html`, not in a subfolder.

1. Enter the extracted folder
2. Select ALL files and folders
3. Click "Move" (top menu)
4. Change path to: `/public_html/`
5. Click "Move File(s)"

Your folder structure should look like:
```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── composer.json
├── artisan
└── ...
```

---

## 4. Setup Database in cPanel

### Step 4.1: Create Database

1. In cPanel, find "MySQL Databases" (under Databases section)
2. In "Create New Database" section:
   - Enter database name: `putrakop_live_chat`
   - Click "Create Database"
3. Note down the full database name (usually: `username_putrakop_live_chat`)

### Step 4.2: Create Database User

1. Scroll down to "MySQL Users" section
2. In "Add New User":
   - Username: `putrakop_user`
   - Password: Create a strong password (save this!)
   - Click "Create User"
3. Note down the full username (usually: `username_putrakop_user`)

### Step 4.3: Assign User to Database

1. Scroll down to "Add User To Database" section
2. Select your user: `username_putrakop_user`
3. Select your database: `username_putrakop_live_chat`
4. Click "Add"
5. Check "ALL PRIVILEGES"
6. Click "Make Changes"

### Step 4.4: Create phpMyAdmin Tables (Alternative Method)

If migrations don't work, you can import the SQL file:

1. In cPanel, find "phpMyAdmin"
2. Select your database
3. Click "Import" tab
4. Click "Choose File"
5. Select the migration file from your project
6. Click "Go"

---

## 5. Configure the Application

### Step 5.1: Create .env File

1. In File Manager, go to `public_html/`
2. Click "+ File" button (top menu)
3. Name it: `.env`
4. Click "Create New File"
5. Right-click on `.env` → Click "Edit"
6. Paste the following content:

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
DB_DATABASE=username_putrakop_live_chat
DB_USERNAME=username_putrakop_user
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
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

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

# Audit Logging (PDPA Malaysia compliance)
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

### Step 5.2: Update .env with Your Values

Replace these placeholders:

| Placeholder | Your Value |
|-------------|------------|
| `yourdomain.com` | Your actual domain |
| `username_putrakop_live_chat` | Your database name from Step 4.1 |
| `username_putrakop_user` | Your database username from Step 4.2 |
| `your_database_password` | Password you created in Step 4.2 |
| `your_gemini_api_key_here` | Your Gemini API key from Step 1 |

### Step 5.3: Generate APP_KEY

You need to generate an application key. Two options:

**Option A: Using cPanel Terminal (if available)**
1. In cPanel, find "Terminal"
2. Navigate to your project: `cd public_html`
3. Run: `php artisan key:generate`

**Option B: Manual Key Generation**
1. Go to: https://laravel-encryption-key-generator.herokuapp.com/
2. Copy the generated key
3. Edit `.env` file
4. Replace `APP_KEY=` with `APP_KEY=base64:your_generated_key_here`

### Step 5.4: Set Folder Permissions

1. In File Manager, right-click on `storage/` folder
2. Click "Change Permissions"
3. Set to: `775` (or `777` if 775 doesn't work)
4. Do the same for:
   - `bootstrap/cache/`
   - `public/`

---

## 6. Run Migrations & Seed Data

### Step 6.1: Access Terminal

1. In cPanel, find "Terminal" (if available)
2. If not available, skip to Method B below

### Step 6.2: Run Migrations (Method A - Terminal)

```bash
cd ~/public_html
php artisan migrate --force
php artisan db:seed --force
```

### Step 6.3: Run Migrations (Method B - SSH)

If you have SSH access:

```bash
ssh username@yourdomain.com
cd ~/public_html
php artisan migrate --force
php artisan db:seed --force
```

### Step 6.4: If Migrations Don't Work

You can import the SQL file manually:

1. Open phpMyAdmin in cPanel
2. Select your database
3. Click "SQL" tab
4. Copy and paste the SQL from `database/migrations/` files
5. Click "Go" to execute

---

## 7. Setup Cron Jobs

### Step 7.1: Open Cron Jobs

1. In cPanel, find "Cron Jobs" (under Advanced section)
2. Click on "Cron Jobs"

### Step 7.2: Add Queue Worker Cron Job

1. In "Add New Cron Job" section:
   - Common Settings: Every minute
   - Or manually: `* * * * *`
2. Command:
   ```bash
   cd ~/public_html && php artisan schedule:run >> /dev/null 2>&1
   ```
3. Click "Add New Cron Job"

### Step 7.3: Add Scheduler Cron Job

1. Add another cron job:
   - Common Settings: Every 5 minutes
   - Or manually: `*/5 * * * *`
2. Command:
   ```bash
   cd ~/public_html && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
   ```
3. Click "Add New Cron Job"

### Step 7.4: Add Session Cleanup (Optional)

1. Add another cron job:
   - Common Settings: Once Daily
   - Or manually: `0 0 * * *`
2. Command:
   ```bash
   cd ~/public_html && php artisan session:gc >> /dev/null 2>&1
   ```
3. Click "Add New Cron Job"

---

## 8. Test Your Deployment

### Step 8.1: Check Health Status

1. Open your browser
2. Go to: `https://yourdomain.com/api/v1/health`
3. You should see:
   ```json
   {
     "status": "healthy",
     "services": {
       "database": {"healthy": true},
       "cache": {"healthy": true},
       "queue": {"healthy": true}
     }
   }
   ```

### Step 8.2: Test Customer Chat

1. Go to: `https://yourdomain.com/chat`
2. Select a department
3. Send a message
4. Check if you get a response

### Step 8.3: Test Admin Panel

1. Go to: `https://yourdomain.com/admin`
2. Login with test credentials
3. Check if you can access all features

---

## 9. Troubleshooting

### Common Issues & Solutions

| Problem | Solution |
|---------|----------|
| **500 Internal Server Error** | Check `.env` file for typos, verify APP_KEY is set |
| **Database Connection Error** | Verify database credentials in `.env` |
| **White Page** | Set `APP_DEBUG=true` temporarily to see errors |
| **Permission Denied** | Set folder permissions to 775 or 777 |
| **Migration Failed** | Check database exists and user has permissions |
| **Queue Not Working** | Verify cron job is set up correctly |
| **AI Not Responding** | Check Gemini API key is valid and has quota |
| **Files Not Uploading** | Check PHP upload_max_filesize in cPanel |

### Check Error Logs

1. In cPanel, find "Error Log"
2. Check for specific error messages
3. Common errors:
   - `SQLSTATE[HY000]` - Database connection issue
   - `No such file or directory` - File path issue
   - `Class not found` - Missing dependencies

### Enable Debug Mode (Temporarily)

1. Edit `.env` file
2. Change: `APP_DEBUG=false` to `APP_DEBUG=true`
3. Save the file
4. Check the error message
5. **Important:** Change back to `false` when done!

---

## Quick Reference

### Important URLs

| Page | URL |
|------|-----|
| Customer Chat | https://yourdomain.com/chat |
| Agent Workspace | https://yourdomain.com/agent/workspace |
| Manager Dashboard | https://yourdomain.com/manager/dashboard |
| Admin Panel | https://yourdomain.com/admin |
| API Health Check | https://yourdomain.com/api/v1/health |

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
| `public/` | 775 |
| `vendor/` | 755 |

---

## Need Help?

If you're still having issues:

1. **Check the error message** in cPanel Error Log
2. **Search online** for the specific error
3. **Contact your hosting provider** for server-specific issues
4. **Ask a developer** for help with code issues

---

*Last Updated: July 8, 2026*
*Version: 1.0*
