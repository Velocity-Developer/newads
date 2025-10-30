<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_frasa_negative', function (Blueprint $table) {
            $table->enum('hasil_cek_ai', ['indonesia', 'luar'])
                ->nullable()
                ->after('parent_term_id'); // ditempatkan sebelum status_input_google

            $table->index('hasil_cek_ai', 'idx_hasil_cek_ai');
        });
    }

    public function down(): void
    {
        Schema::table('new_frasa_negative', function (Blueprint $table) {
            $table->dropIndex('idx_hasil_cek_ai');
            $table->dropColumn('hasil_cek_ai');
        });
    }
};