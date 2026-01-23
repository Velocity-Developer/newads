<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class AdminToolsController extends Controller
{
    public function storageLink()
    {
        $publicDir = public_path();
        $storagePath = $publicDir.DIRECTORY_SEPARATOR.'storage';
        $storageTarget = storage_path('app'.DIRECTORY_SEPARATOR.'public');

        $messages = [];

        if (! is_dir($storageTarget)) {
            if (! @mkdir($storageTarget, 0755, true)) {
                return response()->json([
                    'status' => 'error',
                    'output' => "Target directory does not exist and could not be created: {$storageTarget}",
                ], 500);
            }
            $messages[] = 'Created target directory';
        }

        if ($this->isLinkCorrect($storagePath, $storageTarget)) {
            return response()->json([
                'status' => 'ok',
                'output' => 'Link already exists and is correct',
            ]);
        }

        try {
            Artisan::call('storage:link');
            if ($this->isLinkCorrect($storagePath, $storageTarget) || file_exists($storagePath)) {
                return response()->json([
                    'status' => 'ok',
                    'output' => implode("\n", array_merge($messages, ['Storage link created via artisan'])),
                ]);
            }
            $messages[] = 'Artisan storage:link did not create link (fallback will attempt)';
        } catch (\Throwable $t) {
            $messages[] = 'Artisan storage:link failed: '.$t->getMessage();
        }

        if (function_exists('symlink')) {
            if (@symlink($storageTarget, $storagePath)) {
                return response()->json([
                    'status' => 'ok',
                    'output' => implode("\n", array_merge($messages, ['Storage symlink created via symlink()'])),
                ]);
            }
            $messages[] = 'symlink() failed';
        } else {
            $messages[] = 'symlink() not available';
        }

        if ($this->isWindows() && function_exists('shell_exec')) {
            $cmd = 'cmd /c mklink /J '.escapeshellarg($storagePath).' '.escapeshellarg($storageTarget);
            $result = shell_exec($cmd.' 2>&1');
            if (is_dir($storagePath)) {
                return response()->json([
                    'status' => 'ok',
                    'output' => implode("\n", array_merge($messages, ['Windows junction created via mklink /J', (string) $result])),
                ]);
            }
            $messages[] = 'mklink /J failed: '.$result;
        } else {
            $messages[] = 'mklink not available or not Windows';
        }

        return response()->json([
            'status' => 'error',
            'output' => implode("\n", array_merge($messages, ['Failed to create storage link'])),
        ], 500);
    }

    public function storagePermissions()
    {
        $laravelRoot = base_path();
        $dirs = ['storage', 'storage/app', 'storage/logs', 'storage/framework', 'bootstrap/cache'];

        $fixed = 0;
        $messages = [];

        foreach ($dirs as $dir) {
            $path = $laravelRoot.DIRECTORY_SEPARATOR.$dir;
            if (is_dir($path)) {
                if (@chmod($path, 0755)) {
                    $fixed++;
                    $messages[] = "Fixed permissions for {$dir}";
                } else {
                    $messages[] = "Failed to fix permissions for {$dir}";
                }
            } else {
                $messages[] = "Missing directory: {$dir}";
            }
        }

        return response()->json([
            'status' => 'ok',
            'output' => implode("\n", array_merge($messages, ["Fixed permissions for {$fixed} directories"])),
        ]);
    }

    public function clearLogs()
    {
        $logPath = storage_path('logs');
        if (! is_dir($logPath)) {
            return response()->json([
                'status' => 'error',
                'output' => 'Log directory not found',
            ], 404);
        }

        $files = glob($logPath.DIRECTORY_SEPARATOR.'*.log') ?: [];
        $count = 0;
        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }

        return response()->json([
            'status' => 'ok',
            'output' => "Deleted {$count} log files",
        ]);
    }

    public function checkEnv()
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        $output = [];
        $output[] = '.env exists: '.(file_exists($envPath) ? 'Yes' : 'No');
        $output[] = '.env.example exists: '.(file_exists($envExamplePath) ? 'Yes' : 'No');

        if (file_exists($envPath)) {
            $output[] = '.env size: '.filesize($envPath).' bytes';
            $output[] = '.env modified: '.date('Y-m-d H:i:s', filemtime($envPath));

            $envContent = @file_get_contents($envPath) ?: '';
            foreach (['APP_KEY', 'DB_CONNECTION', 'DB_DATABASE'] as $var) {
                $exists = strpos($envContent, $var.'=') !== false;
                $output[] = "{$var}: ".($exists ? 'Set' : 'Missing');
            }
        }

        return response()->json([
            'status' => 'ok',
            'output' => implode("\n", $output),
        ]);
    }

    public function showEnv()
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return response()->json([
                'status' => 'error',
                'output' => 'Environment file not found',
            ], 404);
        }

        $envContent = @file_get_contents($envPath) ?: '';
        $masked = preg_replace('/(APP_KEY|DB_PASSWORD|.*_SECRET|.*_TOKEN|.*_KEY)=(.+)/i', '$1=***MASKED***', $envContent);

        return response()->json([
            'status' => 'ok',
            'output' => $masked,
        ]);
    }

    public function backupEnv()
    {
        $envPath = base_path('.env');
        $backupPath = base_path('.env.backup.'.date('Y-m-d_H-i-s'));

        if (! file_exists($envPath)) {
            return response()->json([
                'status' => 'error',
                'output' => 'Environment file not found',
            ], 404);
        }

        if (@copy($envPath, $backupPath)) {
            return response()->json([
                'status' => 'ok',
                'output' => 'Environment file backed up to: '.basename($backupPath),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'output' => 'Failed to backup environment file',
        ], 500);
    }

    public function healthCheck()
    {
        $output = [];
        $output[] = 'PHP Version: '.PHP_VERSION;
        $output[] = 'Memory Limit: '.ini_get('memory_limit');
        $output[] = 'Max Execution Time: '.ini_get('max_execution_time').'s';
        $output[] = 'Upload Max Size: '.ini_get('upload_max_filesize');

        $output[] = '';
        $output[] = 'Extensions:';
        foreach (['pdo', 'mbstring', 'tokenizer', 'json', 'openssl', 'curl'] as $ext) {
            $output[] = "{$ext}: ".(extension_loaded($ext) ? 'Loaded' : 'Missing');
        }

        $output[] = '';
        $output[] = 'Laravel Files:';
        foreach (['artisan', 'composer.json', '.env', 'bootstrap/app.php'] as $file) {
            $exists = file_exists(base_path($file));
            $output[] = "{$file}: ".($exists ? 'Exists' : 'Missing');
        }

        $output[] = '';
        $output[] = 'Directories:';
        foreach (['storage', 'storage/app', 'storage/logs', 'storage/framework', 'bootstrap/cache'] as $dir) {
            $path = base_path($dir);
            $exists = is_dir($path);
            $writable = $exists ? is_writable($path) : false;
            $output[] = "{$dir}: ".($exists ? 'Exists' : 'Missing').($writable ? ' (Writable)' : ($exists ? ' (Not Writable)' : ''));
        }

        return response()->json([
            'status' => 'ok',
            'output' => implode("\n", $output),
        ]);
    }

    public function debug500()
    {
        $output = [];
        $output[] = 'Version: '.phpversion();
        $output[] = 'SAPI: '.php_sapi_name();

        $output[] = 'Required Extensions:';
        foreach (['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'curl'] as $ext) {
            $output[] = "- {$ext}: ".(extension_loaded($ext) ? 'Loaded' : 'Missing');
        }

        return response()->json([
            'status' => 'ok',
            'output' => implode("\n", $output),
        ]);
    }

    public function debugHosting()
    {
        $laravelRoot = base_path();
        $publicDir = public_path();
        $storagePath = $publicDir.DIRECTORY_SEPARATOR.'storage';
        $storageTarget = storage_path('app'.DIRECTORY_SEPARATOR.'public');

        $output = [];
        $output[] = "Laravel Root: {$laravelRoot}";
        $output[] = "Public Dir: {$publicDir}";
        $output[] = "Storage Link Path: {$storagePath}";
        $output[] = "Storage Target: {$storageTarget}";
        $output[] = 'Target exists: '.(is_dir($storageTarget) ? 'Yes' : 'No');
        $output[] = 'Link exists: '.(file_exists($storagePath) ? 'Yes' : 'No');

        if (file_exists($storagePath)) {
            if (is_link($storagePath)) {
                $output[] = 'Link type: symlink';
                $output[] = 'Link target: '.@readlink($storagePath);
            } elseif (is_dir($storagePath)) {
                $output[] = 'Link type: directory (possible junction on Windows)';
            } else {
                $output[] = 'Link type: file';
            }
        }

        return response()->json([
            'status' => 'ok',
            'output' => implode("\n", $output),
        ]);
    }

    public function diskSpace()
    {
        $paths = [
            'storage' => storage_path(),
            'storage/app' => storage_path('app'),
            'storage/logs' => storage_path('logs'),
            'storage/framework' => storage_path('framework'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'public' => public_path(),
        ];

        $output = [];
        foreach ($paths as $label => $path) {
            $size = $this->getDirSizeSafe($path);
            $output[] = "{$label}: ".$this->formatBytes($size);
        }

        return response()->json([
            'status' => 'ok',
            'output' => implode("\n", $output),
        ]);
    }

    private function isLinkCorrect(string $storagePath, string $storageTarget): bool
    {
        if (! file_exists($storagePath)) {
            return false;
        }

        if (is_link($storagePath)) {
            $target = @readlink($storagePath);

            return $target === $storageTarget;
        }

        if (is_dir($storagePath)) {
            return realpath($storagePath) === realpath($storageTarget);
        }

        return false;
    }

    private function isWindows(): bool
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    private function getDirSizeSafe(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
