<?php

namespace App\Console\Commands;

use App\Models\NewFrasaNegative;
use App\Models\NewTermsNegative0Click;
use App\Services\AI\TermAnalyzer;
use App\Services\GoogleAds\SearchTermFetcher;
use App\Services\Telegram\NotificationService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestNegativeKeywordsSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negative-keywords:test-system {--component= : Test specific component (google-ads|ai|telegram|database|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the negative keywords automation system components';

    protected $searchTermFetcher;

    protected $termAnalyzer;

    protected $notificationService;

    public function __construct(
        SearchTermFetcher $searchTermFetcher,
        TermAnalyzer $termAnalyzer,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->searchTermFetcher = $searchTermFetcher;
        $this->termAnalyzer = $termAnalyzer;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('🧪 Testing Negative Keywords Automation System');
        $this->newLine();

        $component = $this->option('component') ?? 'all';

        $results = [];

        switch ($component) {
            case 'google-ads':
                $results['google-ads'] = $this->testGoogleAds();
                break;
            case 'ai':
                $results['ai'] = $this->testAI();
                break;
            case 'telegram':
                $results['telegram'] = $this->testTelegram();
                break;
            case 'database':
                $results['database'] = $this->testDatabase();
                break;
            case 'all':
            default:
                $results['database'] = $this->testDatabase();
                $results['google-ads'] = $this->testGoogleAds();
                $results['ai'] = $this->testAI();
                $results['telegram'] = $this->testTelegram();
                break;
        }

        $this->displayResults($results);

        return 0;
    }

    private function testDatabase()
    {
        Log::info('📊 Testing Database Components...');

        $results = [
            'migrations' => false,
            'models' => false,
            'relationships' => false,
        ];

        try {
            // Test if tables exist
            if (\Schema::hasTable('new_terms_negative_0click') && \Schema::hasTable('new_frasa_negative')) {
                $results['migrations'] = true;
                Log::info('✅ Database tables exist');
            } else {
                Log::error('❌ Database tables missing');
            }

            // Test models
            $termModel = new NewTermsNegative0Click;
            $phraseModel = new NewFrasaNegative;

            if ($termModel && $phraseModel) {
                $results['models'] = true;
                Log::info('✅ Models instantiated successfully');
            }

            // Test relationships - use updateOrCreate to avoid duplicate error
            $testTerm = NewTermsNegative0Click::updateOrCreate(
                ['terms' => 'test term for system check'],
                [
                    'hasil_cek_ai' => null,
                    'status_input_google' => null,
                    'retry_count' => 0,
                    'notif_telegram' => null,
                ]
            );

            $testPhrase = NewFrasaNegative::create([
                'frasa' => 'test phrase',
                'parent_term_id' => $testTerm->id,
                'status_input_google' => null,
                'retry_count' => 0,
                'notif_telegram' => null,
            ]);

            if ($testTerm->frasa()->count() > 0 && $testPhrase->parentTerm) {
                $results['relationships'] = true;
                Log::info('✅ Model relationships working');
            }

            // Cleanup test data
            $testPhrase->delete();
            $testTerm->delete();

        } catch (Exception $e) {
            Log::error('❌ Database test failed: '.$e->getMessage());
        }

        return $results;
    }

    private function testGoogleAds()
    {
        Log::info('🎯 Testing Google Ads Integration...');

        $results = [
            'configuration' => false,
            'connection' => false,
        ];

        try {
            // Test configuration
            $config = $this->searchTermFetcher->getConfig();
            if (! empty($config['client_id']) && ! empty($config['developer_token'])) {
                $results['configuration'] = true;
                Log::info('✅ Google Ads configuration found');
            } else {
                Log::error('❌ Google Ads configuration missing');
            }

            // Test connection (placeholder - would need actual API call)
            Log::info('⚠️  Google Ads API connection test requires actual credentials');

        } catch (Exception $e) {
            Log::error('❌ Google Ads test failed: '.$e->getMessage());
        }

        return $results;
    }

    private function testAI()
    {
        Log::info('🤖 Testing AI Analysis Service...');

        $results = [
            'configuration' => false,
            'service' => false,
        ];

        try {
            // Test configuration
            if ($this->termAnalyzer->isConfigured()) {
                $results['configuration'] = true;
                Log::info('✅ AI service configured');
                Log::info('🧩 Model GPT saat ini: '.$this->termAnalyzer->getModel());
            } else {
                Log::error('❌ AI service not configured (missing OpenAI API key)');
            }

            // Test service functionality
            if ($this->termAnalyzer->testService()) {
                $results['service'] = true;
                Log::info('✅ AI service test passed');
            } else {
                Log::error('❌ AI service test failed');
            }

        } catch (Exception $e) {
            Log::error('❌ AI test failed: '.$e->getMessage());
        }

        return $results;
    }

    private function testTelegram()
    {
        Log::info('📱 Testing Telegram Notifications...');

        $results = [
            'configuration' => false,
            'connection' => false,
        ];

        try {
            // Test configuration
            if ($this->notificationService->isConfigured()) {
                $results['configuration'] = true;
                Log::info('✅ Telegram service configured');
            } else {
                Log::error('❌ Telegram service not configured');
            }

            // Test connection
            if ($this->notificationService->testService()) {
                $results['connection'] = true;
                Log::info('✅ Telegram connection test passed');
            } else {
                Log::error('❌ Telegram connection test failed');
            }

        } catch (Exception $e) {
            Log::error('❌ Telegram test failed: '.$e->getMessage());
        }

        return $results;
    }

    private function displayResults($results)
    {
        $this->newLine();
        Log::info('📋 Test Results Summary:');
        $this->newLine();

        $allPassed = true;

        foreach ($results as $component => $componentResults) {
            Log::info("🔧 {$component}:");

            if (is_array($componentResults)) {
                foreach ($componentResults as $test => $passed) {
                    $status = $passed ? '✅' : '❌';
                    Log::info("   {$status} {$test}");
                    if (! $passed) {
                        $allPassed = false;
                    }
                }
            }
            $this->newLine();
        }

        if ($allPassed) {
            Log::info('🎉 All tests passed! System is ready for deployment.');
        } else {
            Log::error('⚠️  Some tests failed. Please check configuration and fix issues before deployment.');
        }

        $this->newLine();
        Log::info('💡 Next steps:');
        Log::info('1. Run migrations: php artisan migrate');
        Log::info('2. Configure environment variables in .env');
        Log::info('3. Set up cron job: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1');
        Log::info('4. Test individual commands manually before enabling automation');
    }
}
