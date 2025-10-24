<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Velocity\NegativeKeywordInputService;
use App\Models\NewTermsNegative0Click;
use App\Models\NewFrasaNegative;

class InputNegativeKeywordsVelocityCommand extends Command
{
    protected $signature = 'negative-keywords:input-velocity 
        {--source=both : Sumber data: terms|frasa|both} 
        {--mode=validate : Mode: validate|execute} 
        {--batch-size=50 : Jumlah maksimal item per sumber}';

    protected $description = 'Input negative keywords ke Velocity API (validate/execute) dari terms AI-negatif dan frasa.';

    public function handle()
    {
        $source = strtolower((string)$this->option('source') ?? 'both');
        $mode = strtolower((string)$this->option('mode') ?? 'validate');
        $batchSize = (int)$this->option('batch-size');

        $svc = new NegativeKeywordInputService();

        $this->info("Velocity Negative Keywords Input");
        $this->line("Source: {$source}, Mode: {$mode}, Batch size: {$batchSize}");

        $sources = [];
        if ($source === 'terms' || $source === 'both') {
            $sources[] = 'terms';
        }
        if ($source === 'frasa' || $source === 'both') {
            $sources[] = 'frasa';
        }

        foreach ($sources as $src) {
            if ($src === 'terms') {
                $this->line('Debug: candidates (terms) = ' . \App\Models\NewTermsNegative0Click::needsGoogleAdsInput()->count());
                $terms = NewTermsNegative0Click::needsGoogleAdsInput()
                    ->limit($batchSize)
                    ->pluck('terms')
                    ->toArray();
                $this->line('Debug: sample terms = ' . implode(', ', array_slice($terms, 0, 5)));

                $matchType = $svc->getMatchTypeForSource('terms');

                if (empty($terms)) {
                    $this->warn('Tidak ada terms untuk diproses.');
                } else {
                    $this->line("Mengirim " . count($terms) . " terms (match_type={$matchType})...");
                    $res = $svc->send($terms, $matchType, $mode);

                    $this->reportResult('terms', $res);

                    if ($mode === 'execute') {
                        $this->updateStatusesForTerms($terms, $res['success']);
                    }
                }
            }

            if ($src === 'frasa') {
                $phrases = NewFrasaNegative::needsGoogleAdsInput()
                    ->limit($batchSize)
                    ->pluck('frasa')
                    ->toArray();

                $matchType = $svc->getMatchTypeForSource('frasa');

                if (empty($phrases)) {
                    $this->warn('Tidak ada frasa untuk diproses.');
                } else {
                    $this->line("Mengirim " . count($phrases) . " frasa (match_type={$matchType})...");
                    $res = $svc->send($phrases, $matchType, $mode);

                    $this->reportResult('frasa', $res);

                    if ($mode === 'execute') {
                        $this->updateStatusesForFrasa($phrases, $res['success']);
                    }
                }
            }
        }

        return 0;
    }

    private function reportResult(string $src, array $res): void
    {
        if ($res['success']) {
            $this->info("✅ {$src}: API success (status={$res['status']})");
        } else {
            $this->error("❌ {$src}: API failed (status=" . ($res['status'] ?? 'N/A') . ")");
            if (!empty($res['error'])) {
                $this->error("Error: {$res['error']}");
            }
        }
    }

    private function updateStatusesForTerms(array $terms, bool $success): void
    {
        if ($success) {
            NewTermsNegative0Click::whereIn('terms', $terms)
                ->update([
                    'status_input_google' => NewTermsNegative0Click::STATUS_BERHASIL,
                    'notif_telegram' => NewTermsNegative0Click::NOTIF_BERHASIL,
                ]);
        } else {
            $rows = NewTermsNegative0Click::whereIn('terms', $terms)->get();
            foreach ($rows as $row) {
                $row->incrementRetry();
                $row->update(['status_input_google' => NewTermsNegative0Click::STATUS_GAGAL]);
            }
        }
    }

    private function updateStatusesForFrasa(array $phrases, bool $success): void
    {
        if ($success) {
            NewFrasaNegative::whereIn('frasa', $phrases)
                ->update([
                    'status_input_google' => NewFrasaNegative::STATUS_BERHASIL,
                    'notif_telegram' => NewFrasaNegative::NOTIF_BERHASIL,
                ]);
        } else {
            $rows = NewFrasaNegative::whereIn('frasa', $phrases)->get();
            foreach ($rows as $row) {
                $row->incrementRetry();
                $row->update(['status_input_google' => NewFrasaNegative::STATUS_GAGAL]);
            }
        }
    }
}