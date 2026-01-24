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
            //tambahkan unique constraint pada kolom term
            $table->unique('term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_terms', function (Blueprint $table) {
            //hapus unique constraint pada kolom term
            $table->dropUnique('term');
        });
    }
};
