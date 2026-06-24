# Requirements Document

## Introduction

APELS (Adaptive Professional English Learning System) adalah platform pembelajaran Bahasa Inggris berbasis web untuk mahasiswa yang mengukur tiga skill: Speaking, Grammar, dan Vocabulary. Sistem berfokus pada *Learning Brain* — Adaptive Engine berbasis aturan (rule-based) yang secara otomatis menentukan jalur belajar (learning path) berbeda untuk setiap mahasiswa berdasarkan hasil Diagnostic Test.

Tiga pilar utama:

1. Diagnostic Test akurat untuk Speaking (audio), Grammar (teks), dan Vocabulary (teks).
2. Adaptive Engine yang memetakan skor ke salah satu dari delapan learning path.
3. Feedback otomatis berupa teks seolah ada tutor pribadi.

Tiga role pengguna: Admin, Dosen, dan Mahasiswa. MVP wajib mencakup modul Financial Presentation dan Client Meeting, batas satu Diagnostic Test per hari per mahasiswa, dan integrasi OpenAI Whisper + GPT-4o Mini. Dokumen ini menjadi sumber kebenaran fungsional dan non-fungsional untuk implementasi bertahap (Phase 1–4).

## Glossary

- **APELS_System**: Keseluruhan platform APELS berbasis Laravel 12 + Livewire 3 + Filament 4. (Catatan: composer.json lock final menggunakan Livewire 3.8 dan Filament 4.11 — Filament 4 belum support Livewire 4, sehingga starter kit Livewire 4 di-downgrade otomatis ke v3.8 saat Filament di-install. Glossary semula menyebut Filament 3; di-update agar konsisten dengan dependency aktual.)
- **Auth_System**: Subsistem autentikasi berbasis Laravel built-in dan Livewire Starter Kit.
- **Authorization_System**: Subsistem otorisasi berbasis Spatie Laravel Permission yang mengelola tiga role.
- **Daily_Test_Limit_Middleware**: Middleware HTTP yang memblokir akses ke Diagnostic Test jika mahasiswa sudah mengerjakan tes pada hari kalender yang sama.
- **Diagnostic_System**: Subsistem yang mengorkestrasi alur Diagnostic Test (DiagnosticService).
- **Diagnostic_Test**: Tes diagnosis tiga komponen (speaking audio, grammar teks, vocabulary teks) yang menghasilkan tiga skor 0–100.
- **NlpAnalysisService**: Service wrapper OpenAI GPT-4o Mini untuk analisis grammar dan vocabulary dari teks.
- **SpeechToTextService**: Service wrapper OpenAI Whisper API untuk transkripsi audio dan scoring speaking.
- **ScoringService**: Service yang menghitung overall score (weighted average) dan menormalisasi skor ke rentang 0–100.
- **AdaptiveEngineService**: Service rule-based yang memetakan tiga skor ke salah satu dari delapan learning path key.
- **FeedbackService**: Service yang menghasilkan teks feedback otomatis berdasarkan tiga skor dan path key aktif.
- **ProcessSpeakingAudio_Job**: Queue job asinkron yang memproses audio melalui Whisper, mengupdate skor speaking, lalu memicu Adaptive Engine dan Feedback.
- **ReEvaluationService**: Service yang menentukan apakah mahasiswa layak disarankan tes ulang berdasarkan progres modul.
- **Learning_Path**: Jalur belajar yang ditetapkan untuk mahasiswa, berasal dari salah satu dari delapan path key yang didefinisikan di `config/learning_paths.php`.
- **Path_Key**: Identifier string untuk learning path. Nilai yang valid: `emergency_foundation`, `fundamental_communication`, `basic_speaking`, `grammar_foundation`, `vocabulary_builder`, `intermediate_path`, `professional_simulation`, `industry_ready`.
- **Module**: Unit konten pembelajaran. Setiap Module memiliki `path_key`, `level` (`basic` | `intermediate` | `advanced` | `professional`), dan `tag`.
- **Module_Progress**: Status per pasangan (mahasiswa, modul) yang menyimpan flag `is_unlocked`, `is_completed`, dan `score`.
- **Question_Bank**: Kumpulan soal pilihan ganda (a/b/c/d) bertipe `grammar` atau `vocabulary` yang dikelola oleh Dosen/Admin.
- **Exercise**: Latihan/quiz per modul yang diambil dari Question_Bank.
- **Dashboard**: Halaman utama mahasiswa yang menampilkan radar chart tiga skill, label readiness, feedback, dan learning roadmap.
- **Lecturer_Report_System**: Subsistem laporan berkala (bukan real-time) untuk Dosen di Filament.
- **Admin_Panel**: Filament 3 admin panel untuk Admin dan Dosen.
- **API_Response**: Format JSON standar respons API APELS dengan field `status`, `message`, dan opsional `data` / `errors` / `code`.
- **Audio_Storage**: Lokasi penyimpanan privat untuk file audio diagnostic (`storage/private`).
- **NLP_Cache**: Cache hasil analisis NLP berbasis hash teks dengan TTL 24 jam.
- **OpenAI_API**: Layanan eksternal OpenAI yang mencakup Whisper API dan GPT-4o Mini Chat Completions API.
- **Threshold**: Batas skor yang menentukan kategori kemampuan: `< 60` rendah, `60–70` sedang, `> 70` tinggi.
- **Overall_Score**: Weighted average dari tiga skor dengan bobot Speaking 0.40, Grammar 0.30, Vocabulary 0.30.
- **Attempt**: Nomor urut percobaan Diagnostic Test untuk seorang mahasiswa, dimulai dari 1.
- **Pretty_Printer (CSV_Question_Exporter)**: Komponen yang mem-format daftar Question kembali ke representasi CSV/Excel sesuai grammar import.

## Requirements

### Requirement 1: Registrasi dan Autentikasi Mahasiswa

**User Story:** Sebagai Mahasiswa, saya ingin mendaftar akun dan login sendiri, supaya saya dapat mengakses Diagnostic Test dan dashboard pembelajaran tanpa perlu bantuan administrator.

#### Acceptance Criteria

1. WHEN Mahasiswa mengirim formulir registrasi dengan name, email unik, NIM unik, dan password yang valid, THE Auth_System SHALL membuat akun User baru dengan role `mahasiswa` dan field NIM tersimpan.
2. IF email atau NIM yang dikirim sudah terdaftar, THEN THE Auth_System SHALL menolak registrasi dan mengembalikan pesan kesalahan validasi yang spesifik per field.
3. WHEN User mengirim kredensial login yang valid, THE Auth_System SHALL membuat session dan mengarahkan user ke route default sesuai role.
4. IF User mengirim kredensial login yang tidak valid, THEN THE Auth_System SHALL menolak login dan mengembalikan pesan kesalahan generik tanpa membocorkan apakah email atau password yang salah.
5. WHEN User memicu logout, THE Auth_System SHALL menghapus session aktif dan mengarahkan user ke halaman login.
6. THE Auth_System SHALL menerapkan CSRF protection bawaan Laravel pada semua route POST/PUT/PATCH/DELETE non-API.

### Requirement 2: Role-Based Authorization

**User Story:** Sebagai pemilik sistem, saya ingin setiap endpoint terlindungi sesuai role pengguna, supaya tidak ada akses tidak sah ke fitur yang bukan haknya.

#### Acceptance Criteria

1. THE Authorization_System SHALL menyediakan tiga role: `admin`, `dosen`, dan `mahasiswa`.
2. WHEN User dengan role `mahasiswa` mengakses route `/dashboard`, `/learning-path`, `/exercises`, atau `/diagnostic`, THE Authorization_System SHALL mengizinkan akses.
3. IF User tanpa role `mahasiswa` mengakses route mahasiswa, THEN THE Authorization_System SHALL menolak akses dan mengembalikan HTTP 403.
4. WHEN User dengan role `dosen` mengakses route `/reports` dan resource Filament `QuestionResource`, `ModuleResource`, dan `DiagnosticResultResource`, THE Authorization_System SHALL mengizinkan akses.
5. IF User dengan role `dosen` mencoba membuat, mengubah, atau menghapus record pada `DiagnosticResultResource`, THEN THE Authorization_System SHALL menolak aksi tersebut.
6. WHEN User dengan role `admin` mengakses Admin_Panel, THE Authorization_System SHALL mengizinkan akses penuh ke seluruh resource yang terdaftar.
7. THE Authorization_System SHALL memberikan permission `take diagnostic`, `view dashboard`, `view learning path`, dan `do exercises` ke role `mahasiswa`.
8. THE Authorization_System SHALL memberikan permission `manage questions`, `view reports`, dan `import questions` ke role `dosen`.
9. THE Authorization_System SHALL memberikan permission `manage users`, `manage modules`, dan `manage all` ke role `admin`.
10. IF guest (tidak terautentikasi) mengakses route yang dilindungi, THEN THE Auth_System SHALL mengembalikan HTTP 401 untuk endpoint API atau redirect ke halaman login untuk route web.

### Requirement 3: Pembatasan Satu Diagnostic Test Per Hari

**User Story:** Sebagai pemilik sistem, saya ingin setiap mahasiswa dibatasi mengerjakan Diagnostic Test maksimal satu kali per hari, supaya konsumsi API OpenAI dan beban sistem tetap terkontrol.

#### Acceptance Criteria

1. WHEN Mahasiswa mengakses route Diagnostic Test pada hari kalender yang sama dengan tes sebelumnya, THE Daily_Test_Limit_Middleware SHALL menolak request dan mengembalikan HTTP 429 dengan body API_Response berisi `status` `error`, `message` "Anda sudah mengerjakan tes hari ini. Coba lagi besok.", dan `code` 429.
2. WHILE Mahasiswa belum memiliki record DiagnosticResult untuk hari kalender saat ini, THE Daily_Test_Limit_Middleware SHALL meneruskan request ke handler Diagnostic Test.
3. THE Daily_Test_Limit_Middleware SHALL menentukan "hari kalender saat ini" menggunakan timezone aplikasi yang dikonfigurasi di `config/app.php`.

### Requirement 4: Pengiriman Diagnostic Test (Wizard Tiga Langkah)

**User Story:** Sebagai Mahasiswa, saya ingin mengerjakan Diagnostic Test dalam wizard tiga langkah (grammar teks, vocabulary teks, speaking audio), supaya sistem dapat mengukur tiga skill saya.

#### Acceptance Criteria

1. THE Diagnostic_System SHALL menerima input berupa array `text_answers` (jawaban grammar dan vocabulary) dan satu file `audio` per submission.
2. WHEN Mahasiswa mengirim submission yang valid, THE Diagnostic_System SHALL membuat record DiagnosticResult dengan field `user_id`, `grammar`, `vocabulary` (hasil sinkron NlpAnalysisService), `speaking` = 0, `overall` = 0, `attempt` = jumlah DiagnosticResult sebelumnya + 1, `audio_path`, dan `is_speaking_processed` = false.
3. WHEN DiagnosticResult berhasil dibuat dan `audio_path` tersedia, THE Diagnostic_System SHALL men-dispatch ProcessSpeakingAudio_Job dengan parameter `userId`, `audioPath`, dan `diagnosticId`.
4. WHEN DiagnosticResult berhasil dibuat tanpa `audio_path`, THE Diagnostic_System SHALL menjalankan AdaptiveEngineService dan FeedbackService secara sinkron menggunakan skor speaking = 0.
5. THE Diagnostic_System SHALL mengembalikan API_Response sukses berisi `diagnostic_id`, `grammar`, `vocabulary`, dan `message` yang menyatakan speaking sedang diproses.
6. IF NlpAnalysisService melempar exception atau timeout, THEN THE Diagnostic_System SHALL menyimpan `grammar` = 0 dan `vocabulary` = 0 untuk submission tersebut dan tetap melanjutkan alur tanpa membatalkan record DiagnosticResult.

### Requirement 5: Validasi Input Diagnostic Test

**User Story:** Sebagai pemilik sistem, saya ingin semua submission Diagnostic Test divalidasi ketat, supaya data buruk tidak masuk ke pipeline OpenAI dan database.

#### Acceptance Criteria

1. THE Diagnostic_System SHALL memvalidasi `text_answers` sebagai array dengan minimal 3 elemen dan maksimal 20 elemen.
2. THE Diagnostic_System SHALL memvalidasi setiap elemen `text_answers` sebagai string dengan panjang minimal 5 karakter dan maksimal 500 karakter.
3. THE Diagnostic_System SHALL memvalidasi field `audio` sebagai file dengan tipe MIME dalam himpunan {`wav`, `mp3`, `webm`, `ogg`} dan ukuran tidak lebih dari `APELS_AUDIO_MAX_MB` megabyte (default 10 MB).
4. IF salah satu aturan validasi gagal, THEN THE Diagnostic_System SHALL mengembalikan HTTP 422 dengan API_Response error berisi field `errors` per field yang gagal.
5. THE Diagnostic_System SHALL menyimpan file audio yang valid pada Audio_Storage privat (`storage/private`), bukan pada storage public.

### Requirement 6: Pemrosesan Speaking via OpenAI Whisper

**User Story:** Sebagai Mahasiswa, saya ingin audio jawaban speaking saya diproses otomatis dan diberi skor, supaya saya tidak perlu menunggu evaluator manual.

#### Acceptance Criteria

1. WHEN ProcessSpeakingAudio_Job dijalankan, THE SpeechToTextService SHALL mengirim file pada `audio_path` ke OpenAI Whisper API dengan parameter `model` = `whisper-1` dan `language` = `en`.
2. WHEN Whisper API mengembalikan transkrip yang berisi jumlah kata `n`, THE SpeechToTextService SHALL menghitung skor speaking sebagai `min(round(n / 20 * 100), 100)`.
3. IF jumlah kata transkrip kurang dari 5, THEN THE SpeechToTextService SHALL mengembalikan skor speaking yang nilainya `max(round(n / 20 * 100), 10)`.
4. WHEN ProcessSpeakingAudio_Job berhasil mendapatkan skor speaking, THE ProcessSpeakingAudio_Job SHALL mengupdate DiagnosticResult dengan `speaking` = skor dan `is_speaking_processed` = true.
5. WHEN DiagnosticResult selesai diupdate, THE ProcessSpeakingAudio_Job SHALL memanggil `DiagnosticService::runEngineAndFeedback` untuk DiagnosticResult tersebut.
6. WHEN ProcessSpeakingAudio_Job selesai, THE ProcessSpeakingAudio_Job SHALL menghapus file audio pada `audio_path` dari Audio_Storage.
7. IF Whisper API melempar exception atau melebihi `APELS_STT_TIMEOUT` detik (default 60), THEN THE SpeechToTextService SHALL mencatat error dengan `Log::error` dan mengembalikan skor speaking 0.
8. THE ProcessSpeakingAudio_Job SHALL mencoba ulang maksimal 3 kali dengan backoff 5 detik sebelum dianggap gagal.
9. IF ProcessSpeakingAudio_Job gagal setelah seluruh percobaan, THEN THE ProcessSpeakingAudio_Job SHALL mengupdate DiagnosticResult dengan `speaking` = 0 dan `is_speaking_processed` = true, lalu mencatat error pada log.

### Requirement 7: Pemrosesan Grammar dan Vocabulary via OpenAI GPT-4o Mini

**User Story:** Sebagai Mahasiswa, saya ingin jawaban teks saya dianalisis otomatis untuk grammar dan vocabulary, supaya saya menerima skor objektif untuk dua skill tersebut.

#### Acceptance Criteria

1. WHEN NlpAnalysisService dipanggil dengan array `text_answers`, THE NlpAnalysisService SHALL menggabungkan elemen array menjadi satu string dengan separator spasi sebagai input ke GPT-4o Mini.
2. THE NlpAnalysisService SHALL memanggil OpenAI Chat Completions API dengan model `gpt-4o-mini`, system prompt yang meminta JSON object berisi field `grammar_score` dan `vocab_score` (integer 0–100), dan `response_format` `json_object`.
3. WHEN GPT-4o Mini mengembalikan JSON yang valid, THE NlpAnalysisService SHALL mengembalikan array `['grammar_score' => int, 'vocab_score' => int]` dengan kedua nilai dipotong ke rentang 0–100.
4. IF GPT-4o Mini melempar exception, melebihi `APELS_NLP_TIMEOUT` detik (default 30), atau mengembalikan JSON yang tidak dapat di-parse, THEN THE NlpAnalysisService SHALL mencatat error dan mengembalikan `['grammar_score' => 0, 'vocab_score' => 0]`.
5. THE NlpAnalysisService SHALL meng-cache hasil analisis pada NLP_Cache dengan key `nlp_result_{md5(implode('|', textAnswers))}` selama 24 jam.
6. WHEN cache hit terjadi pada NLP_Cache untuk hash teks yang sama, THE NlpAnalysisService SHALL mengembalikan hasil cache tanpa memanggil OpenAI API.

### Requirement 8: Perhitungan Overall Score

**User Story:** Sebagai Mahasiswa, saya ingin sistem menghitung satu overall score dari tiga skill, supaya saya tahu posisi kemampuan saya secara keseluruhan.

#### Acceptance Criteria

1. THE ScoringService SHALL menerapkan bobot Speaking = 0.40, Grammar = 0.30, dan Vocabulary = 0.30 (jumlah bobot = 1.00).
2. WHEN ScoringService menghitung overall, THE ScoringService SHALL mengembalikan `round(speaking * 0.40 + grammar * 0.30 + vocabulary * 0.30, 2)`.
3. THE ScoringService SHALL menerima skor input pada rentang 0–100 dan mengembalikan overall pada rentang 0–100.
4. WHEN ScoringService dipanggil dengan satu skor yang tidak ada di array input, THE ScoringService SHALL memperlakukan skor tersebut sebagai 0.
5. THE ScoringService SHALL menyediakan fungsi `normalize(float $score, float $min = 0.0, float $max = 1.0): int` yang mengembalikan integer hasil `round((score - min) / (max - min) * 100)`.

### Requirement 9: Adaptive Engine — Penentuan Learning Path

**User Story:** Sebagai pemilik sistem, saya ingin Adaptive Engine memetakan tiga skor mahasiswa ke salah satu dari delapan learning path secara deterministik, supaya jalur belajar yang ditugaskan konsisten dan dapat diaudit.

#### Acceptance Criteria

1. THE AdaptiveEngineService SHALL mengevaluasi aturan path dalam urutan paling spesifik ke paling umum sebagai berikut:
   1. IF Speaking < 50 AND Grammar < 50 AND Vocabulary < 50, THEN path_key = `emergency_foundation`.
   2. ELSE IF Speaking < 60 AND Vocabulary < 60, THEN path_key = `fundamental_communication`.
   3. ELSE IF Speaking < 60, THEN path_key = `basic_speaking`.
   4. ELSE IF Grammar < 60, THEN path_key = `grammar_foundation`.
   5. ELSE IF Vocabulary < 60, THEN path_key = `vocabulary_builder`.
   6. ELSE IF Speaking ≥ 80 AND Grammar ≥ 75 AND Vocabulary ≥ 75, THEN path_key = `industry_ready`.
   7. ELSE IF Speaking ≥ 70 AND Grammar ≥ 70, THEN path_key = `professional_simulation`.
   8. ELSE path_key = `intermediate_path`.
2. THE AdaptiveEngineService SHALL bersifat deterministik: untuk input tiga skor yang identik, output `path_key` SHALL sama setiap saat (idempotence pada `evaluateRules`).
3. THE AdaptiveEngineService SHALL selalu mengembalikan salah satu dari delapan path_key yang valid: `emergency_foundation`, `fundamental_communication`, `basic_speaking`, `grammar_foundation`, `vocabulary_builder`, `intermediate_path`, `professional_simulation`, `industry_ready` (totality).
4. WHEN AdaptiveEngineService dijalankan untuk seorang mahasiswa, THE AdaptiveEngineService SHALL membuat atau mengupdate satu record `user_learning_paths` aktif untuk mahasiswa tersebut dengan `path_key`, `status` = `active`, dan `assigned_at` = waktu eksekusi.
5. WHEN AdaptiveEngineService memutuskan path baru, THE AdaptiveEngineService SHALL mengembalikan struktur `['learning_path' => path_key, 'path_label' => label, 'modules_unlocked' => array_id, 'modules_locked' => array_id]`.
6. THE AdaptiveEngineService SHALL memvalidasi bahwa nilai input setiap skor berada pada rentang 0–100 inklusif.

### Requirement 10: Logika Unlock dan Lock Modul

**User Story:** Sebagai Mahasiswa, saya ingin sistem membuka modul yang sesuai dengan kemampuan saya dan mengunci modul yang belum siap saya akses, supaya pembelajaran saya bertahap.

#### Acceptance Criteria

1. THE AdaptiveEngineService SHALL meng-unlock setiap Module yang `path_key`-nya sama dengan path aktif mahasiswa.
2. THE AdaptiveEngineService SHALL meng-unlock setiap Module yang memiliki `level` = `basic` untuk semua mahasiswa, terlepas dari path aktif.
3. WHERE Module memiliki `level` = `professional`, THE AdaptiveEngineService SHALL meng-unlock Module hanya jika Speaking ≥ 70 AND Grammar ≥ 70 AND Vocabulary ≥ 70.
4. WHERE Module tidak memenuhi kondisi unlock di atas, THE AdaptiveEngineService SHALL menandai Module tersebut sebagai locked.
5. WHEN AdaptiveEngineService memproses unlock/lock untuk seorang mahasiswa, THE AdaptiveEngineService SHALL membuat atau mengupdate record `user_module_progress` (unik per pasangan `user_id` + `module_id`) dengan flag `is_unlocked` yang sesuai.
6. THE AdaptiveEngineService SHALL hanya memproses Module dengan `is_active` = true.
7. THE Module schema SHALL menyimpan field `title`, `description`, `path_key`, `level` (`basic` | `intermediate` | `advanced` | `professional`), `tag` (`basic` | `advanced`), `order_index`, `content` (JSON), dan `is_active`.

### Requirement 11: Modul MVP Wajib

**User Story:** Sebagai Dosen, saya ingin modul Financial Presentation dan Client Meeting tersedia di MVP, supaya simulasi profesional inti siap digunakan saat go-live.

#### Acceptance Criteria

1. THE APELS_System SHALL menyediakan seeder yang membuat Module `financial_presentation` dengan `level` = `professional`, `path_key` = `professional_simulation`, dan `is_active` = true.
2. THE APELS_System SHALL menyediakan seeder yang membuat Module `client_meeting` dengan `level` = `professional`, `path_key` = `professional_simulation`, dan `is_active` = true.
3. WHERE path_key aktif mahasiswa = `industry_ready`, THE AdaptiveEngineService SHALL meng-unlock Module `financial_presentation`, `client_meeting`, dan `advanced_negotiation`.

### Requirement 12: Generasi Feedback Teks

**User Story:** Sebagai Mahasiswa, saya ingin menerima feedback teks otomatis tentang tiga skill saya dan langkah berikutnya, supaya saya tahu apa yang perlu saya tingkatkan.

#### Acceptance Criteria

1. WHEN FeedbackService dipanggil dengan tiga skor dan path_key, THE FeedbackService SHALL menghasilkan satu pesan teks yang mengevaluasi Speaking, Grammar, Vocabulary, dan menyebutkan langkah berikutnya berdasarkan path_key.
2. THE FeedbackService SHALL memilih template Speaking sesuai aturan: skor < 60 → `speaking_low`; 60 ≤ skor < 70 → `speaking_mid`; skor ≥ 70 → `speaking_high`.
3. THE FeedbackService SHALL memilih template Grammar dan Vocabulary mengikuti aturan threshold yang sama dengan butir 12.2.
4. THE FeedbackService SHALL menyertakan kalimat "Langkah berikutnya: fokus pada {next_focus}." dengan `next_focus` diambil dari peta `pathNextFocus` per path_key.
5. WHEN FeedbackService selesai memproses, THE FeedbackService SHALL mengembalikan struktur `['message' => string, 'details' => array, 'next_focus' => string]`.
6. WHEN FeedbackService dipanggil dari `runEngineAndFeedback`, THE Diagnostic_System SHALL menyimpan satu record Feedback berisi `user_id`, `diagnostic_result_id`, `message`, `details` (JSON), dan `next_focus`.
7. IF path_key tidak ditemukan di peta `pathNextFocus`, THEN THE FeedbackService SHALL menggunakan teks default "pengembangan skill lebih lanjut".
8. THE FeedbackService SHALL menghasilkan output dalam Bahasa Indonesia.

### Requirement 13: Dashboard Mahasiswa dengan Radar Chart

**User Story:** Sebagai Mahasiswa, saya ingin melihat dashboard yang menampilkan tiga skill saya dalam radar chart, label readiness, feedback, dan roadmap modul, supaya saya mendapat ringkasan progres yang jelas.

#### Acceptance Criteria

1. THE Dashboard SHALL menampilkan radar chart ApexCharts dengan tiga sumbu (Speaking, Grammar, Vocabulary) dan dua seri data (`Your Level` dan `Target` dengan target 80 untuk semua skill).
2. THE Dashboard SHALL menampilkan label path aktif (Foundation / Communication Fundamentals / Speaking Starter / Grammar Builder / Vocabulary Builder / Intermediate English / Professional Simulation / Industry Ready).
3. THE Dashboard SHALL menampilkan teks feedback terbaru dari record Feedback milik mahasiswa.
4. WHILE DiagnosticResult terbaru memiliki `is_speaking_processed` = false, THE Dashboard SHALL menampilkan notifikasi "Hasil speaking sedang diproses..." dan melakukan polling Livewire setiap 5 detik untuk memuat ulang data.
5. THE Dashboard SHALL menampilkan daftar modul mahasiswa beserta status `Completed` / `In Progress` / `Locked`.
6. THE Dashboard SHALL menampilkan progress bar dengan persentase modul selesai = `round(jumlah_modul_completed / jumlah_modul_total * 100)` (= 0 jika total = 0).
7. WHERE mahasiswa belum pernah mengerjakan Diagnostic Test, THE Dashboard SHALL menampilkan empty state yang mengarahkan mahasiswa untuk mengerjakan Diagnostic Test pertama.

### Requirement 14: Learning Roadmap dan Progress Modul

**User Story:** Sebagai Mahasiswa, saya ingin melihat roadmap modul saya dengan status unlock, locked, in-progress, dan completed, supaya saya tahu urutan belajar yang harus saya ikuti.

#### Acceptance Criteria

1. THE Learning_Path_System SHALL menampilkan modul yang terurut berdasarkan `order_index` ascending.
2. WHERE Module `is_unlocked` = true dan `is_completed` = false, THE Learning_Path_System SHALL menampilkan status `In Progress`.
3. WHERE Module `is_unlocked` = true dan `is_completed` = true, THE Learning_Path_System SHALL menampilkan status `Completed` beserta skor latihan.
4. WHERE Module `is_unlocked` = false, THE Learning_Path_System SHALL menampilkan status `Locked` dan menonaktifkan link akses ke detail modul.
5. WHEN Mahasiswa membuka modul yang ter-unlock, THE Learning_Path_System SHALL menampilkan halaman detail modul (deskripsi, konten, dan tombol mulai latihan).

### Requirement 15: Latihan/Quiz per Modul

**User Story:** Sebagai Mahasiswa, saya ingin mengerjakan latihan soal per modul dari bank soal dosen, supaya saya dapat menguji pemahaman dan menyelesaikan modul.

#### Acceptance Criteria

1. WHEN Mahasiswa membuka latihan modul, THE Exercise_System SHALL menampilkan kumpulan Question yang `is_active` = true dan `tag` modulnya cocok dengan tag modul.
2. THE Exercise_System SHALL menampilkan setiap Question dengan `question`, empat opsi (`option_a`–`option_d`), dan satu input pilihan jawaban.
3. WHEN Mahasiswa mengirim jawaban latihan, THE Exercise_System SHALL menghitung skor latihan = `round(jumlah_jawaban_benar / jumlah_total_soal * 100)`.
4. WHEN skor latihan ≥ 70, THE Exercise_System SHALL menandai Module sebagai `is_completed` = true, mengisi `score`, dan mengisi `completed_at` = waktu eksekusi pada record `user_module_progress`.
5. WHEN skor latihan < 70, THE Exercise_System SHALL menyimpan `score` terbaru tanpa menandai modul sebagai completed dan mengizinkan mahasiswa mengulang latihan.

### Requirement 16: Trigger Re-evaluation Setelah 70% Modul Selesai

**User Story:** Sebagai Mahasiswa, saya ingin sistem menyarankan saya mengerjakan Diagnostic Test ulang setelah saya menyelesaikan 70% modul aktif, supaya learning path saya diperbarui sesuai progres terbaru.

#### Acceptance Criteria

1. WHEN ratio modul completed terhadap total modul mahasiswa ≥ 0.70, THE ReEvaluationService SHALL mengembalikan true dari `shouldReEvaluateByProgress`.
2. WHILE `shouldReEvaluateByProgress` mengembalikan true dan mahasiswa belum tes hari ini, THE Dashboard SHALL menampilkan saran "Kamu siap untuk tes ulang!".
3. WHERE total modul mahasiswa = 0, THE ReEvaluationService SHALL mengembalikan false dari `shouldReEvaluateByProgress`.
4. THE ReEvaluationService SHALL menyediakan `canTakeTestToday(int $userId): bool` yang mengembalikan true bila tidak ada DiagnosticResult untuk mahasiswa pada hari kalender saat ini.

### Requirement 17: Format Standar API Response

**User Story:** Sebagai pengembang front-end dan integrator, saya ingin format API response yang konsisten, supaya konsumen API dapat memparsing respons dengan mudah.

#### Acceptance Criteria

1. THE APELS_System SHALL mengembalikan API_Response sukses dengan struktur `{ "status": "success", "message": string, "data": object }`.
2. THE APELS_System SHALL mengembalikan API_Response error dengan struktur `{ "status": "error", "message": string, "errors": object, "code": int }`.
3. WHEN endpoint API mengembalikan HTTP 4xx atau 5xx, THE APELS_System SHALL menyertakan field `code` yang bernilai sama dengan HTTP status code.
4. THE APELS_System SHALL meng-encode semua API_Response sebagai JSON dengan header `Content-Type: application/json`.

### Requirement 18: Admin Panel — Manajemen User

**User Story:** Sebagai Admin, saya ingin mengelola user dan role melalui Filament, supaya saya dapat menambah, mengubah, atau menonaktifkan akun.

#### Acceptance Criteria

1. THE Admin_Panel SHALL menyediakan `UserResource` yang dapat diakses oleh role `admin` saja.
2. WHEN Admin membuat User baru di `UserResource`, THE Admin_Panel SHALL meminta field `name`, `email`, `nim` (opsional, unik bila diisi), `password`, dan satu role dari himpunan {`admin`, `dosen`, `mahasiswa`}.
3. WHEN Admin menyimpan User baru, THE Admin_Panel SHALL menugaskan role yang dipilih melalui Spatie Laravel Permission.
4. THE Admin_Panel SHALL menyediakan fitur edit dan delete record pada `UserResource` untuk role `admin`.

### Requirement 19: Admin Panel — Bank Soal (Question_Bank)

**User Story:** Sebagai Dosen, saya ingin mengelola bank soal grammar dan vocabulary melalui Admin_Panel, supaya saya dapat memperbarui soal latihan tanpa bergantung pada developer.

#### Acceptance Criteria

1. THE Admin_Panel SHALL menyediakan `QuestionResource` yang dapat diakses oleh role `admin` dan `dosen`.
2. WHEN Dosen atau Admin membuat Question, THE Admin_Panel SHALL meminta field `question`, `option_a`–`option_d`, `correct_answer` ∈ {`a`,`b`,`c`,`d`}, `type` ∈ {`grammar`, `vocabulary`}, `tag` ∈ {`basic`, `intermediate`, `advanced`}, `difficulty` ∈ 1..5, dan `is_active`.
3. THE Admin_Panel SHALL menyediakan filter pada `QuestionResource` berdasarkan `type`, `tag`, dan `is_active`.
4. THE Admin_Panel SHALL menyediakan kolom tabel `question` (limit 60 karakter, searchable), `type`, `tag`, `is_active`, dan `created_at`.

### Requirement 20: Import Bank Soal dari CSV/Excel

**User Story:** Sebagai Dosen, saya ingin mengimpor bank soal dari file CSV atau Excel, supaya saya dapat menambahkan banyak soal sekaligus.

#### Acceptance Criteria

1. THE Admin_Panel SHALL menyediakan aksi "Import" pada `QuestionResource` yang dapat diakses oleh role `admin` dan `dosen`.
2. THE Admin_Panel SHALL menerima file dengan ekstensi `.csv` atau `.xlsx` yang berisi kolom: `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `type`, `tag`, `difficulty`.
3. WHEN file import diproses, THE Admin_Panel SHALL memvalidasi setiap baris dan membuat record Question baru hanya untuk baris yang lolos validasi.
4. IF satu atau lebih baris tidak lolos validasi, THEN THE Admin_Panel SHALL melaporkan jumlah baris berhasil dan daftar baris gagal beserta alasannya, tanpa membatalkan baris yang berhasil.
5. THE Admin_Panel SHALL menyediakan Pretty_Printer (CSV_Question_Exporter) yang mem-format daftar Question kembali ke representasi CSV sesuai grammar import.
6. FOR ALL daftar Question yang valid, melakukan ekspor lalu impor ulang SHALL menghasilkan kumpulan Question yang ekuivalen secara field-by-field (round-trip property).

### Requirement 21: Admin Panel — Manajemen Modul

**User Story:** Sebagai Dosen atau Admin, saya ingin mengelola konten modul, supaya materi pembelajaran selalu mutakhir.

#### Acceptance Criteria

1. THE Admin_Panel SHALL menyediakan `ModuleResource` yang dapat diakses oleh role `admin` dan `dosen`.
2. WHEN Dosen atau Admin membuat atau mengedit Module, THE Admin_Panel SHALL meminta field `title`, `description`, `path_key`, `level` ∈ {`basic`,`intermediate`,`advanced`,`professional`}, `tag` ∈ {`basic`,`advanced`}, `order_index`, `content` (JSON), dan `is_active`.
3. THE Admin_Panel SHALL memvalidasi `path_key` agar termasuk dalam delapan path_key yang didefinisikan di `config/learning_paths.php`.

### Requirement 22: Laporan Berkala Diagnostic Result untuk Dosen

**User Story:** Sebagai Dosen, saya ingin melihat laporan berkala hasil Diagnostic Test mahasiswa, supaya saya dapat memantau perkembangan kelas tanpa membutuhkan dashboard real-time.

#### Acceptance Criteria

1. THE Admin_Panel SHALL menyediakan `DiagnosticResultResource` yang dapat diakses oleh role `admin` (full) dan `dosen` (read-only).
2. THE Admin_Panel SHALL menampilkan kolom `user.name`, `speaking`, `grammar`, `vocabulary`, `overall`, `attempt`, dan `created_at` pada `DiagnosticResultResource`.
3. THE Admin_Panel SHALL menyediakan filter pada `DiagnosticResultResource` berdasarkan mahasiswa (`user_id`) dan rentang tanggal (`from`, `until`).
4. IF role `dosen` mencoba aksi create, edit, atau delete pada `DiagnosticResultResource`, THEN THE Admin_Panel SHALL menyembunyikan atau menonaktifkan aksi tersebut.

### Requirement 23: Penyimpanan dan Penghapusan File Audio

**User Story:** Sebagai pemilik sistem, saya ingin file audio disimpan secara privat dan dihapus setelah diproses, supaya storage hemat dan data tidak bocor.

#### Acceptance Criteria

1. THE Audio_Storage SHALL menyimpan file audio Diagnostic Test pada disk privat (`storage/private`) dan tidak menjadikannya dapat diakses publik melalui URL langsung.
2. WHEN ProcessSpeakingAudio_Job selesai dengan sukses, THE ProcessSpeakingAudio_Job SHALL menghapus file audio terkait dari Audio_Storage.
3. IF ProcessSpeakingAudio_Job gagal dan habis seluruh percobaan, THEN THE ProcessSpeakingAudio_Job SHALL menghapus file audio terkait dan mencatat penghapusan di log.
4. WHERE file audio yang dirujuk pada `audio_path` tidak ditemukan di Audio_Storage, THE SpeechToTextService SHALL mengembalikan skor speaking 0 dan mencatat error.

### Requirement 24: Manajemen API Key dan Konfigurasi OpenAI

**User Story:** Sebagai pemilik sistem, saya ingin API key OpenAI dikelola dengan aman, supaya tidak terjadi kebocoran kredensial.

#### Acceptance Criteria

1. THE APELS_System SHALL membaca `OPENAI_API_KEY` hanya melalui file `.env`.
2. THE APELS_System SHALL mengakses API key di kode aplikasi melalui helper `config('openai.api_key')` dan tidak melalui pemanggilan `env()` langsung di service runtime.
3. THE APELS_System SHALL memiliki entri `.env` pada `.gitignore` sehingga tidak pernah di-commit.
4. IF `OPENAI_API_KEY` tidak terisi atau kosong, THEN THE APELS_System SHALL mencatat error pada log saat service NLP atau STT dipanggil dan mengembalikan skor 0 sebagai fallback.

### Requirement 25: Performa, Uptime, dan Budget API

**User Story:** Sebagai pemilik sistem, saya ingin sistem responsif, andal, dan hemat biaya, supaya pengalaman 100+ mahasiswa terjamin tanpa membengkaknya tagihan.

#### Acceptance Criteria

1. WHEN Mahasiswa men-submit Diagnostic Test, THE Diagnostic_System SHALL mengembalikan API_Response sinkron (sebelum speaking diproses queue) dalam waktu kurang dari atau sama dengan 60 detik pada beban normal.
2. THE APELS_System SHALL ditargetkan beroperasi dengan uptime minimal 99% per bulan kalender dalam lingkungan production.
3. THE APELS_System SHALL men-set timeout SpeechToTextService pada `APELS_STT_TIMEOUT` detik (default 60) dan timeout NlpAnalysisService pada `APELS_NLP_TIMEOUT` detik (default 30).
4. THE APELS_System SHALL menargetkan total biaya OpenAI API tidak melebihi 20 USD per bulan untuk 100+ mahasiswa dengan asumsi 8 tes per mahasiswa per bulan, melalui kombinasi limit 1 tes/hari, NLP_Cache 24 jam, dan penghapusan audio setelah diproses.
5. THE APELS_System SHALL mendukung minimal 100 mahasiswa aktif konkuren tanpa kegagalan request pada lingkungan VPS dengan 2–4 vCPU dan 4 GB RAM.

### Requirement 26: Idempotence dan Konsistensi Adaptive Engine pada Pemanggilan Berulang

**User Story:** Sebagai pemilik sistem, saya ingin pemanggilan ulang Adaptive Engine dengan input dan state mahasiswa yang sama tidak menimbulkan duplikasi atau perubahan path, supaya state mahasiswa konsisten.

#### Acceptance Criteria

1. WHEN AdaptiveEngineService dijalankan dua kali berturut-turut untuk seorang mahasiswa dengan tiga skor yang sama dan state Module yang sama, THE AdaptiveEngineService SHALL menghasilkan path_key, modules_unlocked, dan modules_locked yang identik (idempotence).
2. THE AdaptiveEngineService SHALL menjamin bahwa tidak ada duplikasi record `user_learning_paths` aktif untuk seorang mahasiswa (maksimum satu record per `user_id` dengan `status` = `active`).
3. THE AdaptiveEngineService SHALL menjamin keunikan record `user_module_progress` per pasangan (`user_id`, `module_id`).

### Requirement 27: Penanganan Kegagalan API Eksternal

**User Story:** Sebagai pemilik sistem, saya ingin sistem tetap berjalan saat OpenAI API down atau lambat, supaya mahasiswa tidak mengalami kegagalan total.

#### Acceptance Criteria

1. IF OpenAI Whisper API mengalami kegagalan jaringan atau HTTP 5xx, THEN THE SpeechToTextService SHALL mencatat error pada log dan mengembalikan skor 0.
2. IF OpenAI GPT-4o Mini API mengalami kegagalan jaringan atau HTTP 5xx, THEN THE NlpAnalysisService SHALL mencatat error pada log dan mengembalikan `['grammar_score' => 0, 'vocab_score' => 0]`.
3. WHEN service eksternal gagal sehingga skor 0, THE Diagnostic_System SHALL tetap mempersist record DiagnosticResult, menjalankan AdaptiveEngineService, dan men-generate Feedback berbasis skor 0.
4. THE ProcessSpeakingAudio_Job SHALL melakukan retry maksimal 3 kali dengan backoff 5 detik sebelum memanggil handler `failed`.

### Requirement 28: Standar Service Layer dan Error Logging

**User Story:** Sebagai pengembang, saya ingin business logic terisolasi di Service dengan logging error yang konsisten, supaya kode mudah dirawat dan diaudit.

#### Acceptance Criteria

1. THE APELS_System SHALL menempatkan business logic Diagnostic, Scoring, Adaptive Engine, Feedback, STT, dan NLP pada class Service di namespace `App\Services`, bukan di Controller atau Livewire component.
2. THE APELS_System SHALL mendeklarasikan return type untuk setiap method publik pada Service.
3. WHEN method Service menangkap exception dari operasi API atau database, THE method SHALL mencatat error melalui `Log::error` dengan pesan yang menyertakan konteks user dan operasi.
4. THE APELS_System SHALL menyediakan fallback value yang aman (mis. skor 0, array kosong) untuk setiap method Service yang menangkap exception, sehingga sistem tidak crash.

### Requirement 29: Konvensi Naming dan Kode

**User Story:** Sebagai pengembang baru, saya ingin konvensi naming yang seragam, supaya saya cepat memahami struktur kode.

#### Acceptance Criteria

1. THE APELS_System SHALL menggunakan PascalCase singular untuk nama Model, contoh `DiagnosticResult`.
2. THE APELS_System SHALL menggunakan PascalCase + Service untuk nama Service, contoh `AdaptiveEngineService`.
3. THE APELS_System SHALL menggunakan kebab-case untuk nama route dan blade view file.
4. THE APELS_System SHALL menggunakan camelCase untuk nama variabel PHP dan snake_case untuk nama key file konfigurasi.
5. THE APELS_System SHALL menyimpan konfigurasi global pada `config/apels.php` dan konfigurasi learning path pada `config/learning_paths.php`.

### Requirement 30: Skema Database dan Integritas Referensial

**User Story:** Sebagai pengembang, saya ingin skema database yang konsisten dengan PRD, supaya semua fitur dapat dibangun di atas fondasi data yang sama.

#### Acceptance Criteria

1. THE APELS_System SHALL membuat kolom tambahan pada tabel `users`: `nim` (string nullable unique), `level` (integer default 1), dan `avatar` (string nullable).
2. THE APELS_System SHALL membuat tabel `diagnostic_results` dengan kolom `user_id` (FK cascade onDelete), `speaking`, `grammar`, `vocabulary` (integer default 0), `overall` (float default 0), `attempt` (integer default 1), `audio_path` (string nullable), `is_speaking_processed` (boolean default false), dan timestamps.
3. THE APELS_System SHALL membuat tabel `user_learning_paths` dengan kolom `user_id` (FK cascade onDelete), `path_key` (string), `status` (enum `active`/`completed` default `active`), `assigned_at` (timestamp), dan timestamps.
4. THE APELS_System SHALL membuat tabel `modules` dengan kolom yang sesuai Requirement 10.7 dan timestamps.
5. THE APELS_System SHALL membuat tabel `user_module_progress` dengan unique index pada (`user_id`, `module_id`) dan kolom `is_unlocked`, `is_completed`, `score` (nullable), `completed_at` (nullable), serta timestamps.
6. THE APELS_System SHALL membuat tabel `questions` dengan kolom yang sesuai Requirement 19.2 dan timestamps.
7. THE APELS_System SHALL membuat tabel `feedbacks` dengan kolom `user_id` (FK cascade onDelete), `diagnostic_result_id` (FK cascade onDelete), `message` (text), `details` (JSON nullable), `next_focus` (string nullable), dan timestamps.

### Requirement 31: Pengelolaan Queue Worker

**User Story:** Sebagai pemilik sistem, saya ingin queue worker dijalankan terus-menerus di production, supaya speaking audio diproses tepat waktu.

#### Acceptance Criteria

1. THE APELS_System SHALL menyediakan tabel queue (`php artisan queue:table` lalu migrate) saat driver queue = `database`.
2. THE APELS_System SHALL mendokumentasikan konfigurasi Supervisor untuk menjalankan minimal 2 proses queue worker di lingkungan production.
3. THE ProcessSpeakingAudio_Job SHALL diregistrasi pada queue default dan tidak memerlukan flag khusus untuk dijalankan oleh worker.

### Requirement 32: Build, Asset, dan Deployment Production

**User Story:** Sebagai DevOps, saya ingin checklist deployment yang jelas, supaya setiap rilis production konsisten dan aman.

#### Acceptance Criteria

1. THE APELS_System SHALL mendukung perintah `npm run build` untuk menghasilkan asset front-end production menggunakan TailwindCSS dan ApexCharts.
2. THE APELS_System SHALL mendukung perintah `php artisan optimize` (cache config, route, dan view) untuk lingkungan production.
3. WHERE lingkungan = production, THE APELS_System SHALL berjalan dengan `APP_ENV=production` dan `APP_DEBUG=false`.
4. THE APELS_System SHALL mendukung penggunaan SSL via Let's Encrypt pada domain production.
5. THE APELS_System SHALL menargetkan platform desktop/laptop (lebar layar minimum 1024 px) dan tidak diwajibkan responsif untuk perangkat mobile pada MVP.

### Requirement 33: Roadmap Implementasi Bertahap (Phase 1–4)

**User Story:** Sebagai project manager, saya ingin urutan pengembangan jelas per phase, supaya tim dapat memprioritaskan pekerjaan menuju deadline awal Juli 2025.

#### Acceptance Criteria

1. THE APELS_System SHALL diimplementasikan secara bertahap dalam empat phase: Phase 1 (MVP Core), Phase 2 (Learning System), Phase 3 (Admin & Laporan), Phase 4 (Polish & Testing).
2. Phase 1 SHALL mencakup setup project Laravel 12 + Livewire Starter Kit, migrasi database, RoleSeeder, ModuleSeeder, autentikasi tiga role, Daily_Test_Limit_Middleware, DiagnosticTest Livewire wizard, NlpAnalysisService, SpeechToTextService, ProcessSpeakingAudio_Job, ScoringService, AdaptiveEngineService, FeedbackService, dan Dashboard.
3. Phase 2 SHALL mencakup learning roadmap, halaman detail Module Financial Presentation dan Client Meeting, exercise per modul, progress tracking, dan trigger re-evaluation 70%.
4. Phase 3 SHALL mencakup `QuestionResource`, `ModuleResource`, `DiagnosticResultResource`, dan import bank soal CSV/Excel.
5. Phase 4 SHALL mencakup unit test AdaptiveEngine (delapan skenario path), feature test endpoint utama, UI polish, performance test 100+ user konkuren, security review, dan deployment ke VPS.
6. AdaptiveEngineService SHALL diselesaikan dan stabil sebelum Phase 2 dimulai.

### Requirement 34: Testing Functional dan Properti Korektnis

**User Story:** Sebagai pemilik sistem, saya ingin pengujian otomatis menjamin korektnis Adaptive Engine, integritas alur Diagnostic, dan import bank soal, supaya regresi cepat terdeteksi.

#### Acceptance Criteria

1. THE APELS_System SHALL menyediakan unit test yang mencakup kedelapan skenario rule Adaptive Engine.
2. FOR ALL kombinasi tiga skor (Speaking, Grammar, Vocabulary) pada rentang 0–100, AdaptiveEngineService.evaluateRules SHALL mengembalikan satu path_key valid (totality property) dan menghasilkan output yang sama untuk input yang sama (idempotence).
3. THE APELS_System SHALL menyediakan feature test untuk endpoint Diagnostic Test yang memverifikasi: submit sukses, blokir tes kedua dalam hari yang sama (HTTP 429), dan blokir guest (HTTP 401).
4. FOR ALL kumpulan Question valid, ekspor CSV dilanjutkan impor CSV SHALL menghasilkan record Question yang ekuivalen field-by-field (round-trip property pada parser/printer CSV).
5. THE APELS_System SHALL menyediakan target coverage minimal 70% untuk modul Adaptive Engine, ScoringService, dan FeedbackService.
