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
        {--batch-size=0 : Jumlah maksimal item per sumber}';

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
                $query = NewTermsNegative0Click::needsGoogleAdsInput()
                    ->where('retry_count', '<', 3)
                    ->select(['terms', 'campaign_id']);

                if ($batchSize > 0) {
                    $query->limit($batchSize);
                }

                $rows = $query->get();
                $groups = $rows->groupBy(function ($row) {
                    return $row->campaign_id === null ? 'null' : (string)$row->campaign_id;
                });

                $matchType = $svc->getMatchTypeForSource('terms');

                foreach ($groups as $cidKey => $group) {
                    $terms = $group->pluck('terms')
                        ->map(fn($t) => trim((string)$t))
                        ->filter(fn($t) => $t !== '')
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($terms)) {
                        continue;
                    }

                    $campaignId = $cidKey === 'null' ? null : (int)$cidKey;

                    $res = $svc->send($terms, $matchType, $mode, $campaignId);

                    $this->reportResult('terms', $res);
                    $this->notifyTelegram($notifier, 'terms', $terms, $matchType, $mode, $res);
                    $this->updateStatusesForTerms($terms, $res['success']);
                }
            }

            if ($src === 'frasa') {
                $query = NewFrasaNegative::needsGoogleAdsInput()
                    ->where('retry_count', '<', 3)
                    ->select(['frasa', 'campaign_id']);

                if ($batchSize > 0) {
                    $query->limit($batchSize);
                }

                $rows = $query->get();
                $groups = $rows->groupBy(function ($row) {
                    return $row->campaign_id === null ? 'null' : (string)$row->campaign_id;
                });

                $matchType = $svc->getMatchTypeForSource('frasa');

                foreach ($groups as $cidKey => $group) {
                    $phrases = $group->pluck('frasa')
                        ->map(fn($t) => trim((string)$t))
                        ->filter(fn($t) => $t !== '')
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($phrases)) {
                        continue;
                    }

                    $campaignId = $cidKey === 'null' ? null : (int)$cidKey;

                    $res = $svc->send($phrases, $matchType, $mode, $campaignId);

                    $this->reportResult('frasa', $res);
                    $this->notifyTelegram($notifier, 'frasa', $phrases, $matchType, $mode, $res);
                    $this->updateStatusesForFrasa($phrases, $res['success']);
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
            $this->info("✅ Berhasil Input Negative Keywords!");
            $this->line("Status API: {$status}");
            if ($count !== null) $this->line("Jumlah: {$count}");
            if ($matchType !== null) $this->line("Match type (API): {$matchType}");
            if ($validateOnly !== null) $this->line("Validate only: " . ($validateOnly ? 'true' : 'false'));
            if ($campaignId !== null) $this->line("Campaign ID: {$campaignId}");
        } else {
            $error = $res['error'] ?? 'Unknown error';
            $this->error("❌ Gagal Input Negative Keywords: {$error}");
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
        $timestamp = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $data = $res['json']['data'] ?? [];
        $campaignId = $data['campaign_id'] ?? null;
        $apiMatchType = $data['match_type'] ?? null;
        $validateOnly = $data['validate_only'] ?? null;

        // Format list sesuai match type (gunakan API match type jika tersedia)
        $effectiveMatchType = strtoupper($apiMatchType ?? $matchType);
        $list = implode("\n", array_map(function ($item) use ($effectiveMatchType) {
            if ($effectiveMatchType === 'EXACT') {
                return '[' . $item . ']';
            } elseif ($effectiveMatchType === 'PHRASE') {
                return '"' . $item . '"';
            }
            // Fallback untuk tipe lain: tampilkan apa adanya
            return $item . ' ' . $effectiveMatchType;
        }, array_slice($items, 0, 50)));

        if ($res['success']) {
            $message = "✅ <b>Berhasil Input Keywords Negative</b>\n\n" .
                // "📦 <b>Sumber:</b> {$src}\n" .
                "🧮 <b>Jumlah:</b> {$count} data\n" .
                // "📝 <b>Match Type:</b> {$matchType}" . ($apiMatchType ? " (API={$apiMatchType})" : "") . "\n" .
                "⚙️ <b>Mode:</b> {$mode}" . (is_bool($validateOnly) ? " (validate_only=" . ($validateOnly ? 'true' : 'false') . ")" : "") . "\n" .
                // ($campaignId ? "📣 <b>Campaign ID:</b> {$campaignId}\n" : "") .
                "⏰ <b>Waktu:</b> {$timestamp}\n" .
                "🗒️ <b>Keywords:</b>\n{$list}\n";
        } else {
            $error = $res['error'] ?? 'Unknown error';
            $status = $res['status'] ?? 'N/A';
            $message = "❌ <b>Gagal Input Keywords Negative</b>\n\n" .
                // "📦 <b>Sumber:</b> {$src}\n" .
                "🧮 <b>Jumlah:</b> {$count} data\n" .
                // "📝 <b>Match Type:</b> {$matchType}\n" .
                "⚙️ <b>Mode:</b> {$mode}\n" .
                "📡 <b>Status API:</b> {$status}\n" .
                "❗ <b>Error:</b> {$error}\n" .
                "⏰ <b>Waktu:</b> {$timestamp}\n" .
                "🗒️ <b>Keywords:</b>\n\n{$list}\n";
        }

        $notifier->sendMessage($message);
        // $this->line($message);
    }
}