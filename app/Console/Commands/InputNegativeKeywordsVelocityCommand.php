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
        $source = strtolower((string)$this->option('source') ?? '');
        $mode = strtolower((string)$this->option('mode') ?? 'validate');
        $batchSize = (int)$this->option('batch-size');

        $svc = new NegativeKeywordInputService();
        $notifier = app(\App\Services\Telegram\NotificationService::class);

        $this->info("Velocity Negative Keywords Input");
        $this->line("Source: {$source}, Mode: {$mode}, Batch size: {$batchSize}");

        $sources = [];
        if ($source === 'terms' || $source === '') {
            $sources[] = 'terms';
        }
        if ($source === 'frasa' || $source === '') {
            $sources[] = 'frasa';
        }

        foreach ($sources as $src) {
            if ($src === 'terms') {
                // Tambah debug nama database aktif
                $this->line('Debug: DB connection = ' . \DB::connection()->getDatabaseName());

                // Snapshot statistik cepat
                $total = NewTermsNegative0Click::query()->count();
                $cntStatus = NewTermsNegative0Click::whereIn('status_input_google', [null, NewTermsNegative0Click::STATUS_GAGAL, NewTermsNegative0Click::STATUS_ERROR])->count();
                $cntRetryOk = NewTermsNegative0Click::where('retry_count', '<', 3)->count();
                $cntNegatif = NewTermsNegative0Click::where('hasil_cek_ai', NewTermsNegative0Click::HASIL_AI_NEGATIF)->count();
                $this->line("Debug: totals = total={$total}, status_in=[null,gagal,error]={$cntStatus}, retry<3={$cntRetryOk}, hasil_cek_ai=negatif={$cntNegatif}");

                // Gunakan query lebih longgar saat mode validate
                $query = NewTermsNegative0Click::query();
                if ($mode === 'validate') {
                    // Validate hanya untuk kandidat AI-negatif, tanpa membatasi status_input_google
                    $query->where('hasil_cek_ai', NewTermsNegative0Click::HASIL_AI_NEGATIF)
                        ->whereIn('status_input_google', [null, NewTermsNegative0Click::STATUS_GAGAL])
                        ->where('retry_count', '<', 3);
                } else {
                    $query->where('hasil_cek_ai', NewTermsNegative0Click::HASIL_AI_NEGATIF)
                        ->whereIn('status_input_google', [null, NewTermsNegative0Click::STATUS_GAGAL])
                        ->where('retry_count', '<', 3);
                }

                $this->line('Debug: candidates (terms) = ' . $query->count());

                $terms = $query
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
                    // $this->notifyTelegram($notifier, 'terms', $terms, $matchType, $mode, $res);

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
                    // $this->notifyTelegram($notifier, 'frasa', $phrases, $matchType, $mode, $res);

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
        $success = $res['success'] ?? false;
        $status = $res['status'] ?? 'N/A';
        $data = $res['json']['data'] ?? [];
        $count = $data['count'] ?? null;
        $matchType = $data['match_type'] ?? null;
        $campaignId = $data['campaign_id'] ?? null;
        $validateOnly = $data['validate_only'] ?? null;

        if ($success) {
            $this->info("✅ New Ads Berhasil Input Negative Keywords!");
            $this->line("Status API: {$status}");
            if ($count !== null) $this->line("Jumlah: {$count}");
            if ($matchType !== null) $this->line("Match type (API): {$matchType}");
            if ($validateOnly !== null) $this->line("Validate only: " . ($validateOnly ? 'true' : 'false'));
            if ($campaignId !== null) $this->line("Campaign ID: {$campaignId}");
        } else {
            $error = $res['error'] ?? 'Unknown error';
            $this->error("❌ New Ads Gagal Input Negative Keywords: {$error}");
            $this->line("Status API: {$status}");
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
        $timestamp = now()->format('Y-m-d H:i:s');

        $data = $res['json']['data'] ?? [];
        $campaignId = $data['campaign_id'] ?? null;
        $apiMatchType = $data['match_type'] ?? null;
        $validateOnly = $data['validate_only'] ?? null;

        // Format list sesuai match type (gunakan API match type jika tersedia)
        $effectiveMatchType = strtoupper($apiMatchType ?? $matchType);
        $list = implode("\n", array_map(function ($item) use ($effectiveMatchType) {
            if ($effectiveMatchType === 'EXACT') {
                return '[' . $item . '] EXACT';
            } elseif ($effectiveMatchType === 'PHRASE') {
                return '"' . $item . '" PHRASE';
            }
            // Fallback untuk tipe lain: tampilkan apa adanya
            return $item . ' ' . $effectiveMatchType;
        }, array_slice($items, 0, 50)));

        if ($res['success']) {
            $message = "✅ <b>News Ads Berhasil Input Keywords Negative</b>\n\n" .
                // "📦 <b>Sumber:</b> {$src}\n" .
                "🧮 <b>Jumlah:</b> {$count}\n" .
                "📝 <b>Match Type:</b> {$matchType}" . ($apiMatchType ? " (API={$apiMatchType})" : "") . "\n" .
                // "⚙️ <b>Mode:</b> {$mode}" . (is_bool($validateOnly) ? " (validate_only=" . ($validateOnly ? 'true' : 'false') . ")" : "") . "\n" .
                // ($campaignId ? "📣 <b>Campaign ID:</b> {$campaignId}\n" : "") .
                "⏰ <b>Waktu:</b> {$timestamp}\n" .
                "🗒️ <b>Keywords:</b>\n{$list}\n";
        } else {
            $error = $res['error'] ?? 'Unknown error';
            $status = $res['status'] ?? 'N/A';
            $message = "❌ <b>News Ads Gagal Input Keywords Negative</b>\n\n" .
                "📦 <b>Sumber:</b> {$src}\n" .
                "🧮 <b>Jumlah:</b> {$count}\n" .
                "📝 <b>Match Type:</b> {$matchType}\n" .
                "⚙️ <b>Mode:</b> {$mode}\n" .
                "📡 <b>Status API:</b> {$status}\n" .
                "❗ <b>Error:</b> {$error}\n" .
                "⏰ <b>Waktu:</b> {$timestamp}\n" .
                "🗒️ <b>Keywords:</b>\n{$list}\n";
        }

        $notifier->sendMessage($message);
    }
}