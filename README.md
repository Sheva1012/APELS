# 🎓 APELS — Adaptive Personalised E-Learning System (AI-Powered)

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-Volt%20%26%20Flux-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-v4.x-D97706?style=for-the-badge&logo=laravel&logoColor=white)
![AI Engine](https://img.shields.io/badge/AI-Groq%20LLM%20%26%20STT%2FNLP-007ACC?style=for-the-badge&logo=openai&logoColor=white)
![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20SQLite-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

> **APELS (Adaptive Personalised E-Learning System)** adalah Platform Pembelajaran Adaptif & Tes Diagnostik Cerdas berbasis Web. Dibangun menggunakan **Laravel 12**, **Livewire Volt & Flux**, **Filament v4**, serta terintegrasi dengan **Groq AI (Natural Language Processing & Speech-to-Text)** untuk rekomendasi materi belajar adaptif dan evaluasi tes otomatis.

---

## 📌 Tentang Aplikasi

Setiap mahasiswa memiliki kecepatan dan gaya belajar yang berbeda. **APELS** hadir sebagai sistem *EdTech* adaptif yang mempersonalisasi jalur pembelajaran (*learning path*) mahasiswa secara dinamis berdasarkan hasil evaluasi **Tes Diagnostik**.

Menggabungkan arsitektur reaktif **Livewire Volt & Flux** dengan modul manajemen **Filament v4** serta integrasi **Groq AI**, APELS menyajikan ekosistem pembelajaran pintar yang menghubungkan Mahasiswa, Dosen/Pengajar, dan Administrator secara *real-time*.

---

## 🚀 Fitur-Fitur Utama & Ekosistem Multi-Role

### 🤖 1. AI-Assisted Diagnostic Test & Adaptive Learning Path
* **Tes Diagnostik Adaptif**: Pengujian berkala (*dengan pembatasan kuota harian `daily.test.limit`*) untuk mengukur tingkat pemahaman mahasiswa.
* **Integrasi AI Groq Engine (`GroqService.php`)**: Memanfaatkan LLM dan kecerdasan buatan NLP/STT untuk mengevaluasi jawaban dan memberikan saran perbaikan secara kontekstual.
* **Personalized Learning Path**: Mengarahkan mahasiswa ke modul dan materi pelajaran yang paling relevan dengan tingkat penguasaannya secara otomatis.

### 👨‍🎓 2. Portal Mahasiswa (`role:mahasiswa`)
* **Student Dashboard**: Ringkasan progres indikator pembelajaran, akumulasi skor, dan status modul.
* **Modul & Exercise Interaktif**: Pengaksesan materi pelajaran (`lesson-detail`), modul (`module-detail`), serta latihan soal (`exercises`).
* **Test History**: Riwayat dan rekapitulasi grafik hasil evaluasi tes diagnostik.

### 👩‍🏫 3. Portal Dosen & Pengajar (`role:dosen`)
* **Lecturer Reports (`lecturer.reports`)**: Dashboard rekapitulasi progres mahasiswa, pemantauan hasil tes diagnostik per kelas, serta analisis area kelemahan mahasiswa.
* **Ekspor Laporan PDF & Excel**: Cetak laporan performa mahasiswa secara langsung.

### 🛠️ 4. Admin Dashboard SPA Mode (`role:admin` / Filament v4)
* **Manajemen Modul & Pelajaran (`ModuleResource`)**: Pembuatan modul pembelajaran, bab pelajaran, dan prasyarat tingkat.
* **Bank Soal & Diagnostik (`QuestionResource`, `DiagnosticResultResource`)**: Pengelolaan bank soal diagnostik, pilihan jawaban, bobot nilai, dan evaluasi hasil.
* **Manajemen Pengguna (`UserResource`)**: Pengelolaan akun mahasiswa, dosen, dan admin berbasis **Spatie Laravel Permission**.

### 📊 5. Ekspor Data Multi-Engine & High-Performance
* **Fast-Excel & Spreadsheet Export (`rap2hpoutre/fast-excel`, `maatwebsite/excel`, `league/csv`)**: Ekspor data berkecepatan tinggi dengan *styled XLSX templates*.
* **Redesign Laporan PDF (`barryvdh/laravel-dompdf`)**: Cetak laporan hasil diagnostik dan sertifikat pencapaian berformat PDF yang elegan.

---

## 🌐 Arsitektur Ekosistem 3 Role

```text
                               ┌────────────────────────────────┐
                               │     ADMIN PANEL (Filament v4)  │
                               │  - Bank Soal Diagnostik        │
                               │  - Modul & Lesson Management   │
                               └───────────────┬────────────────┘
                                               │
                       ┌───────────────────────┴───────────────────────┐
                       ▼                                               ▼
     👨‍🎓 PORTAL MAHASISWA (Volt/Flux)                  👩‍🏫 PORTAL DOSEN (Lecturer Reports)
     ───────────────────────────────                  ─────────────────────────────────
     • Tes Diagnostik Adaptif (Groq AI)              • Rekapitulasi Progres Mahasiswa
     • Adaptive Learning Path                        • Analisis Hasil Diagnostik Kelas
     • Latihan Soal & Modul Interaktif               • Ekspor Laporan PDF & Excel (Fast-Excel)
```

---

## 🛠️ Stack Teknologi & Dependencies

| Kategori | Pustaka / Teknologi |
| :--- | :--- |
| **Framework Utama** | [Laravel 12.x](https://laravel.com/) |
| **Reactive UI** | Livewire Volt (`livewire/volt: ^1.6`) & Livewire Flux (`livewire/flux: ^2.0`) |
| **Admin Panel** | [Filament PHP v4.x](https://filamentphp.com/) (SPA Mode) |
| **AI Integration** | Groq AI Client (`GroqService.php`) |
| **Otorisasi RBAC** | `spatie/laravel-permission` (Admin, Dosen, Mahasiswa) |
| **Ekspor Excel/CSV** | `rap2hpoutre/fast-excel`, `maatwebsite/excel`, `league/csv` |
| **Ekspor PDF** | `barryvdh/laravel-dompdf` |
| **Testing Engine** | Pest PHP (`pestphp/pest: ^3.8`) |

---

## ⚙️ Panduan Instalasi & Jalankan Project

### 1. Clone Repositori
```bash
git clone https://github.com/AndhikaBagus115/APELS.git
cd APELS
```

### 2. Install Dependency PHP
```bash
composer install
```

### 3. Install & Compile Asset Node.js
```bash
npm install
npm run dev
```

### 4. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Generate Application Key & Filament Upgrade:
```bash
php artisan key:generate
php artisan filament:upgrade
```
Isi API Key Groq AI pada `.env`:
```env
GROQ_API_KEY=your-groq-api-key
```

### 5. Setup Database & Migrasi
```bash
php artisan migrate --seed
```

### 6. Jalankan Server Lokal
```bash
php artisan serve
```
Buka browser Anda dan akses:
* Portal Mahasiswa / Utama: `http://127.0.0.1:8000`
* Filament Admin Panel: `http://127.0.0.1:8000/admin`

---

## 📁 Struktur Direktori Utama

```text
app/
├── Filament/
│   └── Resources/            # Filament Admin Resources (Module, Question, DiagnosticResult, User)
├── Http/
│   ├── Controllers/          # API & Web Controllers
│   └── Middleware/           # Custom Middleware (DailyTestLimit)
├── Services/
│   └── GroqService.php       # Integrasi AI Groq Engine (NLP/STT & Evaluation)
├── Models/                   # Model Data (Module, Lesson, Question, DiagnosticResult, LearningPath, User)
└── Livewire/
    └── Volt/                 # Component Livewire Volt (student.learning-path, student.diagnostic-test, dll)
```

---

## 👨‍💻 Penulis & Lisensi

Dikembangkan oleh **Andhika Bagus Satria** & Tim sebagai Platform Pembelajaran Adaptif APELS.

* **Lisensi**: Open-source di bawah lisensi [MIT License](LICENSE).
