<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAds\SearchTermFetcher;
use App\Services\AI\TermAnalyzer;
use App\Services\Telegram\NotificationService;
use App\Models\NewTermsNegative0Click;
use App\Models\NewFrasaNegative;
use Exception;

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
        $this->info('🧪 Testing Negative Keywords Automation System');
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
        $this->info('📊 Testing Database Components...');
        
        $results = [
            'migrations' => false,
            'models' => false,
            'relationships' => false
        ];

        try {
            // Test if tables exist
            if (\Schema::hasTable('new_terms_negative_0click') && \Schema::hasTable('new_frasa_negative')) {
                $results['migrations'] = true;
                $this->line('✅ Database tables exist');
            } else {
                $this->error('❌ Database tables missing');
            }

            // Test models
            $termModel = new NewTermsNegative0Click();
            $phraseModel = new NewFrasaNegative();
            
            if ($termModel && $phraseModel) {
                $results['models'] = true;
                $this->line('✅ Models instantiated successfully');
            }

            // Test relationships
            $testTerm = NewTermsNegative0Click::create([
                'terms' => 'test term for system check',
                'hasil_cek_ai' => null,
                'status_input_google' => null,
                'retry_count' => 0,
                'notif_telegram' => null
            ]);

            $testPhrase = NewFrasaNegative::create([
                'frasa' => 'test phrase',
                'parent_term_id' => $testTerm->id,
                'status_input_google' => null,
                'retry_count' => 0,
                'notif_telegram' => null
            ]);

            if ($testTerm->phrases()->count() > 0 && $testPhrase->parentTerm) {
                $results['relationships'] = true;
                $this->line('✅ Model relationships working');
            }

            // Cleanup test data
            $testPhrase->delete();
            $testTerm->delete();

        } catch (Exception $e) {
            $this->error("❌ Database test failed: " . $e->getMessage());
        }

        return $results;
    }

    private function testGoogleAds()
    {
        $this->info('🎯 Testing Google Ads Integration...');
        
        $results = [
            'configuration' => false,
            'connection' => false
        ];

        try {
            // Test configuration
            $config = $this->searchTermFetcher->getConfig();
            if (!empty($config['client_id']) && !empty($config['developer_token'])) {
                $results['configuration'] = true;
                $this->line('✅ Google Ads configuration found');
            } else {
                $this->error('❌ Google Ads configuration missing');
            }

            // Test connection (placeholder - would need actual API call)
            $this->line('⚠️  Google Ads API connection test requires actual credentials');
            
        } catch (Exception $e) {
            $this->error("❌ Google Ads test failed: " . $e->getMessage());
        }

        return $results;
    }

    private function testAI()
    {
        $this->info('🤖 Testing AI Analysis Service...');
        
        $results = [
            'configuration' => false,
            'service' => false
        ];

        try {
            // Test configuration
            if ($this->termAnalyzer->isConfigured()) {
                $results['configuration'] = true;
                $this->line('✅ AI service configured');
            } else {
                $this->error('❌ AI service not configured (missing OpenAI API key)');
            }

            // Test service functionality
            if ($this->termAnalyzer->testService()) {
                $results['service'] = true;
                $this->line('✅ AI service test passed');
            } else {
                $this->error('❌ AI service test failed');
            }

        } catch (Exception $e) {
            $this->error("❌ AI test failed: " . $e->getMessage());
        }

        return $results;
    }

    private function testTelegram()
    {
        $this->info('📱 Testing Telegram Notifications...');
        
        $results = [
            'configuration' => false,
            'connection' => false
        ];

        try {
            // Test configuration
            if ($this->notificationService->isConfigured()) {
                $results['configuration'] = true;
                $this->line('✅ Telegram service configured');
            } else {
                $this->error('❌ Telegram service not configured');
            }

            // Test connection
            if ($this->notificationService->testConnection()) {
                $results['connection'] = true;
                $this->line('✅ Telegram connection test passed');
            } else {
                $this->error('❌ Telegram connection test failed');
            }

        } catch (Exception $e) {
            $this->error("❌ Telegram test failed: " . $e->getMessage());
        }

        return $results;
    }

    private function displayResults($results)
    {
        $this->newLine();
        $this->info('📋 Test Results Summary:');
        $this->newLine();

        $allPassed = true;

        foreach ($results as $component => $componentResults) {
            $this->line("🔧 {$component}:");
            
            if (is_array($componentResults)) {
                foreach ($componentResults as $test => $passed) {
                    $status = $passed ? '✅' : '❌';
                    $this->line("   {$status} {$test}");
                    if (!$passed) $allPassed = false;
                }
            }
            $this->newLine();
        }

        if ($allPassed) {
            $this->info('🎉 All tests passed! System is ready for deployment.');
        } else {
            $this->error('⚠️  Some tests failed. Please check configuration and fix issues before deployment.');
        }

        $this->newLine();
        $this->info('💡 Next steps:');
        $this->line('1. Run migrations: php artisan migrate');
        $this->line('2. Configure environment variables in .env');
        $this->line('3. Set up cron job: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1');
        $this->line('4. Test individual commands manually before enabling automation');
    }
}
