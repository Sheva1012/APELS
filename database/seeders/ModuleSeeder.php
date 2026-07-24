<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data lama
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Lesson::truncate();
        Module::truncate();
        DB::table('user_lesson_progress')->truncate();
        DB::table('user_module_progress')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Modul Basic
        $modul1 = Module::create([
            'title' => 'Tenses Dasar',
            'path_key' => 'grammar_foundation',
            'description' => 'Modul 1: Tenses Dasar',
            'level' => 'basic',
            'tag' => 'basic',
            'order_index' => 1,
            'is_active' => true,
        ]);

        Lesson::create([
            'module_id' => $modul1->id, 'title' => 'Pengertian Tenses',
            'type' => 'text', 'content' => '<p><b>Tenses</b> adalah perubahan bentuk kata kerja (verb) dalam bahasa Inggris untuk menunjukkan waktu terjadinya suatu peristiwa atau perbuatan.</p><p>Secara umum, tenses dibagi berdasarkan tiga waktu utama:</p><ul><li><b>Present</b> (Masa Kini)</li><li><b>Past</b> (Masa Lalu)</li><li><b>Future</b> (Masa Depan)</li></ul><p>Memahami tenses adalah kunci utama untuk menyusun kalimat yang baik dan benar sesuai dengan tata bahasa Inggris baku.</p>', 'estimated_minutes' => 5, 'order_index' => 1, 'is_active' => true
        ]);
        Lesson::create([
            'module_id' => $modul1->id, 'title' => 'Simple Present & Past',
            'type' => 'text', 'content' => '<h3>1. Simple Present Tense</h3><p>Digunakan untuk menyatakan fakta umum atau kebiasaan (habitual action).</p><p><b>Rumus:</b> S + V1 (s/es)</p><p><i>Contoh:</i> She goes to school every day.</p><hr><h3>2. Simple Past Tense</h3><p>Digunakan untuk menyatakan kejadian yang sudah selesai di masa lalu.</p><p><b>Rumus:</b> S + V2</p><p><i>Contoh:</i> I bought a new car yesterday.</p>', 'estimated_minutes' => 5, 'order_index' => 2, 'is_active' => true
        ]);

        // 2. Modul Intermediate
        $modul2 = Module::create([
            'title' => 'Perfect Tenses',
            'path_key' => 'grammar_foundation',
            'description' => 'Modul 2: Perfect Tenses',
            'level' => 'intermediate',
            'tag' => 'intermediate',
            'order_index' => 2,
            'is_active' => true,
        ]);
        Lesson::create([
            'module_id' => $modul2->id, 'title' => 'Present Perfect',
            'type' => 'text', 'content' => '<p><b>Present Perfect Tense</b> digunakan untuk menyatakan kejadian yang sudah terjadi di masa lalu namun efek atau hasilnya masih terasa sampai sekarang, atau belum selesai sepenuhnya.</p><p><b>Rumus:</b> S + have/has + V3</p><ul><li><i>I have eaten my breakfast.</i> (Saya sudah makan pagi - dan saat ini masih kenyang)</li><li><i>She has lived here since 2010.</i> (Dia sudah tinggal di sini sejak 2010 - dan sekarang masih tinggal di sini)</li></ul>', 'estimated_minutes' => 5, 'order_index' => 1, 'is_active' => true
        ]);
        Lesson::create([
            'module_id' => $modul2->id, 'title' => 'Past Perfect',
            'type' => 'text', 'content' => '<p><b>Past Perfect Tense</b> digunakan untuk menyatakan bahwa suatu kejadian telah selesai pada suatu titik tertentu di masa lalu, sebelum kejadian lain di masa lalu itu terjadi.</p><p><b>Rumus:</b> S + had + V3</p><ul><li><i>When I arrived at the station, the train had left.</i> (Ketika saya tiba di stasiun, kereta tersebut sudah berangkat lebih dulu)</li><li><i>She had finished her homework before she went out to play.</i></li></ul>', 'estimated_minutes' => 5, 'order_index' => 2, 'is_active' => true
        ]);

        // 3. Modul Advanced
        $modul3 = Module::create([
            'title' => 'Conditional & Passive',
            'path_key' => 'grammar_foundation',
            'description' => 'Modul 3: Conditional & Passive',
            'level' => 'advanced',
            'tag' => 'advanced',
            'order_index' => 3,
            'is_active' => true,
        ]);
        Lesson::create([
            'module_id' => $modul3->id, 'title' => 'If Clause',
            'type' => 'text', 'content' => '<p><b>If Clause</b> (Conditional Sentence) adalah kalimat pengandaian. Ada beberapa tipe yang umum digunakan:</p><ol><li><b>Type 1:</b> Future possibility. <br><i>If it rains, I will stay at home.</i></li><li><b>Type 2:</b> Present unreal. <br><i>If I had a million dollars, I would buy an island.</i></li><li><b>Type 3:</b> Past unreal. <br><i>If you had studied harder, you would have passed the exam.</i></li></ol>', 'estimated_minutes' => 5, 'order_index' => 1, 'is_active' => true
        ]);
        Lesson::create([
            'module_id' => $modul3->id, 'title' => 'Passive Voice',
            'type' => 'text', 'content' => '<p><b>Passive Voice</b> (Kalimat Pasif) memfokuskan tindakan pada objek (penderita) daripada subjek (pelaku).</p><p><b>Rumus Umum:</b> S + to be + V3</p><ul><li><b>Active:</b> The dog bit the postman.</li><li><b>Passive:</b> The postman was bitten by the dog.</li></ul><p>Perhatikan perubahan bentuk <i>to be</i> yang harus disesuaikan dengan tense yang digunakan pada kalimat aktifnya.</p>', 'estimated_minutes' => 5, 'order_index' => 2, 'is_active' => true
        ]);
    }
}
