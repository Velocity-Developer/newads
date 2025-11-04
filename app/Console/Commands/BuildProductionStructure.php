<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BuildProductionStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build:production-structure {--include-vendor : Include vendor folder in build} {--include-storage : Include storage folder in build} {--development : Create development build with unoptimized assets} {--minimal : Create minimal build excluding non-essential files} {--zip : Create ZIP artifact} {--zip-only : Keep only ZIP, remove dist folders} {--zip-output=production.zip : Output ZIP filename} {--zip-dir=zip : Subfolder under dist for ZIP output} {--max-compress : Use maximum ZIP compression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build production folder structure with Laravel and public_html directories';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $includeVendor = $this->option('include-vendor');
        $includeStorage = $this->option('include-storage');
        $development = $this->option('development');
        $minimal = $this->option('minimal');

        // Opsi ZIP baru
        $zip = (bool) $this->option('zip'); // opsional, kita tetap buat ZIP meski flag tidak diberikan
        $zipOnly = (bool) $this->option('zip-only');
        $zipOutput = $this->option('zip-output') ?: null;
        $zipDir = $this->option('zip-dir') ?: 'zip';
        $maxCompress = (bool) $this->option('max-compress');

        $buildType = 'Production';
        if ($development) $buildType = 'Development';
        elseif ($minimal) $buildType = 'Minimal';
        elseif ($includeVendor && $includeStorage) $buildType = 'Full Production';
        elseif ($includeVendor) $buildType = 'Production with Vendor';

        Log::info("Building {$buildType} structure...");

        $distPath = base_path('dist');
        $laravelPath = $distPath.'/laravel';
        $publicHtmlPath = $distPath.'/public_html';

        // Clean and create directories
        if (File::exists($distPath)) {
            File::deleteDirectory($distPath);
        }
        File::makeDirectory($laravelPath, 0755, true, true);
        File::makeDirectory($publicHtmlPath, 0755, true, true);

        Log::info('Created dist directories');

        // Copy Laravel files (excluding public directory)
        $this->copyLaravelFiles($laravelPath, $includeVendor, $includeStorage, $minimal);
        Log::info('Copied Laravel files');

        // Copy public files to public_html
        $this->copyPublicFiles($publicHtmlPath, $minimal);
        Log::info('Copied public files to public_html');

        // Create public directory in Laravel folder and copy build assets
        $this->createLaravelPublicBuild($laravelPath, $publicHtmlPath);
        Log::info('Created Laravel public/build directory');

        // Modify index.php for production structure
        $this->modifyIndexPhpForProduction($publicHtmlPath, $includeVendor);
        Log::info('Modified index.php for production structure');

        // Create production.zip (output ke dist/zip)
        $this->createZip($distPath, $buildType, $zipDir, $zipOutput, $maxCompress, $includeVendor, $includeStorage, $minimal);
        Log::info('Created production ZIP');

        // Jika zip-only, hapus folder laravel/ dan public_html/ agar hanya ZIP yang tersisa
        if ($zipOnly) {
            if (File::exists($laravelPath)) {
                File::deleteDirectory($laravelPath);
            }
            if (File::exists($publicHtmlPath)) {
                File::deleteDirectory($publicHtmlPath);
            }
            Log::info('Removed dist folders; kept only ZIP artifact');
        }

        // Show build summary (menunjukkan lokasi ZIP baru)
        $this->showBuildSummary($buildType, $includeVendor, $includeStorage, $development, $minimal, $distPath, $zipOutput, $zipDir);

        return self::SUCCESS;
    }

    private function copyLaravelFiles(string $destination, bool $includeVendor = false, bool $includeStorage = false, bool $minimal = false): void
    {
        Log::info('Copying essential Laravel files...');

        // Define essential files and directories to copy
        $essentialPaths = [
            'app',
            'bootstrap',
            'config',
            'database',
            'routes',
            'artisan',
            'composer.json',
            '.env.example'
        ];

        // Include composer.lock only if vendor is included
        if ($includeVendor) {
            $essentialPaths[] = 'composer.lock';
        }

        // Additional paths for non-minimal builds
        if (!$minimal) {
            $essentialPaths[] = 'resources';
        }

        $basePath = base_path();

        // Copy essential files
        foreach ($essentialPaths as $path) {
            $sourcePath = $basePath.'/'.$path;
            $destPath = $destination.'/'.$path;

            if (is_dir($sourcePath)) {
                // Copy directory
                $this->copyDirectory($sourcePath, $destPath);
            } elseif (is_file($sourcePath)) {
                // Copy single file
                File::copy($sourcePath, $destPath);
            }
        }

        // Include vendor folder if requested
        if ($includeVendor) {
            $vendorPath = $basePath.'/vendor';
            if (File::exists($vendorPath)) {
                Log::info('Including vendor folder...');
                $this->copyDirectory($vendorPath, $destination.'/vendor');
            }
        }

        // Include storage folder if requested
        if ($includeStorage) {
            $storagePath = $basePath.'/storage';
            if (File::exists($storagePath)) {
                Log::info('Including storage folder...');
                $this->copyDirectory($storagePath, $destination.'/storage');
    
                // Pangkas log dan cache runtime agar artefak produksi tetap kecil
                Log::info('Pruning storage logs and runtime caches...');
                $pruneDirs = [
                    'logs',
                    'framework/cache',
                    'framework/sessions',
                    'framework/testing',
                ];
                foreach ($pruneDirs as $dir) {
                    $target = $destination.'/storage/'.$dir;
                    if (File::exists($target)) {
                        File::deleteDirectory($target);
                    }
                    File::makeDirectory($target, 0755, true, true);
                    File::put($target.'/.gitkeep', '');
                }
            }
        } else {
            // Create necessary storage directories
            Log::info('Creating storage directories...');
            $storageDirs = [
                'app/public',
                'framework/cache/data',
                'framework/sessions',
                'framework/views',
                'logs',
            ];

            foreach ($storageDirs as $dir) {
                File::makeDirectory($destination.'/storage/'.$dir, 0755, true, true);
                // Add .gitkeep to preserve empty directories
                File::put($destination.'/storage/'.$dir.'/.gitkeep', '');
            }
        }

        // Copy .env.production as .env if it exists
        $envProduction = base_path('.env.production');
        if (File::exists($envProduction)) {
            File::copy($envProduction, $destination.'/.env');
        }

        Log::info('Essential Laravel files copied successfully');
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!File::isDirectory($source)) {
            return;
        }

        // Ensure destination directory exists
        File::makeDirectory($destination, 0755, true);

        $files = File::allFiles($source);
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $destPath = $destination.'/'.$relativePath;

            // Create parent directory if needed
            $destDir = dirname($destPath);
            if (!File::isDirectory($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            // Copy file to destination
            File::copy($file->getPathname(), $destPath);
        }
    }

    private function copyPublicFiles(string $destination, bool $minimal = false): void
    {
        $publicPath = base_path('public');

        if (! File::exists($publicPath)) {
            return;
        }

        // Copy files manually to exclude storage and other unnecessary files
        foreach (File::allFiles($publicPath) as $file) {
            $relativePath = str_replace($publicPath.DIRECTORY_SEPARATOR, '', $file->getPathname());

            // Skip storage symlink and directory
            if (str_starts_with($relativePath, 'storage')) {
                continue;
            }

            $destinationFile = $destination.DIRECTORY_SEPARATOR.$relativePath;
            $destinationDir = dirname($destinationFile);

            if (! File::exists($destinationDir)) {
                File::makeDirectory($destinationDir, 0755, true);
            }

            File::copy($file->getPathname(), $destinationFile);
        }

        // Copy .htaccess explicitly if it exists
        $htaccess = $publicPath.'/.htaccess';
        if (File::exists($htaccess)) {
            File::copy($htaccess, $destination.'/.htaccess');
        }

        // Remove development files from production build
        $devFiles = ['hot', 'mix-manifest.json'];
        foreach ($devFiles as $devFile) {
            $devFilePath = $destination.'/'.$devFile;
            if (File::exists($devFilePath)) {
                File::delete($devFilePath);
            }
        }

        // For minimal build, remove unnecessary files
        if ($minimal) {
            $unnecessaryFiles = [
                'admin-tools.php',
                'simple-admin-tools.php'
            ];
            foreach ($unnecessaryFiles as $file) {
                $filePath = $destination.'/'.$file;
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }
        }
    }

    private function modifyIndexPhpForProduction(string $publicHtmlPath, bool $includeVendor = false): void
    {
        $indexPhpPath = $publicHtmlPath.'/index.php';

        if (!File::exists($indexPhpPath)) {
            Log::error('index.php not found in public_html directory');
            return;
        }

        $content = File::get($indexPhpPath);

        // Replace paths to point to ../laravel/ instead of ../
        if ($includeVendor) {
            // If vendor is included, use relative path to laravel/vendor
            $content = str_replace(
                "require __DIR__.'/../vendor/autoload.php';",
                "require __DIR__.'/../laravel/vendor/autoload.php';",
                $content
            );
        } else {
            // If vendor is not included, assume it will be installed in parent directory
            $content = str_replace(
                "require __DIR__.'/../vendor/autoload.php';",
                "require __DIR__.'/../vendor/autoload.php';",
                $content
            );
        }

        $content = str_replace(
            "require_once __DIR__.'/../bootstrap/app.php';",
            "require_once __DIR__.'/../laravel/bootstrap/app.php';",
            $content
        );

        $content = str_replace(
            "file_exists(\$maintenance = __DIR__.'/../storage/framework/maintenance.php')",
            "file_exists(\$maintenance = __DIR__.'/../laravel/storage/framework/maintenance.php')",
            $content
        );

        File::put($indexPhpPath, $content);
    }

    private function createLaravelPublicBuild(string $laravelPath, string $publicHtmlPath): void
    {
        $laravelPublicPath = $laravelPath.'/public';
        $laravelBuildPath = $laravelPublicPath.'/build';
        $publicHtmlBuildPath = $publicHtmlPath.'/build';

        // Create public directory in Laravel folder
        File::makeDirectory($laravelPublicPath, 0755, true, true);
        File::makeDirectory($laravelBuildPath, 0755, true, true);

        // Copy build directory from public_html to laravel/public
        if (File::exists($publicHtmlBuildPath)) {
            // Copy manifest.json
            if (File::exists($publicHtmlBuildPath.'/manifest.json')) {
                File::copy($publicHtmlBuildPath.'/manifest.json', $laravelBuildPath.'/manifest.json');
            }

            // Copy assets directory
            $assetsSource = $publicHtmlBuildPath.'/assets';
            $assetsDestination = $laravelBuildPath.'/assets';

            if (File::exists($assetsSource)) {
                File::makeDirectory($assetsDestination, 0755, true, true);

                // Copy all files in assets directory
                foreach (File::allFiles($assetsSource) as $file) {
                    $relativePath = str_replace($assetsSource.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $destinationFile = $assetsDestination.DIRECTORY_SEPARATOR.$relativePath;
                    $destinationDir = dirname($destinationFile);

                    if (!File::exists($destinationDir)) {
                        File::makeDirectory($destinationDir, 0755, true);
                    }

                    File::copy($file->getPathname(), $destinationFile);
                }
            }
        }
    }

    private function createZip(
        string $distPath,
        string $buildType = 'Production',
        string $zipDir = 'zip',
        ?string $zipOutput = null,
        bool $maxCompress = false,
        bool $includeVendor = false,
        bool $includeStorage = false,
        bool $minimal = false
    ): void
    {
        // Buat folder output ZIP di bawah dist
        $zipOutDir = $distPath . '/' . $zipDir;
        if (!File::exists($zipOutDir)) {
            File::makeDirectory($zipOutDir, 0755, true, true);
        }
    
        $zipName = $zipOutput ?: ($buildType === 'Production'
            ? 'production.zip'
            : strtolower(str_replace(' ', '-', $buildType)).'.zip');
        $zipFile = $zipOutDir . '/' . $zipName;
    
        if (File::exists($zipFile)) {
            File::delete($zipFile);
        }
    
        $compressionLevel = $maxCompress ? 9 : 6;
    
        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Pola eksklusi untuk mengecilkan ZIP
            $exclusions = [
                '/.git/', '/.github/', '/.gitignore', '/.gitattributes', '/.editorconfig',
                '/.prettier', '/.prettierrc',
                '/vendor/tests/', '/vendor/test/', '/vendor/docs/', '/vendor/doc/',
                '/vendor/examples/', '/vendor/benchmarks/', '/vendor/.git/', '/vendor/.github/',
                '/phpunit.xml', '/phpunit.dist.xml', '/psalm.xml', '/infection.json', '/phpstan.neon', '/ecs.php',
            ];
    
            // Jika storage disertakan, exclude konten berat runtime agar artefak tetap kecil
            if ($includeStorage) {
                $exclusions = array_merge($exclusions, [
                    '/storage/logs/',
                    '/storage/framework/cache/',
                    '/storage/framework/sessions/',
                    '/storage/framework/testing/',
                ]);
            }
    
            // Tambahkan laravel/
            $this->addDirectoryToZip($zip, realpath($distPath.'/laravel'), 'laravel', $exclusions, $compressionLevel);
    
            // Tambahkan public_html/
            $this->addDirectoryToZip($zip, realpath($distPath.'/public_html'), 'public_html', $exclusions, $compressionLevel);
    
            $zip->close();
        }
    }

    private function addDirectoryToZip(
        ZipArchive $zip,
        string $dir,
        string $zipDir,
        array $exclusions = [],
        int $compressionLevel = 6
    ): void
    {
        if (! $dir || ! is_dir($dir)) {
            return;
        }
    
        $realDir = realpath($dir);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
    
            // Hitung path relatif dan path dalam zip
            $relativePath = str_replace($realDir, '', $filePath);
            $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
            $relativePath = str_replace('\\', '/', $relativePath);
            $zipPath = $relativePath ? $zipDir.'/'.$relativePath : $zipDir;
    
            if ($file->isDir()) {
                if ($zipPath !== $zipDir) {
                    // Lewati direktori akar
                    $zip->addEmptyDir($zipPath);
                }
            } elseif ($file->isFile()) {
                // Filter eksklusi agar ZIP tetap kecil
                if ($this->shouldExclude($zipPath, $exclusions)) {
                    continue;
                }
                $zip->addFile($filePath, $zipPath);
                // Set kompresi per file
                $zip->setCompressionName($zipPath, ZipArchive::CM_DEFLATE, $compressionLevel);
            }
        }
    }

    private function shouldExclude(string $zipPath, array $exclusions): bool
    {
        foreach ($exclusions as $pattern) {
            if (str_contains($zipPath, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function showBuildSummary(
        string $buildType,
        bool $includeVendor,
        bool $includeStorage,
        bool $development,
        bool $minimal,
        string $distPath,
        ?string $zipOutput = null,
        string $zipDir = 'zip'
    ): void
    {
        Log::info('✅ ' . $buildType . ' build completed successfully!');
        Log::info('📁 Files created in: ' . $distPath);
    
        // Tentukan zip filename dan lokasi baru
        $zipName = $zipOutput ?: ($buildType === 'Production' ? 'production.zip' : strtolower(str_replace(' ', '-', $buildType)).'.zip');
        $zipPath = $distPath . '/' . $zipDir . '/' . $zipName;
    
        if (File::exists($zipPath)) {
            $zipSize = $this->formatBytes(File::size($zipPath));
            Log::info('📦 Production archive: ' . $zipPath . ' (' . $zipSize . ')');
        }
    
        Log::info('');
        Log::info('📋 Build Configuration:');
        Log::info('   • Build Type: ' . $buildType);
        Log::info('   • Vendor Folder: ' . ($includeVendor ? '✅ Included' : '❌ Excluded'));
        Log::info('   • Storage Folder: ' . ($includeStorage ? '✅ Included' : '❌ Excluded'));
        Log::info('   • Development Mode: ' . ($development ? '✅ Enabled' : '❌ Disabled'));
        Log::info('   • Minimal Build: ' . ($minimal ? '✅ Enabled' : '❌ Disabled'));
    
        Log::info('');
        Log::info('📁 Production structure:');
    
        $laravelSize = $this->getDirectorySize($distPath . '/laravel');
        $publicHtmlSize = $this->getDirectorySize($distPath . '/public_html');
    
        Log::info('   📂 laravel/ (' . $this->formatBytes($laravelSize) . ')');
        Log::info('   📂 public_html/ (' . $this->formatBytes($publicHtmlSize) . ')');
    
        if ($includeVendor) {
            Log::info('       └── vendor/ (Included)');
        }
    
        if ($includeStorage) {
            Log::info('       └── storage/ (Included)');
        }
    
        Log::info('');
        Log::info('🚀 Deployment Instructions:');
    
        if ($includeVendor) {
            Log::info('   1. Extract ZIP to server');
            Log::info('   2. Run: php artisan key:generate');
            Log::info('   3. Run: php artisan migrate');
        } else {
            Log::info('   1. Extract ZIP to server');
            Log::info('   2. Run: composer install --no-dev --optimize-autoloader');
            Log::info('   3. Run: php artisan key:generate');
            Log::info('   4. Run: php artisan migrate');
        }
    
        Log::info('   5. Configure .env file');
        Log::info('   6. Set file permissions');
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getDirectorySize($path): int
    {
        if (!File::exists($path)) {
            return 0;
        }

        $totalSize = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
            }
        }

        return $totalSize;
    }
}
