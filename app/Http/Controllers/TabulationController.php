<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\Exposure;
use App\Models\Score;
use Illuminate\Http\Request;

class TabulationController extends Controller
{
    public function tabulate(Request $request, Contest $contest)
    {
        $contest->load(['exposures.criteria', 'contestants', 'judges']);

        $exposureId = $request->query('exposure');
        $exposure = $exposureId
            ? $contest->exposures->find($exposureId)
            : $contest->activeExposure() ?? $contest->exposures->first();

        if (!$exposure) {
            return redirect()->route('contests.settings', $contest)->with('error', 'No exposures configured.');
        }

        $exposure->load('criteria');
        $scores = Score::where('exposure_id', $exposure->id)->get();

        // For final exposures with top_n, filter to qualifying contestants
        $contestants = $this->getQualifyingContestants($contest, $exposure);

        return view('contests.tabulate', compact('contest', 'exposure', 'scores', 'contestants'));
    }

    public function storeScores(Request $request, Contest $contest, Exposure $exposure)
    {
        $contest->load(['contestants', 'judges']);
        $exposure->load('criteria');

        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.*.*' => 'required|numeric',
        ]);

        foreach ($validated['scores'] as $contestantId => $judges) {
            $contestant = $contest->contestants->find($contestantId);
            if (!$contestant) continue;

            foreach ($judges as $judgeId => $criteriaScores) {
                $judge = $contest->judges->find($judgeId);
                if (!$judge) continue;

                foreach ($criteriaScores as $criteriaId => $scoreValue) {
                    $criteria = $exposure->criteria->find($criteriaId);
                    if (!$criteria) continue;

                    $scoreValue = max($criteria->min_score, min($criteria->max_score, (float)$scoreValue));

                    Score::updateOrCreate(
                        [
                            'contest_id' => $contest->id,
                            'exposure_id' => $exposure->id,
                            'contestant_id' => $contestantId,
                            'judge_id' => $judgeId,
                            'criteria_id' => $criteriaId,
                        ],
                        ['score' => $scoreValue]
                    );
                }
            }
        }

        return redirect()->route('contests.tabulate', [$contest, 'exposure' => $exposure->id])
            ->with('success', 'Scores saved for "' . $exposure->name . '"!');
    }

    public function lockExposure(Contest $contest, Exposure $exposure)
    {
        $exposure->update(['is_locked' => true]);

        $next = $contest->exposures()
            ->where('sort_order', '>', $exposure->sort_order)
            ->where('is_locked', false)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            return redirect()->route('contests.tabulate', [$contest, 'exposure' => $next->id])
                ->with('success', '"' . $exposure->name . '" locked! Now scoring "' . $next->name . '".');
        }

        return redirect()->route('contests.results', $contest)
            ->with('success', 'All exposures completed! View the final results.');
    }

    public function unlockExposure(Contest $contest, Exposure $exposure)
    {
        $exposure->update(['is_locked' => false]);
        return redirect()->route('contests.tabulate', [$contest, 'exposure' => $exposure->id])
            ->with('success', '"' . $exposure->name . '" unlocked for editing.');
    }

    public function results(Contest $contest)
    {
        $contest->load(['exposures.criteria', 'contestants', 'judges', 'scores']);

        $exposureResults = [];
        foreach ($contest->exposures as $exposure) {
            $exposureResults[$exposure->id] = $this->calculateExposureResults($contest, $exposure);
        }

        $overallResults = $this->calculateOverallResults($contest, $exposureResults);

        return view('contests.results', compact('contest', 'exposureResults', 'overallResults'));
    }

    public function printScores(Request $request, Contest $contest)
    {
        $contest->load(['exposures.criteria', 'contestants', 'judges', 'scores']);

        $exposureId = $request->query('exposure');
        $exposure = $exposureId ? $contest->exposures->find($exposureId) : null;

        if ($exposure) {
            $results = $this->calculateExposureResults($contest, $exposure);
            return view('contests.print-scores', compact('contest', 'exposure', 'results'));
        }

        // Print all exposures
        $allResults = [];
        foreach ($contest->exposures as $exp) {
            $allResults[$exp->id] = $this->calculateExposureResults($contest, $exp);
        }
        return view('contests.print-scores-all', compact('contest', 'allResults'));
    }

    public function printResults(Contest $contest)
    {
        $contest->load(['exposures.criteria', 'contestants', 'judges', 'scores']);

        $exposureResults = [];
        foreach ($contest->exposures as $exposure) {
            $exposureResults[$exposure->id] = $this->calculateExposureResults($contest, $exposure);
        }

        $overallResults = $this->calculateOverallResults($contest, $exposureResults);

        return view('contests.print-results', compact('contest', 'exposureResults', 'overallResults'));
    }

    public function printRankings(Contest $contest)
    {
        $contest->load(['exposures.criteria', 'contestants', 'judges', 'scores']);

        $exposureResults = [];
        foreach ($contest->exposures as $exposure) {
            $exposureResults[$exposure->id] = $this->calculateExposureResults($contest, $exposure);
        }

        $overallResults = $this->calculateOverallResults($contest, $exposureResults);

        return view('contests.print-rankings', compact('contest', 'overallResults'));
    }

    public function complete(Contest $contest)
    {
        $contest->update(['status' => 'completed']);
        return redirect()->route('contests.results', $contest)->with('success', 'Contest marked as completed!');
    }

    // ---- Calculation helpers ----

    private function getQualifyingContestants(Contest $contest, Exposure $exposure)
    {
        if (!$exposure->is_final || !$exposure->top_n) {
            return $contest->contestants;
        }

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

    private function calculateExposureResults(Contest $contest, Exposure $exposure): array
    {
        $results = [];
        $exposureScores = $contest->scores->where('exposure_id', $exposure->id);

        // Use qualifying contestants for final rounds with top_n
        $contestants = $this->getQualifyingContestants($contest, $exposure);

        foreach ($contestants as $contestant) {
            $data = [
                'contestant' => $contestant,
                'criteria_scores' => [],
                'judge_totals' => [],
                'total_weighted' => 0,
                'rank' => 0,
            ];

            // Check if this judge uses final score only mode
            foreach ($exposure->criteria as $criteria) {
                $judgeScores = [];
                foreach ($contest->judges as $judge) {
                    // Check if judge used final score only mode
                    $finalScoreRecord = $exposureScores
                        ->where('contestant_id', $contestant->id)
                        ->where('judge_id', $judge->id)
                        ->where('is_final_score_only', true)
                        ->first();

                    if ($finalScoreRecord) {
                        // Judge used final score only mode - use their final score
                        $judgeScores[$judge->id] = (float)$finalScoreRecord->score;
                    } else {
                        // Judge used criteria mode - get criteria score
                        $score = $exposureScores
                            ->where('contestant_id', $contestant->id)
                            ->where('judge_id', $judge->id)
                            ->where('criteria_id', $criteria->id)
                            ->first();
                        $judgeScores[$judge->id] = $score ? (float)$score->score : null;
                    }
                }

                $validScores = array_filter($judgeScores, fn($v) => $v !== null);
                $average = count($validScores) > 0 ? array_sum($validScores) / count($validScores) : 0;
                $weighted = $average * ($criteria->percentage / 100);

                $data['criteria_scores'][$criteria->id] = [
                    'average' => round($average, 2),
                    'weighted' => round($weighted, 2),
                    'judges' => $judgeScores,
                ];

                $data['total_weighted'] += $weighted;
            }

            // Per-judge weighted totals
            foreach ($contest->judges as $judge) {
                // Check if judge used final score only mode
                $finalScoreRecord = $exposureScores
                    ->where('contestant_id', $contestant->id)
                    ->where('judge_id', $judge->id)
                    ->where('is_final_score_only', true)
                    ->first();

                if ($finalScoreRecord) {
                    // Use the final score directly as the judge's total
                    $data['judge_totals'][$judge->id] = round((float)$finalScoreRecord->score, 2);
                } else {
                    // Calculate from criteria scores
                    $jTotal = 0;
                    foreach ($exposure->criteria as $criteria) {
                        $s = $exposureScores
                            ->where('contestant_id', $contestant->id)
                            ->where('judge_id', $judge->id)
                            ->where('criteria_id', $criteria->id)
                            ->first();
                        if ($s) {
                            $jTotal += (float)$s->score * ($criteria->percentage / 100);
                        }
                    }
                    $data['judge_totals'][$judge->id] = round($jTotal, 2);
                }
            }

            $data['total_weighted'] = round($data['total_weighted'], 2);
            $results[] = $data;
        }

        usort($results, fn($a, $b) => $b['total_weighted'] <=> $a['total_weighted']);

        $rank = 1;
        foreach ($results as &$r) {
            $r['rank'] = $rank++;
        }

        return $results;
    }

    private function calculateOverallResults(Contest $contest, array $exposureResults): array
    {
        $totals = []; // contestant_id => ['contestant' => ..., 'exposure_scores' => [...], 'grand_total' => 0]

        foreach ($contest->contestants as $contestant) {
            $totals[$contestant->id] = [
                'contestant' => $contestant,
                'exposure_scores' => [],
                'grand_total' => 0,
            ];
        }

        foreach ($contest->exposures as $exposure) {
            $results = $exposureResults[$exposure->id] ?? [];

            foreach ($results as $r) {
                $cid = $r['contestant']->id;
                $exposureTotal = $r['total_weighted'];

                // For final rounds: carry-over is based on the AVERAGE of prior exposures
                if ($exposure->is_final && $exposure->carry_over_percentage > 0) {
                    $priorSum = 0;
                    $priorCount = 0;
                    foreach ($contest->exposures as $priorExposure) {
                        if ($priorExposure->id === $exposure->id) break;
                        if ($priorExposure->is_final) continue;
                        $priorSum += $totals[$cid]['exposure_scores'][$priorExposure->id]['raw'] ?? 0;
                        $priorCount++;
                    }
                    $priorAverage = $priorCount > 0 ? $priorSum / $priorCount : 0;
                    $carryOver = $priorAverage * ($exposure->carry_over_percentage / 100);
                    $totals[$cid]['exposure_scores'][$exposure->id] = [
                        'raw' => $exposureTotal,
                        'carry_over' => round($carryOver, 2),
                        'combined' => round($exposureTotal + $carryOver, 2),
                        'rank' => $r['rank'],
                    ];
                } else {
                    $totals[$cid]['exposure_scores'][$exposure->id] = [
                        'raw' => $exposureTotal,
                        'carry_over' => 0,
                        'combined' => $exposureTotal,
                        'rank' => $r['rank'],
                    ];
                }
            }
        }

        // Grand total = sum of all exposure combined scores
        foreach ($totals as &$t) {
            $t['grand_total'] = round(array_sum(array_column($t['exposure_scores'], 'combined')), 2);
        }

        // Sort by grand total descending
        uasort($totals, fn($a, $b) => $b['grand_total'] <=> $a['grand_total']);

        $rank = 1;
        foreach ($totals as &$t) {
            $t['rank'] = $rank++;
        }

        return array_values($totals);
    }
}
