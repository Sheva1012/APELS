# ⚡ Groq API - Quick Start Guide

## 🚀 Setup dalam 3 Langkah

### 1️⃣ Dapatkan API Key (GRATIS)

1. Buka: **https://console.groq.com/keys**
2. Sign up / Login
3. Klik "Create API Key"
4. Copy key yang dimulai dengan `gsk_...`

### 2️⃣ Update Environment

```bash
# Edit .env file
nano .env

# Tambahkan baris ini:
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 3️⃣ Clear Cache & Test

```bash
# Clear config cache
php artisan config:clear

# Clear application cache
php artisan cache:clear

# Restart queue workers
php artisan queue:restart

# Test configuration
php artisan tinker
>>> app(\App\Services\GroqService::class)->getConfig();
```

---

## ✅ Verifikasi Cepat

```bash
# Check Groq config
php artisan tinker
>>> config('groq.api_key') !== null
# Expected: true

>>> config('groq.whisper_model')
# Expected: "whisper-large-v3"

>>> config('groq.llm_model')
# Expected: "llama-3.3-70b-versatile"

# Test GroqService
>>> app(\App\Services\GroqService::class)->isConfigured()
# Expected: true
```

---

## 📊 Model yang Digunakan

| Fungsi | Model Groq | Kecepatan |
|--------|------------|-----------|
| **Speech-to-Text** | `whisper-large-v3` | ~0.5-1 detik |
| **Grammar & Vocabulary** | `llama-3.3-70b-versatile` | ~0.3-0.8 detik |

---

## ⚡ Rate Limits (Free Tier)

```
✅ 30 requests per minute
✅ 7,000 requests per day
✅ Unlimited tokens (dalam batas requests)
```

**Tips:**
- NLP results otomatis di-cache 24 jam
- Audio processing menggunakan queue (async)
- Retry otomatis 3x jika gagal

---

## 🧪 Testing Manual

### Test 1: Speech-to-Text

```php
// Di tinker (php artisan tinker)
$stt = app(\App\Services\SpeechToTextService::class);

// Test scoring logic
$stt->scoreFromTranscript('This is a test with exactly twenty one words in this sentence to get full score');
// Expected: 100

$stt->scoreFromWordCount(20);
// Expected: 100

$stt->scoreFromWordCount(10);
// Expected: 50
```

### Test 2: NLP Analysis

```php
// Di tinker
$nlp = app(\App\Services\NlpAnalysisService::class);

// Test with sample text
$result = $nlp->analyze([
    'I am learning English every day',
    'The weather is beautiful today',
    'She goes to school by bus'
]);

// Expected: ['grammar_score' => X, 'vocab_score' => Y]
// X dan Y antara 0-100
```

### Test 3: Full Diagnostic Flow

```bash
# 1. Login sebagai mahasiswa
# 2. Akses diagnostic test
# 3. Upload audio file
# 4. Isi text answers
# 5. Submit
# 6. Check hasil di dashboard

# Monitor queue:
php artisan queue:work --verbose

# Monitor logs:
tail -f storage/logs/laravel.log | grep -E "Groq|Speech|NLP"
```

---

## 🐛 Common Issues

### ❌ "GROQ_API_KEY is not set"

```bash
# Fix:
1. Pastikan GROQ_API_KEY ada di .env
2. Jangan ada spasi sebelum/sesudah nilai
3. Jangan ada quotes jika tidak perlu
4. Run: php artisan config:clear
```

**Benar:**
```bash
GROQ_API_KEY=gsk_abc123...
```

**Salah:**
```bash
GROQ_API_KEY = gsk_abc123...    # Ada spasi
GROQ_API_KEY="gsk_abc123..."    # Tidak perlu quotes
```

### ❌ Rate Limit Exceeded

```bash
# Solusi:
1. Tunggu 60 detik
2. Check console: https://console.groq.com/settings/limits
3. Reduce concurrent requests
```

### ❌ Connection Timeout

```bash
# Tambah di .env:
GROQ_TIMEOUT=120

# Clear cache:
php artisan config:clear
```

---

## 📈 Monitoring

### Check API Usage

```bash
# Groq Console
https://console.groq.com/settings/limits

# Shows:
- Requests today
- Requests this minute
- Rate limit status
```

### Check Application Logs

```bash
# Monitor Groq API calls
tail -f storage/logs/laravel.log | grep Groq

# Monitor errors only
tail -f storage/logs/laravel.log | grep "ERROR.*Groq"
```

### Check Cache Performance

```bash
# Di tinker
use Illuminate\Support\Facades\Cache;

# Check cache stats (jika menggunakan Redis)
Cache::getStore()->getRedis()->info();

# Clear specific NLP cache
Cache::flush(); // Clear all (hati-hati!)
```

---

## 🎯 Performance Tips

1. **Cache is King**
   - NLP results otomatis di-cache 24 jam
   - Identical text answers = cache hit = no API call
   - Monitor cache hit rate

2. **Use Queue**
   - Audio processing selalu async
   - Hindari blocking request
   - Scale queue workers sesuai load

3. **Batch Processing**
   - Process multiple tests in queue
   - Respect rate limits (30/min)
   - Use retry with exponential backoff

---

## 📞 Support

- **Groq Documentation**: https://console.groq.com/docs
- **Groq Discord**: https://discord.gg/groq
- **Status Page**: https://status.groq.com/
- **Migration Guide**: `docs/GROQ_MIGRATION.md`

---

## 🎉 That's It!

Groq API sudah siap digunakan. Performa lebih cepat, biaya $0! 🚀

**Next Steps:**
1. Get API key dari Groq Console
2. Update `.env` file
3. Clear cache
4. Test dengan diagnostic test
5. Monitor performance
6. Enjoy! 🎊
