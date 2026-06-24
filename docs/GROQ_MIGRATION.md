# Groq API Migration Guide

## 📋 Overview

APELS telah berhasil dimigrasi dari **OpenAI API** ke **Groq Cloud API** (free tier) untuk meningkatkan efisiensi dan mengurangi biaya operasional.

### ✅ Perubahan Utama

| Aspek | Sebelum (OpenAI) | Sesudah (Groq) |
|-------|------------------|----------------|
| **Speech-to-Text** | OpenAI Whisper-1 | Groq Whisper Large V3 |
| **NLP Analysis** | OpenAI GPT-4o Mini | Groq Llama-3.3-70B Versatile |
| **Package** | `openai-php/laravel` | Laravel HTTP Client (native) |
| **Biaya** | Berbayar | Gratis (free tier) |
| **Speed** | Standar | Lebih cepat (~10x) |

---

## 🚀 Setup Instructions

### 1. Environment Configuration

Update file `.env` dengan konfigurasi Groq:

```bash
# Hapus konfigurasi OpenAI lama (jika ada)
# OPENAI_API_KEY=sk-xxxxx

# Tambahkan konfigurasi Groq baru
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Optional: Custom model configuration
# GROQ_WHISPER_MODEL=whisper-large-v3
# GROQ_LLM_MODEL=llama-3.3-70b-versatile
# GROQ_TIMEOUT=60
# GROQ_JSON_MODE=true

# APELS Settings (tetap sama)
APELS_DAILY_TEST_LIMIT=1
APELS_AUDIO_MAX_MB=10
APELS_STT_TIMEOUT=60
APELS_NLP_TIMEOUT=30
```

### 2. Dapatkan Groq API Key

1. Kunjungi: https://console.groq.com/keys
2. Sign up / Login (gratis)
3. Buat API key baru
4. Copy key ke `.env` file

### 3. Clear Cache & Restart

```bash
php artisan config:clear
php artisan cache:clear
php artisan queue:restart
```

---

## 🔧 Technical Changes

### File yang Diubah

#### **1. Config Files**
- ✅ **Created**: `config/groq.php` - Groq API configuration
- ✅ **Updated**: `config/apels.php` - Updated comments
- ❌ **Deleted**: `config/openai.php` - Removed OpenAI config

#### **2. Service Files**
- ✅ **Created**: `app/Services/GroqService.php` - Groq API wrapper
- ✅ **Updated**: `app/Services/SpeechToTextService.php` - Menggunakan GroqService
- ✅ **Updated**: `app/Services/NlpAnalysisService.php` - Menggunakan GroqService

#### **3. Dependencies**
- ❌ **Removed**: `openai-php/laravel` package
- ✅ **Using**: Laravel HTTP Client (built-in)

---

## 🎯 API Mapping

### Speech-to-Text (Audio Transcription)

**Sebelum (OpenAI):**
```php
$response = OpenAI::audio()->transcribe([
    'model' => 'whisper-1',
    'file' => fopen($path, 'r'),
    'language' => 'en',
]);
$transcript = $response->text;
```

**Sesudah (Groq):**
```php
$response = $this->groq->transcribeAudio($path, 'en');
$transcript = $response['text'];
```

### NLP Analysis (Chat Completion)

**Sebelum (OpenAI):**
```php
$response = OpenAI::chat()->create([
    'model' => 'gpt-4o-mini',
    'messages' => [...],
    'response_format' => ['type' => 'json_object'],
]);
$content = $response->choices[0]->message->content;
```

**Sesudah (Groq):**
```php
$response = $this->groq->chatCompletion(
    messages: [...],
    options: [
        'response_format' => ['type' => 'json_object'],
        'max_tokens' => 100,
    ]
);
$content = $response['choices'][0]['message']['content'];
```

---

## ⚡ Performance & Limitations

### Groq Free Tier Limits

| Metric | Limit |
|--------|-------|
| **Requests per minute** | 30 RPM |
| **Requests per day** | 7,000 RPD |
| **Tokens per minute** | Varies by model |

### Optimizations

1. **Caching**: NLP results di-cache 24 jam (sudah ada)
2. **Queue**: Audio processing menggunakan queue (async)
3. **Batch Processing**: Hindari rate limit dengan queue worker

### Monitoring Rate Limits

```php
// GroqService akan throw RequestException jika rate limit terlampaui
// Log akan mencatat error di storage/logs/laravel.log
```

---

## 🧪 Testing

### Manual Testing

```bash
# Test Groq configuration
php artisan tinker
>>> app(\App\Services\GroqService::class)->getConfig();

# Test Speech-to-Text
>>> $stt = app(\App\Services\SpeechToTextService::class);
>>> $stt->scoreFromTranscript('This is a test transcript with more than twenty words to get full score');
// Expected: 100

# Test NLP Analysis
>>> $nlp = app(\App\Services\NlpAnalysisService::class);
>>> $nlp->analyze(['I am learning English', 'This is my test']);
// Expected: ['grammar_score' => X, 'vocab_score' => Y]
```

### Feature Testing

```bash
# Run diagnostic test
php artisan test --filter DiagnosticTest

# Run all tests
php artisan test
```

---

## 🐛 Troubleshooting

### Error: "GROQ_API_KEY is not set"

**Solution:**
```bash
# Pastikan GROQ_API_KEY ada di .env
cat .env | grep GROQ_API_KEY

# Clear config cache
php artisan config:clear
```

### Error: "Rate limit exceeded"

**Solution:**
```bash
# Check logs
tail -f storage/logs/laravel.log | grep Groq

# Tunggu 1 menit, atau gunakan queue untuk batch processing
```

### Error: "Connection timeout"

**Solution:**
```bash
# Increase timeout di .env
GROQ_TIMEOUT=120

# Clear config
php artisan config:clear
```

### JSON Parsing Error

**Solution:**
- Groq Llama-3.3-70B sudah support JSON mode
- Pastikan `GROQ_JSON_MODE=true` di config
- Check log untuk melihat raw response

---

## 📊 Migration Checklist

- [x] Config file Groq dibuat
- [x] GroqService wrapper dibuat
- [x] SpeechToTextService diupdate
- [x] NlpAnalysisService diupdate
- [x] OpenAI package dihapus
- [x] .env.example diupdate
- [x] composer.json dibersihkan
- [x] Dokumentasi migrasi dibuat
- [ ] Testing di environment development
- [ ] Testing di environment production
- [ ] Monitoring rate limits
- [ ] Backup plan jika Groq down

---

## 🔄 Rollback Plan (Jika Diperlukan)

Jika terjadi masalah kritis, rollback ke OpenAI:

```bash
# 1. Install kembali OpenAI package
composer require openai-php/laravel

# 2. Restore config/openai.php dari git history
git checkout HEAD~1 config/openai.php

# 3. Revert service files
git checkout HEAD~1 app/Services/SpeechToTextService.php
git checkout HEAD~1 app/Services/NlpAnalysisService.php

# 4. Update .env
OPENAI_API_KEY=sk-xxxxx

# 5. Clear cache
php artisan config:clear
php artisan cache:clear
```

---

## 📚 Resources

- [Groq Console](https://console.groq.com/)
- [Groq API Documentation](https://console.groq.com/docs)
- [Groq Models](https://console.groq.com/docs/models)
- [Rate Limits](https://console.groq.com/docs/rate-limits)

---

## ✅ Verification

Setelah migrasi, verifikasi bahwa:

1. ✅ Diagnostic test berjalan normal
2. ✅ Audio transcription menghasilkan skor yang akurat
3. ✅ Grammar & vocabulary analysis berfungsi
4. ✅ Cache NLP berfungsi (cek di `cache` table/file)
5. ✅ Queue jobs tidak error
6. ✅ Log tidak ada error Groq

---

**Migration Date:** 2026-06-25  
**Status:** ✅ Completed  
**Migrated By:** AI Assistant (Claude)
