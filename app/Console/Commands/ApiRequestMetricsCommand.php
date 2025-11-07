<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApiRequestMetricsCommand extends Command
{
    protected $signature = 'metrics:api-requests {--date=} {--component=}';
    protected $description = 'Hitung jumlah request API per hari dari log METRIC:API_REQUEST';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $component = $this->option('component');

        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            $this->error('Log file not found: ' . $logPath);
            return 1;
        }

        $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $this->error('Unable to read log file.');
            return 1;
        }

        $totalRequests = 0;
        $totalTerms = 0;
        $byComponent = [];

        foreach ($lines as $line) {
            $pos = strpos($line, 'METRIC:API_REQUEST ');
            if ($pos === false) continue;

            $jsonPart = substr($line, $pos + strlen('METRIC:API_REQUEST '));
            $data = json_decode($jsonPart, true);
            if (!is_array($data)) continue;

            if (($data['date'] ?? null) !== $date) continue;
            if ($component && ($data['component'] ?? null) !== $component) continue;

            $req = (int)($data['request_count'] ?? 1);
            $cnt = (int)($data['terms_count'] ?? 0);
            $comp = (string)($data['component'] ?? 'unknown');

            $totalRequests += $req;
            $totalTerms += $cnt;
            $byComponent[$comp] = ($byComponent[$comp] ?? 0) + $req;
        }

        $this->info("📊 API Requests on {$date}");
        $this->line("Total Requests: {$totalRequests}");
        $this->line("Total Terms Sent: {$totalTerms}");
        if (!empty($byComponent)) {
            $this->line("By Component:");
            foreach ($byComponent as $comp => $count) {
                $this->line("- {$comp}: {$count}");
            }
        }

        return 0;
    }
}