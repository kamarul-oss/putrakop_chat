# Quick Start Checklist — PutraKop Live Chat System

Print this page and check off each step as you complete it.

---

## Pre-Installation Checklist

- [ ] PHP 8.3 or higher installed
- [ ] Composer installed
- [ ] Node.js 18+ installed
- [ ] MySQL 8.0 installed
- [ ] Redis installed
- [ ] Code editor installed (VS Code recommended)
- [ ] Google AI Studio account created
- [ ] Gemini API key obtained

---

## Installation Checklist

### Step 1: Create Project Folder
```
mkdir putrakop_live_chat
cd putrakop_live_chat
```
- [ ] Project folder created

### Step 2: Install PHP Dependencies
```
composer install
```
- [ ] Composer install completed

### Step 3: Install JavaScript Dependencies
```
npm install
```
- [ ] NPM install completed

### Step 4: Configure Environment
- [ ] Copied `.env.example` to `.env`
- [ ] Updated database settings
- [ ] Added Gemini API key
- [ ] Generated application key

### Step 5: Setup Database
- [ ] Created MySQL database
- [ ] Created database user
- [ ] Ran migrations
- [ ] Seeded initial data

### Step 6: Build Frontend
```
npm run build
```
- [ ] Frontend built successfully

### Step 7: Start Application
- [ ] Laravel server running
- [ ] Queue worker running
- [ ] Application accessible in browser

---

## Testing Checklist

### Admin Panel
- [ ] Login as admin
- [ ] Create departments
- [ ] Create users
- [ ] Configure settings

### Agent Workspace
- [ ] Login as agent
- [ ] Set status to online
- [ ] Accept conversations
- [ ] Send messages

### Manager Dashboard
- [ ] Login as manager
- [ ] View statistics
- [ ] Monitor agents
- [ ] Check reports

### Customer Chat
- [ ] Start new conversation
- [ ] Send messages
- [ ] Receive AI responses
- [ ] Submit rating

---

## Troubleshooting Quick Reference

| Problem | Command to Run |
|---------|----------------|
| PHP not found | Add to PATH: `C:\php` |
| MySQL won't start | `net start mysql80` |
| Redis won't start | Run `redis-server.exe` |
| Cache issues | `php artisan cache:clear` |
| Migration issues | `php artisan migrate:fresh --seed` |
| Build fails | Delete `node_modules` and run `npm install` |

---

## Important URLs

| Page | URL |
|------|-----|
| Customer Chat | http://localhost:8000/chat |
| Agent Workspace | http://localhost:8000/agent/workspace |
| Manager Dashboard | http://localhost:8000/manager/dashboard |
| Admin Panel | http://localhost:8000/admin |
| API Health Check | http://localhost:8000/api/v1/health |

---

## Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@putrakop.org | password |
| Manager | manager@putrakop.org | password |
| Agent | agent@putrakop.org | password |

*Note: Change these passwords after first login!*

---

## Need Help?

1. **Check the full guide:** `IMPLEMENTATION_GUIDE.md`
2. **Check error messages:** Read the exact error
3. **Search online:** Google the error message
4. **Contact support:** support@putrakop.org

---

*Good luck! You've got this! 💪*
