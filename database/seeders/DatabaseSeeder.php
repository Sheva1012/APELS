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
        // Bootstrap role + permission terlebih dulu (Req 2.1, 2.7-2.9).
        $this->call(RoleSeeder::class);

        // Modul MVP (Financial Presentation, Client Meeting) + basic placeholder
        // (Req 10.2, 11.1, 11.2, 11.3).
        $this->call(ModuleSeeder::class);

        // Seed sample questions
        $this->call(QuestionSeeder::class);

        // User::factory(10)->create();

        // Create Admin User
        $admin = User::where('email', 'admin@apels.com')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin APELS',
                'email' => 'admin@apels.com',
                'password' => bcrypt('password'),
            ]);
            $admin->assignRole('admin');
        } else {
            if (!$admin->hasRole('admin')) {
                $admin->assignRole('admin');
            }
        }

        // Create Default Test User
        $testUser = User::where('email', 'test@example.com')->first();
        if (!$testUser) {
            $testUser = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
            $testUser->assignRole('mahasiswa');
        } else {
            if (!$testUser->hasRole('mahasiswa')) {
                $testUser->assignRole('mahasiswa');
            }
        }
    }
}

