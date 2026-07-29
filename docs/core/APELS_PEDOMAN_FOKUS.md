# APELS — Pedoman Fokus Development

> Adaptive Professional English Learning System
> Dokumen ini menjadi acuan utama agar development tidak melenceng dari inti riset.

## Prinsip Utama

**Yang kita bangun adalah "otak sistem" (Learning Brain), bukan "tubuh sistem".**

Nilai project ini ditentukan oleh kemampuan adaptifnya — bukan oleh banyaknya fitur, kompleksitas tampilan, atau animasi. Selama adaptive engine berjalan dengan baik dan benar-benar mempersonalisasi jalur belajar tiap mahasiswa, sistem ini sudah memenuhi tujuan penelitian.

## Jangan Terlalu Fokus Ke

- UI detail
- Animasi
- Fitur tambahan di luar MVP

## Fokus Utama

- Logic decision (adaptive rule-based engine)
- Data processing (diagnosis → skor → keputusan)
- Adaptive routing (jalur belajar berbeda untuk setiap mahasiswa)

## Core System Flow (End-to-End)

1. User login
2. User mengerjakan diagnostic test (speaking, grammar, vocabulary)
3. Input dikirim ke backend
4. Backend memanggil API eksternal (speech-to-text / text analysis)
5. API mengembalikan skor
6. Backend menjalankan rule-based engine
7. Sistem menentukan learning path
8. Hasil ditampilkan di dashboard (radar chart, feedback otomatis, roadmap)

Flow ini wajib stabil sebelum fitur lain dikembangkan.

## Empat Komponen Inti

1. **Diagnosis** — membaca kemampuan mahasiswa via tes speaking, grammar, vocabulary
2. **Adaptive Engine** (PRIORITAS UTAMA) — rule-based (if–then) untuk menentukan jalur belajar
3. **Konten Profesional** — simulasi dunia kerja akuntansi (presentasi laporan keuangan, komunikasi klien)
4. **Feedback Cerdas** — respons otomatis seolah dibimbing tutor

## Backend Architecture (Modular)

**Service Layer:**
- `DiagnosticService`
- `ScoringService`
- `AdaptiveEngineService`
- `FeedbackService`

**Endpoint minimal:**
```
POST /api/diagnostic/submit
GET  /api/dashboard
GET  /api/learning-path
GET  /api/feedback
```

## Adaptive Engine — Contoh Logic (MVP, hardcoded dulu)

```js
function generateLearningPath(score) {
  if (score.speaking < 60) return "basic_speaking";
  if (score.grammar < 60) return "grammar_foundation";
  if (score.speaking > 70 && score.grammar > 70) return "professional_simulation";
  return "intermediate_path";
}
```

Multi-condition:
```js
if (speaking < 60 && vocabulary < 60) {
  path = "fundamental_communication";
}
```

## Database Minimal (MVP)

- `users` (id, name, email)
- `diagnostic_results` (id, user_id, speaking, grammar, vocabulary, created_at)
- `learning_paths` (id, user_id, path_name)
- `modules` (id, title, level, tag)
- `questions` (id, question, option_a–d, correct_answer, tag)

## Development Priority — JANGAN DILANGGAR

**Phase 1 (WAJIB SELESAI)**
- Diagnostic input
- API integration
- Rule-based engine
- Dashboard basic

**Phase 2**
- Learning roadmap
- Module system

**Phase 3**
- Speech scoring refinement
- Lecturer dashboard

## Checklist Sebelum Lanjut ke Fitur Baru

- [ ] Apakah adaptive engine sudah menghasilkan learning path yang berbeda untuk skor berbeda?
- [ ] Apakah flow diagnosis → skor → path → dashboard sudah end-to-end jalan tanpa fitur tambahan?
- [ ] Apakah fitur baru ini menambah kemampuan *keputusan* sistem, atau hanya menambah *tampilan*?

Jika jawaban poin terakhir adalah "hanya tampilan" → tunda dulu, kembali ke logic.
