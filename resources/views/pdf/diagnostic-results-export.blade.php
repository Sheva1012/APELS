<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Hasil Tes Diagnostik — APELS</title>
    <style>
        @page {
            margin: 50px 40px 60px 40px;
        }
        table, th, td, div, span, p {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
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

        /* Results Table */
        table.results-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.results-table thead {
            display: table-header-group;
        }
        table.results-table tr {
            page-break-inside: avoid;
        }
        table.results-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 6px;
            border: 1px solid #0f172a;
            text-align: center;
        }
        table.results-table th.th-left {
            text-align: left;
        }
        table.results-table td {
            padding: 7px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            font-size: 8.5px;
            text-align: center;
        }
        table.results-table td.td-left {
            text-align: left;
        }
        table.results-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Columns Widths */
        .col-num { width: 4%; }
        .col-student { width: 26%; }
        .col-set { width: 8%; }
        .col-score { width: 10%; }
        .col-overall { width: 12%; }
        .col-attempt { width: 8%; }
        .col-date { width: 18%; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2.5px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }
        .badge-set-a { background-color: #dbeafe; color: #1d4ed8; }
        .badge-set-b { background-color: #fef3c7; color: #b45309; }
        .badge-set-c { background-color: #dcfce7; color: #15803d; }
        .badge-set-other { background-color: #f1f5f9; color: #475569; }

        .score-box {
            font-weight: 600;
            color: #334155;
        }
        .overall-box {
            display: inline-block;
            font-weight: bold;
            color: #1e40af;
            background-color: #eff6ff;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #bfdbfe;
        }
        .student-name {
            font-weight: bold;
            color: #0f172a;
        }
        .student-email {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 1px;
        }
    </style>
</head>
<body>
    {{-- Header on first page --}}
    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title">APELS — Rekapitulasi Hasil Tes Diagnostik</div>
                <div class="brand-subtitle">Adaptive Professional English Learning System</div>
            </td>
            <td class="meta-info">
                <div>Tanggal Export: <strong>{{ now()->format('d/m/Y H:i') }} WIB</strong></div>
                <div>Total Data: <strong>{{ $results->count() }} Percobaan Tes</strong></div>
            </td>
        </tr>
    </table>

    <table class="results-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-student th-left">Mahasiswa</th>
                <th class="col-set">Set Soal</th>
                <th class="col-score">Speaking</th>
                <th class="col-score">Grammar</th>
                <th class="col-score">Vocabulary</th>
                <th class="col-overall">Overall</th>
                <th class="col-attempt">Attempt</th>
                <th class="col-date">Tanggal Tes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($results as $i => $row)
            @php
                $rawSet = strtoupper(trim((string) ($row->set_used ?? '')));
                $setCode = str_starts_with($rawSet, 'SET ') ? trim(substr($rawSet, 4)) : $rawSet;
                if ($setCode === '' || $setCode === '-') {
                    $setCode = '-';
                    $badgeClass = 'badge-set-other';
                } else {
                    $badgeClass = match ($setCode) {
                        'A' => 'badge-set-a',
                        'B' => 'badge-set-b',
                        'C' => 'badge-set-c',
                        default => 'badge-set-other',
                    };
                }
            @endphp
            <tr>
                <td class="col-num">{{ $i + 1 }}</td>
                <td class="col-student td-left">
                    <div class="student-name">{{ $row->user?->name ?? 'User #' . $row->user_id }}</div>
                    @if($row->user?->email)
                        <div class="student-email">{{ $row->user->email }}</div>
                    @endif
                </td>
                <td class="col-set">
                    @if($setCode !== '-')
                        <span class="badge {{ $badgeClass }}">Set {{ $setCode }}</span>
                    @else
                        <span style="color: #94a3b8;">-</span>
                    @endif
                </td>
                <td class="col-score">
                    <span class="score-box">{{ $row->speaking ?? 0 }}</span>
                </td>
                <td class="col-score">
                    <span class="score-box">{{ $row->grammar ?? 0 }}</span>
                </td>
                <td class="col-score">
                    <span class="score-box">{{ $row->vocabulary ?? 0 }}</span>
                </td>
                <td class="col-overall">
                    <span class="overall-box">{{ number_format($row->overall, 1) }}</span>
                </td>
                <td class="col-attempt">
                    Ke-{{ $row->attempt ?? $row->attempt_number ?? 1 }}
                </td>
                <td class="col-date">
                    {{ $row->created_at ? $row->created_at->format('d M Y, H:i') : '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="padding: 20px; color: #64748b; text-align: center;">
                    Tidak ada data hasil tes diagnostik.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen Laporan APELS &nbsp;•&nbsp; Rekapitulasi Diagnostic Results &nbsp;•&nbsp; Digenerate pada {{ now()->format('d F Y, H:i') }} WIB
    </div>
</body>
</html>
