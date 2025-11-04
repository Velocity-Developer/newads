<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('new_terms_negative_0click', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('id');
            $table->index('campaign_id', 'idx_terms_campaign_id');
        });

        Schema::table('new_frasa_negative', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('id');
            $table->index('campaign_id', 'idx_frasa_campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('new_terms_negative_0click', function (Blueprint $table) {
            $table->dropIndex('idx_terms_campaign_id');
            $table->dropColumn('campaign_id');
        });

        Schema::table('new_frasa_negative', function (Blueprint $table) {
            $table->dropIndex('idx_frasa_campaign_id');
            $table->dropColumn('campaign_id');
        });
    }
};