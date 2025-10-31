<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateAllDataCommand extends Command
{
    protected $signature = 'data:truncate-all {--confirm : Skip confirmation prompt} {--only= : Comma-separated tables to truncate}';
    protected $description = 'Truncate all data tables and reset auto-increment IDs to 1';

    public function handle()
    {
        if (!$this->option('confirm')) {
            if (!$this->confirm('⚠️  PERINGATAN: Ini akan TRUNCATE semua tabel data. Lanjutkan?')) {
                $this->info('Operasi dibatalkan.');
                return 0;
            }
        }

        $this->info('🗑️  Memulai TRUNCATE tabel...');

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Tables to truncate (child tables first)
            $tables = [
                'new_frasa_negative',
                'new_terms_negative_0click',
            ];

            // Filter tables if --only provided
            $onlyOpt = $this->option('only');
            if ($onlyOpt) {
                $requested = array_values(array_filter(array_map('trim', explode(',', $onlyOpt))));
                if (!empty($requested)) {
                    $tables = array_values(array_intersect($tables, $requested));
                    if (empty($tables)) {
                        $this->error('❌ Tidak ada tabel yang cocok dengan opsi --only.');
                        // Re-enable foreign key checks before exit
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');
                        return 1;
                    }
                }
            }

            // Konfirmasi dengan daftar tabel yang akan di-truncate
            if (!$this->option('confirm')) {
                $list = implode(', ', $tables);
                if (!$this->confirm("⚠️  PERINGATAN: Ini akan TRUNCATE tabel: {$list}. Lanjutkan?")) {
                    $this->info('Operasi dibatalkan.');
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    return 0;
                }
            }

            $truncated = [];
            $skipped = [];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        DB::statement("TRUNCATE TABLE `{$table}`");
                        $truncated[] = "{$table} ({$count} records)";
                        $this->line("✅ TRUNCATED: {$table} ({$count} records)");
                    } else {
                        $skipped[] = "{$table} (kosong)";
                        $this->line("⏭️  SKIPPED: {$table} (sudah kosong)");
                    }
                } else {
                    $skipped[] = "{$table} (tidak ada)";
                    $this->line("⚠️  NOT FOUND: {$table}");
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->newLine();
            $this->info('🎉 TRUNCATE selesai!');
            
            if (!empty($truncated)) {
                $this->info('📊 Tabel yang di-truncate:');
                foreach ($truncated as $table) {
                    $this->line("   • {$table}");
                }
            }

            if (!empty($skipped)) {
                $this->newLine();
                $this->comment('⏭️  Tabel yang dilewati:');
                foreach ($skipped as $table) {
                    $this->line("   • {$table}");
                }
            }

            $this->newLine();
            $this->info('💡 Auto-increment ID sudah direset ke 1');
            $this->info('💡 Tabel users & site_settings tidak disentuh');

            return 0;

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}