<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Velocity\NegativeKeywordsPipelineService;
use Illuminate\Support\Facades\Log;

class RunNegativeKeywordsPipelineCommand extends Command
{
    protected $signature = 'negative-keywords:pipeline {--apply : Apply changes instead of validate} {--json : Output JSON summary}';
    protected $description = 'Run Negative Keywords pipeline sequentially (fetch → analyze → velocity terms → process phrases → velocity frasa)';

    public function handle(NegativeKeywordsPipelineService $service): int
    {
        $validate = !$this->option('apply');
        $summary = $service->run($validate);

        if ($this->option('json')) {
            Log::info(json_encode($summary, JSON_PRETTY_PRINT));
        } else {
            Log::info('🚀Negative Keywords Pipeline');
            // Log::info('Version: ' . ($summary['version'] ?? 'n/a'));
            // Log::info('Mode: ' . ($validate ? 'validate' : 'apply'));
            // foreach ($summary['steps'] as $step) {
            //     Log::info(sprintf(
            //         '- Step %d: %s (%ss) exit=%d %s',
            //         $step['step'],
            //         $step['command'],
            //         $step['duration_seconds'],
            //         $step['exit_code'],
            //         $step['success'] ? 'OK' : 'FAIL'
            //     ));
            // }
            Log::info('Total: ' . $summary['total_duration_seconds'] . 's');
            Log::info('Result: ' . ($summary['success'] ? 'SUCCESS' : 'PARTIAL/FAILED'));
        }

        return $summary['success'] ? 0 : 1;
    }
}