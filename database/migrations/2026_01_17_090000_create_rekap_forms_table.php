<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_forms', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->nullable();
            $table->string('source_id', 64)->nullable()->index();
            $table->string('nama', 255)->nullable();
            $table->string('no_whatsapp', 30)->nullable();
            $table->string('jenis_website', 50)->nullable();
            $table->text('ai_result')->nullable();
            $table->string('via', 50)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_medium', 50)->nullable();
            $table->string('greeting', 255)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('gclid', 255)->nullable()->index();
            $table->boolean('cek_konversi_ads')->nullable();
            $table->boolean('cek_konversi_nominal')->nullable();
            $table->string('kategori_konversi_nominal', 100)->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_forms');
    }
};

