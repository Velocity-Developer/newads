<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use Illuminate\Support\Facades\Log;

class TestGoogleAdsConnectionCommand extends Command
{
    protected $signature = 'test:google-ads-connection {--dry-run : Test tanpa eksekusi API}';
    protected $description = 'Test koneksi Google Ads API dengan aman';

    public function handle()
    {
        $this->info('🔍 Testing Google Ads API Connection...');
        $this->newLine();

        $fetcher = new SearchTermFetcher();
        
        // Step 1: Test konfigurasi
        $this->info('1️⃣ Checking configuration...');
        $config = $fetcher->getConfig();
        
        $requiredFields = ['client_id', 'client_secret', 'developer_token', 'customer_id', 'campaign_id'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            $this->error('❌ Missing configuration: ' . implode(', ', $missingFields));
            return 1;
        }
        
        $this->info('✅ Configuration OK');
        $this->table(['Field', 'Status'], [
            ['Client ID', !empty($config['client_id']) ? '✅ Set' : '❌ Missing'],
            ['Client Secret', !empty($config['client_secret']) ? '✅ Set' : '❌ Missing'],
            ['Developer Token', !empty($config['developer_token']) ? '✅ Set' : '❌ Missing'],
            ['Customer ID', !empty($config['customer_id']) ? '✅ Set' : '❌ Missing'],
            ['Campaign ID', !empty($config['campaign_id']) ? '✅ Set' : '❌ Missing'],
            ['Refresh Token', !empty($config['refresh_token']) ? '✅ Available' : '❌ Missing'],
        ]);
        
        // Step 2: Test refresh token
        $this->newLine();
        $this->info('2️⃣ Checking refresh token...');
        
        if (empty($config['refresh_token'])) {
            $this->error('❌ Refresh token tidak ditemukan!');
            $this->warn('Jalankan: php generate_refresh_token.php');
            return 1;
        }
        
        $this->info('✅ Refresh token available');
        
        // Step 3: Test API connection (jika tidak dry-run)
        if (!$this->option('dry-run')) {
            $this->newLine();
            $this->info('3️⃣ Testing API connection...');
            
            try {
                $result = $fetcher->testConnection();
                
                if ($result['success']) {
                    $this->info('✅ API Connection successful!');
                    $this->info('📊 Campaign found: ' . ($result['campaign_name'] ?? 'Unknown'));
                } else {
                    $this->error('❌ API Connection failed: ' . $result['error']);
                    return 1;
                }
                
            } catch (\Exception $e) {
                $this->error('❌ Connection test failed: ' . $e->getMessage());
                Log::error('Google Ads connection test failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return 1;
            }
        } else {
            $this->warn('⏭️ Skipping API test (dry-run mode)');
        }
        
        $this->newLine();
        $this->info('🎉 All tests passed!');
        
        if (!$this->option('dry-run')) {
            $this->info('Next steps:');
            $this->info('- Test fetch: php artisan test:safe-fetch');
            $this->info('- Run full system: php artisan fetch:zero-click-terms');
        }
        
        return 0;
    }
}