<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) env('ADMIN_NAME', ''));
        $email = filter_var(trim((string) env('ADMIN_EMAIL', '')), FILTER_VALIDATE_EMAIL);
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($name === '' || $email === false || strlen($password) < 16) {
            throw new InvalidArgumentException('Set ADMIN_NAME, a valid ADMIN_EMAIL, and an ADMIN_PASSWORD of at least 16 characters.');
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if ($existingUser->role !== 'admin') {
                throw new RuntimeException('The configured admin email already belongs to a non-admin user.');
            }

            $this->command?->info("Admin account {$email} already exists; no changes were made.");

            return;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'role' => 'admin',
            'password' => Hash::make($password),
        ]);

        $this->command?->info("Admin account {$email} created.");
    }
}
