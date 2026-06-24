# Halaman 1

To: Developer 
Saya ingin menyampaikan bahwa sistem yang akan kita bangun, yaitu APELS (Adaptive Professional 
English Learning System), bukan sekadar Learning Management System biasa. Fokus utama dari sistem 
ini bukan pada banyaknya fitur atau tampilan yang kompleks, tetapi pada bagaimana sistem ini mampu 
“berpikir” dan mengambil keputusan pembelajaran secara otomatis. 
Konsep utama yang saya bawa adalah “Learning Brain”. Artinya, sistem ini harus mampu menentukan 
secara mandiri materi apa yang dibutuhkan oleh setiap mahasiswa berdasarkan hasil tes mereka. Jadi, 
yang kita bangun bukan hanya platform belajar, tetapi sebuah mekanisme adaptif yang 
mempersonalisasi jalur belajar setiap pengguna. 
Dalam pengembangannya, kita tidak perlu membuat teknologi kecerdasan buatan dari nol. Kita akan 
menggunakan pendekatan yang efisien melalui integrasi API. Untuk kebutuhan seperti analisis suara 
dan teks, kita bisa memanfaatkan layanan seperti speech-to-text dan text analysis yang sudah tersedia. 
Peran sistem kita adalah mengolah hasil dari API tersebut menjadi keputusan pembelajaran yang 
tepat. 
Struktur sistem ini terdiri dari empat bagian utama. Pertama, bagian diagnosis, di mana sistem 
membaca kemampuan mahasiswa melalui tes speaking, grammar, dan vocabulary. Kedua, bagian inti 
yaitu adaptive engine, di mana sistem menggunakan logika berbasis aturan sederhana seperti if–then 
untuk menentukan jalur belajar. Ketiga, bagian konten profesional yang berisi simulasi dunia kerja, 
khususnya dalam konteks akuntansi seperti presentasi laporan keuangan atau komunikasi dengan 
klien. Keempat, bagian feedback cerdas yang memberikan respon otomatis kepada mahasiswa seolah-
olah mereka dibimbing oleh tutor. 
Hal yang paling penting dalam sistem ini adalah mekanisme pengambilan keputusan, bukan tampilan 
visual. Oleh karena itu, saya ingin tim developer memprioritaskan pengembangan logika sistem, alur 
data, dan bagaimana sistem menentukan jalur belajar yang berbeda untuk setiap mahasiswa. 
Untuk tahap awal atau MVP, kita cukup fokus pada tiga hal utama: sistem diagnosis, rule-based engine, 
dan dashboard sederhana yang menampilkan hasil dalam bentuk visual seperti radar chart serta 
feedback otomatis. Fitur lain seperti roadmap yang lebih kompleks atau integrasi lanjutan bisa kita 
kembangkan di tahap berikutnya. 
Selain itu, saya juga membutuhkan panel admin yang sederhana dan user-friendly, agar tim kami bisa 
menginput soal, materi, dan menentukan tag pembelajaran tanpa bergantung pada developer. Ini 
penting untuk keberlanjutan sistem ke depan. 
Sebagai penutup, saya ingin menegaskan bahwa nilai utama dari sistem ini ada pada kemampuan 
adaptifnya. Jadi, yang kita bangun adalah “otak sistem”, bukan sekadar “tubuh sistem”. Selama 
mekanisme adaptif ini berjalan dengan baik, maka sistem ini sudah memenuhi tujuan penelitian yang 
saya kembangkan.


# Halaman 2

Tim Developer, 
Saya akan langsung masuk ke sisi teknis agar implementasi sistem APELS ini jelas sejak awal. Fokus 
utama kita adalah membangun adaptive decision system, bukan sekadar platform konten. 
1. Core System Flow (End-to-End) 
Alur utama sistem harus berjalan seperti ini: 
1. User login 
2. User mengerjakan diagnostic test (speaking, grammar, vocabulary) 
3. Input dikirim ke backend 
4. Backend memanggil API eksternal (speech-to-text / text analysis) 
5. API mengembalikan skor 
6. Backend menjalankan rule-based engine 
7. Sistem menentukan learning path 
8. Hasil ditampilkan di dashboard: 
o 
Radar chart 
o 
Feedback otomatis


# Halaman 3

o 
Roadmap belajar 
Flow ini wajib stabil sebelum fitur lain dikembangkan. 
 
2. Backend Architecture (WAJIB JELAS) 
Gunakan pendekatan modular: 
A. Service Layer 
Pisahkan logic ke dalam service: 
• 
DiagnosticService 
• 
ScoringService 
• 
AdaptiveEngineService 
• 
FeedbackService 
 
B. Endpoint Structure 
Minimal endpoint: 
POST 
/api/diagnostic/submit 
GET 
/api/dashboard 
GET 
/api/learning-path 
GET /api/feedback 
 
3. Diagnostic Engine (Input Processing) 
A. Speaking Input 
Flow: 
• 
Audio dikirim dari frontend 
• 
Backend kirim ke API (Whisper / Google STT) 
• 
Response: transcript + confidence score 
Contoh response: 
{ 
  "transcript": "The financial report shows...", 
  "confidence": 0.87 
} 
 
B. Text Input (Grammar & Vocabulary)


# Halaman 4

Kirim ke API NLP → hasil: 
{ 
  "grammar_score": 55, 
  "vocab_score": 68 
} 
 
C. Final Scoring Object 
Gabungkan semua: 
{ 
  "user_id": 1, 
  "speaking": 65, 
  "grammar": 55, 
  "vocabulary": 68 
} 
Simpan ke database: diagnostic_results 
 
4. Adaptive Engine (CORE LOGIC – PRIORITAS UTAMA) 
Gunakan rule-based system (hardcoded dulu untuk MVP). 
A. Basic Rule Example 
function generateLearningPath(score) { 
  if (score.speaking < 60) { 
    return "basic_speaking"; 
  } 
 
  if (score.grammar < 60) { 
    return "grammar_foundation"; 
  } 
 
  if (score.speaking > 70 && score.grammar > 70) { 
    return "professional_simulation"; 
  }


# Halaman 5

return "intermediate_path"; 
} 
 
B. Multi-Condition Rule 
if (speaking < 60 && vocabulary < 60) { 
  path = "fundamental_communication"; 
} 
 
C. Output Structure 
{ 
  "learning_path": "grammar_foundation", 
  "modules_unlocked": [ 
    "basic_tenses", 
    "sentence_structure" 
  ], 
  "modules_locked": [ 
    "client_meeting", 
    "financial_presentation" 
  ] 
} 
Simpan ke: learning_paths 
 
5. Feedback Engine (Template-Based) 
Gunakan template dinamis. 
A. Logic 
function generateFeedback(score) { 
  let message = ""; 
 
  if (score.speaking < 60) { 
    message += "Your speaking needs improvement. ";


# Halaman 6

} 
 
  if (score.vocabulary > 65) { 
    message += "Your vocabulary is improving. "; 
  } 
 
  message += "Next focus: Accounting Communication."; 
 
  return message; 
} 
 
B. Output 
{ 
  "feedback": "Your speaking needs improvement. Your vocabulary is improving. Next focus: 
Accounting Communication." 
} 
 
6. Database Design (MINIMAL MVP) 
Tables: 
users 
• 
id 
• 
name 
• 
email 
diagnostic_results 
• 
id 
• 
user_id 
• 
speaking 
• 
grammar 
• 
vocabulary 
• 
created_at 
learning_paths


# Halaman 7

• 
id 
• 
user_id 
• 
path_name 
modules 
• 
id 
• 
title 
• 
level 
• 
tag 
questions 
• 
id 
• 
question 
• 
option_a–d 
• 
correct_answer 
• 
tag 
 
7. Frontend Integration 
A. Dashboard API Consumption 
Frontend harus memanggil: 
GET /api/dashboard 
Response: 
{ 
  "skills": { 
    "speaking": 65, 
    "grammar": 55, 
    "vocabulary": 68, 
    "writing": 60, 
    "business_vocab": 62 
  }, 
  "learning_path": "grammar_foundation", 
  "feedback": "Your speaking needs improvement..." 
}


# Halaman 8

B. Radar Chart Mapping 
Mapping ke Chart.js / Recharts: 
[ 
  { subject: "Speaking", value: 65 }, 
  { subject: "Grammar", value: 55 }, 
  { subject: "Vocabulary", value: 68 } 
] 
 
8. Admin Panel (Dynamic Content Control) 
A. Input Soal 
Form → POST /api/questions 
Field: 
• 
question 
• 
options 
• 
correct_answer 
• 
tag 
 
B. Tag-Based Logic 
Contoh: 
• 
tag = “basic” → muncul di path dasar 
• 
tag = “advanced” → muncul jika score tinggi 
 
9. Development Priority (JANGAN DILANGGAR) 
Phase 1 (WAJIB SELESAI) 
• 
Diagnostic input 
• 
API integration 
• 
Rule-based engine 
• 
Dashboard basic 
Phase 2 
• 
Learning roadmap


# Halaman 9

• 
Module system 
Phase 3 
• 
Speech scoring refinement 
• 
Lecturer dashboard 
 
10. Critical Reminder 
Jangan terlalu fokus ke: 
• 
UI detail 
• 
Animasi 
• 
fitur tambahan 
Fokus utama: 
• 
logic decision 
• 
data processing 
• 
adaptive routing 
Kalau adaptive engine sudah berjalan, maka sistem ini sudah “hidup”. 
Itu adalah inti dari sistem APELS yang harus kita bangun. 
 
PENJELASAN ALUR  
1. Input Layer (User Interaction) 
Mahasiswa memulai dengan: 
• 
login sistem  
• 
mengerjakan tes diagnosis  
Data yang dikumpulkan: 
• 
audio (speaking)  
• 
teks (grammar & vocabulary)  
  Ini menjadi input utama “Learning Brain” 
 
2. Processing Layer (API-Assisted Intelligence) 
Sistem tidak memproses secara manual, tetapi: 
• 
mengirim data ke API eksternal  
• 
menerima hasil analisis otomatis


# Halaman 10

Output: 
• 
skor speaking  
• 
skor grammar  
• 
skor vocabulary  
  Ini memastikan sistem: 
• 
efisien  
• 
scalable  
• 
low-cost  
 
3. Adaptive Engine (Core Innovation) 
Ini adalah inti penelitian. 
Sistem menggunakan: 
• 
rule-based decision system (if–then)  
Contoh: 
• 
skor rendah → jalur dasar  
• 
skor tinggi → simulasi profesional  
  Di sinilah terjadi: 
personalisasi jalur belajar 
 
4. Learning Path Generation 
Hasil keputusan sistem: 
• 
menentukan rute belajar unik tiap mahasiswa  
• 
membuka (unlock) modul tertentu  
• 
mengunci (lock) modul lain  
  Ini menghasilkan: 
• 
dynamic learning roadmap  
 
5. Feedback Generation (AI-Assisted Response) 
Sistem menghasilkan: 
• 
analisis performa  
• 
rekomendasi belajar


# Halaman 11

• 
target berikutnya  
  Disajikan dalam bentuk: 
“seolah-olah ada tutor pribadi” 
 
6. Output Visualization (Dashboard) 
Mahasiswa melihat: 
• 
Radar Chart (profil skill)  
• 
Timeline pembelajaran adaptif  
• 
Feedback otomatis  
  Ini memperkuat: 
• 
learning awareness  
• 
self-regulated learning  
 
7. Iterative Learning Loop 
Setelah belajar: 
• 
mahasiswa mengulang tes  
• 
sistem mengevaluasi ulang  
• 
jalur belajar diperbarui  
  Sistem menjadi: 
continuous adaptive system 
 
3. Mekanisme Kerja Sistem 
Secara operasional, sistem APELS bekerja melalui tahapan berikut: 
1. Mahasiswa melakukan login ke sistem  
2. Mahasiswa mengerjakan tes diagnosis  
3. Sistem memproses input melalui API  
4. Sistem menghasilkan skor kemampuan  
5. Adaptive engine menentukan jalur belajar  
6. Sistem membuka dan mengunci modul sesuai kebutuhan  
7. Sistem memberikan feedback otomatis  
8. Mahasiswa melanjutkan pembelajaran sesuai roadmap


# Halaman 12

9. Sistem melakukan evaluasi ulang secara berkala  
Proses ini berlangsung secara berulang sehingga membentuk siklus pembelajaran adaptif (adaptive 
learning loop). 
 
4. Komponen Utama Sistem 
Untuk mendukung mekanisme tersebut, APELS memiliki beberapa komponen inti: 
1. User Management 
Mengelola data pengguna (mahasiswa, dosen, admin). 
2. Diagnostic Module 
Melakukan pengukuran kemampuan awal dan berkala. 
3. Adaptive Engine 
Menjadi pusat pengambilan keputusan jalur belajar. 
4. Learning Path Manager 
Mengatur distribusi modul dan progres pembelajaran. 
5. Content Management System 
Memungkinkan admin/dosen mengelola soal dan materi. 
6. Feedback Engine 
Menghasilkan umpan balik otomatis berbasis data. 
7. Analytics Dashboard 
Menyajikan data performa mahasiswa secara visual.
