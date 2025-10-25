<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Velocity\NegativeKeywordInputService;
use App\Models\NewTermsNegative0Click;
use App\Models\NewFrasaNegative;
use App\Services\Telegram\NotificationService;

class InputNegativeKeywordsVelocityCommand extends Command
{
    protected $signature = 'negative-keywords:input-velocity 
        {--source=terms : Sumber data: terms|frasa} 
        {--mode=validate : Mode: validate|execute} 
        {--batch-size=50 : Jumlah maksimal item per sumber}';

    protected $description = 'Input negative keywords ke Velocity API (validate/execute) dari terms AI-negatif dan frasa.';

    public function handle()
    {
        $source = strtolower((string)$this->option('source') ?? 'both');
        $mode = strtolower((string)$this->option('mode') ?? 'validate');
        $batchSize = (int)$this->option('batch-size');

        $svc = new NegativeKeywordInputService();
        $notifier = app(\App\Services\Telegram\NotificationService::class);

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
                    // Kirim notifikasi Telegram untuk terms
                    $this->notifyTelegram($notifier, 'terms', $terms, $matchType, $mode, $res);

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
                    // Kirim notifikasi Telegram untuk frasa
                    $this->notifyTelegram($notifier, 'frasa', $phrases, $matchType, $mode, $res);

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
            $data = $res['json']['data'] ?? [];
            if (!empty($data)) {
                $details = [];
                if (array_key_exists('campaign_id', $data)) { $details[] = "campaign_id={$data['campaign_id']}"; }
                if (array_key_exists('match_type', $data)) { $details[] = "match_type={$data['match_type']}"; }
                if (array_key_exists('validate_only', $data)) { $details[] = "validate_only=" . ($data['validate_only'] ? 'true' : 'false'); }
                if (array_key_exists('terms', $data) && is_array($data['terms'])) { $details[] = 'terms_count=' . count($data['terms']); }
                if (!empty($details)) { $this->line('Details: ' . implode(', ', $details)); }
            }
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
                if ($row->retry_count >= 3) {
                    $row->update(['status_input_google' => NewTermsNegative0Click::STATUS_ERROR]);
                } else {
                    $row->update(['status_input_google' => NewTermsNegative0Click::STATUS_GAGAL]);
                }
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
                if ($row->retry_count >= 3) {
                    $row->update(['status_input_google' => NewFrasaNegative::STATUS_ERROR]);
                } else {
                    $row->update(['status_input_google' => NewFrasaNegative::STATUS_GAGAL]);
                }
            }
        }
    }

    // Helper: kirim notifikasi Telegram dengan daftar item
    private function notifyTelegram(NotificationService $notifier, string $src, array $items, string $matchType, string $mode, array $res): void
    {
        $count = count($items);
        $list = implode(', ', array_slice($items, 0, 50));
        $timestamp = now()->format('Y-m-d H:i:s');

        $data = $res['json']['data'] ?? [];
        $campaignId = $data['campaign_id'] ?? null;
        $apiMatchType = $data['match_type'] ?? null;
        $validateOnly = $data['validate_only'] ?? null;

        if ($res['success']) {
            $message = "✅ <b>Input Keywords Berhasil</b>\n\n" .
                "📦 <b>Sumber:</b> {$src}\n" .
                "🧮 <b>Jumlah:</b> {$count}\n" .
                "📝 <b>Match Type:</b> {$matchType}" . ($apiMatchType ? " (API={$apiMatchType})" : "") . "\n" .
                "⚙️ <b>Mode:</b> {$mode}" . (is_bool($validateOnly) ? " (validate_only=" . ($validateOnly ? 'true' : 'false') . ")" : "") . "\n" .
                ($campaignId ? "📣 <b>Campaign ID:</b> {$campaignId}\n" : "") .
                "🗒️ <b>Items:</b> {$list}\n" .
                "⏰ <b>Waktu:</b> {$timestamp}";
        } else {
            $error = $res['error'] ?? 'Unknown error';
            $status = $res['status'] ?? 'N/A';
            $message = "❌ <b>Input Keywords Gagal</b>\n\n" .
                "📦 <b>Sumber:</b> {$src}\n" .
                "🧮 <b>Jumlah:</b> {$count}\n" .
                "📝 <b>Match Type:</b> {$matchType}\n" .
                "⚙️ <b>Mode:</b> {$mode}\n" .
                "📡 <b>Status API:</b> {$status}\n" .
                "❗ <b>Error:</b> {$error}\n" .
                "🗒️ <b>Items:</b> {$list}\n" .
                "⏰ <b>Waktu:</b> {$timestamp}" .
                "<b>Test by : kdt</b>";
        }

        $notifier->sendMessage($message);
    }
}