@extends('layouts.app')

@section('title', 'Results: ' . $contest->name)

@section('content')
<div class="container-wide">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1>{{ $contest->name }} — Results</h1>
            <p class="text-muted text-sm">{{ $contest->getTypeLabel() }} · {{ $contest->contestants->count() }} contestants · {{ $contest->judges->count() }} judges · {{ $contest->exposures->count() }} exposures</p>
        </div>
        <div class="flex gap-2 no-print">
            @if($contest->status === 'active')
                <a href="{{ route('contests.tabulate', $contest) }}" class="btn btn-primary">← Back to Scoring</a>
                <form action="{{ route('contests.complete', $contest) }}" method="POST" class="inline-form" onsubmit="return confirm('Mark this contest as completed?')">
                    @csrf
                    <button type="submit" class="btn btn-success">✓ Mark Completed</button>
                </form>
            @endif
            <a href="{{ route('contests.print-results', $contest) }}" class="btn btn-outline" target="_blank">🖨 Print Results</a>
            <a href="{{ route('contests.print-rankings', $contest) }}" class="btn btn-outline" target="_blank">🖨 Print Rankings</a>
            <a href="{{ route('contests.print-scores', $contest) }}" class="btn btn-outline" target="_blank">🖨 Print All Scores</a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline">Dashboard</a>
        </div>
    </div>

    {{-- Overall Rankings --}}
    <div class="card">
        <div class="card-header">
            <h2 style="margin-bottom:0">🏆 Overall Rankings</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>#</th>
                    <th>Contestant</th>
                    @foreach($contest->exposures as $exp)
                        <th class="text-center">
                            {{ $exp->name }}
                            @if($exp->is_final) <span class="badge badge-yellow" style="font-size:0.6rem">F</span> @endif
                        </th>
                    @endforeach
                    <th class="text-center" style="background:#eef2ff">Grand Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overallResults as $result)
                    <tr class="{{ $result['rank'] <= 3 ? 'rank-' . $result['rank'] : '' }}">
                        <td>
                            @if($result['rank'] === 1) 🥇
                            @elseif($result['rank'] === 2) 🥈
                            @elseif($result['rank'] === 3) 🥉
                            @else {{ $result['rank'] }}
                            @endif
                        </td>
                        <td>{{ $result['contestant']->number }}</td>
                        <td><strong>{{ $result['contestant']->getDisplayName() }}</strong></td>
                        @foreach($contest->exposures as $exp)
                            @php $es = $result['exposure_scores'][$exp->id] ?? null; @endphp
                            <td class="text-center">
                                @if($es)
                                    {{ $es['combined'] }}
                                    @if($es['carry_over'] > 0)
                                        <br><span class="text-xs text-muted">raw: {{ $es['raw'] }} + carry: {{ $es['carry_over'] }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center" style="background:#eef2ff; font-weight:700; font-size:1rem">{{ $result['grand_total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Per-Exposure Detailed Results --}}
    @foreach($contest->exposures as $exposure)
        @php $results = $exposureResults[$exposure->id] ?? []; @endphp
        <div class="card">
            <div class="card-header">
                <h2 style="margin-bottom:0">
                    {{ $exposure->name }}
                    @if($exposure->is_final) <span class="badge badge-yellow">Final</span> @endif
                    @if($exposure->is_locked) <span class="badge badge-green">Locked</span> @endif
                </h2>
                <a href="{{ route('contests.print-scores', [$contest, 'exposure' => $exposure->id]) }}" class="btn btn-outline btn-sm no-print" target="_blank">🖨 Print</a>
            </div>

            {{-- Rankings for this exposure --}}
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>#</th>
                        <th>Contestant</th>
                        @foreach($exposure->criteria as $criteria)
                            <th class="text-center">{{ $criteria->name }}<br><span class="text-xs">({{ $criteria->percentage }}%)</span></th>
                        @endforeach
                        <th class="text-center">Weighted Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $r)
                        <tr class="{{ $r['rank'] <= 3 ? 'rank-' . $r['rank'] : '' }}">
                            <td>
                                @if($r['rank'] === 1) 🥇
                                @elseif($r['rank'] === 2) 🥈
                                @elseif($r['rank'] === 3) 🥉
                                @else {{ $r['rank'] }}
                                @endif
                            </td>
                            <td>{{ $r['contestant']->number }}</td>
                            <td><strong>{{ $r['contestant']->getDisplayName() }}</strong></td>
                            @foreach($exposure->criteria as $criteria)
                                <td class="text-center">
                                    {{ $r['criteria_scores'][$criteria->id]['average'] ?? '—' }}
                                    <br><span class="text-xs text-muted">w: {{ $r['criteria_scores'][$criteria->id]['weighted'] ?? '—' }}</span>
                                </td>
                            @endforeach
                            <td class="text-center"><strong>{{ $r['total_weighted'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Per-Judge Breakdown --}}
            @if($exposure->criteria->count() > 0)
                <details class="mt-4" style="padding:0 0.75rem 0.75rem">
                    <summary style="cursor:pointer; font-weight:600; color:#6b7280; font-size:0.875rem; padding:0.5rem 0">📋 Show Detailed Scores by Judge</summary>
                    <div style="overflow-x:auto; margin-top:0.5rem">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Contestant</th>
                                    @foreach($contest->judges as $judge)
                                        <th class="text-center" colspan="{{ $exposure->criteria->count() }}">J{{ $judge->number }}: {{ $judge->name }}</th>
                                        <th class="text-center" style="background:#e5e7eb">Total</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    @foreach($contest->judges as $judge)
                                        @foreach($exposure->criteria as $criteria)
                                            <th class="text-center text-xs">{{ $criteria->name }}</th>
                                        @endforeach
                                        <th style="background:#e5e7eb"></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $r)
                                    <tr>
                                        <td>{{ $r['contestant']->number }}</td>
                                        <td>{{ $r['contestant']->getDisplayName() }}</td>
                                        @foreach($contest->judges as $judge)
                                            @foreach($exposure->criteria as $criteria)
                                                <td class="text-center">
                                                    {{ $r['criteria_scores'][$criteria->id]['judges'][$judge->id] ?? '—' }}
                                                </td>
                                            @endforeach
                                            <td class="text-center" style="background:#f9fafb; font-weight:600">
                                                {{ $r['judge_totals'][$judge->id] ?? '—' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </div>
    @endforeach
</div>
@endsection