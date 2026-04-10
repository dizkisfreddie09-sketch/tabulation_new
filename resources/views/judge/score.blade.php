@extends('layouts.judge')

@section('title', 'Judge ' . $judge->number . ': ' . $judge->name . ' - Scoring')

@section('navbar')
<div class="navbar">
    <div class="brand">Judge Scoring</div>
    <div class="judge-info">
        <span class="badge">Judge #{{ $judge->number }}: {{ $judge->name }}</span>
        <span class="badge">{{ $contest->name }}</span>
        <form action="{{ route('judge.logout') }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm" style="color:#fff; border-color:rgba(255,255,255,0.3)">Logout</button>
        </form>
    </div>
</div>
@endsection

@section('content')
<div class="container container-full">

    @if(!isset($exposure) || !$exposure)
        <div class="card text-center" style="padding:3rem">
            <h2>Waiting for contest to begin</h2>
            <p class="text-muted mt-4">No exposures are configured yet. Please wait for the administrator to set up the contest.</p>
        </div>
    @else
        <div class="tab-bar">
            @foreach($contest->exposures as $exp)
                <a href="{{ route('judge.score', ['exposure' => $exp->id]) }}"
                   class="{{ $exp->id === $exposure->id ? 'active' : '' }} {{ $exp->is_locked ? 'locked' : '' }}">
                    {{ $exp->name }}
                    @if($exp->is_final) <span class="badge badge-yellow" style="margin-left:4px; font-size:0.6rem">FINAL</span> @endif
                    @if($exp->is_locked) Locked @endif
                </a>
            @endforeach
        </div>

        <div class="card" style="padding:1rem 1.25rem">
            <div class="flex justify-between items-center">
                <div>
                    <h2 style="margin-bottom:2px">{{ $exposure->name }}</h2>
                    <div class="text-sm text-muted">
                        {{ $exposure->criteria->count() }} criteria
                        @if($exposure->is_final)
                            · <span class="badge badge-yellow">Final Round - Top {{ $exposure->top_n ?? 'All' }}</span>
                        @endif
                        @if($exposure->is_locked)
                            · <span class="text-success" style="font-weight:600">Scoring Complete</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($exposure->is_locked)
            <div class="card text-center" style="padding:2rem">
                <h3>This exposure is locked</h3>
                <p class="text-muted mt-4">Scoring for "{{ $exposure->name }}" has been finalized by the administrator.</p>
            </div>
        @elseif($exposure->criteria->count() === 0)
            <div class="card text-center" style="padding:2rem">
                <h3>No criteria yet</h3>
                <p class="text-muted mt-4">The administrator hasn't added criteria for this exposure yet.</p>
            </div>
        @else
            <div class="card" style="padding:1rem 1.25rem; margin-bottom:1rem">
                <div class="flex items-center gap-3">
                    <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; margin:0">
                        <input
                            type="checkbox"
                            id="finalScoreOnlyToggle"
                            style="width:18px; height:18px; cursor:pointer"
                        >
                        <span style="font-weight:500">Final Score Only Mode</span>
                    </label>
                    <span class="text-xs text-muted" style="margin-left:auto">
                        Toggle to provide only a final total score instead of individual criteria scores
                    </span>
                </div>
            </div>

            <form action="{{ route('judge.storeScores', $exposure) }}" method="POST" id="scoringForm">
                @csrf
                <input type="hidden" id="useFinalScoreOnly" name="use_final_score_only" value="0">

                @php
                    $contestantGroups = $contest->type === 'double'
                        ? [
                            'Mr Contestants' => $contestants->filter(fn ($contestant) => str_starts_with($contestant->name, 'Mr ')),
                            'Ms Contestants' => $contestants->filter(fn ($contestant) => str_starts_with($contestant->name, 'Ms ')),
                        ]
                        : ['Contestants' => $contestants];
                @endphp

                <div class="{{ $contest->type === 'double' ? 'contestant-columns' : '' }}">
                    @foreach($contestantGroups as $groupLabel => $groupContestants)
                        <div class="{{ $contest->type === 'double' ? 'contestant-column' : '' }}">
                            @if($contest->type === 'double')
                                <div class="contestant-column-title">
                                    <span>{{ $groupLabel }}</span>
                                    <span class="badge badge-blue">{{ $groupContestants->count() }}</span>
                                </div>
                            @endif

                            @foreach($groupContestants as $contestant)
                                <div class="contestant-row" data-contestant-id="{{ $contestant->id }}">
                                    <div class="contestant-header">
                                        <div class="contestant-identity">
                                            <span class="contestant-number">{{ $contestant->number }}</span>
                                            <span class="contestant-name">{{ $contestant->getDisplayName() }}</span>
                                        </div>
                                        <div class="final-score-display contestant-score-summary">
                                            <span style="font-size:0.75rem; color:#666; display:block">Total Score</span>
                                            <span style="font-size:1.5rem; font-weight:700; color:#666" class="final-score-value">-</span>
                                        </div>
                                    </div>

                                    <div class="criteria-grid criteria-inputs" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))">
                                        @foreach($exposure->criteria as $criteria)
                                            @php
                                                $existing = $scores
                                                    ->where('contestant_id', $contestant->id)
                                                    ->where('criteria_id', $criteria->id)
                                                    ->first();
                                            @endphp
                                            <div style="text-align:center">
                                                <label class="text-xs text-muted" style="display:block; margin-bottom:4px">
                                                    {{ $criteria->name }} ({{ $criteria->percentage }}%)
                                                    <br><span style="font-size:0.65rem">{{ $criteria->min_score }}-{{ $criteria->max_score }}</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    name="scores[{{ $contestant->id }}][{{ $criteria->id }}]"
                                                    class="score-input criteria-score"
                                                    data-criteria-id="{{ $criteria->id }}"
                                                    data-percentage="{{ $criteria->percentage }}"
                                                    data-min-score="{{ $criteria->min_score }}"
                                                    data-max-score="{{ $criteria->max_score }}"
                                                    value="{{ $existing ? $existing->score : '' }}"
                                                    min="{{ $criteria->min_score }}"
                                                    max="{{ $criteria->max_score }}"
                                                    step="0.01"
                                                    required
                                                >
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="direct-final-score-section" style="display:none; margin-top:1rem; text-align:center; padding:1rem; background:#fef3c7; border-radius:8px; border:2px dashed #f59e0b">
                                        <label style="display:block; margin-bottom:0.5rem; font-weight:600; color:#92400e">
                                            Final Score <span class="final-score-label">(Optional)</span>
                                        </label>
                                        @php
                                            $finalScoreExisting = $scores
                                                ->where('contestant_id', $contestant->id)
                                                ->where('criteria_id', null)
                                                ->first();
                                        @endphp
                                        <input
                                            type="number"
                                            name="direct_final_score[{{ $contestant->id }}]"
                                            class="score-input direct-final-score"
                                            min="1"
                                            max="100"
                                            step="0.01"
                                            value="{{ $finalScoreExisting ? $finalScoreExisting->score : '' }}"
                                            style="font-size:1.25rem; width:150px; margin:0 auto; display:block"
                                            placeholder="Enter score 1-100"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div style="position:sticky; bottom:0; background:#f0f4ff; padding:1rem 0; text-align:center; border-top:1px solid #e5e7eb; margin-top:1rem">
                    <button type="submit" class="btn btn-success btn-lg" style="min-width:300px; justify-content:center">
                        Submit My Scores
                    </button>
                </div>
            </form>

            <script>
                const toggle = document.getElementById('finalScoreOnlyToggle');
                const form = document.getElementById('scoringForm');
                const useFinalScoreOnlyInput = document.getElementById('useFinalScoreOnly');

                toggle.addEventListener('change', function() {
                    const isFinalScoreOnly = this.checked;
                    useFinalScoreOnlyInput.value = isFinalScoreOnly ? '1' : '0';

                    document.querySelectorAll('[data-contestant-id]').forEach(row => {
                        const criteriaInputs = row.querySelector('.criteria-inputs');
                        const directFinalScoreInput = row.querySelector('.direct-final-score');
                        const directFinalScoreSection = row.querySelector('.direct-final-score-section');
                        const finalScoreLabel = row.querySelector('.final-score-label');
                        const finalScoreDisplay = row.querySelector('.final-score-value');
                        const scoreInputs = row.querySelectorAll('.criteria-score');

                        if (isFinalScoreOnly) {
                            criteriaInputs.style.display = 'none';
                            directFinalScoreSection.style.display = 'block';
                            directFinalScoreSection.style.background = '#dbeafe';
                            directFinalScoreSection.style.borderColor = '#0084ff';
                            finalScoreLabel.textContent = '(Required)';

                            if (!directFinalScoreInput.hasAttribute('readonly')) {
                                directFinalScoreInput.setAttribute('required', 'required');
                            }

                            scoreInputs.forEach(input => {
                                input.removeAttribute('required');
                                input.setAttribute('disabled', 'disabled');
                            });

                            const currentTotal = finalScoreDisplay.textContent;
                            if (currentTotal !== '-' && !directFinalScoreInput.value && !directFinalScoreInput.hasAttribute('readonly')) {
                                directFinalScoreInput.value = currentTotal;
                            }
                        } else {
                            criteriaInputs.style.display = 'grid';
                            directFinalScoreSection.style.display = 'none';
                            finalScoreLabel.textContent = '(Optional)';
                            directFinalScoreInput.removeAttribute('required');
                            scoreInputs.forEach(input => {
                                input.setAttribute('required', 'required');
                                input.removeAttribute('disabled');
                            });
                        }
                    });
                });

                form.addEventListener('submit', function(e) {
                    const isFinalScoreOnly = toggle.checked;
                    let isValid = true;
                    let errorMessage = '';

                    if (isFinalScoreOnly) {
                        document.querySelectorAll('[data-contestant-id]').forEach(row => {
                            const directFinalScoreInput = row.querySelector('.direct-final-score');
                            if (directFinalScoreInput.style.display !== 'none') {
                                if (!directFinalScoreInput.value || directFinalScoreInput.value.trim() === '') {
                                    isValid = false;
                                    errorMessage = 'Please enter a final score for all contestants.';
                                }
                            }
                        });
                    } else {
                        document.querySelectorAll('[data-contestant-id]').forEach(row => {
                            const scoreInputs = row.querySelectorAll('.criteria-score:not(:disabled)');
                            scoreInputs.forEach(input => {
                                if (!input.value || input.value.trim() === '') {
                                    isValid = false;
                                    errorMessage = 'Please fill all criteria scores for all contestants.';
                                }
                            });
                        });
                    }

                    if (!isValid) {
                        e.preventDefault();
                        alert(errorMessage);
                    }
                });

                document.querySelectorAll('.criteria-score').forEach(input => {
                    input.addEventListener('input', function() {
                        const row = this.closest('[data-contestant-id]');
                        const scoreInputs = row.querySelectorAll('.criteria-score');
                        const finalScoreDisplay = row.querySelector('.final-score-value');

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

                        if (allFilled) {
                            finalScoreDisplay.textContent = totalScore.toFixed(2);
                            finalScoreDisplay.style.color = '#10b981';
                        } else {
                            finalScoreDisplay.textContent = '-';
                            finalScoreDisplay.style.color = '#999';
                        }
                    });
                });

                document.querySelectorAll('.criteria-score').forEach(input => {
                    input.dispatchEvent(new Event('input'));
                });

                window.addEventListener('load', function() {
                    const criteriaScoresWithValues = Array.from(document.querySelectorAll('.criteria-score')).filter(input => input.value.trim() !== '').length;
                    const directFinalScoresWithValues = Array.from(document.querySelectorAll('.direct-final-score')).filter(input => input.value.trim() !== '').length;

                    if (directFinalScoresWithValues > 0 && criteriaScoresWithValues === 0) {
                        toggle.checked = true;
                        toggle.disabled = true;
                        toggle.style.opacity = '0.5';
                        toggle.style.cursor = 'not-allowed';

                        document.querySelectorAll('[data-contestant-id]').forEach(row => {
                            const directFinalScoreInput = row.querySelector('.direct-final-score');
                            const finalScoreDisplay = row.querySelector('.final-score-value');

                            if (directFinalScoreInput && directFinalScoreInput.value) {
                                directFinalScoreInput.setAttribute('readonly', 'readonly');
                                directFinalScoreInput.style.backgroundColor = '#f3f4f6';
                                directFinalScoreInput.style.cursor = 'not-allowed';

                                if (finalScoreDisplay) {
                                    finalScoreDisplay.textContent = parseFloat(directFinalScoreInput.value).toFixed(2);
                                    finalScoreDisplay.style.color = '#10b981';
                                }
                            }
                        });

                        toggle.dispatchEvent(new Event('change'));
                    }

                    if (directFinalScoresWithValues > 0) {
                        document.querySelectorAll('.criteria-score').forEach(input => {
                            input.setAttribute('readonly', 'readonly');
                            input.setAttribute('disabled', 'disabled');
                            input.style.backgroundColor = '#f3f4f6';
                            input.style.cursor = 'not-allowed';
                        });
                    }
                });
            </script>
        @endif
    @endif

</div>
@endsection
