<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewFrasaNegative;
use App\Services\AI\FrasaAnalyzer;

class AnalyzePhrasesWithAICommand extends Command
{
    protected $signature = 'negative-keywords:analyze-frasa {--batch-size=2 : Jumlah frasa dianalisis per batch (0=semua)}';
    protected $description = 'Analisis frasa dengan AI untuk menentukan hasil_cek_ai: indonesia/luar';

    public function handle(FrasaAnalyzer $analyzer): int
    {
        if (!$analyzer->isConfigured()) {
            $this->error('OpenAI belum dikonfigurasi. Set OPENAI_API_KEY.');
            return 1;
        }

        $batchSize = (int)$this->option('batch-size');

        $query = NewFrasaNegative::needsAiAnalysis();
        if ($batchSize > 0) {
            $query->limit($batchSize);
        }
        $items = $query->get();

        if ($items->isEmpty()) {
            $this->info('Tidak ada frasa yang perlu dianalisis AI.');
            return 0;
        }

        $this->info("Menganalisis {$items->count()} frasa...");
        $updated = 0;
        $luar = 0;
        $indonesia = 0;

        foreach ($items as $item) {
            $result = $analyzer->analyzeFrasa($item->frasa);
            if ($result === null) {
                $this->line("Gagal/ambigu: {$item->frasa}");
                continue;
            }

            $normalized = strtolower($result);
            if (!in_array($normalized, [
                \App\Models\NewFrasaNegative::HASIL_CEK_AI_INDONESIA,
                \App\Models\NewFrasaNegative::HASIL_CEK_AI_LUAR
            ], true)) {
                $this->line("Nilai AI tidak valid: {$result} untuk frasa {$item->frasa}");
                continue;
            }

            $item->update(['hasil_cek_ai' => $normalized]);
            $updated++;
            if ($result === NewFrasaNegative::HASIL_CEK_AI_LUAR) $luar++;
            if ($result === NewFrasaNegative::HASIL_CEK_AI_INDONESIA) $indonesia++;
        }

        $this->info("Selesai. Diupdate: {$updated}, luar: {$luar}, indonesia: {$indonesia}");
        return 0;
    }
}