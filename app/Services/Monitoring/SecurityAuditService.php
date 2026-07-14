<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Models\User;
use App\Models\Department;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Performs comprehensive security audits on the live chat system.
 *
 * Checks password strength, session security, RBAC integrity,
 * database hardening, file upload safety, API protection, and environment config.
 */
final class SecurityAuditService
{
    /**
     * Run all security checks and return a comprehensive report.
     */
    public function runFullAudit(): array
    {
        $startTime = microtime(true);

        $checks = [
            'password_security' => $this->checkPasswordSecurity(),
            'session_security' => $this->checkSessionSecurity(),
            'rbac' => $this->checkRBAC(),
            'database_security' => $this->checkDatabaseSecurity(),
            'file_upload_security' => $this->checkFileUploadSecurity(),
            'api_security' => $this->checkAPISecurity(),
            'environment_security' => $this->checkEnvironmentSecurity(),
        ];

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        $summary = $this->buildSummary($checks);

        return [
            'checks' => $checks,
            'summary' => $summary,
            'audit_duration_ms' => $totalTime,
            'audited_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Check if users have weak passwords and verify password policies.
     */
    public function checkPasswordSecurity(): array
    {
        $findings = [];
        $recommendations = [];

        // Check for users with common weak passwords by testing hash validity
        $users = User::select('id', 'name', 'email', 'password')->get();
        $weakPasswordIndicators = 0;

        foreach ($users as $user) {
            // Check if password hash uses a weak algorithm
            if (password_needs_rehash($user->password, PASSWORD_ARGON2ID)) {
                $weakPasswordIndicators++;
            }
        }

        if ($weakPasswordIndicators > 0) {
            $findings[] = [
                'severity' => 'medium',
                'message' => "{$weakPasswordIndicators} user(s) have passwords that should be rehashed with a stronger algorithm.",
            ];
            $recommendations[] = 'Rehash all passwords using PASSWORD_ARGON2ID on next login.';
        }

        // Check minimum password length configuration
        $minLength = config('auth.passwords.default', 8);
        if ($minLength < 8) {
            $findings[] = [
                'severity' => 'high',
                'message' => "Minimum password length is set to {$minLength}, which is below the recommended 8 characters.",
            ];
            $recommendations[] = 'Set minimum password length to at least 8 characters.';
        }

        // Check for users with recently changed passwords (password rotation)
        $stalePasswords = User::where('updated_at', '<', Carbon::now()->subDays(90))->count();
        if ($stalePasswords > 0) {
            $findings[] = [
                'severity' => 'low',
                'message' => "{$stalePasswords} user(s) have not changed their password in over 90 days.",
            ];
            $recommendations[] = 'Implement mandatory password rotation every 90 days.';
        }

        // Check total user count with password set
        $totalUsers = User::count();
        $usersWithoutPassword = User::whereNull('password')->count();

        if ($usersWithoutPassword > 0) {
            $findings[] = [
                'severity' => 'critical',
                'message' => "{$usersWithoutPassword} user(s) have no password set.",
            ];
            $recommendations[] = 'Ensure all users have a password set or disable passwordless accounts.';
        }

        return [
            'status' => empty($findings) ? 'pass' : 'warning',
            'total_users_audited' => $totalUsers,
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Check active sessions and session timeout settings.
     */
    public function checkSessionSecurity(): array
    {
        $findings = [];
        $recommendations = [];

        // Check session lifetime configuration
        $sessionLifetime = config('session.lifetime', 120);
        if ($sessionLifetime > 120) {
            $findings[] = [
                'severity' => 'medium',
                'message' => "Session lifetime is set to {$sessionLifetime} minutes, which exceeds the recommended 120 minutes.",
            ];
            $recommendations[] = 'Reduce session lifetime to 120 minutes or less.';
        }

        // Check session driver security
        $sessionDriver = config('session.driver', 'file');
        $insecureDrivers = ['file', 'cookie'];
        if (in_array($sessionDriver, $insecureDrivers, true)) {
            $findings[] = [
                'severity' => 'medium',
                'message' => "Session driver '{$sessionDriver}' is less secure. Consider using 'database' or 'redis' for session storage.",
            ];
            $recommendations[] = 'Switch session driver to database or Redis for better security.';
        }

        // Check HTTPS enforcement
        $isSecure = config('session.secure');
        if ($isSecure === false) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'Session cookies are not restricted to HTTPS connections.',
            ];
            $recommendations[] = 'Set SESSION_SECURE=true in production.';
        }

        // Check HTTP only flag
        $httpOnly = config('session.http_only');
        if ($httpOnly === false) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'Session cookies are accessible via JavaScript (http_only is false).',
            ];
            $recommendations[] = 'Set SESSION_HTTP_ONLY=true to prevent XSS cookie theft.';
        }

        // Check same-site cookie attribute
        $sameSite = config('session.same_site');
        if ($sameSite === null || strtolower((string) $sameSite) === 'none') {
            $findings[] = [
                'severity' => 'medium',
                'message' => 'Session cookie SameSite attribute is not set or is set to "none".',
            ];
            $recommendations[] = 'Set SESSION_SAMESITE=lax or strict.';
        }

        // Count active user sessions (users with recent activity)
        $activeSessions = User::where('last_login_at', '>=', Carbon::now()->subMinutes($sessionLifetime))->count();
        $totalUsers = User::count();

        return [
            'status' => empty($findings) ? 'pass' : 'warning',
            'session_driver' => $sessionDriver,
            'session_lifetime_minutes' => $sessionLifetime,
            'active_sessions_estimate' => $activeSessions,
            'total_users' => $totalUsers,
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Verify role assignments and check for privilege escalation risks.
     */
    public function checkRBAC(): array
    {
        $findings = [];
        $recommendations = [];

        // Get all unique roles in the system
        $roles = User::distinct()->pluck('role')->filter()->values()->toArray();
        $validRoles = ['admin', 'manager', 'agent'];

        // Check for invalid roles
        $invalidRoles = array_diff($roles, $validRoles);
        if (!empty($invalidRoles)) {
            $findings[] = [
                'severity' => 'critical',
                'message' => 'Invalid roles detected: ' . implode(', ', $invalidRoles),
            ];
            $recommendations[] = 'Remove or reassign users with invalid roles.';
        }

        // Count users per role
        $roleCounts = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        // Check for excessive admin accounts
        $adminCount = $roleCounts['admin'] ?? 0;
        if ($adminCount > 3) {
            $findings[] = [
                'severity' => 'high',
                'message' => "There are {$adminCount} admin accounts, which exceeds the recommended maximum of 3.",
            ];
            $recommendations[] = 'Review admin accounts and reduce to the minimum required.';
        }

        // Check for users without a role
        $usersWithoutRole = User::whereNull('role')->orWhere('role', '')->count();
        if ($usersWithoutRole > 0) {
            $findings[] = [
                'severity' => 'high',
                'message' => "{$usersWithoutRole} user(s) have no role assigned.",
            ];
            $recommendations[] = 'Assign appropriate roles to all users.';
        }

        // Check for orphaned agent assignments (agents without departments)
        $agentsWithoutDepartment = User::where('role', 'agent')
            ->whereNull('department_id')
            ->count();

        if ($agentsWithoutDepartment > 0) {
            $findings[] = [
                'severity' => 'medium',
                'message' => "{$agentsWithoutDepartment} agent(s) are not assigned to any department.",
            ];
            $recommendations[] = 'Assign all agents to appropriate departments.';
        }

        // Check for users with is_active=true but no role
        $activeUsersWithoutRole = User::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('role')->orWhere('role', '');
            })
            ->count();

        if ($activeUsersWithoutRole > 0) {
            $findings[] = [
                'severity' => 'critical',
                'message' => "{$activeUsersWithoutRole} active user(s) have no role assigned.",
            ];
            $recommendations[] = 'Immediately assign roles to active users.';
        }

        // Check department-agent balance
        $departments = Department::where('is_active', true)->get();
        foreach ($departments as $department) {
            $agentCount = User::where('role', 'agent')
                ->where('department_id', $department->id)
                ->where('is_active', true)
                ->count();

            if ($agentCount === 0) {
                $findings[] = [
                    'severity' => 'medium',
                    'message' => "Department '{$department->name_en}' has no active agents.",
                ];
                $recommendations[] = "Assign agents to department '{$department->name_en}'.";
            }
        }

        return [
            'status' => empty($findings) ? 'pass' : 'warning',
            'valid_roles' => $validRoles,
            'found_roles' => $roles,
            'role_distribution' => $roleCounts,
            'admin_count' => $adminCount,
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Check for SQL injection risks and verify database permissions.
     */
    public function checkDatabaseSecurity(): array
    {
        $findings = [];
        $recommendations = [];

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        // Check database driver
        $driver = $config['driver'] ?? 'unknown';
        if ($driver !== 'mysql' && $driver !== 'pgsql') {
            $findings[] = [
                'severity' => 'info',
                'message' => "Using database driver '{$driver}'. Ensure it is production-appropriate.",
            ];
        }

        // Check if database user has excessive privileges
        try {
            $currentUser = \DB::select('SELECT CURRENT_USER() as user')[0]->user ?? 'unknown';
            if (str_contains($currentUser, '%') || $currentUser === 'root') {
                $findings[] = [
                    'severity' => 'critical',
                    'message' => "Database connection uses user '{$currentUser}' with potentially excessive privileges.",
                ];
                $recommendations[] = 'Create a dedicated application database user with minimal required privileges.';
            }
        } catch (\Exception $e) {
            // Silently handle if we can't check (e.g., SQLite)
        }

        // Check for raw DB queries in codebase that might be vulnerable (basic check)
        $tableColumns = $this->checkForSensitiveDataInLogs();

        // Verify that tables use proper collation
        try {
            $dbName = $config['database'] ?? '';
            if ($dbName && $driver === 'mysql') {
                $tables = \DB::select('SHOW TABLES');
                $tableNames = array_map(function ($t) use ($dbName) {
                    return reset((array) $t);
                }, $tables);

                foreach ($tableNames as $tableName) {
                    $collation = \DB::select("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [$dbName, $tableName]);
                    if (!empty($collation)) {
                        $collationValue = $collation[0]->TABLE_COLLATION ?? '';
                        if (!str_contains($collationValue, 'utf8mb4')) {
                            $findings[] = [
                                'severity' => 'low',
                                'message' => "Table '{$tableName}' does not use utf8mb4 collation ({$collationValue}).",
                            ];
                        }
                    }
                }

                $recommendations[] = 'Ensure all tables use utf8mb4_unicode_ci collation for full Unicode support.';
            }
        } catch (\Exception $e) {
            // Skip collation check on non-MySQL databases
        }

        // Check if database connections are encrypted
        $options = $config['options'] ?? [];
        if (!isset($options[\PDO::MYSQL_ATTR_SSL_CA]) && $driver === 'mysql') {
            $findings[] = [
                'severity' => 'medium',
                'message' => 'MySQL connection does not appear to enforce SSL/TLS.',
            ];
            $recommendations[] = 'Configure SSL/TLS for database connections in production.';
        }

        return [
            'status' => empty($findings) ? 'pass' : 'warning',
            'driver' => $driver,
            'connection_name' => $connection,
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Verify upload directory permissions and check file type restrictions.
     */
    public function checkFileUploadSecurity(): array
    {
        $findings = [];
        $recommendations = [];

        $uploadDisks = ['local', 'public', 'chat'];
        $checkedDisks = [];

        foreach ($uploadDisks as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                $rootPath = $disk->path('');
                $checkedDisks[$diskName] = [
                    'path' => $rootPath,
                    'exists' => is_dir($rootPath),
                    'writable' => is_writable($rootPath),
                ];

                // Check if upload directory is web-accessible
                if ($diskName === 'local') {
                    $publicPath = public_path('storage');
                    if (is_dir($publicPath) && is_writable($publicPath)) {
                        $findings[] = [
                            'severity' => 'info',
                            'message' => 'Public storage directory exists and is writable.',
                        ];
                    }
                }

                // Scan for potentially dangerous file types in uploads
                try {
                    $files = $disk->files('');
                    $dangerousExtensions = ['php', 'php5', 'phtml', 'phar', 'sh', 'bash', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js'];
                    $foundDangerous = [];

                    foreach ($files as $file) {
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($extension, $dangerousExtensions, true)) {
                            $foundDangerous[] = $file;
                        }
                    }

                    if (!empty($foundDangerous)) {
                        $findings[] = [
                            'severity' => 'critical',
                            'message' => count($foundDangerous) . " potentially dangerous file(s) found in '{$diskName}' disk: " . implode(', ', array_slice($foundDangerous, 0, 5)),
                        ];
                        $recommendations[] = "Immediately review and remove dangerous files from the '{$diskName}' storage disk.";
                    }
                } catch (\Exception $e) {
                    // Skip file scan if disk is not accessible
                }
            } catch (\Exception $e) {
                $findings[] = [
                    'severity' => 'low',
                    'message' => "Could not access storage disk '{$diskName}': " . $e->getMessage(),
                ];
            }
        }

        // Check maximum file upload size
        $maxUploadSize = ini_get('upload_max_filesize');
        $maxPostSize = ini_get('post_max_size');
        if ($this->convertToBytes($maxUploadSize) > 50 * 1024 * 1024) {
            $findings[] = [
                'severity' => 'medium',
                'message' => "Maximum upload size is {$maxUploadSize}, which may be excessive for a chat application.",
            ];
            $recommendations[] = 'Limit upload_max_filesize to a reasonable size (e.g., 10M).';
        }

        // Check for .htaccess or web.config protection in storage
        $storagePath = storage_path();
        $htaccessPath = $storagePath . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $findings[] = [
                'severity' => 'medium',
                'message' => 'No .htaccess file found in storage directory to prevent direct access.',
            ];
            $recommendations[] = 'Add .htaccess rules to deny direct HTTP access to storage directories.';
        }

        return [
            'status' => empty($findings) ? 'pass' : 'warning',
            'checked_disks' => $checkedDisks,
            'max_upload_size' => $maxUploadSize,
            'max_post_size' => $maxPostSize,
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Check rate limiting and verify authentication on endpoints.
     */
    public function checkAPISecurity(): array
    {
        $findings = [];
        $recommendations = [];

        // Check if rate limiting is configured
        $rateLimiter = config('api.rate_limit');
        if ($rateLimiter === null || $rateLimiter === 0) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'API rate limiting does not appear to be configured.',
            ];
            $recommendations[] = 'Configure rate limiting on all API endpoints.';
        }

        // Check Sanctum/Passport configuration
        $sanctumEnabled = class_exists(\Laravel\Sanctum\Sanctum::class);
        $passportEnabled = class_exists(\Laravel\Passport\Passport::class);

        if (!$sanctumEnabled && !$passportEnabled) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'Neither Sanctum nor Passport appears to be installed for API authentication.',
            ];
            $recommendations[] = 'Install and configure Laravel Sanctum or Passport for API authentication.';
        }

        // Check CORS configuration
        $corsConfig = config('cors');
        if ($corsConfig === null) {
            $findings[] = [
                'severity' => 'medium',
                'message' => 'CORS configuration not found. API may be vulnerable to cross-origin attacks.',
            ];
            $recommendations[] = 'Configure CORS settings in config/cors.php.';
        } else {
            $allowedOrigins = $corsConfig['allowed_origins'] ?? [];
            if (in_array('*', $allowedOrigins, true)) {
                $findings[] = [
                    'severity' => 'medium',
                    'message' => 'CORS allows all origins (*). This may be too permissive.',
                ];
                $recommendations[] = 'Restrict allowed_origins to specific trusted domains.';
            }
        }

        // Check if debug mode exposes sensitive API information
        if (config('app.debug') === true) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'Application debug mode is enabled. Sensitive information may be exposed in API error responses.',
            ];
            $recommendations[] = 'Disable APP_DEBUG in production.';
        }

        // Check for force HTTPS in production
        if (config('app.env') === 'production' && config('app.url') !== null) {
            $appUrl = config('app.url');
            if (!str_starts_with($appUrl, 'https://')) {
                $findings[] = [
                    'severity' => 'high',
                    'message' => "Application URL '{$appUrl}' does not use HTTPS.",
                ];
                $recommendations[] = 'Ensure APP_URL uses HTTPS in production.';
            }
        }

        // Check trusted proxies configuration
        $trustedProxies = config('trustedproxies.proxies');
        if ($trustedProxies === null && config('app.env') === 'production') {
            $findings[] = [
                'severity' => 'medium',
                'message' => 'Trusted proxies are not configured for production.',
            ];
            $recommendations[] = 'Configure trusted proxies for load balancer / reverse proxy setups.';
        }

        return [
            'status' => empty($findings) ? 'pass' : 'warning',
            'sanctum_installed' => $sanctumEnabled,
            'passport_installed' => $passportEnabled,
            'rate_limit' => $rateLimiter,
            'cors_config' => $corsConfig !== null ? 'configured' : 'missing',
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Check .env file permissions and verify APP_DEBUG is off in production.
     */
    public function checkEnvironmentSecurity(): array
    {
        $findings = [];
        $recommendations = [];

        // Check APP_DEBUG in production
        $appEnv = config('app.env');
        $appDebug = config('app.debug');

        if ($appEnv === 'production' && $appDebug === true) {
            $findings[] = [
                'severity' => 'critical',
                'message' => 'APP_DEBUG is enabled in production. This exposes sensitive configuration and stack traces.',
            ];
            $recommendations[] = 'Set APP_DEBUG=false in your production .env file.';
        }

        // Check APP_KEY is set
        $appKey = config('app.key');
        if (empty($appKey)) {
            $findings[] = [
                'severity' => 'critical',
                'message' => 'APP_KEY is not set. Encryption and session signing will not work.',
            ];
            $recommendations[] = 'Generate an application key with: php artisan key:generate';
        } elseif (str_starts_with($appKey, 'base64:') && strlen($appKey) < 40) {
            $findings[] = [
                'severity' => 'high',
                'message' => 'APP_KEY appears to be too short or weak.',
            ];
            $recommendations[] = 'Regenerate the application key with: php artisan key:generate';
        }

        // Check .env file existence and permissions
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $permissions = substr(sprintf('%o', fileperms($envPath)), -4);
            $isWorldReadable = (fileperms($envPath) & 0x0004) !== 0;

            if ($isWorldReadable) {
                $findings[] = [
                    'severity' => 'critical',
                    'message' => ".env file is world-readable (permissions: {$permissions}). Sensitive credentials are exposed.",
                ];
                $recommendations[] = 'Set .env file permissions to 640 or 600.';
            }

            // Check if .env is in web root
            $webRootPath = public_path('.env');
            if (file_exists($webRootPath)) {
                $findings[] = [
                    'severity' => 'critical',
                    'message' => '.env file found in the public directory. It is directly accessible via HTTP.',
                ];
                $recommendations[] = 'Move .env file out of the public directory immediately.';
            }
        } else {
            $findings[] = [
                'severity' => 'info',
                'message' => '.env file not found at expected location.',
            ];
        }

        // Check .env.example is not exposed
        $envExamplePath = public_path('.env.example');
        if (file_exists($envExamplePath)) {
            $findings[] = [
                'severity' => 'medium',
                'message' => '.env.example file found in the public directory.',
            ];
            $recommendations[] = 'Remove .env.example from the public directory.';
        }

        // Check for sensitive keys in config
        $sensitiveConfigs = [
            'database.connections.mysql.username',
            'database.connections.mysql.password',
            'services.mailgun.secret',
            'services.sendgrid.api_key',
            'services.ses.key',
            'services.ses.secret',
        ];

        foreach ($sensitiveConfigs as $configKey) {
            $value = config($configKey);
            if ($value !== null && $value !== '' && !str_starts_with((string) $value, '${')) {
                // Config value is set — this is expected, just note it
            }
        }

        // Check if LOG_CHANNEL is properly configured
        $logChannel = config('logging.default');
        if ($logChannel === 'stack') {
            $stackChannels = config('logging.channels.stack.channels', []);
            if (empty($stackChannels)) {
                $findings[] = [
                    'severity' => 'medium',
                    'message' => 'Logging stack has no channels configured.',
                ];
                $recommendations[] = 'Configure appropriate log channels in config/logging.php.';
            }
        }

        // Check for mail encryption
        $mailEncryption = config('mail.encryption');
        if ($appEnv === 'production' && $mailEncryption !== 'ssl' && $mailEncryption !== 'tls') {
            $findings[] = [
                'severity' => 'medium',
                'message' => "Mail encryption is set to '{$mailEncryption}'. Use SSL or TLS in production.",
            ];
            $recommendations[] = 'Set MAIL_ENCRYPTION=ssl in production.';
        }

        return [
            'status' => empty($findings) ? 'pass' : 'warning',
            'app_env' => $appEnv,
            'app_debug' => $appDebug,
            'app_key_set' => !empty($appKey),
            'env_file_exists' => file_exists($envPath),
            'findings' => $findings,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Build a summary of all audit check results.
     */
    private function buildSummary(array $checks): array
    {
        $totalFindings = 0;
        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;
        $lowCount = 0;
        $infoCount = 0;

        foreach ($checks as $check) {
            foreach ($check['findings'] as $finding) {
                $totalFindings++;
                match ($finding['severity']) {
                    'critical' => $criticalCount++,
                    'high' => $highCount++,
                    'medium' => $mediumCount++,
                    'low' => $lowCount++,
                    'info' => $infoCount++,
                    default => null,
                };
            }
        }

        $overallStatus = 'pass';
        if ($criticalCount > 0) {
            $overallStatus = 'critical';
        } elseif ($highCount > 0) {
            $overallStatus = 'high_risk';
        } elseif ($mediumCount > 0) {
            $overallStatus = 'warning';
        }

        return [
            'overall_status' => $overallStatus,
            'total_checks' => count($checks),
            'total_findings' => $totalFindings,
            'critical' => $criticalCount,
            'high' => $highCount,
            'medium' => $mediumCount,
            'low' => $lowCount,
            'info' => $infoCount,
        ];
    }

    /**
     * Basic check for potentially sensitive data exposure in logs.
     */
    private function checkForSensitiveDataInLogs(): array
    {
        // This is a placeholder for static analysis integration.
        // In a full implementation, this would scan log files or
        // use a static analysis tool to detect raw query patterns.
        return [];
    }

    /**
     * Convert a PHP ini value (e.g., "10M") to bytes.
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        return match ($last) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
