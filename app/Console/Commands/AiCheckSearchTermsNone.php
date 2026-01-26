<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SearchTermsAds\CheckAiServices;

class AiCheckSearchTermsNone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ai-check-search-terms-none';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //CheckAiServices
        $checkAiServices = new CheckAiServices;
        $response = $checkAiServices->check_search_terms_none();

        //return response   
        $this->info(var_dump($response));
    }
}
