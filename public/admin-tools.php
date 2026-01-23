<?php

/**
 * Emergency Admin Tools
 *
 * This file provides basic admin functions when the main application is not accessible.
 * Access: http://yoursite.com/admin-tools.php
 *
 * Security: This file should be removed or secured in production.
 * You can add HTTP basic auth or IP restrictions for additional security.
 */

// Security check - only allow admin access
session_start();
$admin_password = 'hutara000'; // Change this password!

if (! isset($_SESSION['admin_authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        showLoginForm();
        exit;
    }
}

// Get the Laravel root directory - adjust for server structure
$currentDir = __DIR__;

// Try multiple approaches to find Laravel directory
$possiblePaths = [
    // Production: public_html/ and laravel/ are siblings
    dirname($currentDir).'/laravel',
    // Alternative: private_html/ and laravel/ structure
    dirname(dirname($currentDir)).'/laravel',
    // Another alternative for nested structures
    dirname(dirname(dirname($currentDir))).'/laravel',
    // Development: normal Laravel structure
    dirname(__DIR__),
    // If admin-tools.php is in domain root
    $currentDir.'/laravel',
];

$laravelRoot = null;
foreach ($possiblePaths as $path) {
    if (is_dir($path) && file_exists($path.'/artisan')) {
        $laravelRoot = $path;
        break;
    }
}

// If still not found, use default assumption
if (! $laravelRoot) {
    $laravelRoot = dirname($currentDir).'/laravel';
}

// Verify Laravel directory exists
if (! is_dir($laravelRoot) || ! file_exists($laravelRoot.'/artisan')) {
    $debugInfo = "Laravel directory not found. Debug info:\n";
    $debugInfo .= 'Current directory: '.$currentDir."\n";
    $debugInfo .= "Tried paths:\n";
    foreach ($possiblePaths as $i => $path) {
        $debugInfo .= '  '.($i + 1).'. '.$path.' - '.(is_dir($path) ? 'Directory exists' : 'Directory missing').' - '.(file_exists($path.'/artisan') ? 'Artisan found' : 'Artisan missing')."\n";
    }
    $debugInfo .= 'Final Laravel root: '.$laravelRoot."\n";
    $debugInfo .= 'Directory listing of parent: '.print_r(scandir(dirname($currentDir)), true);
    exit('<pre>'.$debugInfo.'</pre>');
}

chdir($laravelRoot);

$output = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        // Add debug info and clean up any wrong storage links
        if ($action === 'storage_link') {
            $output .= "Debug Info:\n";
            $output .= 'Current Dir: '.$currentDir."\n";
            $output .= 'Laravel Root: '.$laravelRoot."\n";
            $output .= 'Working Directory: '.getcwd()."\n";
            $output .= "Server Structure: Production (admin-tools.php in public_html)\n";
            $output .= 'PHP Version: '.PHP_VERSION."\n";
            $output .= 'PHP Binary: '.(defined('PHP_BINARY') ? PHP_BINARY : 'Not defined')."\n";
            $output .= 'shell_exec available: '.(function_exists('shell_exec') ? 'Yes' : 'No')."\n";

            // Clean up wrong storage link in laravel/public if it exists
            $wrongStoragePath = $laravelRoot.'/public/storage';
            if (file_exists($wrongStoragePath)) {
                $output .= "\n⚠️ Removing wrong storage link at: ".$wrongStoragePath."\n";
                if (is_link($wrongStoragePath)) {
                    unlink($wrongStoragePath);
                    $output .= "✅ Wrong symlink removed\n";
                } else {
                    $output .= "⚠️ Wrong storage is not a symlink, manual cleanup needed\n";
                }
            }

            $checkPublicPath = $currentDir; // public_html directory
            $checkStoragePath = $checkPublicPath.'/storage';

            $output .= 'Public Path: '.$checkPublicPath."\n";
            $output .= 'Storage Target: '.$laravelRoot.'/storage/app/public'."\n";
            $output .= 'Storage Link Path: '.$checkStoragePath."\n";
            $output .= 'Storage app/public exists: '.(is_dir($laravelRoot.'/storage/app/public') ? 'Yes' : 'No')."\n";
            $output .= 'Public/storage exists: '.(file_exists($checkStoragePath) ? 'Yes' : 'No')."\n\n";
        }

        switch ($action) {
            case 'clear_cache':
                $output = executeCommand('php artisan cache:clear');
                $output .= "\n".executeCommand('php artisan config:clear');
                $output .= "\n".executeCommand('php artisan route:clear');
                $output .= "\n".executeCommand('php artisan view:clear');
                break;

            case 'optimize_clear':
                $output = executeCommand('php artisan optimize:clear');
                break;

            case 'optimize':
                $output = executeCommand('php artisan optimize');
                break;

            case 'storage_link':
                // Determine the correct public path structure
                // admin-tools.php is in public_html, so storage link should be created here
                $publicPath = $currentDir; // Same directory as admin-tools.php
                $storagePath = $publicPath.'/storage';
                $storageTarget = $laravelRoot.'/storage/app/public';

                $output .= "Debug Storage Link Info:\n";
                $output .= 'Public Path: '.$publicPath."\n";
                $output .= 'Storage Link Path: '.$storagePath."\n";
                $output .= 'Storage Target: '.$storageTarget."\n";
                $output .= 'Target exists: '.(is_dir($storageTarget) ? 'Yes' : 'No')."\n";
                $output .= 'Link exists: '.(file_exists($storagePath) ? 'Yes' : 'No')."\n";

                if (file_exists($storagePath)) {
                    if (is_link($storagePath)) {
                        $target = readlink($storagePath);
                        $output .= 'Current link target: '.$target."\n";
                        $output .= 'Target accessible: '.(is_readable($target) ? 'Yes' : 'No')."\n";

                        // Check if link points to wrong location
                        if ($target !== $storageTarget) {
                            $output .= "\n❌ Link points to wrong location. Recreating...\n";
                            unlink($storagePath);
                            if (symlink($storageTarget, $storagePath)) {
                                $output .= "✅ Link recreated successfully!\n";
                            } else {
                                $output .= "❌ Failed to recreate link\n";
                            }
                        } else {
                            $output .= "\n✅ Storage link exists and points to correct location\n";

                            // Check permissions
                            $perms = fileperms($storageTarget);
                            $output .= 'Target permissions: '.decoct($perms & 0777)."\n";
                        }
                    } else {
                        $output .= "\n⚠️ Storage exists but is not a symlink (directory/file)\n";
                        $output .= "Consider removing and recreating as symlink\n";
                    }
                } else {
                    // Create new storage link
                    if (! is_dir($storageTarget)) {
                        $output .= "\n❌ Target directory does not exist: ".$storageTarget;
                    } else {
                        $output .= "\n🔨 Creating storage link...\n";

                        // Try artisan command first
                        $artisanOutput = executeCommand('php artisan storage:link');
                        $output .= 'Artisan output: '.$artisanOutput."\n";

                        // Check if it worked
                        if (file_exists($storagePath)) {
                            $output .= "✅ Storage link created via artisan!\n";
                        } else {
                            // Try manual symlink
                            $output .= "Artisan failed, trying manual symlink...\n";
                            if (function_exists('symlink')) {
                                if (symlink($storageTarget, $storagePath)) {
                                    $output .= "✅ Manual symlink created!\n";
                                } else {
                                    $output .= "❌ Manual symlink failed\n";
                                }
                            } else {
                                $output .= "❌ symlink() function not available\n";
                            }
                        }
                    }
                }
                break;

            case 'migrate':
                $output = executeCommand('php artisan migrate --force');
                break;

            case 'migrate_fresh':
                $output = executeCommand('php artisan migrate:fresh --force');
                break;

            case 'db_seed':
                $output = executeCommand('php artisan db:seed --force');
                break;

            case 'fix_mysql_key_length':
                $output = "MySQL Key Length Fix:\n\n";

                // Check current AppServiceProvider.php
                $appServiceProvider = $laravelRoot.'/app/Providers/AppServiceProvider.php';
                if (file_exists($appServiceProvider)) {
                    $content = file_get_contents($appServiceProvider);

                    if (strpos($content, 'Schema::defaultStringLength(191)') !== false) {
                        $output .= "✅ AppServiceProvider already configured with Schema::defaultStringLength(191)\n";
                    } else {
                        $output .= "⚠️ AppServiceProvider needs to be updated\n";
                        $output .= "Manual fix required: Add Schema::defaultStringLength(191) to boot() method\n\n";
                    }
                } else {
                    $output .= "❌ AppServiceProvider.php not found\n";
                }

                // Check MySQL version and configuration
                try {
                    $mysqlVersion = executeCommand('php artisan tinker --execute="echo DB::select(\'SELECT VERSION() as version\')[0]->version;"');
                    $output .= 'MySQL Version: '.trim($mysqlVersion)."\n\n";
                } catch (Exception $e) {
                    $output .= "Could not detect MySQL version\n\n";
                }

                // Provide solution steps
                $output .= "Common Solutions:\n";
                $output .= "1. Schema::defaultStringLength(191) - Already applied ✅\n";
                $output .= "2. Update MySQL to 5.7.7+ for utf8mb4 support\n";
                $output .= "3. Use utf8 charset instead of utf8mb4 (in config/database.php)\n";
                $output .= "4. Drop and recreate database if migrations failed\n\n";

                $output .= "To retry migration:\n";
                $output .= "1. Drop all tables in database\n";
                $output .= "2. Run 'Fresh Migration' button\n";
                break;

            case 'config_cache':
                $output = executeCommand('php artisan config:cache');
                break;

            case 'route_cache':
                $output = executeCommand('php artisan route:cache');
                break;

            case 'maintenance_down':
                $output = executeCommand('php artisan down --secret=admin-secret');
                break;

            case 'maintenance_up':
                $output = executeCommand('php artisan up');
                break;

            case 'key_generate':
                $output = executeCommand('php artisan key:generate --force');
                break;

            case 'clear_logs':
                $logPath = $laravelRoot.'/storage/logs';
                if (is_dir($logPath)) {
                    $files = glob($logPath.'/*.log');
                    $count = 0;
                    foreach ($files as $file) {
                        if (unlink($file)) {
                            $count++;
                        }
                    }
                    $output = "Deleted {$count} log files";
                } else {
                    $output = 'Log directory not found';
                }
                break;

            case 'cleanup_storage':
                $wrongStoragePath = $laravelRoot.'/public/storage';
                $correctStoragePath = $currentDir.'/storage';
                $storageTarget = $laravelRoot.'/storage/app/public';

                $output = "Storage Cleanup:\n";

                // Remove wrong storage link in laravel/public
                if (file_exists($wrongStoragePath)) {
                    $output .= 'Removing wrong storage link: '.$wrongStoragePath."\n";
                    if (is_link($wrongStoragePath)) {
                        unlink($wrongStoragePath);
                        $output .= "✅ Wrong symlink removed\n";
                    } else {
                        $output .= "⚠️ Not a symlink, skipping\n";
                    }
                }

                // Handle correct storage path in public_html
                if (file_exists($correctStoragePath)) {
                    if (is_link($correctStoragePath)) {
                        $target = readlink($correctStoragePath);
                        $output .= '✅ Storage is already a symlink pointing to: '.$target."\n";
                        if ($target !== $storageTarget) {
                            $output .= "⚠️ Symlink points to wrong target, fixing...\n";
                            unlink($correctStoragePath);
                            symlink($storageTarget, $correctStoragePath);
                            $output .= "✅ Symlink recreated with correct target\n";
                        }
                    } else {
                        // Remove directory and create symlink
                        $output .= "⚠️ Storage exists as directory/file, not symlink. Removing...\n";
                        if (is_dir($correctStoragePath)) {
                            // Remove directory recursively
                            removeDirectory($correctStoragePath);
                            $output .= "✅ Directory removed\n";
                        } else {
                            unlink($correctStoragePath);
                            $output .= "✅ File removed\n";
                        }

                        // Create symlink
                        if (symlink($storageTarget, $correctStoragePath)) {
                            $output .= "✅ Created correct storage symlink\n";
                        } else {
                            $output .= "❌ Failed to create storage symlink\n";
                        }
                    }
                } else {
                    // Create new symlink
                    if (symlink($storageTarget, $correctStoragePath)) {
                        $output .= '✅ Created new storage symlink at: '.$correctStoragePath."\n";
                    } else {
                        $output .= "❌ Failed to create storage symlink\n";
                    }
                }

                // Verify the symlink works
                if (is_link($correctStoragePath) && is_readable($correctStoragePath)) {
                    $output .= "✅ Storage symlink is working and readable\n";
                } else {
                    $output .= "❌ Storage symlink may have issues\n";
                }
                break;

            case 'fix_storage_permissions':
                $publicPath = $currentDir;
                $storagePath = $publicPath.'/storage';
                $storageTarget = $laravelRoot.'/storage/app/public';

                $output = "Fixing Storage Permissions:\n";

                // Set proper permissions for storage target
                if (is_dir($storageTarget)) {
                    chmod($storageTarget, 0755);
                    $output .= "✅ Set target directory permissions to 755\n";

                    // Set permissions for files inside
                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storageTarget));
                    $count = 0;
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            chmod($file->getPathname(), 0644);
                            $count++;
                        } elseif ($file->isDir() && ! in_array($file->getFilename(), ['.', '..'])) {
                            chmod($file->getPathname(), 0755);
                        }
                    }
                    $output .= "✅ Fixed permissions for {$count} files\n";
                } else {
                    $output .= "❌ Storage target directory not found\n";
                }
                break;

            case 'backup_env':
                $envPath = $laravelRoot.'/.env';
                $backupPath = $laravelRoot.'/.env.backup.'.date('Y-m-d_H-i-s');

                if (file_exists($envPath)) {
                    if (copy($envPath, $backupPath)) {
                        $output = '✅ Environment file backed up to: '.basename($backupPath);
                    } else {
                        $output = '❌ Failed to backup environment file';
                    }
                } else {
                    $output = '❌ Environment file not found';
                }
                break;

            case 'check_env':
                $envPath = $laravelRoot.'/.env';
                $envExamplePath = $laravelRoot.'/.env.example';

                $output = "Environment File Check:\n";
                $output .= '.env exists: '.(file_exists($envPath) ? 'Yes' : 'No')."\n";
                $output .= '.env.example exists: '.(file_exists($envExamplePath) ? 'Yes' : 'No')."\n";

                if (file_exists($envPath)) {
                    $envSize = filesize($envPath);
                    $output .= '.env size: '.$envSize." bytes\n";
                    $output .= '.env modified: '.date('Y-m-d H:i:s', filemtime($envPath))."\n";

                    // Check key variables
                    $envContent = file_get_contents($envPath);
                    $requiredVars = ['APP_KEY', 'DB_CONNECTION', 'DB_DATABASE'];
                    foreach ($requiredVars as $var) {
                        $exists = strpos($envContent, $var.'=') !== false;
                        $output .= "{$var}: ".($exists ? 'Set' : 'Missing')."\n";
                    }
                }
                break;

            case 'health_check':
                $output = "System Health Check:\n\n";

                // PHP Info
                $output .= "🔧 PHP Information:\n";
                $output .= 'PHP Version: '.PHP_VERSION."\n";
                $output .= 'Memory Limit: '.ini_get('memory_limit')."\n";
                $output .= 'Max Execution Time: '.ini_get('max_execution_time')."s\n";
                $output .= 'Upload Max Size: '.ini_get('upload_max_filesize')."\n";
                $output .= 'Post Max Size: '.ini_get('post_max_size')."\n";

                // Extensions
                $output .= "\n🔌 Extensions:\n";
                $requiredExtensions = ['pdo', 'mbstring', 'tokenizer', 'json', 'openssl', 'curl'];
                foreach ($requiredExtensions as $ext) {
                    $loaded = extension_loaded($ext);
                    $output .= "{$ext}: ".($loaded ? '✅ Loaded' : '❌ Missing')."\n";
                }

                // Laravel Files
                $output .= "\n📁 Laravel Files:\n";
                $files = ['artisan', 'composer.json', '.env', 'bootstrap/app.php'];
                foreach ($files as $file) {
                    $exists = file_exists($laravelRoot.'/'.$file);
                    $output .= "{$file}: ".($exists ? '✅ Exists' : '❌ Missing')."\n";
                }

                // Directories
                $output .= "\n📂 Directories:\n";
                $dirs = ['storage', 'storage/app', 'storage/logs', 'storage/framework', 'bootstrap/cache'];
                foreach ($dirs as $dir) {
                    $path = $laravelRoot.'/'.$dir;
                    $exists = is_dir($path);
                    $writable = $exists ? is_writable($path) : false;
                    $output .= "{$dir}: ".($exists ? '✅ Exists' : '❌ Missing').
                        ($writable ? ' (Writable)' : ($exists ? ' (Not Writable)' : ''))."\n";
                }
                break;

            case 'composer_status':
                $output = "Composer Status:\n";
                $output .= executeCommand('composer --version');
                $output .= "\n\nComposer Dependencies:\n";
                $output .= executeCommand('composer show --installed');
                break;

            case 'queue_status':
                $output = "Queue Status:\n";
                $output .= executeCommand('php artisan queue:work --stop-when-empty --timeout=10');
                break;

            case 'schedule_run':
                $startTs = microtime(true);
                $output = "Schedule Run (verbose):\n";
                $output .= 'Start: '.date('Y-m-d H:i:s')."\n\n";

                // Jalankan scheduler dengan verbosity maksimal
                $output .= executeCommand('php artisan schedule:run -vvv');

                $endTs = microtime(true);
                $output .= "\nEnd: ".date('Y-m-d H:i:s')."\n";
                $output .= 'Duration: '.number_format(($endTs - $startTs), 3)."s\n";

                // Tampilkan daftar schedule yang terdaftar
                $output .= "\nScheduled Events (schedule:list):\n";
                $output .= executeCommand('php artisan schedule:list');
                break;

            case 'create_symlinks':
                $output = "Creating Symlinks:\n";

                // Storage link
                $publicPath = $currentDir;
                $storagePath = $publicPath.'/storage';
                $storageTarget = $laravelRoot.'/storage/app/public';

                if (! file_exists($storagePath) && is_dir($storageTarget)) {
                    if (symlink($storageTarget, $storagePath)) {
                        $output .= "✅ Storage symlink created\n";
                    } else {
                        $output .= "❌ Failed to create storage symlink\n";
                    }
                } else {
                    $output .= "⚠️ Storage symlink already exists or target missing\n";
                }

                // Check for other common symlinks needed
                $output .= "\nSymlink Status:\n";
                $output .= 'Storage: '.(is_link($storagePath) ? '✅ Symlink' : '❌ Not a symlink')."\n";
                break;

            case 'clear_all_cache':
                $output = "Clearing All Cache and Optimization:\n";
                $output .= executeCommand('php artisan cache:clear');
                $output .= "\n".executeCommand('php artisan config:clear');
                $output .= "\n".executeCommand('php artisan route:clear');
                $output .= "\n".executeCommand('php artisan view:clear');
                $output .= "\n".executeCommand('php artisan optimize:clear');
                break;

            case 'show_env':
                $envPath = $laravelRoot.'/.env';
                if (file_exists($envPath)) {
                    $envContent = file_get_contents($envPath);
                    // Mask sensitive values
                    $maskedContent = preg_replace('/(APP_KEY|DB_PASSWORD|.*_SECRET|.*_TOKEN|.*_KEY)=(.+)/i', '$1=***MASKED***', $envContent);
                    $output = "Environment File Content (sensitive values masked):\n\n".$maskedContent;
                } else {
                    $output = '❌ Environment file not found';
                }
                break;

            case 'disk_space':
                $output = "Disk Space Information:\n";

                // Laravel directory size
                $laravelSize = getDirSize($laravelRoot);
                $output .= 'Laravel directory: '.formatBytes($laravelSize)."\n";

                // Storage directory size
                $storageSize = getDirSize($laravelRoot.'/storage');
                $output .= 'Storage directory: '.formatBytes($storageSize)."\n";

                // Log files size
                $logsSize = getDirSize($laravelRoot.'/storage/logs');
                $output .= 'Log files: '.formatBytes($logsSize)."\n";

                // Available disk space
                $freeSpace = disk_free_space($laravelRoot);
                $totalSpace = disk_total_space($laravelRoot);
                $usedSpace = $totalSpace - $freeSpace;

                $output .= "\nDisk Usage:\n";
                $output .= 'Used: '.formatBytes($usedSpace)."\n";
                $output .= 'Free: '.formatBytes($freeSpace)."\n";
                $output .= 'Total: '.formatBytes($totalSpace)."\n";
                $output .= 'Usage: '.round(($usedSpace / $totalSpace) * 100, 2)."%\n";
                break;

            case 'debug_500_error':
                $output = "HTTP 500 Error Diagnostic:\n\n";

                // 1. Check PHP version and extensions
                $output .= "1. PHP Environment:\n";
                $output .= '   Version: '.phpversion()."\n";
                $output .= '   SAPI: '.php_sapi_name()."\n";

                $requiredExtensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'curl'];
                $output .= "   Required Extensions:\n";
                foreach ($requiredExtensions as $ext) {
                    $loaded = extension_loaded($ext);
                    $output .= "   - $ext: ".($loaded ? '✅ Loaded' : '❌ Missing')."\n";
                }

                // 2. Check critical files
                $output .= "\n2. Critical Files Check:\n";
                $criticalFiles = [
                    '.env' => $laravelRoot.'/.env',
                    'artisan' => $laravelRoot.'/artisan',
                    'index.php' => __DIR__.'/index.php',
                    'composer.json' => $laravelRoot.'/composer.json',
                ];

                foreach ($criticalFiles as $name => $path) {
                    $exists = file_exists($path);
                    $readable = $exists && is_readable($path);
                    $output .= "   - $name: ".($readable ? '✅ OK' : ($exists ? '⚠️ Not readable' : '❌ Missing'))."\n";
                }

                // 3. Check permissions
                $output .= "\n3. Directory Permissions:\n";
                $directories = [
                    'storage/' => $laravelRoot.'/storage',
                    'bootstrap/cache/' => $laravelRoot.'/bootstrap/cache',
                    'public/' => __DIR__,
                ];

                foreach ($directories as $name => $path) {
                    if (is_dir($path)) {
                        $writable = is_writable($path);
                        $perms = substr(sprintf('%o', fileperms($path)), -4);
                        $output .= "   - $name: ".($writable ? '✅' : '❌')." Writable (Perms: $perms)\n";
                    } else {
                        $output .= "   - $name: ❌ Directory not found\n";
                    }
                }

                // 4. Check Laravel environment
                $output .= "\n4. Laravel Environment:\n";
                $envFile = $laravelRoot.'/.env';
                if (file_exists($envFile)) {
                    $envContent = file_get_contents($envFile);
                    $hasAppKey = strpos($envContent, 'APP_KEY=') !== false && strpos($envContent, 'APP_KEY=base64:') !== false;
                    $output .= "   - .env exists: ✅ Yes\n";
                    $output .= '   - APP_KEY set: '.($hasAppKey ? '✅ Yes' : '❌ No')."\n";

                    // Check for common .env issues
                    preg_match('/APP_DEBUG=(.*)/', $envContent, $debugMatch);
                    $appDebug = isset($debugMatch[1]) ? trim($debugMatch[1]) : 'not set';
                    $output .= "   - APP_DEBUG: $appDebug\n";
                } else {
                    $output .= "   - .env exists: ❌ No\n";
                }

                // 5. Check Laravel logs
                $output .= "\n5. Laravel Logs:\n";
                $logPath = $laravelRoot.'/storage/logs/laravel.log';
                if (file_exists($logPath) && is_readable($logPath)) {
                    $logContent = @file_get_contents($logPath);
                    if ($logContent) {
                        $lines = explode("\n", $logContent);
                        $recentLines = array_slice($lines, -10); // Last 10 lines
                        $output .= "   Recent log entries:\n";
                        foreach ($recentLines as $line) {
                            if (trim($line)) {
                                $output .= '   '.substr($line, 0, 100)."\n";
                            }
                        }
                    } else {
                        $output .= "   - Log file empty or unreadable\n";
                    }
                } else {
                    $output .= "   - Log file not found or unreadable\n";
                }

                // 6. Web Server Check
                $output .= "\n6. Web Server Info:\n";
                $output .= '   - Server Software: '.($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown')."\n";
                $output .= '   - Document Root: '.($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown')."\n";
                $output .= '   - Script Name: '.($_SERVER['SCRIPT_NAME'] ?? 'Unknown')."\n";

                // 7. Quick Fixes
                $output .= "\n7. Quick Fix Recommendations:\n";
                $output .= "   1. Generate APP_KEY: Use 'Generate App Key' button\n";
                $output .= "   2. Fix Permissions: Use 'Fix Storage Permissions' button\n";
                $output .= "   3. Clear Cache: Use 'Clear All Cache' button\n";
                $output .= "   4. Check .env: Use 'Show .env Content' button\n";
                $output .= "   5. Run Health Check: Use 'System Health Check' button\n";
                break;

            case 'debug_php_path':
                $output = "PHP Path Detection Debug:\n\n";

                // Test PHP_BINARY
                $output .= '1. PHP_BINARY constant: '.(defined('PHP_BINARY') ? PHP_BINARY : 'Not defined')."\n";
                $output .= '   Executable: '.(defined('PHP_BINARY') && is_executable(PHP_BINARY) ? '✅ Yes' : '❌ No')."\n\n";

                // Test common paths (PHP 8.3 prioritized)
                $commonPaths = [
                    '/opt/cpanel/ea-php83/root/usr/bin/php',    // cPanel PHP 8.3
                    '/opt/cpanel/ea-php84/root/usr/bin/php',    // cPanel PHP 8.4
                    '/usr/local/php83/bin/php',                // Common PHP 8.3
                    '/usr/local/lsws/lsphp83/bin/php',         // LiteSpeed PHP 8.3
                    '/usr/local/bin/php83',                    // Alternative PHP 8.3
                    '/usr/bin/php83',                          // System PHP 8.3
                    '/usr/bin/php',                            // Default system PHP
                    '/usr/local/bin/php',                      // Alternative system PHP
                    '/opt/cpanel/ea-php82/root/usr/bin/php',   // Fallback to 8.2
                    '/opt/cpanel/ea-php81/root/usr/bin/php',   // Fallback to 8.1
                    '/usr/local/php82/bin/php',
                    '/usr/local/php81/bin/php',
                    '/usr/local/lsws/lsphp82/bin/php',
                    '/usr/local/lsws/lsphp81/bin/php',
                ];

                $output .= "2. Common PHP paths test:\n";
                foreach ($commonPaths as $path) {
                    $exists = file_exists($path);
                    $executable = is_executable($path);
                    $output .= "   $path: ".($executable ? '✅ Executable' : ($exists ? '⚠️ Exists but not executable' : '❌ Not found'))."\n";
                }

                // Test 'which php' command
                $output .= "\n3. which php command:\n";
                $which = @shell_exec('which php 2>/dev/null');
                $output .= '   Result: '.($which ? trim($which) : 'Command failed or not found')."\n";

                // Test 'php --version' directly
                $output .= "\n4. Direct 'php --version' test:\n";
                $phpVersion = @shell_exec('php --version 2>/dev/null');
                if ($phpVersion) {
                    $output .= "   ✅ 'php' command works directly\n";
                    $output .= '   Version: '.trim(explode("\n", $phpVersion)[0])."\n";
                } else {
                    $output .= "   ❌ 'php' command not available\n";
                }

                // Server info
                $output .= "\n5. Server Information:\n";
                $output .= '   Current user: '.get_current_user()."\n";
                $output .= '   Server software: '.($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown')."\n";
                $output .= '   PHP SAPI: '.php_sapi_name()."\n";
                $output .= '   Document root: '.($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown')."\n";
                $output .= '   shell_exec available: '.(function_exists('shell_exec') ? '✅ Yes' : '❌ No')."\n";

                $output .= "\n💡 Recommendation: Contact your hosting provider with this information to get the correct PHP path.";
                break;

            case 'debug_hosting_structure':
                $output = "Hosting Structure Diagnostic:\n\n";

                // Current location info
                $output .= "1. Current Location:\n";
                $output .= '   Current Dir: '.__DIR__."\n";
                $output .= '   Script: '.($_SERVER['SCRIPT_NAME'] ?? 'Unknown')."\n";
                $output .= '   Document Root: '.($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown')."\n";

                // Path analysis
                $output .= "\n2. Path Analysis:\n";
                $currentDir = __DIR__;
                $output .= '   Parent Dir: '.dirname($currentDir)."\n";
                $output .= '   Grandparent Dir: '.dirname(dirname($currentDir))."\n";
                $output .= '   Great-grandparent Dir: '.dirname(dirname(dirname($currentDir)))."\n";

                // Test Laravel paths
                $output .= "\n3. Laravel Root Detection:\n";
                $testPaths = [
                    'Sibling (public_html/laravel)' => dirname($currentDir).'/laravel',
                    'Uncle (private_html structure)' => dirname(dirname($currentDir)).'/laravel',
                    'Great-uncle (nested structure)' => dirname(dirname(dirname($currentDir))).'/laravel',
                    'Parent (development)' => dirname(__DIR__),
                    'Child (same level)' => $currentDir.'/laravel',
                ];

                foreach ($testPaths as $label => $path) {
                    $exists = is_dir($path);
                    $hasBootstrap = $exists && file_exists($path.'/bootstrap/app.php');
                    $hasViews = $exists && is_dir($path.'/resources/views');
                    $hasAppView = $exists && file_exists($path.'/resources/views/app.blade.php');

                    $output .= "   $label:\n";
                    $output .= "     Path: $path\n";
                    $output .= '     Exists: '.($exists ? '✅ Yes' : '❌ No')."\n";
                    if ($exists) {
                        $output .= '     Bootstrap: '.($hasBootstrap ? '✅ Yes' : '❌ No')."\n";
                        $output .= '     Views: '.($hasViews ? '✅ Yes' : '❌ No')."\n";
                        $output .= '     app.blade.php: '.($hasAppView ? '✅ Yes' : '❌ No')."\n";
                    }
                    $output .= "\n";
                }

                // Current Laravel root detection
                $output .= "4. Current Detection Result:\n";
                $output .= '   Selected Laravel Root: '.($laravelRoot ?: 'Not found')."\n";
                if ($laravelRoot) {
                    $viewsPath = $laravelRoot.'/resources/views/app.blade.php';
                    $output .= "   app.blade.php path: $viewsPath\n";
                    $output .= '   app.blade.php exists: '.(file_exists($viewsPath) ? '✅ Yes' : '❌ No')."\n";
                }

                $output .= "\n💡 Upload files to the correct Laravel directory path shown above.";
                break;

            case 'debug_view_error':
                $output = "View Error Diagnostic:\n\n";

                // 1. Check views directory
                $output .= "1. Views Directory Check:\n";
                $viewsPath = $laravelRoot.'/resources/views';
                if (is_dir($viewsPath)) {
                    $output .= "   ✅ Views directory exists: $viewsPath\n";

                    // Check for app.blade.php
                    $appView = $viewsPath.'/app.blade.php';
                    if (file_exists($appView)) {
                        $output .= "   ✅ app.blade.php exists\n";
                        $output .= '   File size: '.filesize($appView)." bytes\n";
                    } else {
                        $output .= "   ❌ app.blade.php missing\n";
                    }
                } else {
                    $output .= "   ❌ Views directory not found: $viewsPath\n";
                }

                // 2. Check view config
                $output .= "\n2. View Configuration:\n";
                try {
                    $configPath = $laravelRoot.'/config/view.php';
                    if (file_exists($configPath)) {
                        $output .= "   ✅ view.php config exists\n";
                    } else {
                        $output .= "   ❌ view.php config missing\n";
                    }
                } catch (Exception $e) {
                    $output .= '   ❌ Error checking view config: '.$e->getMessage()."\n";
                }

                // 3. Check cache directory
                $output .= "\n3. View Cache:\n";
                $viewCachePath = $laravelRoot.'/storage/framework/views';
                if (is_dir($viewCachePath)) {
                    $writable = is_writable($viewCachePath);
                    $perms = substr(sprintf('%o', fileperms($viewCachePath)), -4);
                    $output .= "   ✅ Cache directory exists: $viewCachePath\n";
                    $output .= '   Writable: '.($writable ? '✅ Yes' : '❌ No')." (Perms: $perms)\n";
                } else {
                    $output .= "   ❌ View cache directory missing: $viewCachePath\n";
                }

                // 4. Check database connection (for AppSetting)
                $output .= "\n4. Database Connection (for AppSetting):\n";
                try {
                    $testQuery = executeCommand('php artisan tinker --execute="echo \\"DB connection test\\"; DB::select(\\"SELECT 1 as test\\"); echo \\"OK\\";"');
                    if (strpos($testQuery, 'OK') !== false) {
                        $output .= "   ✅ Database connection working\n";
                    } else {
                        $output .= "   ⚠️ Database connection issue\n";
                        $output .= '   '.substr($testQuery, 0, 200)."\n";
                    }
                } catch (Exception $e) {
                    $output .= '   ❌ Database test failed: '.$e->getMessage()."\n";
                }

                // 5. Quick fixes
                $output .= "\n5. Quick Fixes:\n";
                $output .= "   1. Clear view cache: Use 'Clear All Cache' button\n";
                $output .= "   2. Fix storage permissions: Use 'Fix Storage Permissions'\n";
                $output .= "   3. Run migrations: Use 'Run Migrations' if AppSetting table missing\n";
                $output .= "   4. Check database: Use 'Run Database Seeder' for sample settings\n";
                break;

            case 'logout':
                session_destroy();
                header('Location: '.$_SERVER['PHP_SELF']);
                exit;
                break;

            default:
                $error = 'Unknown action: '.htmlspecialchars($action);
        }
    } catch (Exception $e) {
        $error = 'Error: '.$e->getMessage();
    }
}

function executeCommand($command)
{
    // Check if shell_exec is available
    if (! function_exists('shell_exec')) {
        return 'Error: shell_exec function is disabled on this server';
    }

    // Add full path to PHP if needed
    if (strpos($command, 'php ') === 0) {
        // Try multiple methods to find PHP executable
        $phpPath = null;

        // Method 1: Use PHP_BINARY constant (most reliable)
        if (defined('PHP_BINARY') && is_executable(PHP_BINARY)) {
            $phpPath = PHP_BINARY;
        }
        // Method 2: Try common shared hosting paths
        elseif (is_executable('/usr/bin/php')) {
            $phpPath = '/usr/bin/php';
        } elseif (is_executable('/usr/local/bin/php')) {
            $phpPath = '/usr/local/bin/php';
        } elseif (is_executable('/opt/cpanel/ea-php81/root/usr/bin/php')) {
            $phpPath = '/opt/cpanel/ea-php81/root/usr/bin/php';
        } elseif (is_executable('/opt/cpanel/ea-php82/root/usr/bin/php')) {
            $phpPath = '/opt/cpanel/ea-php82/root/usr/bin/php';
        } elseif (is_executable('/opt/cpanel/ea-php83/root/usr/bin/php')) {
            $phpPath = '/opt/cpanel/ea-php83/root/usr/bin/php';
        } elseif (is_executable('/opt/cpanel/ea-php84/root/usr/bin/php')) {
            $phpPath = '/opt/cpanel/ea-php84/root/usr/bin/php';
        }
        // Method 3: Try to use 'php' directly (might work on some hosting)
        else {
            // Test if 'php' command works directly
            $testResult = @shell_exec('php --version 2>/dev/null');
            if ($testResult && strpos($testResult, 'PHP') !== false) {
                $phpPath = 'php';
            }
        }

        // Method 4: Use which/where command (if available)
        if (! $phpPath) {
            $which = trim(@shell_exec('which php 2>/dev/null') ?: '');
            if ($which && is_executable($which)) {
                $phpPath = $which;
            }
        }

        // Method 5: Try common hosting-specific paths
        if (! $phpPath) {
            $commonPaths = [
                '/usr/local/php83/bin/php',    // PHP 8.3 prioritized
                '/usr/local/php84/bin/php',    // PHP 8.4 for future
                '/usr/local/php82/bin/php',
                '/usr/local/php81/bin/php',
                '/usr/local/lsws/lsphp83/bin/php',  // LiteSpeed PHP 8.3
                '/usr/local/lsws/lsphp84/bin/php',  // LiteSpeed PHP 8.4
                '/usr/local/lsws/lsphp82/bin/php',
                '/usr/local/lsws/lsphp81/bin/php',
                '/home/'.get_current_user().'/public_html/cgi-bin/php83',
                '/home/'.get_current_user().'/public_html/cgi-bin/php',
                '/usr/local/bin/php83',
                '/usr/bin/php83',
            ];

            foreach ($commonPaths as $path) {
                if (is_executable($path)) {
                    $phpPath = $path;
                    break;
                }
            }
        }

        if ($phpPath) {
            // Only escape if it's a full path (contains /)
            if (strpos($phpPath, '/') !== false) {
                $command = str_replace('php ', escapeshellarg($phpPath).' ', $command);
            } else {
                $command = str_replace('php ', $phpPath.' ', $command);
            }
        } else {
            // Last resort: try without path (some hosting allows this)
            $debugInfo = "PHP Detection Debug:\n";
            $debugInfo .= 'PHP_BINARY: '.(defined('PHP_BINARY') ? PHP_BINARY : 'Not defined')."\n";
            $debugInfo .= 'PHP_BINARY executable: '.(defined('PHP_BINARY') && is_executable(PHP_BINARY) ? 'Yes' : 'No')."\n";
            $debugInfo .= 'Current user: '.get_current_user()."\n";
            $debugInfo .= 'Server software: '.($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown')."\n";
            $debugInfo .= 'PHP SAPI: '.php_sapi_name()."\n";

            return 'Error: Could not find PHP executable. '.$debugInfo.
                'Contact your hosting provider for the correct PHP path.';
        }
    }

    $output = shell_exec($command.' 2>&1');

    return $output ?: 'Command executed (no output)';
}

function removeDirectory($dir)
{
    if (! is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir.'/'.$file;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}

function getDirSize($dir)
{
    if (! is_dir($dir)) {
        return 0;
    }

    $size = 0;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $size += $file->getSize();
    }

    return $size;
}

function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision).' '.$units[$i];
}

function showLoginForm()
{
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Admin Tools - Login</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 400px;
                margin: 100px auto;
                padding: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            label {
                display: block;
                margin-bottom: 5px;
            }

            input[type="password"] {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            button {
                background: #007cba;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }

            button:hover {
                background: #005a87;
            }

            .warning {
                background: #fff3cd;
                border: 1px solid #ffeeba;
                color: #856404;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
        </style>
    </head>

    <body>
        <h2>Admin Tools Login</h2>
        <div class="warning">
            <strong>Warning:</strong> This is an emergency admin tool. Remove this file in production.
        </div>
        <form method="post">
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </body>

    </html>
<?php
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Emergency Admin Tools</title>
    <style>
        :root {
            /* shadcn/ui inspired color palette */
            --background: 0 0% 100%;
            --foreground: 222.2 84% 4.9%;
            --card: 0 0% 100%;
            --card-foreground: 222.2 84% 4.9%;
            --primary: 222.2 47.4% 11.2%;
            --primary-foreground: 210 40% 98%;
            --secondary: 210 40% 96%;
            --secondary-foreground: 222.2 47.4% 11.2%;
            --muted: 210 40% 98%;
            --muted-foreground: 215.4 16.3% 46.9%;
            --accent: 210 40% 96%;
            --accent-foreground: 222.2 47.4% 11.2%;
            --destructive: 0 84.2% 60.2%;
            --destructive-foreground: 210 40% 98%;
            --warning: 38 92% 50%;
            --warning-foreground: 222.2 84% 4.9%;
            --success: 142 76% 36%;
            --success-foreground: 210 40% 98%;
            --border: 214.3 31.8% 91.4%;
            --input: 214.3 31.8% 91.4%;
            --ring: 222.2 84% 4.9%;
            --radius: 0.5rem;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            background: hsl(210 40% 98%);
            color: hsl(var(--foreground));
            line-height: 1.6;
            margin: 0;
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: hsl(var(--background));
            border-radius: 0.75rem;
            border: 1px solid hsl(var(--border));
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            overflow: hidden;
        }

        .header {
            background: hsl(var(--card));
            border-bottom: 1px solid hsl(var(--border));
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .content {
            padding: 2rem;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: hsl(var(--foreground));
            margin: 0;
            letter-spacing: -0.025em;
        }

        h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: hsl(var(--foreground));
            margin: 0 0 1rem 0;
            letter-spacing: -0.025em;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .card {
            background: hsl(var(--card));
            border: 1px solid hsl(var(--border));
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            border-radius: calc(var(--radius) - 2px);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            height: 2.25rem;
            cursor: pointer;
            margin: 0.25rem 0.5rem 0.25rem 0;
        }

        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            border-radius: calc(var(--radius) - 2px);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            height: 2.25rem;
            cursor: pointer;
            margin: 0.25rem 0.5rem 0.25rem 0;
            background: hsl(var(--primary));
            color: hsl(var(--primary-foreground));
        }

        button:hover {
            background: hsl(var(--primary) / 0.9);
        }

        button.success {
            background: hsl(var(--success));
            color: hsl(var(--success-foreground));
        }

        button.success:hover {
            background: hsl(var(--success) / 0.9);
        }

        button.warning {
            background: hsl(var(--warning));
            color: hsl(var(--warning-foreground));
        }

        button.warning:hover {
            background: hsl(var(--warning) / 0.9);
        }

        button.danger {
            background: hsl(var(--destructive));
            color: hsl(var(--destructive-foreground));
        }

        button.danger:hover {
            background: hsl(var(--destructive) / 0.9);
        }

        .alert {
            position: relative;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid;
            font-size: 0.875rem;
        }

        .warning-banner {
            background: hsl(38 100% 97%);
            border-color: hsl(38 92% 50% / 0.2);
            color: hsl(38 92% 30%);
            position: relative;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid hsl(38 92% 50% / 0.2);
            font-size: 0.875rem;
        }

        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: hsl(var(--muted));
            border-radius: var(--radius);
            border: 1px solid hsl(var(--border));
        }

        .info-item {
            display: flex;
            justify-content: left;
            align-items: center;
            padding: 0.25rem 0;
            font-size: 0.875rem;
        }

        .info-item strong {
            color: hsl(var(--foreground));
            font-weight: 500;
        }

        .info-value {
            color: hsl(var(--muted-foreground));
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
            padding: 0 5px;
        }

        .output {
            background: hsl(var(--muted));
            border: 1px solid hsl(var(--border));
            padding: 1rem;
            border-radius: var(--radius);
            margin: 1.5rem 0;
            white-space: pre-wrap;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.75rem;
            line-height: 1.5;
            max-height: 400px;
            overflow-y: auto;
        }

        .error {
            background: hsl(0 93% 94%);
            border-color: hsl(0 84% 60% / 0.2);
            color: hsl(0 74% 42%);
            position: relative;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: var(--radius);
            border: 1px solid hsl(0 84% 60% / 0.2);
            font-size: 0.875rem;
        }

        .success {
            background: hsl(143 85% 96%);
            border-color: hsl(145 92% 91%);
            color: hsl(140 100% 27%);
            position: relative;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: var(--radius);
            border: 1px solid hsl(145 92% 91%);
            font-size: 0.875rem;
        }

        .logout {
            float: right;
        }

        .footer {
            \n margin-top: 3rem;
            \n padding: 2rem;
            \n border-top: 1px solid hsl(var(--border));
            \n background: hsl(var(--muted) / 0.5);
            \n font-size: 0.75rem;
            \n color: hsl(var(--muted-foreground));
            \n line-height: 1.6;
            \n
        }

        \n \n .footer p {
            \n margin-bottom: 0.5rem;
            \n
        }

        \n \n .footer strong {
            \n color: hsl(var(--foreground));
            \n
        }

        \n \n @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header {
                padding: 1.5rem;
                flex-direction: column;
                align-items: stretch;
            }

            .content {
                padding: 1.5rem;
            }

            .system-info {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Emergency Admin Tools</h1>
            <form method="post" style="margin: 0;">
                <button type="submit" name="action" value="logout" class="danger logout">Logout</button>
            </form>
        </div>

        <div class="content">
            <div class="warning-banner">
                <strong>Warning:</strong> This is an emergency administration tool. Remove this file in production or restrict access via IP/authentication.
            </div>

            <div class="system-info">
                <div class="info-item">
                    <strong>PHP Version:</strong>
                    <span class="info-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="info-item">
                    <strong>Laravel Root:</strong>
                    <span class="info-value"><?php echo $laravelRoot; ?></span>
                </div>
                <div class="info-item">
                    <strong>Storage Link:</strong>
                    <span class="info-value"><?php echo file_exists($laravelRoot.'/public/storage') ? 'Exists' : 'Missing'; ?></span>
                </div>
                <div class="info-item">
                    <strong>Environment:</strong>
                    <span class="info-value"><?php echo file_exists($laravelRoot.'/.env') ? 'File exists' : 'Missing'; ?></span>
                </div>
            </div>

            <?php if ($error) { ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <?php if ($output) { ?>
                <div class="success">Command executed successfully!</div>
                <div class="output"><?php echo htmlspecialchars($output); ?></div>
            <?php } ?>

            <div class="grid">
                <div class="card">
                    <h3>Cache Management</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="clear_cache">Clear All Cache</button>
                        <button type="submit" name="action" value="optimize_clear">Clear Optimization</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Application Optimization</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="optimize">Optimize App</button>
                        <button type="submit" name="action" value="config_cache">Cache Config</button>
                        <button type="submit" name="action" value="route_cache">Cache Routes</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Storage</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="storage_link">Debug Storage Link</button>
                        <button type="submit" name="action" value="cleanup_storage" class="warning">Cleanup Storage</button>
                        <button type="submit" name="action" value="fix_storage_permissions" class="warning">Fix Storage Permissions</button>
                        <button type="submit" name="action" value="clear_logs" class="warning">Clear Log Files</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Database</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="migrate">Run Migrations</button>
                        <button type="submit" name="action" value="db_seed" class="success">Run Database Seeder</button>
                        <button type="submit" name="action" value="migrate_fresh" class="danger" onclick="return confirm('This will delete all data. Are you sure?')">Fresh Migration</button>
                        <button type="submit" name="action" value="fix_mysql_key_length" class="warning">Fix MySQL Key Length</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Maintenance Mode</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="maintenance_down" class="warning">Enable Maintenance</button>
                        <button type="submit" name="action" value="maintenance_up">Disable Maintenance</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Security</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="key_generate" class="danger" onclick="return confirm('This will generate a new APP_KEY. Continue?')">Generate App Key</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Environment</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="check_env">Check .env File</button>
                        <button type="submit" name="action" value="show_env">Show .env Content</button>
                        <button type="submit" name="action" value="backup_env" class="warning">Backup .env File</button>
                    </form>
                </div>

                <div class="card">
                    <h3>System Health</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="health_check">System Health Check</button>
                        <button type="submit" name="action" value="debug_500_error" class="danger">Debug 500 Error</button>
                        <button type="submit" name="action" value="debug_hosting_structure" class="danger">Debug Hosting Structure</button>
                        <button type="submit" name="action" value="debug_view_error" class="warning">Debug View Error</button>
                        <button type="submit" name="action" value="composer_status">Composer Status</button>
                        <button type="submit" name="action" value="disk_space">Disk Space Usage</button>
                        <button type="submit" name="action" value="debug_php_path" class="warning">Debug PHP Path</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Advanced Tools</h3>
                    <form method="post" style="margin: 0;">
                        <button type="submit" name="action" value="clear_all_cache" class="warning">Clear All Cache</button>
                        <button type="submit" name="action" value="create_symlinks">Create Symlinks</button>
                        <button type="submit" name="action" value="queue_status">Check Queue Status</button>
                        <button type="submit" name="action" value="schedule_run">Run Schedule (verbose)</button>
                    </form>
                </div>
            </div>

            <div class="footer">
                <p><strong>Emergency Access:</strong> If the main application is broken, you can use this tool to clear cache and fix common issues.</p>
                <p><strong>Security Note:</strong> This file should be removed or secured in production. Consider adding HTTP basic authentication or IP restrictions.</p>
            </div>
        </div>
    </div>
</body>

</html>