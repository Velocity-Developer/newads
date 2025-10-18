<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('new_terms_negative_0click', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('terms', 500)->unique();
            $table->enum('hasil_cek_ai', ['relevan', 'negatif'])->nullable();
            $table->enum('status_input_google', ['berhasil', 'gagal', 'error'])->nullable();
            $table->integer('retry_count')->default(0);
            $table->boolean('notif_telegram')->default(false);
            
            // Indexes for performance
            $table->index(['hasil_cek_ai', 'status_input_google'], 'idx_ai_status');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_terms_negative_0click');
    }
};
