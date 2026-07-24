# APELS — Spesifikasi Eksekusi Diagnostic Test

> **Untuk:** AI Coding Agent (Antigravity)
> **Stack:** Laravel + Filament (admin) + Blade/Livewire (student)
> **Status:** 60 soal sudah ter-import ke tabel `questions`.
> **Tanggal:** 13 Juli 2026

---

## 0. ATURAN MUTLAK — BACA SEBELUM NGODING

1. **JANGAN mengubah teks soal, opsi, atau kunci jawaban.** Soal berasal dari dosen dan bersifat final.
2. **JANGAN membangun API NLP untuk Grammar & Vocabulary.** Kedua tahap itu pilihan ganda → skoring deterministik. API eksternal **hanya** untuk Speaking (STT).
3. **JANGAN membuat tab/halaman terpisah per level.** Level (`tag`) adalah **bobot skor**, bukan navigasi.
4. **JANGAN memasukkan soal ke tabel `modules`.** Soal = tabel `questions`. Modul = materi pembelajaran.
5. Kerjakan berurutan sesuai nomor bagian. Jangan lompat.

---

## 1. KONDISI DATA SAAT INI

Tabel `questions` sudah berisi **60 soal** dari 3 SET.

| Kolom | Tipe | Nilai |
|---|---|---|
| `question` | text | Teks soal |
| `option_a` – `option_d` | string | Opsi jawaban |
| `correct_answer` | enum | `A` / `B` / `C` / `D` |
| `type` | enum | `Grammar` / `Vocabulary` |
| `tag` | enum | `Basic` / `Intermediate` / `Advanced` |
| `difficulty` | integer | `1` (Basic) / `2` (Intermediate) / `3` (Advanced) |
| `is_active` | boolean | `true` |

### Komposisi per SET (identik untuk SET-A, SET-B, SET-C)

| Level | Grammar | Vocabulary | Subtotal | Bobot |
|---|---|---|---|---|
| Basic | 3 | 4 | 7 | 1 |
| Intermediate | 3 | 4 | 7 | 2 |
| Advanced | 3 | 3 | 6 | 3 |
| **TOTAL** | **9** | **11** | **20** | — |

**Total bobot Grammar** = (3×1) + (3×2) + (3×3) = **18**
**Total bobot Vocabulary** = (4×1) + (4×2) + (3×3) = **21**

### ⚠️ Catatan integritas data (diketahui, diterima apa adanya)

- Distribusi kunci jawaban timpang: **B = 46, C = 12, A = 2, D = 0** dari 60 soal.
- **Mitigasi WAJIB (Bagian 6):** acak **posisi tampil** opsi di frontend. Isi soal tidak diubah, hanya urutan render.

---

## 2. MIGRATION — Tambah kolom `set`

```php
// database/migrations/xxxx_add_set_to_questions_table.php
Schema::table('questions', function (Blueprint $table) {
    $table->enum('set', ['A', 'B', 'C'])->after('id')->index();
});
```

Tambahkan juga ke Filament `QuestionResource`:

```php
Forms\Components\Select::make('set')
    ->options(['A' => 'Set A', 'B' => 'Set B', 'C' => 'Set C'])
    ->required();
```

**Setelah migration: hapus 60 record lama, import ulang dengan kolom `set` terisi.**
File CSV: `ALL-question-importer.csv` (tambahkan kolom `set` berisi A/B/C).

---

## 3. MIGRATION — Tabel `diagnostic_results`

```php
Schema::create('diagnostic_results', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('set_used', ['A', 'B', 'C']);
    $table->unsignedInteger('attempt_number');
    $table->unsignedTinyInteger('grammar');      // 0-100
    $table->unsignedTinyInteger('vocabulary');   // 0-100
    $table->unsignedTinyInteger('speaking');     // 0-100
    $table->json('answers')->nullable();         // jawaban mentah mahasiswa
    $table->timestamps();
});
```

```php
Schema::create('learning_paths', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('path_name');
    $table->json('modules_unlocked')->nullable();
    $table->json('modules_locked')->nullable();
    $table->timestamps();
});
```

---

## 4. ROTASI SET — `DiagnosticService`

```php
class DiagnosticService
{
    private const SETS = ['A', 'B', 'C'];

    public function resolveActiveSet(int $userId): string
    {
        $attempt = DiagnosticResult::where('user_id', $userId)->count();
        return self::SETS[$attempt % count(self::SETS)];
    }

    public function nextAttemptNumber(int $userId): int
    {
        return DiagnosticResult::where('user_id', $userId)->count() + 1;
    }
}
```

**Perilaku:** Tes ke-1 → SET A · ke-2 → SET B · ke-3 → SET C · ke-4 → SET A (rotasi ulang, tanpa batas).

---

## 5. ALUR DIAGNOSTIC TEST (3 TAHAP)

```
Mahasiswa klik "Mulai Diagnostic Test"
        ↓
Sistem tentukan $setAktif via resolveActiveSet()
        ↓
TAHAP 1 — GRAMMAR   : 9 soal   (urut basic → intermediate → advanced)
        ↓ (submit, tidak bisa mundur)
TAHAP 2 — VOCABULARY: 11 soal  (urut basic → intermediate → advanced)
        ↓ (submit)
TAHAP 3 — SPEAKING  : rekam audio perkenalan diri (tanpa soal pilihan ganda)
        ↓ (submit)
Backend: hitung skor → adaptive engine → simpan → redirect ke Dashboard
```

### Query pengambilan soal

```php
Question::where('set', $setAktif)
    ->where('type', $tipe)          // 'Grammar' atau 'Vocabulary'
    ->where('is_active', true)
    ->orderBy('difficulty')         // basic → intermediate → advanced
    ->get();
```

**Level TIDAK ditampilkan sebagai tab.** Soal tampil sebagai satu daftar berurutan.

---

## 6. RANDOMISASI POSISI OPSI (WAJIB)

Isi soal tidak diubah. Hanya **urutan render** yang diacak, agar bias kunci "B" (77%) tidak dieksploitasi.

```php
// Saat menyiapkan soal untuk ditampilkan
$options = collect([
    'A' => $q->option_a,
    'B' => $q->option_b,
    'C' => $q->option_c,
    'D' => $q->option_d,
])->shuffle(); // urutan tampil acak, key asli (A/B/C/D) tetap ikut

// Frontend mengirim balik KEY ASLI, bukan posisi tampil.
// Validasi tetap: $jawaban === $q->correct_answer
```

Seed shuffle dengan `$userId . $attemptNumber` agar konsisten jika halaman di-refresh.

---

## 7. SCORING — `ScoringService`

Skor berbobot berdasarkan `difficulty`.

```php
public function scoreSection(Collection $questions, array $answers): int
{
    $earned = 0;
    $total  = $questions->sum('difficulty');

    foreach ($questions as $q) {
        if (($answers[$q->id] ?? null) === $q->correct_answer) {
            $earned += $q->difficulty;
        }
    }

    return $total > 0 ? (int) round(($earned / $total) * 100) : 0;
}
```

| Section | Total bobot |
|---|---|
| Grammar | 18 |
| Vocabulary | 21 |

**Speaking:** skor dari confidence score API STT × 100, dibulatkan ke integer 0–100.

---

## 8. ADAPTIVE ENGINE — `AdaptiveEngineService`

Rule-based, hardcoded (sesuai PDF §4, Phase 1). **Urutan `if` penting — evaluasi dari atas.**

```php
public function generateLearningPath(array $score): array
{
    if ($score['speaking'] < 60 && $score['vocabulary'] < 60) {
        $path = 'fundamental_communication';
    } elseif ($score['speaking'] < 60) {
        $path = 'basic_speaking';
    } elseif ($score['grammar'] < 60) {
        $path = 'grammar_foundation';
    } elseif ($score['speaking'] > 70 && $score['grammar'] > 70) {
        $path = 'professional_simulation';
    } else {
        $path = 'intermediate_path';
    }

    return [
        'learning_path'    => $path,
        'modules_unlocked' => $this->unlockedFor($path),
        'modules_locked'   => $this->lockedFor($path),
    ];
}
```

Simpan hasil ke tabel `learning_paths`.

---

## 9. FEEDBACK ENGINE — `FeedbackService`

Template-based, **bukan** disimpan per-soal.

```php
public function generate(array $score): string
{
    $msg = '';
    if ($score['speaking'] < 60)   $msg .= 'Your speaking needs improvement. ';
    if ($score['grammar'] < 60)    $msg .= 'Focus on sentence structure and tenses. ';
    if ($score['vocabulary'] > 65) $msg .= 'Your vocabulary is improving. ';
    $msg .= 'Next focus: Accounting Communication.';
    return $msg;
}
```

---

## 10. ENDPOINTS

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/diagnostic/start` | Kembalikan `set_used` + soal Grammar |
| `POST` | `/api/diagnostic/submit` | Terima semua jawaban → skor → adaptive → simpan |
| `GET` | `/api/dashboard` | Skor (radar chart), learning path, feedback |
| `GET` | `/api/learning-path` | Modul unlocked / locked |

### Response `/api/dashboard`

```json
{
  "attempt_number": 1,
  "set_used": "A",
  "skills": { "grammar": 55, "vocabulary": 68, "speaking": 65 },
  "learning_path": "grammar_foundation",
  "feedback": "Focus on sentence structure and tenses. Your vocabulary is improving. Next focus: Accounting Communication."
}
```

---

## 11. PERBAIKAN UI (WAJIB)

| # | Lokasi | Masalah | Perbaikan |
|---|---|---|---|
| 1 | Sidebar | Menu **Exercises** membuka Diagnostic Test | Buat menu **Diagnostic Test** terpisah. `Exercises` = latihan di modul yang sudah unlock. |
| 2 | Step 1 Grammar | Teks *"Tulis setidaknya 3 kalimat... AI akan mengevaluasi"* | **HAPUS.** Kontradiksi dengan UI pilihan ganda. Ganti: *"Pilih jawaban yang paling tepat untuk setiap soal."* |
| 3 | Step 2 Vocabulary | (cek teks serupa) | Ganti dengan instruksi pilihan ganda. |
| 4 | Header test | Tidak menampilkan SET | Tampilkan: *"Diagnostic Test — Attempt #1 (Set A)"* |
| 5 | Step 3 Speaking | — | Sudah benar. Rekam audio, tanpa soal. |

---

## 12. DEFINITION OF DONE

- [ ] Kolom `set` ada di `questions`, 60 soal ter-import ulang dengan `set` terisi (20/20/20)
- [ ] Tabel `diagnostic_results` dan `learning_paths` dibuat
- [ ] Rotasi SET berjalan: tes ke-4 kembali ke SET A tanpa error
- [ ] Diagnostic Test: 9 soal Grammar → 11 soal Vocabulary → Speaking
- [ ] Posisi opsi teracak, validasi tetap membandingkan key asli
- [ ] Skoring berbobot (Grammar /18, Vocabulary /21) menghasilkan 0–100
- [ ] Adaptive engine mengembalikan 1 dari 5 `learning_path`
- [ ] Dashboard menampilkan radar chart + feedback
- [ ] Menu `Diagnostic Test` terpisah dari `Exercises`
- [ ] Instruksi "tulis kalimat" sudah dihapus

---

## 13. JANGAN DIKERJAKAN SEKARANG (Phase 2/3)

- Adaptive branching (skip soal advanced kalau gagal basic)
- Roadmap kompleks
- Lecturer dashboard
- Refinement speech scoring
- Animasi / polish UI

> PDF §10: *"Jangan terlalu fokus ke UI detail, animasi, fitur tambahan. Fokus utama: logic decision, data processing, adaptive routing."*
