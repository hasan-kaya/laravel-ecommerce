<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Eloquent\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => '12345678',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => '12345678',
            'role' => 'user',
        ]);

        $this->command->info('Users created successfully.');
    }
}
