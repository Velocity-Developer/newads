<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Bersihkan duplikat case-insensitive sebelum ubah kolasi
        //    Keep ID terkecil, hapus sisanya agar unique constraint aman.
        $duplicates = DB::table('blacklist_words')
            ->selectRaw('LOWER(word) AS lw, GROUP_CONCAT(id ORDER BY id) AS ids, COUNT(*) AS c')
            ->groupBy('lw')
            ->having('c', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup->ids);
            // Simpan record pertama (ID terkecil)
            array_shift($ids);
            if (!empty($ids)) {
                DB::table('blacklist_words')->whereIn('id', $ids)->delete();
            }
        }

        // 2) Ubah kolom menjadi case-insensitive collation (MySQL 8)
        DB::statement("
            ALTER TABLE `blacklist_words`
            MODIFY `word` VARCHAR(255)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_0900_ai_ci
            NOT NULL
        ");

        // Catatan: index unik `blacklist_words_word_unique` akan mengikuti kolasi kolom,
        // tidak perlu di-drop/recreate jika tidak error.
    }

    public function down(): void
    {
        // Kembalikan ke case-sensitive jika diperlukan
        DB::statement("
            ALTER TABLE `blacklist_words`
            MODIFY `word` VARCHAR(255)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_0900_as_cs
            NOT NULL
        ");
    }
};