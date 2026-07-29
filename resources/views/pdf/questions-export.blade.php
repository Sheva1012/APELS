<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Bank Soal — APELS</title>
    <style>
        @page {
            margin: 25mm 15mm 18mm 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.4;
            color: #1e293b;
            background: #ffffff;
        }

        /* Header Table */
        table.header-table {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 14px;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        table.header-table td {
            vertical-align: middle;
            border: none;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e1b4b;
            line-height: 1.4;
        }
        .brand-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 8px;
        }
        .meta-info {
            text-align: right;
            font-size: 9px;
            color: #475569;
            line-height: 1.6;
        }

        /* Questions Table */
        table.questions-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.questions-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 7px 6px;
            border: 1px solid #1e293b;
            text-align: left;
        }
        table.questions-table td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            font-size: 9px;
        }
        table.questions-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Columns */
        .col-num { width: 4%; text-align: center; font-weight: bold; }
        .col-set { width: 6%; text-align: center; }
        .col-question { width: 33%; }
        .col-options { width: 40%; }
        .col-meta { width: 11%; }
        .col-status { width: 6%; text-align: center; }

        /* Options Grid */
        table.options-grid {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        table.options-grid td {
            width: 50%;
            border: none;
            padding: 1px 2px;
            font-size: 8.5px;
        }
        .opt-box {
            padding: 3px 5px;
            border-radius: 3px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
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
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }
        .badge-set-a { background-color: #ede9fe; color: #6d28d9; }
        .badge-set-b { background-color: #fef3c7; color: #b45309; }
        .badge-set-c { background-color: #e0e7ff; color: #3730a3; }

        .badge-grammar { background-color: #dbeafe; color: #1d4ed8; }
        .badge-vocabulary { background-color: #dcfce7; color: #15803d; }

        .badge-basic { background-color: #f1f5f9; color: #475569; }
        .badge-intermediate { background-color: #ffedd5; color: #c2410c; }
        .badge-advanced { background-color: #ffe4e6; color: #be123c; }

        .status-active { color: #16a34a; font-weight: bold; }
        .status-inactive { color: #dc2626; font-weight: bold; }

        /* Footer */
        .footer {
            margin-top: 10px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div style="padding-top: 40px; padding-left: 20px; padding-right: 20px;">
    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title">APELS — Bank Soal Diagnostic Test</div>
                <div class="brand-subtitle">Adaptive Professional English Learning System</div>
            </td>
            <td class="meta-info">
                <div>Tanggal Export: <strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
                <div>Total Soal: <strong>{{ $questions->count() }} Soal</strong></div>
            </td>
        </tr>
    </table>

    <table class="questions-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-set">Set</th>
                <th class="col-question">Pertanyaan / Soal</th>
                <th class="col-options">Pilihan Jawaban (Kunci ★)</th>
                <th class="col-meta">Kategori</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($questions as $i => $q)
            <tr>
                <td class="col-num">{{ $i + 1 }}</td>
                <td class="col-set">
                    <span class="badge badge-set-{{ strtolower($q->set) }}">Set {{ strtoupper($q->set) }}</span>
                </td>
                <td class="col-question">
                    <strong>{{ $q->question }}</strong>
                </td>
                <td class="col-options">
                    <table class="options-grid">
                        <tr>
                            <td>
                                <div class="opt-box {{ strtolower($q->correct_answer) === 'a' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">A.</span> {{ $q->option_a }}
                                    @if(strtolower($q->correct_answer) === 'a') ★ @endif
                                </div>
                            </td>
                            <td>
                                <div class="opt-box {{ strtolower($q->correct_answer) === 'b' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">B.</span> {{ $q->option_b }}
                                    @if(strtolower($q->correct_answer) === 'b') ★ @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="opt-box {{ strtolower($q->correct_answer) === 'c' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">C.</span> {{ $q->option_c }}
                                    @if(strtolower($q->correct_answer) === 'c') ★ @endif
                                </div>
                            </td>
                            <td>
                                <div class="opt-box {{ strtolower($q->correct_answer) === 'd' ? 'is-correct' : '' }}">
                                    <span class="opt-letter">D.</span> {{ $q->option_d }}
                                    @if(strtolower($q->correct_answer) === 'd') ★ @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="col-meta">
                    <div style="margin-bottom: 3px;">
                        <span class="badge badge-{{ strtolower($q->type) }}">{{ ucfirst($q->type) }}</span>
                    </div>
                    <div style="margin-bottom: 3px;">
                        <span class="badge badge-{{ strtolower($q->tag) }}">{{ ucfirst($q->tag) }}</span>
                    </div>
                    <div style="font-size: 8px; color: #64748b;">
                        Diff: Level {{ $q->difficulty }}
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
        Dokumen Bank Soal APELS &nbsp;•&nbsp; Digenerate otomatis pada {{ now()->format('d F Y, H:i') }}
    </div>
    </div>

</body>
</html>
