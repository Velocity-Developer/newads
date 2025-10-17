<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // idempotent: buat/update Test User
        $test = User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password']
        );
        $test->forceFill([
            'email_verified_at' => $test->email_verified_at ?? now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        // idempotent: buat/update Demo User dari .env
        $email = env('DEMO_LOGIN_EMAIL', 'demo@example.com');
        $password = env('DEMO_LOGIN_PASSWORD', 'password');

        if ($email) {
            $demo = User::updateOrCreate(
                ['email' => $email],
                ['name' => 'Demo User', 'password' => $password]
            );
            $demo->forceFill([
                'email_verified_at' => $demo->email_verified_at ?? now(),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
        }
    }
}
