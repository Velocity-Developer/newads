<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blacklist_words', function (Blueprint $table) {
            $table->id();
            // Gunakan collation case-insensitive agar "Halo" dan "halo" dianggap sama
            $table->string('word')
                ->charset('utf8mb4')
                ->collation('utf8mb4_0900_ai_ci'); // case-insensitive
            $table->boolean('active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique('word'); // unik case-insensitive
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_words');
    }
};