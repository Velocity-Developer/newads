<?php

namespace App\Services\Velocity;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class NegativeKeywordsPipelineService
{
    public function run(bool $validate = true): array
    {
        $version = config('app.version');
        $startAll = microtime(true);
        $summary = [
            'version' => $version,
            'validate_mode' => $validate,
            'steps' => [],
            'total_duration_seconds' => 0,
            'success' => true,
        ];

        $steps = [
            ['cmd' => 'negative-keywords:fetch-terms', 'opts' => []],
            ['cmd' => 'negative-keywords:analyze-terms', 'opts' => []],
            ['cmd' => 'negative-keywords:input-velocity', 'opts' => ['--source' => 'terms', '--mode' => $validate ? 'validate' : 'execute']],
            ['cmd' => 'negative-keywords:process-phrases', 'opts' => []],
            ['cmd' => 'negative-keywords:analyze-frasa', 'opts' => []],
            ['cmd' => 'negative-keywords:input-velocity', 'opts' => ['--source' => 'frasa', '--mode' => $validate ? 'validate' : 'execute']],
        ];

        Log::info("🚀 NegativeKeywordsPipeline started (version: {$version}, mode: ".($validate ? 'validate' : 'execute').')');

        foreach ($steps as $index => $step) {
            $label = "{$step['cmd']} ".(empty($step['opts']) ? '' : json_encode($step['opts']));
            $t0 = microtime(true);

            try {
                $exitCode = Artisan::call($step['cmd'], $step['opts']);
                $output = Artisan::output();
            } catch (\Throwable $e) {
                $exitCode = 1;
                $output = 'Exception: '.$e->getMessage();
            }

            $duration = round(microtime(true) - $t0, 3);
            $success = ($exitCode === 0);

            $summary['steps'][] = [
                'step' => $index + 1,
                'command' => $step['cmd'],
                'options' => $step['opts'],
                'duration_seconds' => $duration,
                'exit_code' => $exitCode,
                'success' => $success,
            ];

            Log::info("➡️ {$label} finished in {$duration}s (exit={$exitCode})");

            if (! $success) {
                $summary['success'] = false;
                // lanjutkan ke step berikutnya, atau break jika Anda ingin stop di failure:
                // break;
            }
        }

        $summary['total_duration_seconds'] = round(microtime(true) - $startAll, 3);
        Log::info("✅ NegativeKeywordsPipeline completed in {$summary['total_duration_seconds']}s, success=".($summary['success'] ? 'true' : 'false'));

        return $summary;
    }
}
