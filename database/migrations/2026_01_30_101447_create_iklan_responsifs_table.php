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
        Schema::create('iklan_responsifs', function (Blueprint $table) {
            $table->id();
            $table->string('group_iklan');
            $table->string('kata_kunci');
            $table->bigInteger('search_term_id')->nullable();
            $table->string('nomor_group_iklan')->nullable();
            $table->string('nomor_kata_kunci')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iklan_responsifs');
    }
};
