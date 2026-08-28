<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('BUGS_SEED_PASSWORD');
        if (! $password) {
            $this->command?->warn('BUGS_SEED_PASSWORD not set — skipping user seed');

            return;
        }

        foreach ([
            ['name' => 'Jamie', 'email' => 'jamie@advancedhouseplans.com'],
            ['name' => 'Keith', 'email' => 'keith@advancedhouseplans.com'],
        ] as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => Hash::make($password), 'email_verified_at' => now()],
            );
        }
    }
}
