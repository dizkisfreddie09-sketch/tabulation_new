<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\Exposure;
use App\Models\Judge;
use App\Models\Score;
use Illuminate\Http\Request;

class JudgeScoringController extends Controller
{
    public function login()
    {
        return view('judge.login');
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'access_code' => 'required|string',
        ]);

        $judge = Judge::where('access_code', $validated['access_code'])->first();

        if (!$judge) {
            return back()->with('error', 'Invalid access code.')->withInput();
        }

        $contest = $judge->contest;
        if ($contest->status !== 'active') {
            return back()->with('error', 'This contest is not active yet.');
        }

        $request->session()->put('judge_id', $judge->id);
        $request->session()->put('judge_contest_id', $contest->id);

        return redirect()->route('judge.score');
    }

    public function score(Request $request)
    {
        $judge = $this->getAuthenticatedJudge($request);
        if (!$judge) {
            return redirect()->route('judge.login')->with('error', 'Please log in first.');
        }

        $contest = $judge->contest;
        $contest->load(['exposures.criteria', 'contestants']);

        $exposureId = $request->query('exposure');
        $exposure = $exposureId
            ? $contest->exposures->find($exposureId)
            : $contest->activeExposure() ?? $contest->exposures->first();

        if (!$exposure) {
            return view('judge.score', compact('judge', 'contest', 'exposure'))
                ->with('error', 'No exposures configured yet.');
        }

        $exposure->load('criteria');

        // For final exposures with top_n, determine qualifying contestants
        $contestants = $this->getQualifyingContestants($contest, $exposure);

        $scores = Score::where('exposure_id', $exposure->id)
            ->where('judge_id', $judge->id)
            ->get();

        return view('judge.score', compact('judge', 'contest', 'exposure', 'contestants', 'scores'));
    }

    public function storeScores(Request $request, Exposure $exposure)
    {
        $judge = $this->getAuthenticatedJudge($request);
        if (!$judge) {
            return redirect()->route('judge.login')->with('error', 'Please log in first.');
        }

        $contest = $judge->contest;
        if ($exposure->contest_id !== $contest->id) {
            abort(403);
        }

        if ($exposure->is_locked) {
            return redirect()->route('judge.score', ['exposure' => $exposure->id])
                ->with('error', 'This exposure is locked. Scores cannot be changed.');
        }

        $exposure->load('criteria');

        $useFinalScoreOnly = (bool) $request->input('use_final_score_only', false);
        $contestants = $this->getQualifyingContestants($contest, $exposure);
        $contestantIds = $contestants->pluck('id')->toArray();

        if ($useFinalScoreOnly) {
            // Final Score Only Mode - use direct_final_score field
            $validated = $request->validate([
                'direct_final_score' => 'required|array',
                'direct_final_score.*' => 'required|numeric|min:1|max:100',
            ]);

            foreach ($validated['direct_final_score'] as $contestantId => $finalScore) {
                if (!in_array((int)$contestantId, $contestantIds)) continue;

                // Delete any existing criteria-based scores for this contestant
                Score::where('contest_id', $contest->id)
                    ->where('exposure_id', $exposure->id)
                    ->where('contestant_id', $contestantId)
                    ->where('judge_id', $judge->id)
                    ->where('is_final_score_only', false)
                    ->delete();

                // Save final score
                Score::updateOrCreate(
                    [
                        'contest_id' => $contest->id,
                        'exposure_id' => $exposure->id,
                        'contestant_id' => $contestantId,
                        'judge_id' => $judge->id,
                        'criteria_id' => null,
                    ],
                    [
                        'score' => (float)$finalScore,
                        'is_final_score_only' => true,
                    ]
                );
            }
        } else {
            // Criteria-Based Scoring Mode
            $validated = $request->validate([
                'scores' => 'required|array',
                'scores.*' => 'required|array',
                'direct_final_score' => 'nullable|array',
                'direct_final_score.*' => 'nullable|numeric|min:1|max:100',
            ]);

            foreach ($validated['scores'] as $contestantId => $criteriaScores) {
                if (!in_array((int)$contestantId, $contestantIds)) continue;

                foreach ($criteriaScores as $criteriaId => $scoreValue) {
                    $criteria = $exposure->criteria->find($criteriaId);
                    if (!$criteria) continue;

                    $scoreValue = max($criteria->min_score, min($criteria->max_score, (float)$scoreValue));

                    Score::updateOrCreate(
                        [
                            'contest_id' => $contest->id,
                            'exposure_id' => $exposure->id,
                            'contestant_id' => $contestantId,
                            'judge_id' => $judge->id,
                            'criteria_id' => $criteriaId,
                        ],
                        [
                            'score' => $scoreValue,
                            'is_final_score_only' => false,
                        ]
                    );
                }
            }

            // Handle optional direct final score input
            if (!empty($validated['direct_final_score'])) {
                foreach ($validated['direct_final_score'] as $contestantId => $finalScore) {
                    if (!in_array((int)$contestantId, $contestantIds) || !$finalScore) continue;

                    // Save the direct final score
                    Score::updateOrCreate(
                        [
                            'contest_id' => $contest->id,
                            'exposure_id' => $exposure->id,
                            'contestant_id' => $contestantId,
                            'judge_id' => $judge->id,
                            'criteria_id' => null,
                        ],
                        [
                            'score' => (float)$finalScore,
                            'is_final_score_only' => false, // Marked as non-exclusive so criteria can coexist
                        ]
                    );
                }
            }

            // Delete any final score entries if direct final score is not provided
            if (empty($validated['direct_final_score'])) {
                foreach ($contestantIds as $contestantId) {
                    Score::where('contest_id', $contest->id)
                        ->where('exposure_id', $exposure->id)
                        ->where('contestant_id', $contestantId)
                        ->where('judge_id', $judge->id)
                        ->where('criteria_id', null)
                        ->delete();
                }
            }
        }

        return redirect()->route('judge.score', ['exposure' => $exposure->id])
            ->with('success', 'Your scores have been saved!');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['judge_id', 'judge_contest_id']);
        return redirect()->route('judge.login')->with('success', 'Logged out successfully.');
    }

    // --- Helpers ---

    private function getAuthenticatedJudge(Request $request): ?Judge
    {
        $judgeId = $request->session()->get('judge_id');
        if (!$judgeId) return null;

        return Judge::with('contest')->find($judgeId);
    }

    private function getQualifyingContestants(Contest $contest, Exposure $exposure)
    {
        $contest->load('contestants');

        if (!$exposure->is_final || !$exposure->top_n) {
            return $contest->contestants;
        }

        // Calculate averaged scores from prior non-final exposures to determine top N
        $contest->load(['scores', 'exposures.criteria']);
        $priorExposures = $contest->exposures->where('is_final', false);
        $exposureCount = $priorExposures->count();

        if ($exposureCount === 0) {
            return $contest->contestants;
        }

        $contestantTotals = [];
        foreach ($contest->contestants as $contestant) {
            $total = 0;
            foreach ($priorExposures as $priorExp) {
                foreach ($priorExp->criteria as $criteria) {
                    $scores = $contest->scores
                        ->where('exposure_id', $priorExp->id)
                        ->where('contestant_id', $contestant->id)
                        ->where('criteria_id', $criteria->id);
                    $validScores = $scores->pluck('score')->filter(fn($v) => $v !== null);
                    $avg = $validScores->count() > 0 ? $validScores->avg() : 0;
                    $total += $avg * ($criteria->percentage / 100);
                }
            }
            // Average across all prior exposures
            $contestantTotals[$contestant->id] = round($total / $exposureCount, 2);
        }

        arsort($contestantTotals);
        $topIds = array_slice(array_keys($contestantTotals), 0, $exposure->top_n);

        return $contest->contestants->whereIn('id', $topIds)->sortBy('number');
    }
}
