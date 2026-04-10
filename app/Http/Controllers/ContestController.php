<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\Criteria;
use App\Models\Contestant;
use App\Models\Exposure;
use App\Models\Judge;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContestController extends Controller
{
    public function create()
    {
        return view('contests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['single', 'double', 'group'])],
            'judge_count' => 'required|integer|min:1|max:20',
            'contestant_count' => 'required_if:type,single,group|nullable|integer|min:0|max:200',
            'male_count' => 'required_if:type,double|nullable|integer|min:0|max:200',
            'female_count' => 'required_if:type,double|nullable|integer|min:0|max:200',
        ]);

        if ($validated['type'] === 'double') {
            $validated['contestant_count'] = 0;
            $validated['male_count'] = $validated['male_count'] ?? 0;
            $validated['female_count'] = $validated['female_count'] ?? 0;
        } else {
            $validated['contestant_count'] = $validated['contestant_count'] ?? 0;
            $validated['male_count'] = 0;
            $validated['female_count'] = 0;
        }

        $contest = Contest::create($validated);

        $this->createContestantsUpTo($contest);
        $this->createJudgesUpTo($contest, $validated['judge_count']);

        return redirect()->route('contests.settings', $contest)->with('success', 'Contest created! Now set up your exposures and criteria.');
    }

    public function settings(Contest $contest)
    {
        $contest->load(['exposures.criteria', 'contestants', 'judges']);
        return view('contests.settings', compact('contest'));
    }

    public function update(Request $request, Contest $contest)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['single', 'double', 'group'])],
            'judge_count' => 'required|integer|min:1|max:20',
            'contestant_count' => 'required_if:type,single,group|nullable|integer|min:0|max:200',
            'male_count' => 'required_if:type,double|nullable|integer|min:0|max:200',
            'female_count' => 'required_if:type,double|nullable|integer|min:0|max:200',
            'tabulator_name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'pageant_logo' => 'nullable|image|max:2048',
        ]);

        if ($validated['type'] === 'double') {
            $validated['contestant_count'] = 0;
            $validated['male_count'] = $validated['male_count'] ?? 0;
            $validated['female_count'] = $validated['female_count'] ?? 0;
        } else {
            $validated['contestant_count'] = $validated['contestant_count'] ?? 0;
            $validated['male_count'] = 0;
            $validated['female_count'] = 0;
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        } else {
            unset($validated['logo']);
        }

        if ($request->boolean('remove_logo') && $contest->logo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($contest->logo);
            $validated['logo'] = null;
        }

        if ($request->hasFile('pageant_logo')) {
            $validated['pageant_logo'] = $request->file('pageant_logo')->store('logos', 'public');
        } else {
            unset($validated['pageant_logo']);
        }

        if ($request->boolean('remove_pageant_logo') && $contest->pageant_logo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($contest->pageant_logo);
            $validated['pageant_logo'] = null;
        }

        $oldContestants = $contest->contestants()->count();
        $contest->update($validated);

        $this->createContestantsUpTo($contest);

        return redirect()->route('contests.settings', $contest)->with('success', 'Contest updated!');
    }

    public function destroy(Contest $contest)
    {
        $contest->delete();
        return redirect()->route('dashboard')->with('success', 'Contest deleted.');
    }

    // --- Exposures ---

    public function storeExposure(Request $request, Contest $contest)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_final' => 'sometimes|boolean',
            'carry_over_percentage' => 'required_if:is_final,1|nullable|numeric|min:0|max:100',
            'top_n' => 'nullable|integer|min:1',
        ]);

        $validated['sort_order'] = $contest->exposures()->count();
        $validated['is_final'] = $request->boolean('is_final');
        $validated['carry_over_percentage'] = $validated['is_final'] ? ($validated['carry_over_percentage'] ?? 0) : 0;
        $validated['top_n'] = $validated['is_final'] ? ($validated['top_n'] ?? null) : null;

        $contest->exposures()->create($validated);

        return redirect()->route('contests.settings', $contest)->with('success', 'Exposure added!');
    }

    public function destroyExposure(Contest $contest, Exposure $exposure)
    {
        $exposure->delete();
        return redirect()->route('contests.settings', $contest)->with('success', 'Exposure removed.');
    }

    // --- Criteria (now per exposure) ---

    public function storeCriteria(Request $request, Contest $contest, Exposure $exposure)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0.01|max:100',
            'min_score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|gt:min_score',
        ]);

        $validated['sort_order'] = $exposure->criteria()->count();
        $exposure->criteria()->create($validated);

        return redirect()->route('contests.settings', $contest)->with('success', 'Criteria added!');
    }

    public function destroyCriteria(Contest $contest, Exposure $exposure, Criteria $criterion)
    {
        $criterion->delete();
        return redirect()->route('contests.settings', $contest)->with('success', 'Criteria removed.');
    }

    // --- Contestants ---

    public function storeContestant(Request $request, Contest $contest)
    {
        if ($contest->type === 'double') {
            $validated = $request->validate([
                'gender' => 'required|in:male,female',
                'name' => 'nullable|string|max:255',
            ]);

            $validated['contest_id'] = $contest->id;
            $validated['number'] = $contest->nextContestantNumber($validated['gender']);

            $validated['name'] = $validated['name'] ?? ($validated['gender'] === 'male'
                ? 'Mr ' . $validated['number']
                : 'Ms ' . $validated['number']);

            $validated['name2'] = null;
            $validated['team_name'] = null;
        } elseif ($contest->type === 'group') {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'team_name' => 'required|string|max:255',
            ]);
            $validated['contest_id'] = $contest->id;
            $validated['number'] = ($contest->contestants()->max('number') ?? 0) + 1;
            $validated['name2'] = null;
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $validated['contest_id'] = $contest->id;
            $validated['number'] = ($contest->contestants()->max('number') ?? 0) + 1;
            $validated['name2'] = null;
            $validated['team_name'] = null;
        }

        Contestant::create($validated);

        return redirect()->route('contests.settings', $contest)->with('success', 'Contestant added!');
    }

    public function destroyContestant(Contest $contest, Contestant $contestant)
    {
        $contestant->delete();
        return redirect()->route('contests.settings', $contest)->with('success', 'Contestant removed.');
    }

    // --- Judges ---

    public function storeJudge(Request $request, Contest $contest)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        $currentCount = $contest->judges()->count();
        if ($currentCount >= $contest->judge_count) {
            return redirect()->route('contests.settings', $contest)
                ->with('error', "Maximum of {$contest->judge_count} judges reached.");
        }

        $validated['number'] = $currentCount + 1;
        $validated['name'] = $validated['name'] ?? "Judge {$validated['number']}";
        $validated['access_code'] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $contest->judges()->create($validated);

        return redirect()->route('contests.settings', $contest)->with('success', 'Judge added!');
    }

    public function destroyJudge(Contest $contest, Judge $judge)
    {
        $judge->delete();
        return redirect()->route('contests.settings', $contest)->with('success', 'Judge removed.');
    }

    private function populateContestantNames(Contest $contest, array &$validated): void
    {
        $number = $validated['number'];

        if ($contest->type === 'double') {
            $validated['name'] = $validated['name'] ?? "Mr {$number}";
            $validated['name2'] = $validated['name2'] ?? "Ms {$number}";
            $validated['team_name'] = null;
            return;
        }

        if ($contest->type === 'group') {
            $validated['name'] = $validated['name'] ?? "Representative {$number}";
            $validated['team_name'] = $validated['team_name'] ?? "Team {$number}";
            $validated['name2'] = null;
            return;
        }

        $validated['name'] = $validated['name'] ?? "Contestant {$number}";
        $validated['name2'] = null;
        $validated['team_name'] = null;
    }

    private function createContestantsUpTo(Contest $contest): void
    {
        $records = [];

        if ($contest->type === 'double') {
            $currentMale = $contest->maleContestantCount();
            $currentFemale = $contest->femaleContestantCount();

            for ($i = $currentMale + 1; $i <= $contest->male_count; $i++) {
                $records[] = [
                    'contest_id' => $contest->id,
                    'number' => $i,
                    'name' => "Mr {$i}",
                    'name2' => null,
                    'team_name' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            for ($i = $currentFemale + 1; $i <= $contest->female_count; $i++) {
                $records[] = [
                    'contest_id' => $contest->id,
                    'number' => $i,
                    'name' => "Ms {$i}",
                    'name2' => null,
                    'team_name' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } else {
            $currentMaxNumber = $contest->contestants()->max('number') ?? 0;
            $currentNumber = $currentMaxNumber;
            $desiredCount = $contest->contestant_count;

            for ($number = $currentNumber + 1; $number <= $desiredCount; $number++) {
                if ($contest->type === 'group') {
                    $records[] = [
                        'contest_id' => $contest->id,
                        'number' => $number,
                        'name' => "Representative {$number}",
                        'name2' => null,
                        'team_name' => "Team {$number}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } else {
                    $records[] = [
                        'contest_id' => $contest->id,
                        'number' => $number,
                        'name' => "Contestant {$number}",
                        'name2' => null,
                        'team_name' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if ($records) {
            Contestant::insert($records);
        }
    }

    private function createJudgesUpTo(Contest $contest, int $desiredCount): void
    {
        $currentNumber = $contest->judges()->max('number') ?? 0;
        $records = [];

        for ($number = $currentNumber + 1; $number <= $desiredCount; $number++) {
            $records[] = [
                'contest_id' => $contest->id,
                'number' => $number,
                'name' => "Judge {$number}",
                'access_code' => strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records) {
            Judge::insert($records);
        }
    }

    // --- CSV Import ---

    public function importCsv(Request $request, Contest $contest)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return redirect()->route('contests.settings', $contest)->with('error', 'Could not read file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return redirect()->route('contests.settings', $contest)->with('error', 'CSV file is empty.');
        }

        // Normalize header names
        $header = array_map(fn($h) => strtolower(trim($h)), $header);
        $required = ['exposure_name', 'criteria_name', 'percentage', 'min_score', 'max_score'];
        $missing = array_diff($required, $header);
        if (count($missing) > 0) {
            fclose($handle);
            return redirect()->route('contests.settings', $contest)
                ->with('error', 'Missing CSV columns: ' . implode(', ', $missing));
        }

        $exposureCache = []; // name => Exposure model
        $rowCount = 0;
        $exposureCount = 0;
        $criteriaCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($header)) continue;

            $data = array_combine($header, array_map('trim', $row));
            $rowCount++;

            $exposureName = $data['exposure_name'] ?? '';
            if (empty($exposureName)) continue;

            // Find or create exposure
            if (!isset($exposureCache[$exposureName])) {
                $existing = $contest->exposures()->where('name', $exposureName)->first();
                if ($existing) {
                    $exposureCache[$exposureName] = $existing;
                } else {
                    $isFinal = filter_var($data['is_final'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $carryOver = is_numeric($data['carry_over_percentage'] ?? '') ? (float)$data['carry_over_percentage'] : 0;
                    $topN = is_numeric($data['top_n'] ?? '') ? (int)$data['top_n'] : null;

                    $exposure = $contest->exposures()->create([
                        'name' => $exposureName,
                        'sort_order' => $contest->exposures()->count(),
                        'is_final' => $isFinal,
                        'carry_over_percentage' => $isFinal ? $carryOver : 0,
                        'top_n' => $isFinal ? $topN : null,
                    ]);
                    $exposureCache[$exposureName] = $exposure;
                    $exposureCount++;
                }
            }

            $exposure = $exposureCache[$exposureName];

            // Create criteria
            $criteriaName = $data['criteria_name'] ?? '';
            $percentage = is_numeric($data['percentage'] ?? '') ? (float)$data['percentage'] : 0;
            $minScore = is_numeric($data['min_score'] ?? '') ? (float)$data['min_score'] : 1;
            $maxScore = is_numeric($data['max_score'] ?? '') ? (float)$data['max_score'] : 100;

            if (!empty($criteriaName) && $percentage > 0 && $maxScore > $minScore) {
                $exposure->criteria()->create([
                    'name' => $criteriaName,
                    'percentage' => $percentage,
                    'min_score' => $minScore,
                    'max_score' => $maxScore,
                    'sort_order' => $exposure->criteria()->count(),
                ]);
                $criteriaCount++;
            }
        }

        fclose($handle);

        return redirect()->route('contests.settings', $contest)
            ->with('success', "Imported {$exposureCount} exposure(s) and {$criteriaCount} criteria from {$rowCount} rows.");
    }

    public function downloadCsvTemplate(Contest $contest)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="exposures_criteria_template.csv"',
        ];

        $callback = function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['exposure_name', 'is_final', 'carry_over_percentage', 'top_n', 'criteria_name', 'percentage', 'min_score', 'max_score']);
            fputcsv($out, ['Best in Professional Attire', '0', '0', '', 'Appropriateness', '35', '1', '100']);
            fputcsv($out, ['Best in Professional Attire', '0', '0', '', 'Style', '30', '1', '100']);
            fputcsv($out, ['Best in Professional Attire', '0', '0', '', 'Confidence', '25', '1', '100']);
            fputcsv($out, ['Best in Professional Attire', '0', '0', '', 'Overall Impact', '10', '1', '100']);
            fputcsv($out, ['Evening Gown', '0', '0', '', 'Elegance', '40', '1', '100']);
            fputcsv($out, ['Evening Gown', '0', '0', '', 'Poise', '30', '1', '100']);
            fputcsv($out, ['Evening Gown', '0', '0', '', 'Stage Presence', '30', '1', '100']);
            fputcsv($out, ['Final Round', '1', '30', '5', 'Beauty', '50', '1', '100']);
            fputcsv($out, ['Final Round', '1', '30', '5', 'Intelligence', '50', '1', '100']);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- Activation ---

    public function activate(Contest $contest)
    {
        if ($contest->exposures()->count() === 0) {
            return redirect()->route('contests.settings', $contest)->with('error', 'Add at least one exposure first.');
        }

        $exposureWithoutCriteria = $contest->exposures()->whereDoesntHave('criteria')->where('is_final', false)->first();
        if ($exposureWithoutCriteria) {
            return redirect()->route('contests.settings', $contest)
                ->with('error', "Exposure \"{$exposureWithoutCriteria->name}\" has no criteria.");
        }

        if ($contest->contestants()->count() === 0) {
            return redirect()->route('contests.settings', $contest)->with('error', 'Add at least one contestant first.');
        }
        if ($contest->judges()->count() === 0) {
            return redirect()->route('contests.settings', $contest)->with('error', 'Add at least one judge first.');
        }

        $contest->update(['status' => 'active']);

        $firstExposure = $contest->exposures()->orderBy('sort_order')->first();
        return redirect()->route('contests.tabulate', [$contest, 'exposure' => $firstExposure->id])
            ->with('success', 'Contest is now active!');
    }
}
