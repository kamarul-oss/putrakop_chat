# PutraKop Live Chat System — Implementation Guide

**For Non-Technical Users**

---

## Table of Contents

1. [What You Need Before Starting](#1-what-you-need-before-starting)
2. [Step-by-Step Installation](#2-step-by-step-installation)
3. [Configuration Guide](#3-configuration-guide)
4. [Running the Application](#4-running-the-application)
5. [Testing the System](#5-testing-the-system)
6. [Troubleshooting](#6-troubleshooting)
7. [Glossary](#7-glossary)

---

## 1. What You Need Before Starting

### Required Software

| Software | Purpose | How to Get It |
|----------|---------|---------------|
| **PHP 8.3+** | Backend programming language | Download from php.net |
| **Composer** | PHP package manager | Download from getcomposer.org |
| **Node.js 18+** | JavaScript runtime | Download from nodejs.org |
| **MySQL 8.0** | Database | Download from mysql.com |
| **Redis** | Cache/queue system | Download from redis.io |
| **Code Editor** | To edit files | Download VS Code from code.visualstudio.com |

### Required Accounts

| Account | Purpose | How to Get It |
|---------|---------|---------------|
| **Google AI Studio** | Gemini API key | Visit aistudio.google.com |
| **GitHub** | Source code repository | Visit github.com |

---

## 2. Step-by-Step Installation

### Step 2.1: Install PHP

1. Go to https://php.net/downloads
2. Download PHP 8.3 or higher (Windows: choose "VS16 x64 Non Thread Safe")
3. Extract the ZIP file to `C:\php`
4. Add PHP to your system PATH:
   - Press Windows key + S, search "Environment Variables"
   - Click "Edit the system environment variables"
   - Click "Environment Variables" button
   - Under "System variables", find "Path" and click "Edit"
   - Click "New" and add `C:\php`
   - Click "OK" on all dialogs

5. Verify PHP is installed:
   - Open Command Prompt (press Windows key + R, type `cmd`, press Enter)
   - Type: `php --version`
   - You should see PHP version information

### Step 2.2: Install Composer

1. Go to https://getcomposer.org/download
2. Download and run `Composer-Setup.exe`
3. Follow the installation wizard
4. Verify Composer is installed:
   - Open Command Prompt
   - Type: `composer --version`

### Step 2.3: Install Node.js

1. Go to https://nodejs.org
2. Download the LTS version
3. Run the installer and follow the wizard
4. Verify Node.js is installed:
   - Open Command Prompt
   - Type: `node --version`
   - Type: `npm --version`

### Step 2.4: Install MySQL

1. Go to https://dev.mysql.com/downloads/mysql/
2. Download MySQL 8.0
3. Run the installer and follow the wizard
4. Remember your root password (you'll need it later)
5. Verify MySQL is installed:
   - Open Command Prompt
   - Type: `mysql --version`

### Step 2.5: Install Redis

1. Go to https://redis.io/download
2. Download the Windows version
3. Extract to `C:\redis`
4. Run `redis-server.exe` to start Redis
5. Verify Redis is installed:
   - Open another Command Prompt
   - Type: `redis-cli ping`
   - You should see "PONG"

---

## 3. Configuration Guide

### Step 3.1: Get Gemini API Key

1. Go to https://aistudio.google.com
2. Sign in with your Google account
3. Click "Get API Key"
4. Click "Create API key"
5. Copy the API key and save it somewhere safe

### Step 3.2: Create the Project

1. Open Command Prompt
2. Navigate to where you want to create the project:
   ```
   cd C:\Projects
   ```
3. Create the project folder:
   ```
   mkdir putrakop_live_chat
   cd putrakop_live_chat
   ```

### Step 3.3: Set Up the Database

1. Open MySQL command line:
   ```
   mysql -u root -p
   ```
2. Enter your MySQL root password
3. Create the database:
   ```sql
   CREATE DATABASE putrakop_live_chat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Create a user (replace `password` with a strong password):
   ```sql
   CREATE USER 'putrakop'@'localhost' IDENTIFIED BY 'password';
   GRANT ALL PRIVILEGES ON putrakop_live_chat.* TO 'putrakop'@'localhost';
   FLUSH PRIVILEGES;
   ```
5. Exit MySQL:
   ```
   EXIT;
   ```

### Step 3.4: Configure Environment File

1. In the project folder, find `.env.example`
2. Copy it and rename to `.env`
3. Open `.env` in your code editor
4. Update these values:

```
# Database settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=putrakop_live_chat
DB_USERNAME=putrakop
DB_PASSWORD=your_password_here

# Gemini AI
GEMINI_API_KEY=your_gemini_api_key_here
```

---

## 4. Running the Application

### Step 4.1: Install Dependencies

1. Open Command Prompt in the project folder
2. Install PHP dependencies:
   ```
   composer install
   ```
3. Install JavaScript dependencies:
   ```
   npm install
   ```

### Step 4.2: Generate Application Key

```
php artisan key:generate
```

### Step 4.3: Run Database Migrations

```
php artisan migrate
```

This creates all the database tables.

### Step 4.4: Seed Initial Data

```
php artisan db:seed
```

This adds sample departments and FAQ entries.

### Step 4.5: Build Frontend

```
npm run build
```

### Step 4.6: Start the Application

Open 3 Command Prompt windows:

**Window 1 - Laravel Server:**
```
cd C:\Projects\putrakop_live_chat
php artisan serve
```

**Window 2 - Queue Worker:**
```
cd C:\Projects\putrakop_live_chat
php artisan queue:work
```

**Window 3 - Vite Dev Server (for development):**
```
cd C:\Projects\putrakop_live_chat
npm run dev
```

### Step 4.7: Access the Application

Open your web browser and go to:
- **Customer Chat:** http://localhost:8000/chat
- **Agent Workspace:** http://localhost:8000/agent/workspace
- **Manager Dashboard:** http://localhost:8000/manager/dashboard
- **Admin Panel:** http://localhost:8000/admin

---

## 5. Testing the System

### Step 5.1: Create Test Users

1. Go to http://localhost:8000/admin
2. Login with admin credentials
3. Go to Users section
4. Create test users:
   - **Admin:** admin@putrakop.org (role: admin)
   - **Manager:** manager@putrakop.org (role: manager)
   - **Agent:** agent@putrakop.org (role: agent)

### Step 5.2: Test Customer Chat

1. Go to http://localhost:8000/chat
2. Select a department
3. Type a message
4. The AI should respond automatically

### Step 5.3: Test Agent Workspace

1. Go to http://localhost:8000/agent/workspace
2. Login as agent
3. Set status to "Online"
4. Accept a conversation from the queue
5. Send messages to the customer

### Step 5.4: Test Manager Dashboard

1. Go to http://localhost:8000/manager/dashboard
2. Login as manager
3. View real-time statistics
4. Monitor agent performance

---

## 6. Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| "php is not recognized" | Add PHP to PATH environment variable |
| "composer is not recognized" | Restart Command Prompt after installing Composer |
| "MySQL connection refused" | Start MySQL service: `net start mysql80` |
| "Redis connection refused" | Start Redis: run `redis-server.exe` |
| "Migration failed" | Check database credentials in `.env` |
| "No such file" error | Make sure you're in the correct project folder |

### Getting Help

1. Check the error message carefully
2. Search the error message on Google
3. Ask on Stack Overflow
4. Contact the development team

---

## 7. Glossary

| Term | Meaning |
|------|---------|
| **API** | Application Programming Interface - how different software components communicate |
| **Backend** | The server-side of the application (PHP, Laravel) |
| **Cache** | Temporary storage for frequently accessed data |
| **CDN** | Content Delivery Network - servers that deliver content faster |
| **Command Line** | Text-based interface to interact with your computer |
| **Database** | Organized collection of data (MySQL) |
| **Deployment** | Making the application available for users |
| **Environment** | The system where the application runs |
| **Frontend** | The user interface of the application (Vue.js) |
| **Migration** | Database structure changes managed by code |
| **Queue** | System for handling background tasks |
| **Redis** | In-memory data store used for caching and queues |
| **Route** | URL path that maps to a controller action |
| **WebSocket** | Real-time communication protocol |
| **API** | Application Programming Interface |
| **CRUD** | Create, Read, Update, Delete operations |
| **Endpoint** | A specific URL where an API can be accessed |
| **JSON** | JavaScript Object Notation - data format |
| **REST** | Representational State Transfer - API architecture style |
| **Sanctum** | Laravel's authentication system |
| **Inertia** | Laravel/Vue.js integration framework |
| **Tailwind** | CSS framework for styling |

---

## Quick Reference Commands

### Development Commands

```bash
# Start development server
php artisan serve

# Run queue worker
php artisan queue:work

# Clear cache
php artisan cache:clear

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Build frontend assets
npm run dev

# Build for production
npm run build
```

### Testing Commands

```bash
# Run PHP tests
php artisan test

# Run load tests
k6 run tests/LoadTest/load_test.js
```

---

## Deployment to Production

### cPanel Deployment

1. Login to cPanel
2. Go to "File Manager"
3. Navigate to `public_html`
4. Upload all project files
5. Setup database in cPanel
6. Update `.env` with production settings
7. Run migrations via SSH or cPanel Terminal

### Environment Variables for Production

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
DB_HOST=localhost
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
GEMINI_API_KEY=your_key
```

---

*Last Updated: July 8, 2026*
*Version: 1.0*
