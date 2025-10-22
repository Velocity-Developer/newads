<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('new_terms_negative_0click', function (Blueprint $table) {
            $table->softDeletes(); // adds `deleted_at` TIMESTAMP nullable
        });
    }

    public function down(): void
    {
        Schema::table('new_terms_negative_0click', function (Blueprint $table) {
            $table->dropSoftDeletes(); // drops `deleted_at`
        });
    }
};