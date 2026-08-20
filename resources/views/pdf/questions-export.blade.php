<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Bank Soal — APELS</title>
    <style>
        @page {
            margin: 50px 40px 60px 40px;
        }
        table, th, td, div, span, p {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            line-height: 1.4;
            color: #1e293b;
            background: #ffffff;
        }

        /* Running Footer on every page */
        .footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }

        /* Header Table */
        table.header-table {
            width: 100%;
            border-bottom: 2.5px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        table.header-table td {
            vertical-align: middle;
            border: none;
            padding: 4px 0;
        }
        .brand-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.3;
        }
        .brand-subtitle {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 4px;
        }
        .meta-info {
            text-align: right;
            font-size: 8.5px;
            color: #475569;
            line-height: 1.6;
        }

        /* Questions Table */
        table.questions-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.questions-table thead {
            display: table-header-group;
        }
        table.questions-table tr {
            page-break-inside: avoid;
        }
        table.questions-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 7px 6px;
            border: 1px solid #0f172a;
            text-align: left;
        }
        table.questions-table td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            font-size: 8.5px;
        }
        table.questions-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Columns Widths */
        .col-num { width: 3.5%; text-align: center; font-weight: bold; }
        .col-set { width: 7.5%; text-align: center; }
        .col-question { width: 34%; }
        .col-options { width: 39.5%; }
        .col-meta { width: 10%; }
        .col-status { width: 5.5%; text-align: center; }

        /* Options Grid */
        table.options-grid {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        table.options-grid td {
            width: 50%;
            border: none;
            padding: 2px 3px;
            font-size: 8px;
            vertical-align: middle;
        }
        .opt-box {
            padding: 3.5px 5.5px;
            border-radius: 3.5px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
            line-height: 1.35;
        }
        .opt-box.is-correct {
            background-color: #dcfce7;
            border-color: #86efac;
            color: #15803d;
            font-weight: bold;
        }
        .opt-letter {
            font-weight: bold;
            margin-right: 2px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2.5px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
        }
        .badge-set-a { background-color: #dbeafe; color: #1d4ed8; }
        .badge-set-b { background-color: #fef3c7; color: #b45309; }
        .badge-set-c { background-color: #dcfce7; color: #15803d; }
        .badge-set-d { background-color: #e0e7ff; color: #4338ca; }
        .badge-set-other { background-color: #f1f5f9; color: #475569; }

        .badge-grammar { background-color: #dbeafe; color: #1d4ed8; }
        .badge-vocabulary { background-color: #dcfce7; color: #15803d; }

        .badge-basic { background-color: #f1f5f9; color: #475569; }
        .badge-intermediate { background-color: #ffedd5; color: #c2410c; }
        .badge-advanced { background-color: #ffe4e6; color: #be123c; }

        .status-active { color: #16a34a; font-weight: bold; font-size: 8px; }
        .status-inactive { color: #dc2626; font-weight: bold; font-size: 8px; }

        .question-text {
            color: #0f172a;
            font-weight: 600;
            line-height: 1.35;
        }
    </style>
</head>
<body>
    {{-- Header on first page --}}
    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title">APELS — Bank Soal Diagnostic Test</div>
                <div class="brand-subtitle">Adaptive Professional English Learning System</div>
            </td>
            <td class="meta-info">
                <div>Tanggal Export: <strong>{{ now()->format('d/m/Y H:i') }} WIB</strong></div>
                <div>Total Bank Soal: <strong>{{ $questions->count() }} Butir Soal</strong></div>
            </td>
        </tr>
    </table>

    <table class="questions-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-set">Set</th>
                <th class="col-question">Pertanyaan / Butir Soal</th>
                <th class="col-options">Pilihan Jawaban (Kunci ★)</th>
                <th class="col-meta">Kategori</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($questions as $i => $q)
            @php
                // Clean Set String
                $rawSet = strtoupper(trim((string) $q->set));
                $setCode = str_starts_with($rawSet, 'SET ') ? trim(substr($rawSet, 4)) : $rawSet;
                if ($setCode === '' || $setCode === '-') {
                    $setCode = 'A'; // Default to Set A if unspecified
                }
                $setBadgeClass = match ($setCode) {
                    'A' => 'badge-set-a',
                    'B' => 'badge-set-b',
                    'C' => 'badge-set-c',
                    'D' => 'badge-set-d',
                    default => 'badge-set-other',
                };

                // Clean Question Text (strip HTML tags, decode entities like &quot;)
                $cleanQuestion = html_entity_decode(strip_tags((string) $q->question), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanOptA = html_entity_decode(strip_tags((string) $q->option_a), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanOptB = html_entity_decode(strip_tags((string) $q->option_b), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanOptC = html_entity_decode(strip_tags((string) $q->option_c), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanOptD = html_entity_decode(strip_tags((string) $q->option_d), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $correct = strtolower(trim((string) $q->correct_answer));
            @endphp
            <tr>
                <td class="col-num">{{ $i + 1 }}</td>
                <td class="col-set">
                    <span class="badge {{ $setBadgeClass }}">Set {{ $setCode }}</span>
                </td>
                <td class="col-question">
                    <div class="question-text">{{ $cleanQuestion }}</div>
                </td>
                <td class="col-options">
                    <table class="options-grid">
                        <tr>
                            <td>
                                <div class="opt-box {{ $correct === 'a' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">A.</span> {{ $cleanOptA }}
                                    @if($correct === 'a') ★ @endif
                                </div>
                            </td>
                            <td>
                                <div class="opt-box {{ $correct === 'b' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">B.</span> {{ $cleanOptB }}
                                    @if($correct === 'b') ★ @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="opt-box {{ $correct === 'c' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">C.</span> {{ $cleanOptC }}
                                    @if($correct === 'c') ★ @endif
                                </div>
                            </td>
                            <td>
                                <div class="opt-box {{ $correct === 'd' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">D.</span> {{ $cleanOptD }}
                                    @if($correct === 'd') ★ @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="col-meta">
                    <div style="margin-bottom: 2px;">
                        <span class="badge badge-{{ strtolower((string)$q->type) }}">{{ ucfirst((string)$q->type) }}</span>
                    </div>
                    <div style="margin-bottom: 2px;">
                        <span class="badge badge-{{ strtolower((string)$q->tag) }}">{{ ucfirst((string)$q->tag) }}</span>
                    </div>
                    <div style="font-size: 7.5px; color: #64748b;">
                        Level {{ $q->difficulty }}
                    </div>
                </td>
                <td class="col-status">
                    @if($q->is_active)
                        <span class="status-active">Aktif</span>
                    @else
                        <span class="status-inactive">Off</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen Bank Soal APELS &nbsp;•&nbsp; Digenerate otomatis pada {{ now()->format('d F Y, H:i') }} WIB
    </div>
</body>
</html>
