@php
    // Calculate time until midnight (next reset)
    $now = \Carbon\Carbon::now(config('app.timezone', 'Asia/Jakarta'));
    $midnight = $now->copy()->endOfDay()->addSecond();
    $diffHours = $now->diffInHours($midnight);
    $diffMinutes = $now->copy()->addHours($diffHours)->diffInMinutes($midnight);
    $timeLabel = $diffHours > 0
        ? "{$diffHours} jam {$diffMinutes} menit"
        : "{$diffMinutes} menit";
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tes Hari Ini Sudah Selesai — APELS</title>
    <meta name="description" content="Kamu sudah mengerjakan Diagnostic Test hari ini. Kembalilah besok untuk mengukur perkembanganmu!">

    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/icon.png') }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Detect & apply theme before paint --}}
    <script>
        (function () {
            const theme = localStorage.getItem('apels-theme');
            if (theme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f8f8fb;
            --surface: #ffffff;
            --surface-2: #f1f0f8;
            --border: rgba(99, 102, 241, 0.12);
            --text-primary: #18181b;
            --text-secondary: #52525b;
            --text-muted: #a1a1aa;
            --indigo: #6366f1;
            --indigo-light: rgba(99,102,241,0.08);
            --violet: #8b5cf6;
            --amber: #f59e0b;
            --amber-light: rgba(245,158,11,0.08);
        }
        html.dark {
            --bg: #0f0f14;
            --surface: #18181f;
            --surface-2: #1e1e28;
            --border: rgba(99, 102, 241, 0.18);
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --text-muted: #52525b;
            --indigo-light: rgba(99,102,241,0.12);
            --amber-light: rgba(245,158,11,0.10);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }

        /* ── Decorative blobs ── */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.35;
        }
        html.dark .blob { opacity: 0.18; }
        .blob-1 {
            width: 520px; height: 520px;
            top: -120px; left: -120px;
            background: radial-gradient(circle, #6366f1 0%, transparent 70%);
            animation: drift1 14s ease-in-out infinite alternate;
        }
        .blob-2 {
            width: 400px; height: 400px;
            bottom: -80px; right: -80px;
            background: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
            animation: drift2 17s ease-in-out infinite alternate;
        }
        .blob-3 {
            width: 280px; height: 280px;
            top: 40%; right: 15%;
            background: radial-gradient(circle, #f59e0b 0%, transparent 70%);
            opacity: 0.2;
            animation: drift3 11s ease-in-out infinite alternate;
        }
        @keyframes drift1 { from { transform: translate(0,0) scale(1); } to { transform: translate(40px, 30px) scale(1.08); } }
        @keyframes drift2 { from { transform: translate(0,0) scale(1); } to { transform: translate(-30px, -20px) scale(1.05); } }
        @keyframes drift3 { from { transform: translate(0,0); } to { transform: translate(20px, -25px); } }

        /* ── Card ── */
        .card {
            position: relative;
            z-index: 1;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 2rem;
            padding: 3rem 2.5rem;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 24px 64px rgba(99,102,241,0.08), 0 4px 16px rgba(0,0,0,0.06);
            animation: slideUp 0.55s cubic-bezier(0.22,1,0.36,1) both;
            backdrop-filter: blur(8px);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(32px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Icon container ── */
        .icon-ring {
            width: 96px; height: 96px;
            border-radius: 50%;
            background: var(--amber-light);
            border: 2px solid rgba(245,158,11,0.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.75rem;
            position: relative;
            animation: pulseRing 2.4s ease-in-out infinite;
        }
        @keyframes pulseRing {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.20); }
            50%       { box-shadow: 0 0 0 14px rgba(245,158,11,0); }
        }
        .icon-ring svg { width: 48px; height: 48px; color: #f59e0b; }

        /* ── Badge ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--amber-light);
            color: #d97706;
            border: 1px solid rgba(245,158,11,0.20);
            border-radius: 999px;
            padding: 0.25rem 0.85rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }
        html.dark .badge { color: #fbbf24; }
        .badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #f59e0b;
            animation: blink 1.2s step-start infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

        /* ── Typography ── */
        h1 {
            font-size: 1.9rem;
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -0.035em;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            text-align: center;
        }
        .subtitle {
            font-size: 0.92rem;
            color: var(--text-secondary);
            line-height: 1.65;
            text-align: center;
            margin-bottom: 2rem;
        }

        /* ── Countdown box ── */
        .countdown-box {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .countdown-icon-wrap {
            width: 44px; height: 44px; flex-shrink: 0;
            border-radius: 0.875rem;
            background: var(--indigo-light);
            display: flex; align-items: center; justify-content: center;
        }
        .countdown-icon-wrap svg { width: 22px; height: 22px; color: var(--indigo); }
        .countdown-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.2rem;
        }
        .countdown-value {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--indigo);
            letter-spacing: -0.02em;
        }
        html.dark .countdown-value { color: #818cf8; }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0 0 1.75rem;
        }

        /* ── Tip list ── */
        .tip-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        .tip-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            font-size: 0.83rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .tip-icon {
            flex-shrink: 0;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: var(--indigo-light);
            display: flex; align-items: center; justify-content: center;
            margin-top: 1px;
        }
        .tip-icon svg { width: 11px; height: 11px; color: var(--indigo); }
        html.dark .tip-icon svg { color: #818cf8; }

        /* ── Buttons ── */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        @media (min-width: 420px) {
            .btn-group { flex-direction: row; }
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.875rem;
            padding: 0.8rem 1.5rem;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.22,1,0.36,1);
            text-decoration: none;
            border: none;
            flex: 1;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:active { transform: translateY(0) scale(0.97); }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(99,102,241,0.28), 0 2px 6px rgba(99,102,241,0.18);
        }
        .btn-primary:hover {
            box-shadow: 0 10px 28px rgba(99,102,241,0.38), 0 3px 10px rgba(99,102,241,0.22);
        }

        .btn-ghost {
            background: var(--surface-2);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            color: var(--text-primary);
            background: var(--surface);
            border-color: rgba(99,102,241,0.28);
        }
        .btn svg { width: 17px; height: 17px; }

        /* ── Footer note ── */
        .footer-note {
            margin-top: 1.75rem;
            font-size: 0.76rem;
            color: var(--text-muted);
            text-align: center;
        }
    </style>
</head>
<body>
    {{-- Decorative background blobs --}}
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <main class="card" role="main">

        {{-- Icon --}}
        <div class="icon-ring" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        {{-- Badge --}}
        <div style="text-align:center">
            <span class="badge"><span class="badge-dot"></span> Batas Harian Tercapai</span>
        </div>

        {{-- Heading --}}
        <h1>Tes hari ini<br>sudah selesai! 🎉</h1>

        <p class="subtitle">
            Kamu sudah mengerjakan <strong>Diagnostic Test</strong> hari ini.
            Hasil tesmu sedang diproses oleh AI kami.
            Kembalilah besok untuk mengukur perkembanganmu!
        </p>

        {{-- Countdown --}}
        <div class="countdown-box" aria-label="Waktu reset berikutnya">
            <div class="countdown-icon-wrap" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div>
                <p class="countdown-label">Reset berikutnya</p>
                <p class="countdown-value" id="countdown-display">{{ $timeLabel }} lagi</p>
            </div>
        </div>

        <hr class="divider">

        {{-- Tips --}}
        <p style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.75rem;">
            Gunakan waktumu sekarang untuk…
        </p>
        <ul class="tip-list" aria-label="Saran aktivitas">
            <li>
                <span class="tip-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span>Pelajari materi di <strong>Roadmap Belajar</strong> untuk memperkuat kemampuanmu sebelum tes berikutnya.</span>
            </li>
            <li>
                <span class="tip-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span>Cek <strong>Dashboard</strong> untuk melihat hasil dan level kemampuan bahasa Inggrismu saat ini.</span>
            </li>
            <li>
                <span class="tip-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span>Kerjakan <strong>latihan soal</strong> di modul pembelajaran untuk berlatih sebelum tes berikutnya.</span>
            </li>
        </ul>

        {{-- Buttons --}}
        <div class="btn-group">
            <a href="{{ route('dashboard') }}" class="btn btn-primary" id="btn-dashboard">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Ke Dashboard
            </a>
            <a href="{{ route('learning-path') }}" class="btn btn-ghost" id="btn-learning">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Roadmap Belajar
            </a>
        </div>

        <p class="footer-note">
            Tes akan direset setiap tengah malam &middot; Timezone: {{ config('app.timezone', 'Asia/Jakarta') }}
        </p>
    </main>

    {{-- Live countdown JS --}}
    <script>
        (function () {
            function getSecondsUntilMidnight() {
                const now = new Date();
                const midnight = new Date(now);
                midnight.setHours(24, 0, 0, 0); // next midnight local
                return Math.max(0, Math.floor((midnight - now) / 1000));
            }

            function formatCountdown(secs) {
                const h = Math.floor(secs / 3600);
                const m = Math.floor((secs % 3600) / 60);
                const s = secs % 60;
                if (h > 0) {
                    return `${h} jam ${m} menit ${s} detik lagi`;
                } else if (m > 0) {
                    return `${m} menit ${s} detik lagi`;
                } else {
                    return `${s} detik lagi`;
                }
            }

            const el = document.getElementById('countdown-display');
            if (!el) return;

            let secs = getSecondsUntilMidnight();
            el.textContent = formatCountdown(secs);

            const timer = setInterval(function () {
                secs--;
                if (secs <= 0) {
                    clearInterval(timer);
                    el.textContent = 'Reset sekarang! Segarkan halaman.';
                    el.style.color = '#10b981';
                } else {
                    el.textContent = formatCountdown(secs);
                }
            }, 1000);
        })();
    </script>
</body>
</html>
