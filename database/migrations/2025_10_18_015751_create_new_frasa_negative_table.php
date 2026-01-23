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
        Schema::create('new_frasa_negative', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('frasa', 255)->unique();
            $table->unsignedBigInteger('parent_term_id')->nullable();
            $table->enum('status_input_google', ['sukses', 'gagal', 'error'])->nullable();
            $table->integer('retry_count')->default(0);
            $table->enum('notif_telegram', ['sukses', 'gagal'])->nullable()->comment('Status notifikasi Telegram: sukses/gagal');

            // Foreign key constraint
            $table->foreign('parent_term_id')->references('id')->on('new_terms_negative_0click')->onDelete('cascade');

            // Indexes for performance
            $table->index('status_input_google', 'idx_status');
            $table->index('frasa', 'idx_frasa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_frasa_negative');
    }
};
