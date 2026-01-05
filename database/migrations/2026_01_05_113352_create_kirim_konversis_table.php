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
        Schema::create('kirim_konversis', function (Blueprint $table) {
            $table->id();
            $table->string('gclid')->required();
            $table->string('jobid')->nullable();
            $table->string('waktu')->nullable();
            $table->string('status')->nullable();
            $table->text('response')->nullable();
            $table->string('source')->nullable();
            $table->string('rekap_form_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kirim_konversis');
    }
};
