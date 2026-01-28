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
        Schema::table('search_terms', function (Blueprint $table) {
            // hapus kolom google_ads_status dan telegram_send dan ai_result
            $table->dropColumn('google_ads_status');
            $table->dropColumn('telegram_send');
            $table->dropColumn('ai_result');
            // tambah kolom check_ai,iklan_dibuat,failure_count
            $table->string('check_ai')->nullable();
            $table->boolean('iklan_dibuat')->default(false);
            $table->integer('failure_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_terms', function (Blueprint $table) {
            // tambah kolom google_ads_status dan telegram_send
            $table->boolean('google_ads_status')->default(false);
            $table->boolean('telegram_send')->default(false);
            // tambah kolom ai_result
            $table->string('ai_result')->nullable();
            // hapus kolom check_ai,iklan_dibuat,failure_count
            $table->dropColumn('check_ai');
            $table->dropColumn('iklan_dibuat');
            $table->dropColumn('failure_count');
        });
    }
};
