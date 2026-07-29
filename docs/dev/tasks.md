# Implementation Plan: APELS Platform

## Overview

Implementasi APELS — platform pembelajaran Bahasa Inggris berbasis Laravel 12 + Livewire 3 + Filament 3 dengan Adaptive Engine rule-based untuk delapan learning path. Roadmap mengikuti empat phase pada Requirement 33 dan section "Pemetaan Phase Implementasi" di design.md:

- **Phase 0** — Project bootstrap (laravel new, composer, konfigurasi `.env`, `config/apels.php`, `config/learning_paths.php`, Filament install, Spatie Permission, queue table).
- **Phase 1** — MVP Core: migrasi, seeder, auth tiga role, `Daily_Test_Limit_Middleware`, Diagnostic wizard, services NLP/STT/Scoring/Adaptive/Feedback, `ProcessSpeakingAudio` job, Dashboard. **`AdaptiveEngineService` HARUS stabil dan lulus seluruh property test sebelum Phase 2 (Req 33.6).**
- **Phase 2** — Learning System: roadmap, Module detail (Financial Presentation + Client Meeting), Exercise per modul, progress tracking, re-evaluation 70%.
- **Phase 3** — Admin & Laporan: `UserResource`, `QuestionResource` + import/export CSV/XLSX, `ModuleResource`, `DiagnosticResultResource`, Lecturer Reports.
- **Phase 4** — Polish & Testing: feature test endpoint, performance test 100+ user, security review, deployment VPS dengan Supervisor + SSL Let's Encrypt + `npm run build` + `php artisan optimize`.

Property test yang ditandai berbasis sepuluh Correctness Properties di `design.md` §Correctness Properties.

## Tasks

- [x] 1. Phase 0 — Project Bootstrap & Konfigurasi Awal
  - [x] 1.1 Inisialisasi project Laravel 12 dengan Livewire Starter Kit
    - Jalankan `laravel new apels --livewire` di workspace root, tetap di branch utama yang tersedia
    - Verifikasi PHP ≥ 8.2 dan Composer terbaru, jalankan `php artisan key:generate`
    - _Requirements: 33.2, 32.5_
  - [x] 1.2 Install package backend produksi via Composer
    - `composer require filament/filament:"^3.0" -W`
    - `composer require spatie/laravel-permission`
    - `composer require openai-php/laravel`
    - `composer require maatwebsite/excel`
    - `composer require league/csv`
    - _Requirements: 2.1, 6.1, 7.2, 18.1, 19.1, 20.1, 20.2_
  - [x] 1.3 Install dev dependencies untuk testing & PBT
    - `composer require --dev pestphp/pest pestphp/pest-plugin-laravel`
    - `composer require --dev giorgiosironi/eris`
    - Bootstrap Pest: `./vendor/bin/pest --init`
    - _Requirements: 34.1, 34.2, 34.4, 34.5_
  - [x] 1.4 Konfigurasi file `.env` baseline development
    - Set `APP_NAME=APELS`, `APP_ENV=local`, `APP_DEBUG=true`
    - Set DB MySQL (`apels_db`), `QUEUE_CONNECTION=database`, `CACHE_DRIVER=file`, `SESSION_DRIVER=database`
    - Tambahkan `OPENAI_API_KEY`, `APELS_DAILY_TEST_LIMIT=1`, `APELS_AUDIO_MAX_MB=10`, `APELS_STT_TIMEOUT=60`, `APELS_NLP_TIMEOUT=30`
    - Pastikan entri `.env` terdaftar pada `.gitignore`
    - _Requirements: 24.1, 24.3, 25.3, 32.3_
  - [x] 1.5 Buat `config/apels.php`
    - Export `daily_test_limit`, `audio_max_mb`, `stt_timeout`, `nlp_timeout` dari env dengan default sesuai Req
    - _Requirements: 5.3, 25.3, 29.5_
  - [x] 1.6 Buat `config/learning_paths.php`
    - Definisikan delapan path key (`emergency_foundation`, `fundamental_communication`, `basic_speaking`, `grammar_foundation`, `vocabulary_builder`, `intermediate_path`, `professional_simulation`, `industry_ready`) dengan field `label`, `color`, `condition`, `modules` sesuai CLAUDE §10
    - _Requirements: 9.3, 11.1, 11.2, 11.3, 13.2, 21.3, 29.5_
  - [x] 1.7 Tambahkan disk privat `private` untuk Audio_Storage di `config/filesystems.php`
    - Disk root `storage_path('private')`, driver `local`, visibility `private`
    - _Requirements: 5.5, 23.1_
  - [x] 1.8 Setup queue & failed_jobs migrations
    - `php artisan queue:table`, `php artisan queue:failed-table`
    - Pastikan migrasi siap dijalankan di task selanjutnya
    - _Requirements: 31.1, 31.3_
  - [x] 1.9 Install Filament 3 panel + publish Spatie Permission migrations
    - `php artisan filament:install --panels`
    - `php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"`
    - Belum perlu `migrate`; akan dieksekusi setelah migrasi domain dibuat
    - _Requirements: 2.1, 18.1_
  - [x] 1.10 Publish konfigurasi `openai-php/laravel`
    - `php artisan vendor:publish --provider="OpenAI\\Laravel\\ServiceProvider"`
    - Verifikasi `config/openai.php` membaca `OPENAI_API_KEY` lewat helper `config()`, bukan `env()` di runtime
    - _Requirements: 24.1, 24.2_

- [x] 2. Phase 1 — Database Schema, Models, dan Seeder
  - [x] 2.1 Migrasi additive tabel `users` (NIM, level, avatar)
    - Tambah kolom `nim` string nullable unique, `level` integer default 1, `avatar` string nullable
    - _Requirements: 1.1, 1.2, 30.1_
  - [x] 2.2 Migrasi `diagnostic_results`
    - Kolom sesuai Req 30.2; tambahkan composite index `(user_id, created_at)` untuk Daily_Test_Limit_Middleware
    - _Requirements: 4.2, 30.2_
  - [x] 2.3 Migrasi `user_learning_paths`
    - Kolom sesuai Req 30.3; tambahkan unique constraint pada `user_id` (single record aktif per mahasiswa)
    - _Requirements: 9.4, 26.2, 30.3_
  - [x] 2.4 Migrasi `modules`
    - Kolom sesuai Req 10.7 + 30.4; tambahkan composite index `(path_key, is_active)`
    - _Requirements: 10.7, 30.4_
  - [x] 2.5 Migrasi `user_module_progress`
    - Kolom sesuai Req 30.5; unique composite `(user_id, module_id)`
    - _Requirements: 10.5, 26.3, 30.5_
  - [x] 2.6 Migrasi `questions`
    - Kolom sesuai Req 19.2 + 30.6; index `(type, tag, is_active)`
    - _Requirements: 19.2, 30.6_
  - [x] 2.7 Migrasi `feedbacks`
    - Kolom sesuai Req 30.7; index `(user_id, created_at)`
    - _Requirements: 12.6, 30.7_
  - [x] 2.8 Eloquent model `User`
    - Trait `HasRoles` (Spatie), guard `web`, relasi `diagnosticResults`, `learningPath`, `moduleProgress`, `feedbacks`; cast password
    - _Requirements: 1.1, 2.1, 30.1_
  - [x] 2.9 Eloquent model `DiagnosticResult`
    - Cast `is_speaking_processed` boolean, `overall` float; relasi `belongsTo user`, `hasOne feedback`
    - _Requirements: 4.2, 30.2_
  - [x] 2.10 Eloquent model `UserLearningPath`
    - Cast `assigned_at` datetime; scope `active`
    - _Requirements: 9.4, 30.3_
  - [x] 2.11 Eloquent model `Module`
    - Cast `content` array, `is_active` boolean; scope `active()` & query helper `byPath()`
    - _Requirements: 10.7, 30.4_
  - [x] 2.12 Eloquent model `UserModuleProgress`
    - Cast booleans + `completed_at` datetime; relasi `belongsTo user`, `belongsTo module`
    - _Requirements: 10.5, 30.5_
  - [x] 2.13 Eloquent model `Question`
    - Cast `is_active` boolean; scope `active()` & filter `byTag`/`byType`
    - _Requirements: 19.2, 30.6_
  - [x] 2.14 Eloquent model `Feedback`
    - Cast `details` array; relasi `belongsTo user`, `belongsTo diagnosticResult`
    - _Requirements: 12.6, 30.7_
  - [x] 2.15 `RoleSeeder` (3 role + 10 permission)
    - Buat role `admin`, `dosen`, `mahasiswa`; permission sesuai Req 2.7–2.9
    - Assign permission ke role dan jalankan via `DatabaseSeeder`
    - _Requirements: 2.1, 2.7, 2.8, 2.9_
  - [x] 2.16 `ModuleSeeder` MVP (Financial Presentation + Client Meeting)
    - Buat module `financial_presentation` dan `client_meeting` dengan `level=professional`, `path_key=professional_simulation`, `is_active=true`
    - Tambah satu module level `basic` placeholder agar logika unlock basic dapat diuji
    - _Requirements: 10.2, 11.1, 11.2_

- [x] 3. Phase 1 — Authentication, Authorization, Routing
  - [x] 3.1 Customisasi form registrasi Livewire Starter Kit (NIM unik + assign role mahasiswa)
    - Tambah field `nim` (required, unique:users) ke component & view
    - Setelah `User::create`, panggil `$user->assignRole('mahasiswa')`
    - _Requirements: 1.1, 1.2, 1.6_
  - [x] 3.2 Login, logout, dan pesan error generik
    - Pastikan pesan `These credentials do not match our records.` dipakai (tidak membocorkan email vs password)
    - Hapus session pada logout & redirect ke `/login`
    - _Requirements: 1.3, 1.4, 1.5_
  - [x] 3.3 Konfigurasi redirect default per role setelah login
    - Override `RouteServiceProvider::HOME` atau gunakan listener `Login` untuk redirect: admin → `/admin`, dosen → `/reports`, mahasiswa → `/dashboard`
    - _Requirements: 1.3, 2.2, 2.4, 2.6_
  - [x] 3.4 Definisikan route group dengan role middleware
    - Group `auth` + `role:mahasiswa` untuk `/dashboard`, `/learning-path`, `/exercises`, `/diagnostic`
    - Group `auth` + `role:dosen` untuk `/reports`
    - Pasang `daily.test.limit` pada route Diagnostic (dipasang di Task 4.1 setelah middleware tersedia)
    - _Requirements: 2.2, 2.3, 2.4, 2.10_
  - [x] 3.5 Filament panel access control
    - Implementasikan `User::canAccessPanel(Panel $panel): bool` → `hasAnyRole(['admin','dosen'])`
    - Pastikan resource lain (di Phase 3) menyaring akses dosen via policy/`canViewAny`
    - _Requirements: 2.5, 2.6_
  - [x] 3.6 Feature test auth + role access
    - Test: registrasi NIM duplikat → 422; login valid; guest 401/redirect; akses cross-role 403
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.2, 2.3, 2.4, 2.10_

- [x] 4. Phase 1 — Daily Test Limit Middleware & API Response Helper
  - [x] 4.1 Implement `EnsureDailyTestLimit` middleware
    - Class `App\Http\Middleware\EnsureDailyTestLimit`
    - Cek `DiagnosticResult::where('user_id', auth()->id())->whereDate('created_at', today())->exists()` (gunakan timezone `config('app.timezone')`)
    - Return JSON 429 dengan `ApiResponse::error('Anda sudah mengerjakan tes hari ini. Coba lagi besok.', 429)`
    - Daftarkan alias `daily.test.limit` di `bootstrap/app.php` dan pasang pada route Diagnostic Test
    - _Requirements: 3.1, 3.2, 3.3_
  - [x] 4.2 Implement helper `App\Support\ApiResponse` (success/error standar)
    - `success(string $message, array $data = []): JsonResponse` → `{status:'success', message, data}`
    - `error(string $message, int $code, array $errors = []): JsonResponse` → `{status:'error', message, errors, code}` dengan HTTP status = `code`
    - Header `Content-Type: application/json`
    - _Requirements: 17.1, 17.2, 17.3, 17.4_
  - [x] 4.3 Feature test `EnsureDailyTestLimit` + bentuk `ApiResponse`
    - Skenario: user belum tes (lewat) vs sudah tes (429); verifikasi struktur JSON success & error
    - _Requirements: 3.1, 3.2, 17.1, 17.2_

- [x] 5. Phase 1 — ScoringService dengan Property Tests
  - [x] 5.1 Implement `ScoringService::calculateOverall` dan `normalize`
    - Bobot `speaking=0.40, grammar=0.30, vocabulary=0.30`
    - `calculateOverall` mengembalikan `round(speaking*0.40 + grammar*0.30 + vocabulary*0.30, 2)` dan menganggap key absent sebagai 0
    - `normalize($score, $min=0.0, $max=1.0)` → `(int) round((($score - $min) / ($max - $min)) * 100)`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
  - [x] 5.2 Property test `ScoringService::calculateOverall`
    - **Property 1: ScoringService.calculateOverall — bounds, missing-key fallback, determinism**
    - **Validates: Requirements 8.2, 8.3, 8.4**
  - [x] 5.3 Property test `ScoringService::normalize`
    - **Property 2: ScoringService.normalize — boundary correctness and monotonicity**
    - **Validates: Requirements 8.5**
  - [x] 5.4 Unit test contoh `ScoringService` (smoke + edge `[100,100,100]`, `[0,0,0]`)
    - _Requirements: 8.1, 8.2_

- [x] 6. Phase 1 — FeedbackService dengan Property Test
  - [x] 6.1 Implement `FeedbackService::generate(array $scores, string $pathKey): array`
    - 9 template (`speaking_low/mid/high`, `grammar_low/mid/high`, `vocabulary_low/mid/high`) dalam Bahasa Indonesia
    - Peta `pathNextFocus` 8 path; fallback "pengembangan skill lebih lanjut"
    - Aturan threshold: `<60 → low`, `60–69 → mid`, `≥70 → high`
    - Kembalikan `['message' => string, 'details' => array, 'next_focus' => string]`
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.7, 12.8_
  - [x] 6.2 Property test `FeedbackService::generate`
    - **Property 7: FeedbackService.generate — threshold determinism and structural completeness**
    - **Validates: Requirements 12.2, 12.3, 12.5, 12.7**
  - [x] 6.3 Unit test fallback path tidak dikenal & locale ID
    - _Requirements: 12.7, 12.8_

- [x] 7. Phase 1 — AdaptiveEngineService (CORE) dengan Property Tests
  - [x] 7.1 Implement `AdaptiveEngineService::evaluateRules(array $scores): string`
    - Pure method (tanpa DB/IO); urutan branching sesuai diagram §6 design.md
    - Validasi setiap skor pada rentang 0..100; di luar rentang → `InvalidArgumentException`
    - _Requirements: 9.1, 9.2, 9.3, 9.6_
  - [x] 7.2 Implement `shouldUnlock` dan `resolveModules`
    - `path_key` cocok → unlock; `level=basic` → selalu unlock; `level=professional` → unlock jika `sp ≥ 70 AND gr ≥ 70 AND vo ≥ 70`; selain itu locked
    - Hanya proses `Module::where('is_active', true)`
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.6, 11.3_
  - [x] 7.3 Implement `persist` dan `run`
    - `UserLearningPath::updateOrCreate(['user_id'=>$id], ['path_key'=>..., 'status'=>'active', 'assigned_at'=>now()])`
    - Loop `unlocked`/`locked` dengan `UserModuleProgress::updateOrCreate(['user_id','module_id'], ['is_unlocked'=>...])`
    - `run` mengembalikan `['learning_path','path_label','modules_unlocked','modules_locked']`
    - _Requirements: 9.4, 9.5, 11.3, 26.2, 26.3_
  - [x] 7.4 Unit test 8 skenario path eksplisit (example test)
    - Contoh klasik per branch: `(40,40,40)`, `(50,50,40)`, `(50,80,80)`, `(80,50,80)`, `(80,80,50)`, `(60,60,60)`, `(75,75,75)`, `(85,80,80)`
    - _Requirements: 9.1, 34.1_
  - [x] 7.5 Property test `evaluateRules` totality + determinism
    - **Property 3: AdaptiveEngineService.evaluateRules — totality and determinism**
    - **Validates: Requirements 9.2, 9.3, 34.2**
  - [x] 7.6 Property test `evaluateRules` rejecting out-of-range input
    - **Property 4: AdaptiveEngineService.evaluateRules — out-of-range input rejection**
    - **Validates: Requirements 9.6**
  - [x] 7.7 Property test Module unlock invariants
    - **Property 5: AdaptiveEngineService — Module unlock invariants**
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.6**
  - [x] 7.8 Property test `run` idempotence + DB invariants
    - **Property 6: AdaptiveEngineService.run — idempotence on repeated invocation**
    - **Validates: Requirements 26.1, 26.2, 26.3**

- [x] 8. Phase 1 — Checkpoint Adaptive Engine Stable
  - Ensure all tests pass, ask the user if questions arise.
  - Wajib: seluruh property test (P1, P2, P3, P4, P5, P6, P7) dan 8 path test eksplisit lulus sebelum lanjut ke Phase 2.
  - _Requirements: 33.6_

- [x] 9. Phase 1 — OpenAI Service Wrappers (STT & NLP)
  - [x] 9.1 Implement `SpeechToTextService::analyze(string $audioPath): int` + `scoreFromTranscript`
    - Cek `Storage::disk('private')->exists($audioPath)`; tidak ada → log error, return 0
    - `OpenAI::audio()->transcribe(['model'=>'whisper-1','file'=>fopen($abs,'r'),'language'=>'en'])` dengan timeout `config('apels.stt_timeout')`
    - `scoreFromTranscript`: `min(round(words/20*100), 100)`; bila `words<5` → `max(round(words/20*100), 10)`
    - Tangkap exception → `Log::error` dengan konteks user/operasi, return 0
    - _Requirements: 6.1, 6.2, 6.3, 6.7, 23.4, 25.3, 27.1, 28.3_
  - [x] 9.2 Property test `scoreFromTranscript` bounds
    - **Property 8: SpeechToTextService.scoreFromTranscript — bounds and short-transcript minimum**
    - **Validates: Requirements 6.2, 6.3**
  - [x] 9.3 Integration test SpeechToTextService dengan `OpenAI::fake`
    - Skenario sukses (transcript banyak kata), exception (timeout/5xx → 0), file hilang → 0
    - _Requirements: 6.1, 6.7, 23.4, 27.1_
  - [x] 9.4 Implement `NlpAnalysisService::analyze(array $textAnswers): array`
    - `combinedText = implode(' ', $textAnswers)`
    - `cacheKey = "nlp_result_" . md5(implode('|', $textAnswers))`, TTL 24 jam via `Cache::remember`
    - `OpenAI::chat()->create(['model'=>'gpt-4o-mini', 'response_format'=>['type'=>'json_object'], 'max_tokens'=>100, 'messages'=>[system,user]])` dengan timeout `config('apels.nlp_timeout')`
    - Parse JSON dan clamp `grammar_score`, `vocab_score` ke 0..100
    - Exception/parse error → log + return `['grammar_score'=>0,'vocab_score'=>0]`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 25.3, 27.2, 28.3_
  - [x] 9.5 Integration test NlpAnalysisService cache hit/miss + fallback
    - Verifikasi panggilan API hanya saat cache miss; exception → fallback skor 0
    - _Requirements: 7.5, 7.6, 27.2_

- [x] 10. Phase 1 — Diagnostic Job & Service Orchestrator
  - [x] 10.1 Implement `App\Jobs\ProcessSpeakingAudio`
    - `tries=3`, `backoff=5`, `timeout=60`
    - `handle`: panggil STT → update DiagnosticResult (`speaking`, `is_speaking_processed=true`) → `DiagnosticService::runEngineAndFeedback` → `Storage::disk('private')->delete($audioPath)`
    - `failed`: log error + set `speaking=0,is_speaking_processed=true` + hapus audio + jalankan engine agar UI tidak menggantung
    - _Requirements: 6.4, 6.5, 6.6, 6.8, 6.9, 23.2, 23.3, 27.4, 31.3_
  - [x] 10.2 Implement `DiagnosticService`
    - `process(int $userId, array $input): array` — orkestrasi NLP sinkron, persist DiagnosticResult, dispatch job (atau jalankan engine sinkron jika tidak ada audio), tangkap exception NLP → skor 0 lanjut alur
    - `runEngineAndFeedback(int $userId, DiagnosticResult $diagnostic): void` — `ScoringService::calculateOverall` → `AdaptiveEngineService::run` → `FeedbackService::generate` → `Feedback::create`
    - `getAttemptNumber` private helper
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 12.6, 27.3, 28.1, 28.2_
  - [x] 10.3 Feature test `ProcessSpeakingAudio` (success + failed) dengan `OpenAI::fake` & `Storage::fake('private')`
    - Verifikasi update DiagnosticResult, panggilan engine, penghapusan file, behavior pada `failed()`
    - _Requirements: 6.4, 6.5, 6.6, 6.9, 23.2, 23.3, 27.4_
  - [x] 10.4 Feature test fallback NLP gagal → DiagnosticResult tetap dibuat & engine tetap jalan
    - _Requirements: 4.6, 27.2, 27.3_

- [x] 11. Phase 1 — Diagnostic Endpoint, Validation, Wizard UI
  - [x] 11.1 Implement `App\Http\Requests\SubmitDiagnosticRequest`
    - `text_answers: required|array|min:3|max:20`
    - `text_answers.*: required|string|min:5|max:500`
    - `audio: required|file|mimes:wav,mp3,webm,ogg|max:(audio_max_mb*1024)`
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  - [x] 11.2 Implement `App\Http\Controllers\Api\DiagnosticController::submit`
    - Pasang middleware `auth`, `role:mahasiswa`, `daily.test.limit`
    - Simpan audio via `Storage::disk('private')->putFile('diagnostic-audio', ...)`
    - Panggil `DiagnosticService::process` dan kembalikan `ApiResponse::success`
    - Definisikan route `POST /api/diagnostic/submit`
    - _Requirements: 4.5, 5.5, 17.1, 17.4, 23.1_
  - [x] 11.3 Implement Livewire `App\Livewire\Student\DiagnosticTest` (wizard 3 step)
    - Step grammar, vocabulary, speaking; navigation forward/back; validasi per step
    - Simpan jawaban di property komponen, agregasikan `text_answers` saat submit
    - _Requirements: 4.1, 4.2_
  - [x] 11.4 Wire Livewire `DiagnosticTest` ke endpoint submit
    - Upload audio via Livewire `WithFileUploads`, simpan ke disk `private`
    - Setelah submit sukses, redirect ke Dashboard dengan flash message
    - _Requirements: 4.3, 4.5, 5.5, 23.1_
  - [x] 11.5 Feature test submit diagnostic happy path (audio + teks)
    - Verifikasi DiagnosticResult tersimpan, job di-dispatch, ApiResponse success
    - _Requirements: 4.1, 4.2, 4.3, 4.5, 5.1, 5.2, 5.3, 5.5, 34.3_
  - [x] 11.6 Feature test 429 daily limit (tes kedua) + 401 guest
    - _Requirements: 2.10, 3.1, 34.3_

- [x] 12. Phase 1 — Dashboard Mahasiswa
  - [x] 12.1 Implement Livewire `App\Livewire\Student\Dashboard`
    - Property: `skills`, `pathKey`, `pathLabel`, `modules`, `feedback`, `progress`, `speakingProcessing`, `shouldReEvaluate`
    - Method `loadData()` membaca DiagnosticResult terbaru, UserLearningPath aktif, UserModuleProgress, Feedback terbaru
    - Empty state ketika belum ada DiagnosticResult
    - _Requirements: 13.1, 13.2, 13.3, 13.5, 13.6, 13.7_
  - [x] 12.2 Implement Blade Dashboard + ApexCharts radar chart
    - Radar 3 sumbu (Speaking, Grammar, Vocabulary) dengan series `Your Level` & target `[80,80,80]`
    - Polling `wire:poll.5000ms="loadData"` aktif hanya saat `speakingProcessing == true` dan tampilkan notifikasi "Hasil speaking sedang diproses..."
    - Tampilkan label readiness, list modul + status, progress bar persen
    - _Requirements: 13.1, 13.2, 13.4, 13.5, 13.6_
  - [x] 12.3 Feature test Dashboard render & polling state
    - Skenario: empty state, polling aktif saat `is_speaking_processed=false`, polling mati saat sudah processed
    - _Requirements: 13.1, 13.4, 13.7_

- [x] 13. Phase 1 — Final Checkpoint MVP Core
  - Ensure all tests pass, ask the user if questions arise.
  - Verifikasi alur end-to-end: register → login → submit diagnostic → job berjalan → dashboard menampilkan radar + feedback.
  - _Requirements: 33.2_

- [x] 14. Phase 2 — Learning Roadmap & Module Detail
  - [x] 14.1 Perluas `ModuleSeeder` untuk delapan path (basic/intermediate/advanced/professional)
    - Setiap path key memiliki minimal satu module yang sesuai (mis. `pronunciation`, `basic_tenses`, `vocab_daily`, dst.)
    - Pastikan `tag` (basic/advanced) konsisten dengan `Question` untuk join exercise (Req 15.1)
    - _Requirements: 10.1, 10.2, 10.7, 11.1, 11.2_
  - [x] 14.2 Implement Livewire `App\Livewire\Student\LearningPath`
    - Query `UserModuleProgress::with('module')->whereHas('module', fn($q)=>$q->where('is_active',true))->get()` dan urutkan via `module.order_index`
    - Map status `Completed` / `In Progress` / `Locked` sesuai aturan Req 14.1–14.4
    - _Requirements: 14.1, 14.2, 14.3, 14.4_
  - [x] 14.3 Implement Livewire `App\Livewire\Student\ModuleDetail`
    - Guard akses: tampilkan 403/redirect jika `is_unlocked=false`
    - Tampilkan deskripsi, konten (rendered dari JSON), tombol "Mulai Latihan"
    - _Requirements: 14.4, 14.5_
  - [x] 14.4 Feature test mapping status roadmap
    - Verifikasi: locked tidak punya link aktif, completed menampilkan score, in-progress muncul saat unlocked tapi belum completed
    - _Requirements: 14.1, 14.2, 14.3, 14.4_

- [x] 15. Phase 2 — Exercise/Quiz per Modul
  - [x] 15.1 Implement Livewire `App\Livewire\Student\Exercise`
    - Load `Question::active()->where('tag', $module->tag)->inRandomOrder()->limit(N)->get()`
    - Render question + 4 opsi + radio jawaban
    - _Requirements: 15.1, 15.2_
  - [x] 15.2 Implement scoring submission & update progress
    - Hitung `score = round(correct / total * 100)`
    - Jika `score ≥ 70` → `UserModuleProgress::updateOrCreate(['user_id','module_id'], ['is_completed'=>true,'score'=>$score,'completed_at'=>now()])`
    - Jika `< 70` → simpan `score` saja, izinkan retry
    - _Requirements: 15.3, 15.4, 15.5_
  - [x] 15.3 Feature test scoring threshold & retry behaviour
    - _Requirements: 15.3, 15.4, 15.5_

- [x] 16. Phase 2 — ReEvaluationService
  - [x] 16.1 Implement `ReEvaluationService`
    - `canTakeTestToday(int $userId): bool` — cek DiagnosticResult hari ini (timezone aplikasi)
    - `shouldReEvaluateByProgress(int $userId): bool` — total=0 → false; else `(completed/total) >= 0.7`
    - _Requirements: 16.1, 16.3, 16.4_
  - [x] 16.2 Wire saran "Kamu siap untuk tes ulang!" ke Dashboard
    - Tampilkan banner ketika `shouldReEvaluateByProgress && canTakeTestToday`
    - _Requirements: 16.2_
  - [x] 16.3 Property test `shouldReEvaluateByProgress`
    - **Property 9: ReEvaluationService.shouldReEvaluateByProgress — threshold rule and zero-total guard**
    - **Validates: Requirements 16.1, 16.3**

- [x] 17. Phase 2 — Checkpoint Learning System
  - Ensure all tests pass, ask the user if questions arise.
  - _Requirements: 33.3_

- [x] 18. Phase 3 — Filament Resources Inti (User, Module, DiagnosticResult)
  - [x] 18.1 `App\Filament\Resources\UserResource` (admin only)
    - Form: name, email, nim (nullable unique), password (hash on save), role select (admin/dosen/mahasiswa)
    - `mutateFormDataBeforeCreate` & `mutateFormDataBeforeUpdate` → `$user->syncRoles([$role])`
    - `canViewAny`/`canCreate`/`canEdit`/`canDelete` → `auth()->user()->hasRole('admin')`
    - _Requirements: 18.1, 18.2, 18.3, 18.4_
  - [x] 18.2 `App\Filament\Resources\ModuleResource` (admin + dosen)
    - Form: title, description, path_key (Select dari `array_keys(config('learning_paths'))`), level enum, tag enum, order_index, content (KeyValue/JSON), is_active
    - Validasi `path_key` ∈ delapan key valid
    - _Requirements: 21.1, 21.2, 21.3_
  - [x] 18.3 `App\Filament\Resources\DiagnosticResultResource` (admin full, dosen read-only)
    - Tabel kolom: `user.name`, `speaking`, `grammar`, `vocabulary`, `overall`, `attempt`, `created_at`
    - Filter: relationship `user_id` + date range `from`/`until`
    - `canCreate`/`canEdit`/`canDelete` return `auth()->user()->hasRole('admin')`
    - _Requirements: 22.1, 22.2, 22.3, 22.4_
  - [x] 18.4 Feature test access control Filament resources
    - Skenario: dosen tidak bisa CRUD User; dosen tidak bisa create/edit/delete DiagnosticResult; admin full
    - _Requirements: 2.5, 18.1, 21.1, 22.1, 22.4_

- [x] 19. Phase 3 — QuestionResource + CSV/Excel Import & Export
  - [x] 19.1 `App\Filament\Resources\QuestionResource` (admin + dosen)
    - Form: question, option_a–d, correct_answer (a/b/c/d), type (grammar/vocabulary), tag (basic/intermediate/advanced), difficulty (1..5), is_active
    - Tabel: question (limit 60, searchable), type, tag, is_active, created_at
    - Filter: type, tag, is_active
    - _Requirements: 19.1, 19.2, 19.3, 19.4_
  - [x] 19.2 Implement `App\Services\Csv\CsvQuestionExporter` (pretty-printer)
    - Header: `question, option_a, option_b, option_c, option_d, correct_answer, type, tag, difficulty`
    - UTF-8, delimiter `,`, enclosure `"`, escape `\`
    - Method `toCsvString(iterable $questions): string` deterministik, `toFile($questions, $path): void`
    - _Requirements: 20.5, 34.4_
  - [x] 19.3 Implement `App\Services\Csv\CsvQuestionImporter`
    - `parse(string $csvContent): array` → `['valid' => Question[], 'errors' => [{row,reasons}]]`
    - Validasi tiap baris: 9 kolom, enum correct_answer/type/tag, difficulty 1..5, field non-empty
    - `importFromUpload(UploadedFile $file): array` — buat Question untuk baris valid, lanjutkan baris error
    - _Requirements: 20.2, 20.3, 20.4_
  - [x] 19.4 Filament Header Action `Import` (CSV/XLSX via `maatwebsite/excel`)
    - Upload `.csv` atau `.xlsx`; `.xlsx` → konversi ke array of rows lalu pakai parser CSV internal
    - Tampilkan notifikasi: jumlah berhasil + daftar baris gagal
    - Akses untuk admin & dosen
    - _Requirements: 20.1, 20.2, 20.4_
  - [x] 19.5 Filament Bulk Action `Export CSV`
    - Panggil `CsvQuestionExporter::toCsvString` untuk records terpilih, stream sebagai download
    - _Requirements: 20.5_
  - [x] 19.6 Property test CSV round-trip (Importer + Exporter)
    - **Property 10: CSV Question Importer/Exporter round-trip**
    - **Validates: Requirements 20.6, 34.4**
  - [x] 19.7 Feature test partial import success (campuran baris valid & invalid)
    - Verifikasi: baris valid tersimpan, baris invalid dilaporkan beserta alasan, tidak ada rollback
    - _Requirements: 20.3, 20.4_

- [x] 20. Phase 3 — Lecturer Reports (Laporan Berkala)
  - [x] 20.1 Implement Livewire `App\Livewire\Lecturer\Reports`
    - List DiagnosticResult dengan filter mahasiswa & rentang tanggal
    - Hanya akses untuk role `dosen`
    - _Requirements: 22.1, 22.2, 22.3_
  - [x] 20.2 Feature test akses laporan dosen
    - Dosen bisa lihat; mahasiswa/admin tanpa role dosen mendapat 403/redirect
    - _Requirements: 2.4, 22.1_

- [x] 21. Phase 3 — Checkpoint Admin & Laporan
  - Ensure all tests pass, ask the user if questions arise.
  - _Requirements: 33.4_

- [x] 22. Phase 4 — Polish, Testing, Security Review
  - [x] 22.1 UI polish: loading state, error handling, empty state pada Diagnostic & Dashboard
    - Loading spinner saat upload audio, error toast saat submit gagal, copy empty state Dashboard
    - _Requirements: 13.4, 13.7, 32.5_
  - [x] 22.2 Konfigurasi coverage threshold di `phpunit.xml` / Pest config
    - Minimum 70% lintas service inti (`AdaptiveEngineService`, `ScoringService`, `FeedbackService`)
    - Tambah skrip CI lokal `composer test:coverage`
    - _Requirements: 34.5_
  - [x] 22.3 Feature test endpoint utama (auth, daily limit, diagnostic submit, dashboard)
    - Konsolidasi test happy path + edge case ke satu suite stabil
    - _Requirements: 1.1, 1.3, 3.1, 4.1, 4.5, 13.1, 34.3_
  - [x] 22.4 Skrip performance test 100+ user concurrent (k6 atau JMeter)
    - Skenario: submit diagnostic + polling dashboard
    - Simpan skrip di `tests/Performance/` + dokumentasi cara menjalankan
    - _Requirements: 25.1, 25.5_
  - [x] 22.5 Security review checklist (validasi input, API key, role policy, audio storage)
    - Buat `docs/security-review.md` dengan daftar verifikasi: env tidak di-commit, `config('openai.api_key')` (bukan `env()` runtime), audio di disk privat, role policy di setiap resource, FormRequest pada semua endpoint
    - _Requirements: 8 (audit), 17, 23.1, 24.1, 24.2, 24.3, 24.4, 28.1, 28.4_

- [x] 23. Phase 4 — Production Readiness & Deployment Artefak
  - [x] 23.1 Buat `.env.production.example` dan dokumentasi env production
    - `APP_ENV=production`, `APP_DEBUG=false`, `QUEUE_CONNECTION=redis`, `CACHE_DRIVER=redis`, OPENAI_API_KEY placeholder, alert OpenAI $15/$20
    - _Requirements: 24.1, 24.3, 25.4, 32.3_
  - [x] 23.2 Konfigurasi Supervisor (2 worker queue) sebagai file template `deploy/supervisor/apels-worker.conf`
    - Command `php artisan queue:work --sleep=3 --tries=3` dengan 2 proses
    - _Requirements: 31.2, 31.3_
  - [x] 23.3 Skrip deploy: `php artisan optimize` + `npm run build`
    - Buat `deploy/deploy.sh` (cache config/route/view, migrate, build asset)
    - _Requirements: 32.1, 32.2_
  - [x] 23.4 Dokumentasi setup SSL Let's Encrypt + Nginx
    - File `docs/deploy-vps.md` dengan langkah `certbot --nginx`, server block, redirect 80→443
    - _Requirements: 32.4_
  - [x] 23.5 Konfigurasi Crontab Laravel Scheduler
    - Entri `* * * * * cd /var/www/apels && php artisan schedule:run >> /dev/null 2>&1`
    - Dokumentasikan di `docs/deploy-vps.md`
    - _Requirements: 31.2_

- [x] 24. Phase 4 — Final Checkpoint & Handoff
  - Ensure all tests pass, ask the user if questions arise.
  - Verifikasi: seluruh property test + feature test hijau, coverage ≥ 70% pada service inti, dokumen deploy lengkap.
  - _Requirements: 33.5, 34.5_

## Notes

- Tasks marked with `*` adalah opsional (test/PBT) dan dapat dilewati untuk MVP cepat, namun **Property 3, 5, 6** pada Task 7 sangat dianjurkan dijalankan sebelum Phase 2 untuk memastikan stabilitas Adaptive Engine (Req 33.6).
- Setiap task mereferensikan klausul Requirement spesifik (mis. 9.1, 26.2) untuk traceability.
- Checkpoint memberi titik henti untuk verifikasi manual oleh user sebelum lanjut.
- Property test memvalidasi sepuluh Correctness Properties pada `design.md`; example test melengkapi cakupan rule eksplisit dan UI flow.
- Phase 0 (Task 1) wajib dijalankan urut karena banyak step bergantung pada `composer.json`/`config/*` yang dihasilkan di task sebelumnya.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2"] },
    { "id": 2, "tasks": ["1.3"] },
    { "id": 3, "tasks": ["1.4", "1.5", "1.6", "1.7", "1.10"] },
    { "id": 4, "tasks": ["1.8", "1.9"] },
    { "id": 5, "tasks": ["2.1", "2.2", "2.3", "2.4", "2.5", "2.6", "2.7"] },
    { "id": 6, "tasks": ["2.8", "2.9", "2.10", "2.11", "2.12", "2.13", "2.14", "4.2"] },
    { "id": 7, "tasks": ["2.15", "2.16", "5.1", "6.1", "7.1"] },
    { "id": 8, "tasks": ["3.1", "3.2", "3.3", "3.5", "4.1", "5.2", "5.3", "5.4", "6.2", "6.3", "7.2"] },
    { "id": 9, "tasks": ["3.4", "7.3", "9.1", "9.4"] },
    { "id": 10, "tasks": ["3.6", "7.4", "7.5", "7.6", "7.7", "7.8", "9.2", "9.3", "9.5", "4.3"] },
    { "id": 11, "tasks": ["10.1", "10.2"] },
    { "id": 12, "tasks": ["10.3", "10.4", "11.1"] },
    { "id": 13, "tasks": ["11.2", "11.3"] },
    { "id": 14, "tasks": ["11.4", "12.1"] },
    { "id": 15, "tasks": ["11.5", "11.6", "12.2"] },
    { "id": 16, "tasks": ["12.3", "14.1"] },
    { "id": 17, "tasks": ["14.2", "14.3", "15.1", "16.1"] },
    { "id": 18, "tasks": ["14.4", "15.2", "16.2", "16.3"] },
    { "id": 19, "tasks": ["15.3", "18.1", "18.2", "18.3", "19.1", "20.1"] },
    { "id": 20, "tasks": ["18.4", "19.2", "19.3", "20.2"] },
    { "id": 21, "tasks": ["19.4", "19.5"] },
    { "id": 22, "tasks": ["19.6", "19.7", "22.1", "22.2", "22.5", "23.1", "23.2", "23.3", "23.4", "23.5"] },
    { "id": 23, "tasks": ["22.3", "22.4"] }
  ]
}
```
