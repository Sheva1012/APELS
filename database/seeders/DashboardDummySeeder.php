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
        // ─── 1. Buat / ambil user demo ──────────────────────────────
        $user = User::firstOrCreate(
            ['email' => 'demo@apels.com'],
            [
                'name'     => 'Lely Indah Kurnia',
                'password' => bcrypt('password'),
            ]
        );

        if (!$user->hasRole('mahasiswa')) {
            $user->assignRole('mahasiswa');
        }

        // ─── 2. Modul dummy (mencerminkan mockup) ───────────────────
        //   Level label di dashboard bergantung pada kolom `level`.
        //   Gunakan nilai slug yang konsisten: basic, industry-ready, employability-oriented
        // tag hanya boleh: 'basic' | 'advanced'  (sesuai ENUM di DB)
        // level hanya boleh: 'basic' | 'intermediate' | 'advanced' | 'professional'
        // description digunakan sebagai sub-label yang ditampilkan di dashboard
        // order_index menentukan urutan DAN grup tampilan:
        //   10-29 → BASIC | 30-49 → INDUSTRY-READY | 50+ → EMPLOYABILITY-ORIENTED
        $modulesData = [
            // ── BASIC (order 10-29) ────────────────────────────────────
            [
                'title'       => 'Financial Presentation',
                'description' => 'Accounting ESP',
                'path_key'    => 'accounting_esp',
                'level'       => 'basic',
                'tag'         => 'basic',
                'order_index' => 10,
                'is_active'   => true,
                'content'     => ['sections' => [
                    ['title' => 'Opening Statement',         'estimated_minutes' => 15],
                    ['title' => 'Presenting Financial Data', 'estimated_minutes' => 25],
                    ['title' => 'Handling Q&A',              'estimated_minutes' => 20],
                ]],
            ],
            [
                'title'       => 'Presentasi Keuangan Negotiation',
                'description' => 'Accounting ESP',
                'path_key'    => 'accounting_esp',
                'level'       => 'basic',
                'tag'         => 'basic',
                'order_index' => 20,
                'is_active'   => true,
                'content'     => ['sections' => [
                    ['title' => 'Negotiation Basics',   'estimated_minutes' => 20],
                    ['title' => 'Counter-Offer Skills', 'estimated_minutes' => 20],
                ]],
            ],
            // ── INDUSTRY-READY (order 30-49) ────────────────────────────
            [
                'title'       => 'Client Meeting Simulation',
                'description' => 'Financial Reporting',
                'path_key'    => 'accounting_esp',
                'level'       => 'intermediate',
                'tag'         => 'advanced',
                'order_index' => 30,
                'is_active'   => true,
                'content'     => ['sections' => [
                    ['title' => 'Small Talk & Rapport',   'estimated_minutes' => 10],
                    ['title' => 'Setting the Agenda',     'estimated_minutes' => 15],
                    ['title' => 'Action Items & Wrap-up', 'estimated_minutes' => 15],
                ]],
            ],
            [
                'title'       => 'Audit Report Email',
                'description' => 'Financial Reporting',
                'path_key'    => 'accounting_esp',
                'level'       => 'intermediate',
                'tag'         => 'advanced',
                'order_index' => 40,
                'is_active'   => true,
                'content'     => ['sections' => [
                    ['title' => 'Email Structure', 'estimated_minutes' => 15],
                    ['title' => 'Formal Language', 'estimated_minutes' => 20],
                ]],
            ],
            // ── EMPLOYABILITY-ORIENTED (order 50+) ──────────────────────
            [
                'title'       => 'Tax Consultation Negotiation',
                'description' => 'Industry-Ready Context',
                'path_key'    => 'accounting_esp',
                'level'       => 'advanced',
                'tag'         => 'advanced',
                'order_index' => 50,
                'is_active'   => true,
                'content'     => ['sections' => [
                    ['title' => 'Tax Terminology', 'estimated_minutes' => 20],
                    ['title' => 'Client Q&A',      'estimated_minutes' => 25],
                ]],
            ],
        ];

        $moduleModels = [];
        foreach ($modulesData as $data) {
            $module = Module::firstOrCreate(
                ['title' => $data['title'], 'path_key' => $data['path_key']],
                $data
            );
            // Pastikan level, description & order_index terupdate jika modul sudah ada
            $module->update([
                'level'       => $data['level'],
                'description' => $data['description'],
                'order_index' => $data['order_index'],
            ]);
            $moduleModels[] = $module;
        }

        // ─── 3. DiagnosticResult dummy ──────────────────────────────
        DiagnosticResult::where('user_id', $user->id)->delete(); // reset

        $diagnostic = DiagnosticResult::create([
            'user_id'              => $user->id,
            'speaking'             => 78,
            'grammar'              => 82,
            'vocabulary'           => 75,
            'overall'              => 78.5,
            'attempt'              => 1,
            'audio_path'           => null,
            'is_speaking_processed'=> true,
            'created_at'           => now(),
        ]);

        // ─── 4. Learning path dummy ─────────────────────────────────
        UserLearningPath::updateOrCreate(
            ['user_id' => $user->id],
            [
                'path_key'    => 'accounting_esp',
                'status'      => 'active',
                'assigned_at' => now(),
            ]
        );

        // ─── 5. AI Feedback dummy ───────────────────────────────────
        Feedback::where('user_id', $user->id)->delete(); // reset

        Feedback::create([
            'user_id'               => $user->id,
            'diagnostic_result_id'  => $diagnostic->id,
            'message'               => 'Kosakata akuntansi Anda meningkat (Sinta 3), fokus berikutnya pada Laporan Keuangan.',
            'next_focus'            => 'Laporan Keuangan & Financial Reporting',
            'details'               => [
                'speaking'   => ['score' => 78, 'note' => 'Pronunciation sudah baik, tingkatkan fluency.'],
                'grammar'    => ['score' => 82, 'note' => 'Struktur kalimat sangat baik.'],
                'vocabulary' => ['score' => 75, 'note' => 'Perkaya kosakata akuntansi tingkat lanjut.'],
            ],
            'created_at' => Carbon::now()->subMinutes(2),
        ]);

        // ─── 6. Module Progress dummy ───────────────────────────────
        //   Financial Presentation     → Completed (score 88)
        //   Presentasi Keuangan Neg.   → In Progress
        //   Client Meeting Simulation  → Locked
        //   Audit Report Email         → Locked
        //   Tax Consultation           → Locked
        UserModuleProgress::where('user_id', $user->id)->delete(); // reset

        $progressMap = [
            'Financial Presentation'             => ['is_unlocked' => true,  'is_completed' => true,  'score' => 88,  'completed_at' => now()->subDays(3)],
            'Presentasi Keuangan Negotiation'    => ['is_unlocked' => true,  'is_completed' => false, 'score' => null,'completed_at' => null],
            'Client Meeting Simulation'          => ['is_unlocked' => false, 'is_completed' => false, 'score' => null,'completed_at' => null],
            'Audit Report Email'                 => ['is_unlocked' => false, 'is_completed' => false, 'score' => null,'completed_at' => null],
            'Tax Consultation Negotiation'       => ['is_unlocked' => false, 'is_completed' => false, 'score' => null,'completed_at' => null],
        ];

        foreach ($moduleModels as $module) {
            $prog = $progressMap[$module->title] ?? ['is_unlocked' => false, 'is_completed' => false, 'score' => null, 'completed_at' => null];
            UserModuleProgress::create([
                'user_id'      => $user->id,
                'module_id'    => $module->id,
                'is_unlocked'  => $prog['is_unlocked'],
                'is_completed' => $prog['is_completed'],
                'score'        => $prog['score'],
                'completed_at' => $prog['completed_at'],
            ]);
        }

        $this->command->info('✅ Dashboard dummy seeded!');
        $this->command->line('   Email    : demo@apels.com');
        $this->command->line('   Password : password');
        $this->command->line('   Role     : mahasiswa');
    }
}
