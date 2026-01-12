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
            $table->string('rekap_form_source', 50)
                ->nullable()
                ->after('rekap_form_id');
            $table->string('conversion_action_id', 32)
                ->nullable()
                ->after('tercatat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kirim_konversis', function (Blueprint $table) {
            //
            $table->dropColumn(['rekap_form_source', 'conversion_action_id']);
        });
    }
};
