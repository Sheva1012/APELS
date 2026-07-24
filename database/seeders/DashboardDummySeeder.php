<?php

namespace Database\Seeders;

use App\Models\DiagnosticResult;
use App\Models\Feedback;
use App\Models\Module;
use App\Models\User;
use App\Models\UserLearningPath;
use App\Models\UserModuleProgress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * DashboardDummySeeder
 *
 * Membuat data dummy lengkap agar tampilan Dashboard Mahasiswa
 * bisa dilihat tanpa harus menjalankan Diagnostic Test sungguhan.
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=DashboardDummySeeder
 *
 * Idempotent — aman dijalankan berulang kali (menggunakan firstOrCreate).
 *
 * Akun yang dibuat / digunakan:
 *   email    : demo@apels.com
 *   password : password
 *   role     : mahasiswa
 */
class DashboardDummySeeder extends Seeder
{
    public function run(): void
    {
        // Semua data dummy dihapus karena akan diinput manual oleh pengguna.
    }
}
