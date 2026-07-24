<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Modul Beginner (Grammar Foundation)
        $modulDasar = Module::firstOrCreate(
            ['title' => 'Pengenalan Tenses Dasar', 'path_key' => 'grammar_foundation'],
            [
                'description' => 'Mempelajari dasar-dasar tenses dalam bahasa Inggris untuk komunikasi sehari-hari.',
                'level' => 'basic',
                'tag' => 'basic',
                'order_index' => 1,
                'is_active' => true,
            ]
        );

        Lesson::firstOrCreate(
            ['module_id' => $modulDasar->id, 'title' => 'Apa itu Tenses?'],
            [
                'type' => 'text',
                'content' => '<p>Tenses adalah bentuk kata kerja dalam bahasa Inggris untuk menunjukkan waktu (sekarang, masa depan, atau masa lalu) terjadinya suatu perbuatan atau peristiwa.</p>',
                'estimated_minutes' => 5,
                'order_index' => 1,
            ]
        );

        Lesson::firstOrCreate(
            ['module_id' => $modulDasar->id, 'title' => 'Simple Present Tense'],
            [
                'type' => 'video',
                'content' => '<p>Tonton materi berikut untuk memahami penggunaan Simple Present Tense dalam rutinitas sehari-hari.</p><br><p><i>(Contoh Embed Video)</i></p>',
                'estimated_minutes' => 10,
                'order_index' => 2,
            ]
        );


        // 2. Modul Advanced (Professional Simulation)
        $modulAdvanced = Module::firstOrCreate(
            ['title' => 'Advanced Financial Reporting', 'path_key' => 'professional_simulation'],
            [
                'description' => 'Bagaimana melakukan presentasi keuangan di depan klien internasional.',
                'level' => 'advanced',
                'tag' => 'advanced',
                'order_index' => 1,
                'is_active' => true,
            ]
        );

        Lesson::firstOrCreate(
            ['module_id' => $modulAdvanced->id, 'title' => 'Financial Jargon in English'],
            [
                'type' => 'text',
                'content' => '<p>Berikut adalah daftar istilah keuangan tingkat lanjut beserta penggunaannya dalam kalimat laporan tahunan.</p><ul><li><strong>Revenue:</strong> Pendapatan dari aktivitas bisnis normal.</li><li><strong>EBITDA:</strong> Earnings Before Interest, Taxes, Depreciation, and Amortization.</li><li><strong>Amortization:</strong> Pengurangan nilai aset tidak berwujud.</li></ul>',
                'estimated_minutes' => 8,
                'order_index' => 1,
            ]
        );
    }
}
