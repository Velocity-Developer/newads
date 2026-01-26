<?php

namespace Database\Seeders;

use App\Models\CronLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CronLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Success example
        CronLog::create([
            'name' => 'report:daily',
            'type' => 'command',
            'started_at' => Carbon::now()->subHours(2),
            'finished_at' => Carbon::now()->subHours(2)->addMinutes(5),
            'duration_ms' => 300000,
            'status' => 'success',
            'error' => null,
        ]);

        // Failed example
        CronLog::create([
            'name' => 'sync:ads',
            'type' => 'job',
            'started_at' => Carbon::now()->subHours(1),
            'finished_at' => Carbon::now()->subHours(1)->addSeconds(30),
            'duration_ms' => 30000,
            'status' => 'failed',
            'error' => 'Connection timeout occurred while syncing ads data.',
        ]);

        // Running example
        CronLog::create([
            'name' => 'process:emails',
            'type' => 'closure',
            'started_at' => Carbon::now()->subMinutes(10),
            'finished_at' => null,
            'duration_ms' => null,
            'status' => 'running',
            'error' => null,
        ]);

        // Another Success example
        CronLog::create([
            'name' => 'backup:run',
            'type' => 'command',
            'started_at' => Carbon::now()->subDay()->subHours(2),
            'finished_at' => Carbon::now()->subDay()->subHours(2)->addMinutes(15),
            'duration_ms' => 900000,
            'status' => 'success',
            'error' => null,
        ]);
    }
}
