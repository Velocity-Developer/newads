<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blacklist_words')) {
            return; // skip jika tabel sudah ada
        }
        Schema::create('blacklist_words', function (Blueprint $table) {
            $table->id();
            // Gunakan collation case-sensitive; sesuaikan dengan versi MySQL/MariaDB Anda
            $table->string('word')
                ->charset('utf8mb4')
                ->collation('utf8mb4_0900_as_cs'); // fallback: 'utf8mb4_bin'
            $table->boolean('active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique('word'); // unik exact-case karena kolom case-sensitive
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_words');
    }
};
