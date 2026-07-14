<?php
/**
 * PutraKop Live Chat System — Automatic Setup Script
 * 
 * This script automatically sets up the application when you visit it.
 * No commands needed! Just upload files and visit this script.
 * 
 * Usage:
 * 1. Upload all files to cPanel public_html
 * 2. Visit: https://yourdomain.com/setup.php
 * 3. Follow the on-screen instructions
 * 4. Delete setup.php after installation
 */

// Error reporting for setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$config = [
    'root_dir' => dirname(__DIR__),
    'env_file' => dirname(__DIR__) . '/.env',
    'env_example' => dirname(__DIR__) . '/.env.example',
    'setup_complete' => dirname(__DIR__) . '/storage/app/.setup_complete',
];

// Check if setup is already complete
if (file_exists($config['setup_complete'])) {
    showAlreadyInstalled();
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleSetup($_POST, $config);
}

// Show setup form
showSetupForm($config);

/**
 * Show the setup form
 */
function showSetupForm($config) {
    $envExists = file_exists($config['env_file']);
    $phpVersion = PHP_VERSION;
    $phpOk = version_compare($phpVersion, '8.1.0', '>=');
    
    // Check PHP extensions
    $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'xml', 'bcmath', 'gd'];
    $missingExtensions = [];
    foreach ($extensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PutraKop Live Chat — Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1E3A5F 0%, #3B82F6 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: #1E3A5F;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 24px; margin-bottom: 8px; }
        .header p { opacity: 0.8; font-size: 14px; }
        .content { padding: 30px; }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .status.ok { background: #D1FAE5; color: #065F46; border: 1px solid #10B981; }
        .status.error { background: #FEE2E2; color: #991B1B; border: 1px solid #EF4444; }
        .status.warning { background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-group small {
            display: block;
            margin-top: 6px;
            color: #6B7280;
            font-size: 12px;
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #3B82F6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563EB;
        }
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        .btn-danger:hover {
            background: #DC2626;
        }
        .section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #E5E7EB;
        }
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .section h3 {
            color: #1E3A5F;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .checklist { list-style: none; }
        .checklist li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checklist li::before {
            content: "✓";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background: #10B981;
            color: white;
            border-radius: 50%;
            font-size: 12px;
        }
        .checklist li.fail::before {
            content: "✗";
            background: #EF4444;
        }
        .footer {
            background: #F3F4F6;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6B7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 PutraKop Live Chat Setup</h1>
            <p>Automatic Installation Wizard</p>
        </div>
        
        <div class="content">
            <!-- System Requirements Check -->
            <div class="section">
                <h3>📋 System Requirements</h3>
                <ul class="checklist">
                    <li class="<?= $phpOk ? '' : 'fail' ?>">
                        PHP Version: <?= $phpVersion ?> <?= $phpOk ? '(OK)' : '(Requires 8.1+)' ?>
                    </li>
                    <?php foreach ($missingExtensions as $ext): ?>
                        <li class="fail">Extension: <?= $ext ?> (Missing)</li>
                    <?php endforeach; ?>
                    <?php if (empty($missingExtensions)): ?>
                        <li>All required extensions installed</li>
                    <?php endif; ?>
                    <li class="<?= is_writable($config['root_dir'] . '/storage') ? '' : 'fail' ?>">
                        Storage folder writable
                    </li>
                    <li class="<?= is_writable($config['root_dir'] . '/bootstrap/cache') ? '' : 'fail' ?>">
                        Cache folder writable
                    </li>
                </ul>
            </div>

            <?php if (!$phpOk || !empty($missingExtensions)): ?>
                <div class="status error">
                    <strong>System requirements not met!</strong><br>
                    Please contact your hosting provider to enable the missing requirements.
                </div>
            <?php endif; ?>

            <!-- Setup Form -->
            <form method="POST" action="">
                <div class="section">
                    <h3>🗄️ Database Configuration</h3>
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <small>Usually "localhost" on shared hosting</small>
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_database" placeholder="username_putrakop" required>
                        <small>From cPanel → MySQL Databases</small>
                    </div>
                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_username" placeholder="username_putrakop_user" required>
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_password" required>
                    </div>
                </div>

                <div class="section">
                    <h3>🌐 Application Settings</h3>
                    <div class="form-group">
                        <label>App URL</label>
                        <input type="url" name="app_url" placeholder="https://yourdomain.com" required>
                        <small>Your website URL with https://</small>
                    </div>
                    <div class="form-group">
                        <label>Gemini API Key</label>
                        <input type="text" name="gemini_api_key" placeholder="AIza..." required>
                        <small>Get from aistudio.google.com</small>
                    </div>
                </div>

                <div class="section">
                    <h3>👤 Admin Account</h3>
                    <div class="form-group">
                        <label>Admin Name</label>
                        <input type="text" name="admin_name" value="Admin" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" placeholder="admin@yourdomain.com" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_password" minlength="8" required>
                        <small>Minimum 8 characters</small>
                    </div>
                </div>

                <?php if ($phpOk && empty($missingExtensions)): ?>
                    <button type="submit" class="btn btn-primary">
                        🚀 Install PutraKop Live Chat
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-danger" disabled>
                        ❌ Fix Requirements First
                    </button>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="footer">
            PutraKop Live Chat System v1.0 | Setup Wizard
        </div>
    </div>
</body>
</html>
<?php
}

/**
 * Handle the setup process
 */
function handleSetup($data, $config) {
    try {
        // Generate APP_KEY
        $appKey = 'base64:' . base64_encode(random_bytes(32));
        
        // Create .env content
        $envContent = generateEnvFile($data, $appKey);
        
        // Write .env file
        if (!file_put_contents($config['env_file'], $envContent)) {
            throw new Exception("Failed to create .env file. Check folder permissions.");
        }
        
        // Connect to database
        $pdo = new PDO(
            "mysql:host={$data['db_host']};dbname={$data['db_database']};charset=utf8mb4",
            $data['db_username'],
            $data['db_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Step 1: Create all tables using raw SQL
        createAllTables($pdo);
        
        // Step 2: Seed default data
        seedData($pdo, $data);
        
        // Step 3: Create admin user
        createAdminUser($pdo, $data);
        
        // Step 4: Try to auto-install dependencies
        $buildLog = tryAutoBuild($config['root_dir']);
        
        // Mark setup as complete
        if (!is_dir(dirname($config['setup_complete']))) {
            mkdir(dirname($config['setup_complete']), 0755, true);
        }
        file_put_contents($config['setup_complete'], date('Y-m-d H:i:s'));
        
        // Show success (with build log)
        showSuccess($data, $buildLog);
        
    } catch (Exception $e) {
        showError($e->getMessage());
    }
}

/**
 * Generate .env file content
 */
function generateEnvFile($data, $appKey) {
    return <<<ENV
APP_NAME="PutraKop Live Chat"
APP_ENV=production
APP_KEY={$appKey}
APP_DEBUG=false
APP_TIMEZONE=Asia/Kuala_Lumpur
APP_URL={$data['app_url']}

APP_LOCALE=ms
APP_FALLBACK_LOCALE=en
APP_MAINTENANCE_DRIVER=file

DB_CONNECTION=mysql
DB_HOST={$data['db_host']}
DB_PORT=3306
DB_DATABASE={$data['db_database']}
DB_USERNAME={$data['db_username']}
DB_PASSWORD={$data['db_password']}

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

QUEUE_CONNECTION=database

GEMINI_API_KEY={$data['gemini_api_key']}
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_MODEL=gemini-2.0-flash
GEMINI_DAILY_LIMIT=1500

FILESYSTEM_DISK=local

SANCTUM_STATEFUL_DOMAINS={$data['app_url']}

DEVICE_FINGERPRINT_ENABLED=true
DEVICE_TRUST_DURATION_DAYS=90

AI_ASSISTANT_ENABLED=true
AI_FALLBACK_TO_CANNED=true

BUSINESS_HOURS_START="09:00"
BUSINESS_HOURS_END="17:00"
BUSINESS_DAYS="mon,tue,wed,thu,fri"

RATING_ENABLED=true
CSP_ENABLED=true
CSP_REPORT_ONLY=false
AUDIT_ENABLED=true
AUDIT_LOG_LEVEL=info

AI_DAILY_LIMIT=1500
AI_RPM_LIMIT=15
AI_TIMEOUT=30
AI_MAX_TOKENS=500

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=info
ENV;
}

/**
 * Create all database tables using raw SQL
 */
function createAllTables($pdo) {
    $tables = getAllTableSQL();
    
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw new Exception("Error creating table '{$tableName}': " . $e->getMessage());
            }
        }
    }
}

/**
 * Get SQL for creating all tables
 */
function getAllTableSQL() {
    return [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(255) UNIQUE NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('customer','agent','manager','admin') DEFAULT 'customer',
            avatar VARCHAR(255) NULL,
            language_preference VARCHAR(5) DEFAULT 'en',
            status ENUM('online','offline','away') DEFAULT 'offline',
            department_id BIGINT UNSIGNED NULL,
            is_active BOOLEAN DEFAULT TRUE,
            last_login_at TIMESTAMP NULL,
            remember_token VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            INDEX idx_role (role),
            INDEX idx_status (status),
            INDEX idx_department_id (department_id)
        ) ENGINE=InnoDB",

        'password_reset_tokens' => "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            email VARCHAR(255) PRIMARY KEY,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL
        ) ENGINE=InnoDB",

        'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(255) PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            payload LONGTEXT NOT NULL,
            last_activity INT NOT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB",
        
        'departments' => "CREATE TABLE IF NOT EXISTS departments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name_en VARCHAR(255) NOT NULL,
            name_bm VARCHAR(255) NOT NULL,
            description_en TEXT NULL,
            description_bm TEXT NULL,
            color VARCHAR(7) DEFAULT '#1E40AF',
            icon VARCHAR(255) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            priority INT DEFAULT 0,
            max_queue_size INT DEFAULT 50,
            max_agents INT DEFAULT 10,
            business_hours JSON NULL,
            ai_config JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            INDEX idx_is_active (is_active),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB",
        
        'conversations' => "CREATE TABLE IF NOT EXISTS conversations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid VARCHAR(36) UNIQUE NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            agent_id BIGINT UNSIGNED NULL,
            department_id BIGINT UNSIGNED NOT NULL,
            status ENUM('pending','queued','active','transferred','closed') DEFAULT 'pending',
            language VARCHAR(5) DEFAULT 'en',
            started_at TIMESTAMP NULL,
            ended_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_customer_id_status (customer_id, status),
            INDEX idx_agent_id_status (agent_id, status),
            INDEX idx_department_id_status (department_id, status)
        ) ENGINE=InnoDB",
        
        'messages' => "CREATE TABLE IF NOT EXISTS messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uuid VARCHAR(36) UNIQUE NOT NULL,
            conversation_id BIGINT UNSIGNED NOT NULL,
            sender_type VARCHAR(255) NOT NULL,
            sender_id BIGINT UNSIGNED NULL,
            content TEXT NOT NULL,
            message_type ENUM('text','image','file','system','ai_response') DEFAULT 'text',
            language VARCHAR(5) DEFAULT 'en',
            is_read BOOLEAN DEFAULT FALSE,
            is_ai_generated BOOLEAN DEFAULT FALSE,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_conversation_id_created_at (conversation_id, created_at),
            INDEX idx_sender_type_sender_id (sender_type, sender_id)
        ) ENGINE=InnoDB",
        
        'department_responses' => "CREATE TABLE IF NOT EXISTS department_responses (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            department_id BIGINT UNSIGNED NOT NULL,
            response_key VARCHAR(100) NOT NULL,
            content_en TEXT NOT NULL,
            content_bm TEXT NOT NULL,
            trigger_keywords JSON NULL,
            priority INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            is_approved BOOLEAN DEFAULT FALSE,
            created_by BIGINT UNSIGNED NOT NULL,
            updated_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_dept_key (department_id, response_key),
            INDEX idx_is_active (is_active),
            INDEX idx_is_approved (is_approved),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB",
        
        'queues' => "CREATE TABLE IF NOT EXISTS queues (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            department_id BIGINT UNSIGNED NOT NULL,
            uuid VARCHAR(36) UNIQUE NOT NULL,
            conversation_id BIGINT UNSIGNED NOT NULL,
            status ENUM('waiting','assigned','cancelled') DEFAULT 'waiting',
            position INT NOT NULL,
            priority_score INT DEFAULT 0,
            estimated_wait_seconds INT NULL,
            started_at TIMESTAMP NOT NULL,
            assigned_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_conversation (conversation_id),
            INDEX idx_department_id_status_position (department_id, status, position)
        ) ENGINE=InnoDB",
        
        'ratings' => "CREATE TABLE IF NOT EXISTS ratings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id BIGINT UNSIGNED UNIQUE NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            feedback TEXT NULL,
            complaint TEXT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        'knowledge_base' => "CREATE TABLE IF NOT EXISTS knowledge_base (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title_en VARCHAR(255) NOT NULL,
            title_bm VARCHAR(255) NULL,
            content_en TEXT NOT NULL,
            content_bm TEXT NULL,
            department_id BIGINT UNSIGNED NULL,
            category VARCHAR(100) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            priority INT DEFAULT 0,
            trigger_keywords JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB",
        
        'settings' => "CREATE TABLE IF NOT EXISTS settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(255) UNIQUE NOT NULL,
            value TEXT NOT NULL,
            type ENUM('string','integer','boolean','json','text') DEFAULT 'string',
            `group` VARCHAR(100) DEFAULT 'general',
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        'audit_logs' => "CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            auditable_type VARCHAR(255) NOT NULL,
            auditable_id BIGINT UNSIGNED NOT NULL,
            event VARCHAR(255) NOT NULL,
            old_values JSON NULL,
            new_values JSON NULL,
            user_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_auditable_type_auditable_id (auditable_type, auditable_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB",
        
        'internal_notes' => "CREATE TABLE IF NOT EXISTS internal_notes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_conversation_id_created_at (conversation_id, created_at)
        ) ENGINE=InnoDB",
        
        'user_devices' => "CREATE TABLE IF NOT EXISTS user_devices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            device_fingerprint VARCHAR(255) NOT NULL,
            device_name VARCHAR(255) NULL,
            device_type VARCHAR(255) NULL,
            browser VARCHAR(255) NULL,
            operating_system VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            is_trusted BOOLEAN DEFAULT FALSE,
            last_active_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            UNIQUE KEY unique_user_fingerprint (user_id, device_fingerprint)
        ) ENGINE=InnoDB",
    ];
}

/**
 * Seed database with sample data
 */
function seedData($pdo, $data) {
    // Insert default departments
    $departments = [
        ['name_en' => 'Insurance', 'name_bm' => 'Insurans', 'color' => '#1E40AF', 'priority' => 1],
        ['name_en' => 'Membership', 'name_bm' => 'Keanggotaan', 'color' => '#059669', 'priority' => 2],
        ['name_en' => 'Finance', 'name_bm' => 'Kewangan', 'color' => '#D97706', 'priority' => 3],
        ['name_en' => 'Technical Support', 'name_bm' => 'Sokongan Teknikal', 'color' => '#DC2626', 'priority' => 4],
        ['name_en' => 'General Inquiry', 'name_bm' => 'Pertanyaan Umum', 'color' => '#7C3AED', 'priority' => 5],
    ];
    
    foreach ($departments as $dept) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name_en, name_bm, color, priority) VALUES (?, ?, ?, ?)");
        $stmt->execute([$dept['name_en'], $dept['name_bm'], $dept['color'], $dept['priority']]);
    }
    
    // Insert default settings
    $settings = [
        ['key' => 'app_name', 'value' => 'PutraKop Live Chat', 'type' => 'string', 'group' => 'general'],
        ['key' => 'business_hours_start', 'value' => '09:00', 'type' => 'string', 'group' => 'business_hours'],
        ['key' => 'business_hours_end', 'value' => '17:00', 'type' => 'string', 'group' => 'business_hours'],
        ['key' => 'ai_daily_limit', 'value' => '1500', 'type' => 'integer', 'group' => 'ai'],
        ['key' => 'max_conversations_per_agent', 'value' => '5', 'type' => 'integer', 'group' => 'chat'],
    ];
    
    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, `value`, `type`, `group`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$setting['key'], $setting['value'], $setting['type'], $setting['group']]);
    }
}

/**
 * Create admin user
 */
function createAdminUser($pdo, $data) {
    // Use PASSWORD_ARGON2ID if available, otherwise fallback to BCRYPT
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    $hashedPassword = password_hash($data['admin_password'], $algo);
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
    $stmt->execute([$data['admin_name'], $data['admin_email'], $hashedPassword]);
}

/**
 * Try to auto-install dependencies and build frontend
 * This attempts to run composer install and npm build on the server
 */
function tryAutoBuild($rootDir) {
    $log = [];
    $execAvailable = function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')));
    $shellExecAvailable = function_exists('shell_exec');
    $procOpenAvailable = function_exists('proc_open');
    
    $runCommand = function($cmd) use ($execAvailable, $shellExecAvailable, $procOpenAvailable) {
        if ($execAvailable) {
            exec($cmd . ' 2>&1', $output, $returnCode);
            return ['output' => implode("\n", $output), 'code' => $returnCode];
        } elseif ($shellExecAvailable) {
            $output = shell_exec($cmd . ' 2>&1');
            return ['output' => $output ?: '(no output)', 'code' => 0];
        } elseif ($procOpenAvailable) {
            $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
            $process = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($process)) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
                $returnCode = proc_close($process);
                return ['output' => $stdout . $stderr, 'code' => $returnCode];
            }
        }
        return ['output' => 'Command execution not available', 'code' => -1];
    };
    
    if (!$execAvailable && !$shellExecAvailable && !$procOpenAvailable) {
        $log[] = ['step' => 'System Check', 'status' => 'warning', 'message' => 'Command execution is disabled on this server. You will need to upload vendor/ and public/build/ manually. See instructions below.'];
        return $log;
    }
    
    // Step 1: Try Composer
    $log[] = ['step' => 'Composer Check', 'status' => 'info', 'message' => 'Attempting to install PHP dependencies...'];
    
    $result = $runCommand("which composer 2>/dev/null || which composer.phar 2>/dev/null || echo 'not_found'");
    $composerPath = trim($result['output']);
    
    if ($composerPath === 'not_found' || empty($composerPath)) {
        // Try downloading composer
        $composerFile = $rootDir . '/composer.phar';
        $log[] = ['step' => 'Composer', 'status' => 'info', 'message' => 'Composer not found. Downloading...'];
        
        $downloadResult = $runCommand("curl -sS https://getcomposer.org/installer -o " . escapeshellarg($composerFile) . " && php " . escapeshellarg($composerFile) . " --install-dir=" . escapeshellarg($rootDir) . " --filename=composer 2>&1");
        
        if (file_exists($rootDir . '/composer')) {
            $composerPath = $rootDir . '/composer';
        } elseif (file_exists($composerFile)) {
            $composerPath = 'php ' . escapeshellarg($composerFile);
        } else {
            $composerPath = '';
        }
    }
    
    if (!empty($composerPath)) {
        $cmd = "$composerPath install --no-dev --optimize-autoloader --no-interaction --working-dir=" . escapeshellarg($rootDir);
        $result = $runCommand($cmd);
        
        if ($result['code'] === 0 && file_exists($rootDir . '/vendor/autoload.php')) {
            $log[] = ['step' => 'Composer', 'status' => 'success', 'message' => 'PHP dependencies installed successfully!'];
        } else {
            $log[] = ['step' => 'Composer', 'status' => 'error', 'message' => 'Composer install failed: ' . substr($result['output'], -500)];
        }
    } else {
        $log[] = ['step' => 'Composer', 'status' => 'error', 'message' => 'Could not install Composer. vendor/ folder is missing.'];
    }
    
    // Step 2: Try npm
    $log[] = ['step' => 'NPM Check', 'status' => 'info', 'message' => 'Attempting to build frontend assets...'];
    
    $nodeCheck = $runCommand("which node 2>/dev/null || echo 'not_found'");
    $nodePath = trim($nodeCheck['output']);
    
    if ($nodePath === 'not_found' || empty($nodePath)) {
        $log[] = ['step' => 'NPM', 'status' => 'warning', 'message' => 'Node.js is not installed on this server. Frontend assets (public/build/) must be uploaded manually.'];
    } else {
        $npmInstall = $runCommand("npm install --no-optional 2>&1 --prefix " . escapeshellarg($rootDir));
        if ($npmInstall['code'] === 0) {
            $log[] = ['step' => 'NPM Install', 'status' => 'success', 'message' => 'NPM packages installed.'];
            
            $npmBuild = $runCommand("npm run build 2>&1 --prefix " . escapeshellarg($rootDir));
            if ($npmBuild['code'] === 0 && is_dir($rootDir . '/public/build')) {
                $log[] = ['step' => 'NPM Build', 'status' => 'success', 'message' => 'Frontend built successfully!'];
            } else {
                $log[] = ['step' => 'NPM Build', 'status' => 'error', 'message' => 'Frontend build failed. You may need to upload public/build/ manually.'];
            }
        } else {
            $log[] = ['step' => 'NPM Install', 'status' => 'error', 'message' => 'NPM install failed. Frontend assets must be uploaded manually.'];
        }
    }
    
    // Step 3: Generate APP_KEY via artisan if possible
    if (file_exists($rootDir . '/vendor/autoload.php') && file_exists($rootDir . '/artisan')) {
        $keyResult = $runCommand("php " . escapeshellarg($rootDir . "/artisan") . " key:generate --force --working-dir=" . escapeshellarg($rootDir) . " 2>&1");
        if ($keyResult['code'] === 0) {
            $log[] = ['step' => 'APP_KEY', 'status' => 'success', 'message' => 'Application key generated.'];
        }
    }
    
    // Check final status
    $vendorExists = file_exists($rootDir . '/vendor/autoload.php');
    $buildExists = is_dir($rootDir . '/public/build');
    
    if ($vendorExists && $buildExists) {
        $log[] = ['step' => 'Complete', 'status' => 'success', 'message' => 'All dependencies installed and frontend built! Your application is ready.'];
    } elseif ($vendorExists) {
        $log[] = ['step' => 'Partial', 'status' => 'warning', 'message' => 'PHP dependencies installed but frontend not built. Try visiting the site — it may work for API routes.'];
    } else {
        $log[] = ['step' => 'Incomplete', 'status' => 'error', 'message' => 'Dependencies not installed. Please follow the manual upload instructions below.'];
    }
    
    return $log;
}

/**
 * Show success message
 */
function showSuccess($data, $buildLog = []) {
    // Check build status
    $rootDir = dirname(__DIR__);
    $vendorOk = file_exists($rootDir . '/vendor/autoload.php');
    $buildOk = is_dir($rootDir . '/public/build');
    $allReady = $vendorOk && $buildOk;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Complete — PutraKop Live Chat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header { text-align: center; padding: 30px; }
        .success-icon { font-size: 60px; margin-bottom: 10px; }
        h1 { color: #065F46; margin-bottom: 8px; font-size: 22px; }
        h2 { color: #1E3A5F; margin: 20px 0 12px; font-size: 16px; }
        p { color: #6B7280; margin-bottom: 15px; padding: 0 30px; font-size: 14px; }
        .links { background: #F3F4F6; padding: 20px 30px; }
        .links a {
            display: block;
            padding: 14px;
            margin: 8px 0;
            background: #3B82F6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
            text-align: center;
        }
        .links a:hover { background: #2563EB; }
        .links a.disabled { background: #9CA3AF; cursor: not-allowed; }
        .log { padding: 20px 30px; text-align: left; }
        .log-item {
            padding: 10px 14px;
            margin: 6px 0;
            border-radius: 6px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .log-item.info { background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; }
        .log-item.success { background: #D1FAE5; color: #065F46; border: 1px solid #10B981; }
        .log-item.warning { background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }
        .log-item.error { background: #FEE2E2; color: #991B1B; border: 1px solid #EF4444; }
        .log-item .icon { font-size: 16px; flex-shrink: 0; }
        .log-item .text { flex: 1; }
        .log-item .step { font-weight: 600; }
        .warning {
            margin: 15px 30px;
            padding: 14px;
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 8px;
            font-size: 13px;
            color: #92400E;
        }
        .danger {
            margin: 15px 30px;
            padding: 14px;
            background: #FEE2E2;
            border: 1px solid #EF4444;
            border-radius: 8px;
            font-size: 13px;
            color: #991B1B;
        }
        .instructions {
            padding: 20px 30px;
            text-align: left;
        }
        .instructions h2 { padding: 0; margin: 15px 0 8px; }
        .instructions ol, .instructions ul {
            padding-left: 20px;
            font-size: 13px;
            color: #374151;
            line-height: 1.8;
        }
        .instructions code {
            background: #F3F4F6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: #DC2626;
        }
        .instructions .url {
            background: #EFF6FF;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            color: #1E40AF;
            display: block;
            margin: 8px 0;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>Database Installation Complete!</h1>
            <p>All 14 tables created, departments seeded, admin account ready.</p>
        </div>
        
        <?php if (!empty($buildLog)): ?>
        <div class="log">
            <h2>Build Status:</h2>
            <?php foreach ($buildLog as $entry): ?>
                <?php
                    $icons = ['info' => 'ℹ️', 'success' => '✅', 'warning' => '⚠️', 'error' => '❌'];
                    $icon = $icons[$entry['status']] ?? 'ℹ️';
                ?>
                <div class="log-item <?= $entry['status'] ?>">
                    <span class="icon"><?= $icon ?></span>
                    <div class="text">
                        <span class="step">[<?= htmlspecialchars($entry['step']) ?>]</span>
                        <?= htmlspecialchars($entry['message']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($allReady): ?>
        <div class="links">
            <h2 style="padding: 0 0 10px; text-align: center;">Everything is ready! Open your application:</h2>
            <a href="<?= $data['app_url'] ?>/chat">💬 Open Customer Chat</a>
            <a href="<?= $data['app_url'] ?>/admin">👨‍💼 Open Admin Panel</a>
            <a href="<?= $data['app_url'] ?>/api/v1/health">🔍 Check Health Status</a>
        </div>
        
        <div class="warning">
            ⚠️ <strong>Important:</strong> Delete this file (<code>setup.php</code>) from your server for security!
        </div>
        
        <?php else: ?>
        
        <div class="danger">
            ⚠️ <strong>Dependencies not installed.</strong> The database is ready, but the PHP packages (<code>vendor/</code>) 
            and/or frontend assets (<code>public/build/</code>) are missing. The app won't work until these are built.
        </div>
        
        <div class="instructions">
            <h2>Option A: GitHub Actions (Easiest — No Software Needed)</h2>
            <ol>
                <li>Create a free account at <a href="https://github.com/signup" target="_blank">github.com</a></li>
                <li>Click <strong>+</strong> → <strong>New repository</strong></li>
                <li>Name it <code>putrakop-chat</code>, make it <strong>Public</strong>, click Create</li>
                <li>Click <strong>"uploading an existing file"</strong></li>
                <li>Upload ALL your project files from your computer</li>
                <li>Click <strong>Commit changes</strong></li>
                <li>Go to <strong>Actions</strong> tab → click <strong>"I understand my workflows, go ahead and enable them"</strong></li>
                <li>The build will start automatically. Wait ~5 minutes for the ✅ green checkmark</li>
                <li>Click the completed build → scroll down to <strong>Artifacts</strong> → download <code>putrakop-livechat-deploy</code></li>
                <li>Extract the ZIP on your computer</li>
                <li>Delete old files on cPanel, then upload everything from the extracted folder</li>
                <li>Also delete <code>setup.php</code> for security</li>
                <li>Visit <span class="url"><?= $data['app_url'] ?>/chat</span></li>
            </ol>
            
            <h2>Option B: cPanel Terminal (If Available)</h2>
            <ol>
                <li>In cPanel, look for <strong>Terminal</strong> (under Advanced)</li>
                <li>If available, run:
                    <br><code>cd ~/public_html && composer install --no-dev --optimize-autoloader && npm install && npm run build</code>
                </li>
            </ol>
            
            <h2>Option C: Ask Your Hosting Provider</h2>
            <ol>
                <li>Contact support and say: <em>"Please enable SSH access and run <code>composer install --no-dev --optimize-autoloader</code> and <code>npm install && npm run build</code> in my public_html directory."</em></li>
            </ol>
            
            <h2>Also: Set Document Root</h2>
            <ol>
                <li>In cPanel → <strong>Domains</strong> → find <code>chat.putrakop.com.my</code></li>
                <li>Change Document Root to: <code>public_html/public</code></li>
                <li>Save</li>
            </ol>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>
<?php
}

/**
 * Show error message
 */
function showError($message) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Error — PutraKop Live Chat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            text-align: center;
        }
        .error-icon { font-size: 80px; margin: 40px 0 20px; }
        h1 { color: #991B1B; margin-bottom: 10px; }
        .error-message {
            margin: 20px 30px;
            padding: 15px;
            background: #FEE2E2;
            border: 1px solid #EF4444;
            border-radius: 8px;
            font-size: 14px;
            color: #991B1B;
            text-align: left;
        }
        .btn {
            display: inline-block;
            margin: 20px 30px 40px;
            padding: 15px 30px;
            background: #3B82F6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">❌</div>
        <h1>Installation Failed</h1>
        <div class="error-message">
            <strong>Error:</strong> <?= htmlspecialchars($message) ?>
        </div>
        <a href="javascript:history.back()" class="btn">← Go Back</a>
    </div>
</body>
</html>
<?php
}

/**
 * Show already installed message
 */
function showAlreadyInstalled() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Installed — PutraKop Live Chat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            text-align: center;
        }
        .icon { font-size: 80px; margin: 40px 0 20px; }
        h1 { color: #374151; margin-bottom: 10px; }
        p { color: #6B7280; margin-bottom: 30px; padding: 0 30px; }
        .links {
            background: #F3F4F6;
            padding: 30px;
        }
        .links a {
            display: block;
            padding: 15px;
            margin: 10px 30px;
            background: #3B82F6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .links a:hover { background: #2563EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">ℹ️</div>
        <h1>Already Installed</h1>
        <p>PutraKop Live Chat is already installed. Delete setup.php for security.</p>
        
        <div class="links">
            <a href="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/chat">💬 Open Customer Chat</a>
            <a href="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/admin">👨‍💼 Open Admin Panel</a>
        </div>
    </div>
</body>
</html>
<?php
}
?>
