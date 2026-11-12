<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('admin.initial_password');

        if (! is_string($password) || $password === '') {
            throw new \InvalidArgumentException('Set ADMIN_INITIAL_PASSWORD before creating the administrator account.');
        }

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@local.test',
                'password' => Hash::make($password),
                'is_admin' => true,
            ],
        );
    }
}
