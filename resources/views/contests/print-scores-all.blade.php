<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print All Scores: {{ $contest->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #000; padding: 1rem; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-bottom: 8px; text-align: center; color: #333; }
        .subtitle { text-align: center; font-size: 11px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: center; font-size: 10px; }
        th { background: #f0f0f0; font-weight: 600; }
        .contestant-name { text-align: left; font-weight: 500; }
        .section { page-break-inside: avoid; margin-bottom: 24px; }
        .section-title { font-size: 13px; font-weight: 700; margin-bottom: 6px; padding: 4px 8px; background: #e0e0e0; }
        .exposure-header { font-size: 15px; font-weight: 700; margin: 20px 0 8px; padding: 6px 10px; background: #333; color: #fff; text-align: center; page-break-before: always; }
        .exposure-header:first-of-type { page-break-before: avoid; }
        .no-print { margin-bottom: 16px; text-align: center; }
        .no-print button { padding: 8px 24px; font-size: 14px; cursor: pointer; background: #3b82f6; color: #fff; border: none; border-radius: 6px; }
        .no-print a { margin-left: 12px; color: #3b82f6; text-decoration: underline; font-size: 14px; }
        .signature-section { margin-top: 50px; page-break-inside: avoid; }
        .signature-grid { display: flex; flex-wrap: wrap; gap: 30px 40px; justify-content: center; margin-top: 24px; }
        .signature-box { text-align: center; min-width: 200px; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin: 0 auto 4px; }
        .signature-label { font-size: 11px; color: #666; }
        .signature-name { font-size: 12px; font-weight: 600; margin-bottom: 40px; }
        .tabulator-block { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 10px; }
        .tabulator-block img { max-height: 60px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨 Print This Page</button>
        <a href="{{ route('contests.results', $contest) }}">← Back to Results</a>
    </div>

    @if($contest->pageant_logo)
        <div style="text-align:center; margin-bottom:10px;"><img src="{{ asset('storage/' . $contest->pageant_logo) }}" alt="Pageant Logo" style="max-height:80px;"></div>
    @endif
    <h1>{{ $contest->name }}</h1>
    <h2>Complete Score Sheets — All Exposures</h2>
    <p class="subtitle">{{ $contest->getTypeLabel() }} · {{ $contest->judges->count() }} Judges · {{ $contest->contestants->count() }} Contestants · {{ $contest->exposures->count() }} Exposures</p>

    @foreach($contest->exposures as $exposure)
        @php $results = $allResults[$exposure->id] ?? []; @endphp

        <div class="exposure-header">{{ $exposure->name }}@if($exposure->is_final) (Final Round)@endif</div>

        @foreach($contest->judges as $judge)
            <div class="section">
                <div class="section-title">Judge {{ $judge->number }}: {{ $judge->name }}</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th style="text-align:left">Contestant</th>
                            @foreach($exposure->criteria as $criteria)
                                <th>{{ $criteria->name }}<br>({{ $criteria->percentage }}%)<br>{{ $criteria->min_score }}–{{ $criteria->max_score }}</th>
                            @endforeach
                            <th>Weighted Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            @php
                                $judgeWeightedTotal = 0;
                            @endphp
                            <tr>
                                <td>{{ $result['contestant']->number }}</td>
                                <td class="contestant-name">{{ $result['contestant']->getDisplayName() }}</td>
                                @foreach($exposure->criteria as $criteria)
                                    @php
                                        $score = $result['criteria_scores'][$criteria->id]['judges'][$judge->id] ?? null;
                                        if ($score !== null) {
                                            $judgeWeightedTotal += $score * ($criteria->percentage / 100);
                                        }
                                    @endphp
                                    <td>{{ $score !== null ? number_format($score, 2) : '—' }}</td>
                                @endforeach
                                <td style="font-weight:600">{{ number_format($judgeWeightedTotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endforeach
    <div class="signature-section">
        @if($contest->tabulator_name || $contest->logo)
            <div class="tabulator-block">
                @if($contest->logo)
                    <img src="{{ asset('storage/' . $contest->logo) }}" alt="Logo">
                @endif
                @if($contest->tabulator_name)
                    <div>
                        <div style="font-size:11px; color:#666;">Official Tabulator</div>
                        <div style="font-weight:700; font-size:15px;">{{ $contest->tabulator_name }}</div>
                    </div>
                @endif
            </div>
        @endif

        <div class="signature-grid">
            @foreach($contest->judges as $judge)
                <div class="signature-box">
                    <div class="signature-name">Judge {{ $judge->number }}: {{ $judge->name }}</div>
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature</div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
