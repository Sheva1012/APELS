<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a2e; background: #fff; }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #4f46e5 100%);
            color: white;
            padding: 18px 24px;
            margin-bottom: 16px;
        }
        .header h1 { font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }
        .header p  { font-size: 9px; opacity: 0.8; margin-top: 3px; }
        .meta { font-size: 8px; opacity: 0.7; margin-top: 6px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 16px;
            width: calc(100% - 32px);
        }
        thead tr {
            background: #1e3a8a;
            color: white;
        }
        thead th {
            padding: 7px 6px;
            text-align: left;
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        tbody tr:nth-child(even) { background: #f0f4ff; }
        tbody tr:nth-child(odd)  { background: #ffffff; }
        tbody td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8px;
            vertical-align: top;
        }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 600;
        }
        .badge-grammar    { background: #dbeafe; color: #1e40af; }
        .badge-vocabulary { background: #d1fae5; color: #065f46; }
        .badge-advanced   { background: #fde8e8; color: #b91c1c; }
        .badge-intermediate { background: #fef3c7; color: #92400e; }
        .badge-basic      { background: #e0f2fe; color: #075985; }
        .badge-set-a      { background: #ede9fe; color: #5b21b6; }
        .badge-set-b      { background: #fef9c3; color: #713f12; }
        .badge-set-c      { background: #dcfce7; color: #14532d; }
        .dot-active   { color: #16a34a; font-size: 11px; }
        .dot-inactive { color: #dc2626; font-size: 11px; }

        .footer {
            margin-top: 14px;
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
            padding-bottom: 10px;
        }

        .q-text { max-width: 200px; }
        .opt-text { max-width: 90px; color: #374151; }
    </style>
</head>
<body>

<div class="header">
    <h1>📋 APELS — Questions Export</h1>
    <p>AI-Powered English Learning System</p>
    <div class="meta">
        Exported: {{ now()->format('d F Y, H:i') }} &nbsp;|&nbsp;
        Total Questions: {{ $questions->count() }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:18px">#</th>
            <th style="width:20px">Set</th>
            <th style="width:195px">Question</th>
            <th style="width:75px">Option A</th>
            <th style="width:75px">Option B</th>
            <th style="width:75px">Option C</th>
            <th style="width:75px">Option D</th>
            <th style="width:22px">Ans</th>
            <th style="width:45px">Type</th>
            <th style="width:50px">Tag</th>
            <th style="width:18px">Dif</th>
            <th style="width:18px">Act</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($questions as $i => $q)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                <span class="badge badge-set-{{ strtolower($q->set) }}">{{ $q->set }}</span>
            </td>
            <td class="q-text">{{ $q->question }}</td>
            <td class="opt-text">{{ $q->option_a }}</td>
            <td class="opt-text">{{ $q->option_b }}</td>
            <td class="opt-text">{{ $q->option_c }}</td>
            <td class="opt-text">{{ $q->option_d }}</td>
            <td style="font-weight:700; color:#4f46e5; text-align:center;">{{ strtoupper($q->correct_answer) }}</td>
            <td>
                <span class="badge badge-{{ $q->type }}">{{ ucfirst($q->type) }}</span>
            </td>
            <td>
                <span class="badge badge-{{ $q->tag }}">{{ ucfirst($q->tag) }}</span>
            </td>
            <td style="text-align:center;">{{ $q->difficulty }}</td>
            <td style="text-align:center;">
                @if($q->is_active)
                    <span class="dot-active">✓</span>
                @else
                    <span class="dot-inactive">✗</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    APELS — AI-Powered English Learning System &nbsp;|&nbsp; Generated automatically on {{ now()->format('d M Y') }}
</div>

</body>
</html>
