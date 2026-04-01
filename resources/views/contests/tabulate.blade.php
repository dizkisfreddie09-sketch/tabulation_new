@extends('layouts.app')

@section('title', 'Tabulate: ' . $contest->name . ' — ' . $exposure->name)

@section('content')
<div class="container-wide">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1>{{ $contest->name }}</h1>
            <p class="text-muted text-sm">{{ $contest->getTypeLabel() }} · Scoring Sheet</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('contests.results', $contest) }}" class="btn btn-primary">📊 Results</a>
            <a href="{{ route('contests.print-scores', [$contest, 'exposure' => $exposure->id]) }}" class="btn btn-outline" target="_blank">🖨 Print This Exposure</a>
            <a href="{{ route('contests.settings', $contest) }}" class="btn btn-outline">⚙ Settings</a>
        </div>
    </div>

    {{-- Exposure Tabs --}}
    <div class="tab-bar">
        @foreach($contest->exposures as $exp)
            <a href="{{ route('contests.tabulate', [$contest, 'exposure' => $exp->id]) }}"
               class="{{ $exp->id === $exposure->id ? 'active' : '' }}">
                {{ $exp->name }}
                @if($exp->is_final) <span class="badge badge-yellow" style="margin-left:4px">F</span> @endif
                @if($exp->is_locked) 🔒 @endif
            </a>
        @endforeach
    </div>

    {{-- Current Exposure Info --}}
    <div class="card" style="padding:1rem 1.5rem">
        <div class="flex justify-between items-center">
            <div>
                <h2 style="margin-bottom:2px">{{ $exposure->name }}</h2>
                <div class="text-sm text-muted">
                    @if($exposure->is_final)
                        Final Round · Top {{ $exposure->top_n ?? 'All' }} · Carry-over: {{ $exposure->carry_over_percentage }}% from prior exposures
                    @else
                        {{ $exposure->criteria->count() }} criteria · Total: {{ $exposure->criteria->sum('percentage') }}%
                    @endif
                    @if($exposure->is_locked)
                        · <span class="text-success" style="font-weight:600">🔒 Scoring Locked</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @if($exposure->is_locked)
                    <form action="{{ route('contests.exposures.unlock', [$contest, $exposure]) }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Unlock this exposure for editing?')">🔓 Unlock</button>
                    </form>
                @else
                    <form action="{{ route('contests.exposures.lock', [$contest, $exposure]) }}" method="POST" class="inline-form">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Lock this exposure? This finalizes scores and moves to next exposure.')">🔒 Lock & Next</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if($exposure->criteria->count() === 0 && !$exposure->is_final)
        <div class="card">
            <div class="empty-state">
                <p>No criteria for this exposure. <a href="{{ route('contests.settings', $contest) }}" style="color:#3b82f6; text-decoration:underline">Go to Settings</a> to add criteria.</p>
            </div>
        </div>
    @else
        <form action="{{ route('contests.scores.store', [$contest, $exposure]) }}" method="POST">
            @csrf

            @foreach($contest->judges as $judge)
                <div class="card">
                    <div class="card-header">
                        <h2 style="margin-bottom:0">Judge {{ $judge->number }}: {{ $judge->name }}</h2>
                    </div>

                    @php
                        // Check if this judge is using final score only mode
                        $judgeScores = $scores->where('judge_id', $judge->id);
                        $useFinalScoreOnly = $judgeScores->where('is_final_score_only', true)->count() > 0;
                    @endphp

                    <div style="overflow-x:auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Contestant</th>
                                    @foreach($exposure->criteria as $criteria)
                                        <th class="text-center">
                                            {{ $criteria->name }}<br>
                                            <span class="text-xs text-muted">({{ $criteria->percentage }}%) {{ $criteria->min_score }}–{{ $criteria->max_score }}</span>
                                        </th>
                                    @endforeach
                                    <th class="text-center" style="background:#eef2ff; font-weight:600">Total</th>
                                    @if($useFinalScoreOnly)
                                        <th class="text-center" style="background:#fef3c7; font-weight:600">Final Score</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contestants as $contestant)
                                    <tr>
                                        <td>{{ $contestant->number }}</td>
                                        <td>{{ $contestant->getDisplayName() }}</td>
                                        @foreach($exposure->criteria as $criteria)
                                            @php
                                                $existing = $judgeScores
                                                    ->where('contestant_id', $contestant->id)
                                                    ->where('criteria_id', $criteria->id)
                                                    ->first();
                                                
                                                // Check if judge has a final score (which means criteria should be disabled)
                                                $hasFinalScore = $judgeScores
                                                    ->where('contestant_id', $contestant->id)
                                                    ->where('criteria_id', null)
                                                    ->count() > 0;
                                            @endphp
                                            <td class="text-center">
                                                <input
                                                    type="number"
                                                    name="scores[{{ $contestant->id }}][{{ $judge->id }}][{{ $criteria->id }}]"
                                                    class="score-input criteria-score-tabulate"
                                                    data-criteria-id="{{ $criteria->id }}"
                                                    data-percentage="{{ $criteria->percentage }}"
                                                    value="{{ $existing ? $existing->score : '' }}"
                                                    min="{{ $criteria->min_score }}"
                                                    max="{{ $criteria->max_score }}"
                                                    step="0.01"
                                                    {{ $useFinalScoreOnly || $hasFinalScore ? 'disabled' : ($exposure->is_locked ? 'readonly' : '') }}
                                                    style="{{ $useFinalScoreOnly || $hasFinalScore ? 'background-color:#f3f4f6; cursor:not-allowed;' : '' }}"
                                                >
                                            </td>
                                        @endforeach
                                        <td class="text-center" style="background:#eef2ff; font-weight:600">
                                            <span class="total-score-display" data-contestant-id="{{ $contestant->id }}" data-judge-id="{{ $judge->id }}" style="font-size:1rem">—</span>
                                        </td>
                                        @if($useFinalScoreOnly)
                                            @php
                                                $finalScore = $judgeScores
                                                    ->where('contestant_id', $contestant->id)
                                                    ->where('is_final_score_only', true)
                                                    ->first();
                                            @endphp
                                            <td class="text-center" style="background:#fef3c7">
                                                <input
                                                    type="number"
                                                    name="final_scores[{{ $contestant->id }}][{{ $judge->id }}]"
                                                    class="score-input"
                                                    value="{{ $finalScore ? $finalScore->score : '' }}"
                                                    min="1"
                                                    max="100"
                                                    step="0.01"
                                                    {{ $exposure->is_locked || $finalScore ? 'disabled' : 'required' }}
                                                    style="font-weight:600{{ $finalScore ? '; background-color:#f3f4f6; cursor:not-allowed;' : '' }}"
                                                >
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            @if(!$exposure->is_locked)
                <div class="flex gap-2 mb-6">
                    <button type="submit" class="btn btn-success btn-lg">💾 Save Scores for "{{ $exposure->name }}"</button>
                    <a href="{{ route('contests.results', $contest) }}" class="btn btn-primary btn-lg">📊 View Results</a>
                </div>
            @endif
        </form>
    @endif
</div>

<script>
    // Auto-calculate totals in tabulate view
    document.querySelectorAll('.criteria-score-tabulate').forEach(input => {
        input.addEventListener('input', function() {
            // Skip calculation if this input is disabled
            if (this.disabled) return;

            // Find the row this input belongs to
            const row = this.closest('tr');
            const scoreInputs = row.querySelectorAll('.criteria-score-tabulate:not(:disabled)');
            const totalDisplay = row.querySelector('.total-score-display');

            if (!totalDisplay) return;

            // Check if all non-disabled inputs have values
            let allFilled = true;
            let totalScore = 0;

            scoreInputs.forEach(input => {
                if (input.value === '') {
                    allFilled = false;
                } else {
                    const percentage = parseFloat(input.dataset.percentage);
                    const score = parseFloat(input.value);
                    totalScore += (score * percentage) / 100;
                }
            });

            // Display total if all criteria are filled
            if (allFilled && scoreInputs.length > 0) {
                totalDisplay.textContent = totalScore.toFixed(2);
                totalDisplay.style.color = '#10b981';
                totalDisplay.style.fontWeight = '700';
            } else {
                totalDisplay.textContent = '—';
                totalDisplay.style.color = '#999';
                totalDisplay.style.fontWeight = '400';
            }
        });

        // Trigger calculation on page load
        input.dispatchEvent(new Event('input'));
    });
</script>
@endsection