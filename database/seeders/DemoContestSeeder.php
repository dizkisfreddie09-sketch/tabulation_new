<?php

namespace Database\Seeders;

use App\Models\Contest;
use App\Models\Contestant;
use App\Models\Criteria;
use App\Models\Exposure;
use App\Models\Judge;
use App\Models\Score;
use Illuminate\Database\Seeder;

class DemoContestSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCompletedSingleContest();
        $this->call(DoubleContestNoScoresSeeder::class);
    }

    private function seedCompletedSingleContest(): void
    {
        $contest = Contest::create([
            'name' => 'Mr. & Ms. Spring Festival 2026',
            'type' => 'single',
            'judge_count' => 4,
            'contestant_count' => 6,
            'male_count' => 0,
            'female_count' => 0,
            'status' => 'completed',
            'tabulator_name' => 'Summit Events Tabulation Team',
        ]);

        $contestants = collect([
            ['number' => 1, 'name' => 'Alyssa Cruz'],
            ['number' => 2, 'name' => 'Bianca Santos'],
            ['number' => 3, 'name' => 'Candice Reyes'],
            ['number' => 4, 'name' => 'Danica Flores'],
            ['number' => 5, 'name' => 'Elise Navarro'],
            ['number' => 6, 'name' => 'Faith Mendoza'],
        ])->map(fn (array $contestant) => Contestant::create([
            'contest_id' => $contest->id,
            ...$contestant,
        ]));

        $judges = collect([
            ['number' => 1, 'name' => 'Judge Aurora Lim', 'access_code' => 'SPRING-J1'],
            ['number' => 2, 'name' => 'Judge Mateo Ong', 'access_code' => 'SPRING-J2'],
            ['number' => 3, 'name' => 'Judge Sofia Tan', 'access_code' => 'SPRING-J3'],
            ['number' => 4, 'name' => 'Judge Rafael Cruz', 'access_code' => 'SPRING-J4'],
        ])->map(fn (array $judge) => Judge::create([
            'contest_id' => $contest->id,
            ...$judge,
        ]));

        $exposures = [
            [
                'name' => 'Production Wear',
                'sort_order' => 1,
                'is_final' => false,
                'carry_over_percentage' => 0,
                'top_n' => null,
                'is_locked' => true,
                'criteria' => [
                    ['name' => 'Stage Presence', 'percentage' => 40, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 1],
                    ['name' => 'Projection', 'percentage' => 30, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 2],
                    ['name' => 'Confidence', 'percentage' => 30, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Evening Gown',
                'sort_order' => 2,
                'is_final' => false,
                'carry_over_percentage' => 0,
                'top_n' => null,
                'is_locked' => true,
                'criteria' => [
                    ['name' => 'Elegance', 'percentage' => 50, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 1],
                    ['name' => 'Poise', 'percentage' => 30, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 2],
                    ['name' => 'Overall Impact', 'percentage' => 20, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Question and Answer',
                'sort_order' => 3,
                'is_final' => true,
                'carry_over_percentage' => 35,
                'top_n' => 3,
                'is_locked' => true,
                'criteria' => [
                    ['name' => 'Content', 'percentage' => 50, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 1],
                    ['name' => 'Clarity', 'percentage' => 25, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 2],
                    ['name' => 'Composure', 'percentage' => 25, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 3],
                ],
            ],
        ];

        $contestantOffsets = [1 => 3.2, 2 => 2.4, 3 => 1.5, 4 => 0.8, 5 => -0.4, 6 => -1.2];
        $judgeOffsets = [1 => 0.0, 2 => 0.3, 3 => -0.2, 4 => 0.5];

        foreach ($exposures as $exposureData) {
            $criteriaData = $exposureData['criteria'];
            unset($exposureData['criteria']);

            $exposure = Exposure::create([
                'contest_id' => $contest->id,
                ...$exposureData,
            ]);

            $criteria = collect($criteriaData)->map(fn (array $criterion) => Criteria::create([
                'exposure_id' => $exposure->id,
                ...$criterion,
            ]));

            foreach ($contestants as $contestant) {
                foreach ($judges as $judge) {
                    foreach ($criteria as $criterion) {
                        $base = match ($exposure->sort_order) {
                            1 => 87.5,
                            2 => 88.8,
                            default => 90.0,
                        };

                        $criterionOffset = match ($criterion->sort_order) {
                            1 => 0.8,
                            2 => 0.3,
                            default => -0.2,
                        };

                        $score = min(
                            (float) $criterion->max_score,
                            max(
                                (float) $criterion->min_score,
                                round($base + $contestantOffsets[$contestant->number] + $judgeOffsets[$judge->number] + $criterionOffset, 2)
                            )
                        );

                        Score::create([
                            'contest_id' => $contest->id,
                            'exposure_id' => $exposure->id,
                            'contestant_id' => $contestant->id,
                            'judge_id' => $judge->id,
                            'criteria_id' => $criterion->id,
                            'score' => $score,
                            'is_final_score_only' => false,
                        ]);
                    }
                }
            }
        }
    }

    private function seedActiveDoubleContest(): void
    {
        $contest = Contest::create([
            'name' => 'Campus Dance Duo Challenge',
            'type' => 'double',
            'judge_count' => 3,
            'contestant_count' => 4,
            'male_count' => 4,
            'female_count' => 4,
            'status' => 'active',
            'tabulator_name' => 'Campus Events Office',
        ]);

        $contestants = collect([
            ['number' => 1, 'name' => 'Mr. Noah Lee', 'name2' => 'Ms. Zara Kim'],
            ['number' => 2, 'name' => 'Mr. Ethan Park', 'name2' => 'Ms. Chloe Sy'],
            ['number' => 3, 'name' => 'Mr. Liam Cruz', 'name2' => 'Ms. Mia Ong'],
            ['number' => 4, 'name' => 'Mr. Lucas Tan', 'name2' => 'Ms. Hana Lim'],
        ])->map(fn (array $contestant) => Contestant::create([
            'contest_id' => $contest->id,
            ...$contestant,
        ]));

        $judges = collect([
            ['number' => 1, 'name' => 'Judge Ina Velasco', 'access_code' => 'DUO-J1'],
            ['number' => 2, 'name' => 'Judge Marco Uy', 'access_code' => 'DUO-J2'],
            ['number' => 3, 'name' => 'Judge Tricia Chua', 'access_code' => 'DUO-J3'],
        ])->map(fn (array $judge) => Judge::create([
            'contest_id' => $contest->id,
            ...$judge,
        ]));

        $talent = Exposure::create([
            'contest_id' => $contest->id,
            'name' => 'Talent Showcase',
            'sort_order' => 1,
            'is_final' => false,
            'carry_over_percentage' => 0,
            'top_n' => null,
            'is_locked' => true,
        ]);

        $criteria = collect([
            ['name' => 'Synchronization', 'percentage' => 40, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 1],
            ['name' => 'Execution', 'percentage' => 35, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 2],
            ['name' => 'Audience Impact', 'percentage' => 25, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 3],
        ])->map(fn (array $criterion) => Criteria::create([
            'exposure_id' => $talent->id,
            ...$criterion,
        ]));

        $pairOffsets = [1 => 2.8, 2 => 1.9, 3 => 0.6, 4 => -0.5];
        $judgeOffsets = [1 => 0.0, 2 => 0.4, 3 => -0.3];

        foreach ($contestants as $contestant) {
            foreach ($judges as $judge) {
                foreach ($criteria as $criterion) {
                    $criterionOffset = match ($criterion->sort_order) {
                        1 => 0.7,
                        2 => 0.1,
                        default => -0.4,
                    };

                    Score::create([
                        'contest_id' => $contest->id,
                        'exposure_id' => $talent->id,
                        'contestant_id' => $contestant->id,
                        'judge_id' => $judge->id,
                        'criteria_id' => $criterion->id,
                        'score' => round(88.4 + $pairOffsets[$contestant->number] + $judgeOffsets[$judge->number] + $criterionOffset, 2),
                        'is_final_score_only' => false,
                    ]);
                }
            }
        }

        $final = Exposure::create([
            'contest_id' => $contest->id,
            'name' => 'Championship Round',
            'sort_order' => 2,
            'is_final' => true,
            'carry_over_percentage' => 25,
            'top_n' => 3,
            'is_locked' => false,
        ]);

        collect([
            ['name' => 'Technique', 'percentage' => 50, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 1],
            ['name' => 'Chemistry', 'percentage' => 30, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 2],
            ['name' => 'Showmanship', 'percentage' => 20, 'min_score' => 75, 'max_score' => 100, 'sort_order' => 3],
        ])->each(fn (array $criterion) => Criteria::create([
            'exposure_id' => $final->id,
            ...$criterion,
        ]));
    }
}
