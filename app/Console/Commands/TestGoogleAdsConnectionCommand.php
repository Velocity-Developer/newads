<?php

namespace App\Console\Commands;

use App\Services\GoogleAds\SearchTermFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestGoogleAdsConnectionCommand extends Command
{
    protected $signature = 'test:google-ads-connection {--dry-run : Test tanpa eksekusi API}';

    protected $description = 'Test koneksi Google Ads API dengan aman';

    public function handle()
    {
        $this->warn('Google Ads connection test is disabled; integration removed.');

        return 0;

        Log::info('🔍 Testing Google Ads API Connection...');
        $this->newLine();

        $fetcher = new SearchTermFetcher;

        // Step 1: Test konfigurasi
        Log::info('1️⃣ Checking configuration...');
        $config = $fetcher->getConfig();

        $requiredFields = ['client_id', 'client_secret', 'developer_token', 'customer_id', 'campaign_id'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            Log::error('❌ Missing configuration: '.implode(', ', $missingFields));

            return 1;
        }

        Log::info('✅ Configuration OK');
        $this->table(['Field', 'Status'], [
            ['Client ID', ! empty($config['client_id']) ? '✅ Set' : '❌ Missing'],
            ['Client Secret', ! empty($config['client_secret']) ? '✅ Set' : '❌ Missing'],
            ['Developer Token', ! empty($config['developer_token']) ? '✅ Set' : '❌ Missing'],
            ['Customer ID', ! empty($config['customer_id']) ? '✅ Set' : '❌ Missing'],
            ['Campaign ID', ! empty($config['campaign_id']) ? '✅ Set' : '❌ Missing'],
            ['Refresh Token', ! empty($config['refresh_token']) ? '✅ Available' : '❌ Missing'],
        ]);

        // Step 2: Test refresh token
        $this->newLine();
        Log::info('2️⃣ Checking refresh token...');

        if (empty($config['refresh_token'])) {
            Log::error('❌ Refresh token tidak ditemukan!');
            $this->warn('Jalankan: php generate_refresh_token.php');

            return 1;
        }

        Log::info('✅ Refresh token available');

        // Step 3: Test API connection (jika tidak dry-run)
        if (! $this->option('dry-run')) {
            $this->newLine();
            Log::info('3️⃣ Testing API connection...');

            try {
                $result = $fetcher->testConnection();

                if ($result['success']) {
                    Log::info('✅ API Connection successful!');
                    Log::info('📊 Campaign found: '.($result['campaign_name'] ?? 'Unknown'));
                } else {
                    Log::error('❌ API Connection failed: '.$result['error']);

                    return 1;
                }

            } catch (\Exception $e) {
                Log::error('❌ Connection test failed: '.$e->getMessage());
                Log::error('Google Ads connection test failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return 1;
            }
        } else {
            $this->warn('⏭️ Skipping API test (dry-run mode)');
        }

        $this->newLine();
        Log::info('🎉 All tests passed!');

        if (! $this->option('dry-run')) {
            Log::info('Next steps:');
            Log::info('- Test fetch: php artisan test:safe-fetch');
            Log::info('- Run full system: php artisan fetch:zero-click-terms');
        }

        return 0;
    }
}
