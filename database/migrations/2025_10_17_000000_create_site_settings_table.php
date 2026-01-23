<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_title')->nullable();
            $table->string('sidebar_title')->nullable();
            $table->string('sidebar_icon_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('apple_touch_icon_path')->nullable();
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            'site_title' => config('app.name', 'Laravel'),
            'sidebar_title' => 'Laravel Starter Kit',
            'sidebar_icon_path' => null,
            'favicon_path' => null,
            'apple_touch_icon_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
