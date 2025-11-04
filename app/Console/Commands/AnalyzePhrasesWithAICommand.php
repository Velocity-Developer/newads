<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewFrasaNegative;
use App\Services\AI\FrasaAnalyzer;

class AnalyzePhrasesWithAICommand extends Command
{
    protected $signature = 'negative-keywords:analyze-frasa {--batch-size=0 : Jumlah frasa dianalisis per batch (0=semua)} {--dry-run : Jangan simpan ke database} {--show-raw : Tampilkan jawaban AI utuh}';
    protected $description = 'Analisis frasa dengan AI untuk menentukan hasil_cek_ai: indonesia/luar';

    public function handle(FrasaAnalyzer $analyzer): int
    {
        if (!$analyzer->isConfigured()) {
            Log::error('OpenAI belum dikonfigurasi. Set OPENAI_API_KEY.');
            return 1;
        }

        $batchSize = (int)$this->option('batch-size');
        $dryRun = (bool)$this->option('dry-run');
        $showRaw = (bool)$this->option('show-raw');

        $query = NewFrasaNegative::needsAiAnalysis();
        if ($batchSize > 0) {
            $query->limit($batchSize);
        }
        $items = $query->get();

        if ($items->isEmpty()) {
            Log::info('Tidak ada frasa yang perlu dianalisis AI.');
            return 0;
        }

        Log::info("Menganalisis {$items->count()} frasa...");
        $updated = 0;
        $luar = 0;
        $indonesia = 0;

        foreach ($items as $item) {
            Log::info("Menganalisis: {$item->frasa}");

            if ($showRaw) {
                $raw = $analyzer->analyzeFrasaRaw($item->frasa);
                if (($raw['success'] ?? false) === true) {
                    Log::info('RAW AI response', $raw['content'] ?? '');
                } else {
                    Log::error('RAW AI error', $raw['error'] ?? 'Unknown error');
                }
            }

            $result = $analyzer->analyzeFrasa($item->frasa);
            if ($result === null) {
                Log::error('❌ AI analysis failed', ['frasa' => $item->frasa]);
                continue;
            }

            Log::info("✅ Hasil: {$item->frasa} → {$result}");

            if ($dryRun) {
                Log::info("⏭️ Dry-run aktif: skip update database untuk {$item->frasa}");
            } else {
                $item->update(['hasil_cek_ai' => $result]);
                $updated++;
                if ($result === NewFrasaNegative::HASIL_CEK_AI_LUAR) $luar++;
                if ($result === NewFrasaNegative::HASIL_CEK_AI_INDONESIA) $indonesia++;
            }
        }

        Log::info("Selesai. Diupdate: {$updated}, luar: {$luar}, indonesia: {$indonesia}");
        return 0;
    }
}