<?php

namespace Database\Seeders;

use App\Models\Contest;
use App\Models\Contestant;
use App\Models\Criteria;
use App\Models\Exposure;
use App\Models\Judge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoubleContestNoScoresSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $contest = Contest::firstOrNew([
                'name' => 'Campus Dance Duo Challenge',
            ]);

            $contest->fill([
                'type' => 'double',
                'judge_count' => 3,
                'contestant_count' => 0,
                'male_count' => 10,
                'female_count' => 9,
                'status' => 'active',
                'tabulator_name' => 'Campus Events Office',
            ]);
            $contest->save();

            // Reset the active double contest so it starts with contestants only and no scores.
            $contest->scores()->delete();
            $contest->exposures()->delete();
            $contest->judges()->delete();
            $contest->contestants()->delete();

            foreach (range(1, 10) as $number) {
                Contestant::create([
                    'contest_id' => $contest->id,
                    'number' => $number,
                    'name' => 'Mr ' . $number,
                    'name2' => null,
                    'team_name' => null,
                ]);
            }

            foreach (range(1, 9) as $number) {
                Contestant::create([
                    'contest_id' => $contest->id,
                    'number' => $number,
                    'name' => 'Ms ' . $number,
                    'name2' => null,
                    'team_name' => null,
                ]);
            }

            collect([
                ['number' => 1, 'name' => 'Judge Ina Velasco', 'access_code' => 'DUAL-J1'],
                ['number' => 2, 'name' => 'Judge Marco Uy', 'access_code' => 'DUAL-J2'],
                ['number' => 3, 'name' => 'Judge Tricia Chua', 'access_code' => 'DUAL-J3'],
            ])->each(function (array $judge) use ($contest): void {
                Judge::create([
                    'contest_id' => $contest->id,
                    ...$judge,
                ]);
            });

            $exposure = Exposure::create([
                'contest_id' => $contest->id,
                'name' => 'Talent Showcase',
                'sort_order' => 1,
                'is_final' => false,
                'carry_over_percentage' => 0,
                'top_n' => null,
                'is_locked' => false,
            ]);

            collect([
                ['name' => 'Stage Presence', 'percentage' => 40, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 1],
                ['name' => 'Execution', 'percentage' => 35, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 2],
                ['name' => 'Overall Impact', 'percentage' => 25, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 3],
            ])->each(function (array $criterion) use ($exposure): void {
                Criteria::create([
                    'exposure_id' => $exposure->id,
                    ...$criterion,
                ]);
            });
        });
    }
}
