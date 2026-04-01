<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Rankings: {{ $contest->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #000; padding: 1.5rem; }
        h1 { font-size: 22px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 16px; text-align: center; color: #333; margin-bottom: 6px; }
        .subtitle { text-align: center; font-size: 12px; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: center; font-size: 11px; }
        th { background: #e8e8e8; font-weight: 700; }
        .contestant-name { text-align: left; font-weight: 500; }
        .rank-cell { font-size: 16px; font-weight: 700; }
        .gold { background: #fff9c4; }
        .silver { background: #f5f5f5; }
        .bronze { background: #ffe0b2; }
        .total-cell { font-weight: 700; font-size: 12px; }
        .winner-box { display: inline-block; padding: 8px 16px; margin: 4px 8px; border: 2px solid #333; border-radius: 8px; font-size: 13px; }
        .winner-box .place { font-weight: 700; font-size: 11px; text-transform: uppercase; color: #666; }
        .winner-box .name { font-size: 15px; font-weight: 700; }
        .winners-section { text-align: center; margin: 24px 0; }
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
    <h2>Overall Rankings</h2>
    <p class="subtitle">{{ $contest->getTypeLabel() }} · {{ $contest->judges->count() }} Judges · {{ $contest->contestants->count() }} Contestants · {{ $contest->exposures->count() }} Exposures</p>

    {{-- Winners Summary --}}
    @if(count($overallResults) >= 1)
        <div class="winners-section">
            @foreach(array_slice($overallResults, 0, min(3, count($overallResults))) as $winner)
                <div class="winner-box {{ $winner['rank'] === 1 ? 'gold' : ($winner['rank'] === 2 ? 'silver' : 'bronze') }}">
                    <div class="place">
                        @if($winner['rank'] === 1) 🥇 1st Place
                        @elseif($winner['rank'] === 2) 🥈 2nd Place
                        @elseif($winner['rank'] === 3) 🥉 3rd Place
                        @endif
                    </div>
                    <div class="name">#{{ $winner['contestant']->number }} {{ $winner['contestant']->getDisplayName() }}</div>
                    <div style="font-size:11px; color:#666">Grand Total: {{ $winner['grand_total'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Overall Rankings Table --}}
    <table>
        <thead>
            <tr>
                <th style="width:50px">Rank</th>
                <th style="width:40px">#</th>
                <th style="text-align:left">Contestant</th>
                @foreach($contest->exposures as $exp)
                    <th>{{ $exp->name }}@if($exp->is_final) *@endif</th>
                @endforeach
                <th>Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overallResults as $result)
                <tr class="{{ $result['rank'] === 1 ? 'gold' : ($result['rank'] === 2 ? 'silver' : ($result['rank'] === 3 ? 'bronze' : '')) }}">
                    <td class="rank-cell">
                        @if($result['rank'] === 1) 🥇
                        @elseif($result['rank'] === 2) 🥈
                        @elseif($result['rank'] === 3) 🥉
                        @else {{ $result['rank'] }}
                        @endif
                    </td>
                    <td>{{ $result['contestant']->number }}</td>
                    <td class="contestant-name">{{ $result['contestant']->getDisplayName() }}</td>
                    @foreach($contest->exposures as $exp)
                        @php $es = $result['exposure_scores'][$exp->id] ?? null; @endphp
                        <td>
                            @if($es)
                                {{ $es['combined'] }}
                                @if($es['carry_over'] > 0)
                                    <br><small>raw:{{ $es['raw'] }}+carry:{{ $es['carry_over'] }}</small>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                    <td class="total-cell">{{ $result['grand_total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
