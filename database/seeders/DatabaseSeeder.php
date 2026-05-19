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
        // Seed an Admin user
        User::factory()->create([
            'name' => 'Admin Notepad',
            'email' => 'admin@mynotepad.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        // Seed some standard users
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);
    }
}
