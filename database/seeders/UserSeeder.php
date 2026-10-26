<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('services.store_admin_email');
        $password = config('services.store_admin_password');

        if (
            blank($email)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || str_ends_with(
                strtolower((string) $email),
                '@example.com'
            )
            || blank($password)
            || strlen((string) $password) < 12
            || str_contains(
                strtolower((string) $password),
                'ganti-dengan-password'
            )
        ) {
            throw new RuntimeException(
                'Set STORE_ADMIN_EMAIL dan STORE_ADMIN_PASSWORD di .env sebelum menjalankan db:seed. Gunakan password unik minimal 12 karakter.'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin Toko Silaturizky',
                'password' => $password,
                'role' => 'admin_toko',
            ]
        );
    }
}