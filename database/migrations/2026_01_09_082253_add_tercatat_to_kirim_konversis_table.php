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
        Schema::table('kirim_konversis', function (Blueprint $table) {
            $table->boolean('tercatat')->default(0)->after('rekap_form_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kirim_konversis', function (Blueprint $table) {
            $table->dropColumn('tercatat');
        });
    }
};
