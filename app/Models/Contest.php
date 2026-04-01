<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Contest extends Model
{
    protected $fillable = ['name', 'type', 'judge_count', 'contestant_count', 'male_count', 'female_count', 'status', 'logo', 'tabulator_name', 'pageant_logo'];

    protected $casts = [
        'judge_count' => 'integer',
        'contestant_count' => 'integer',
        'male_count' => 'integer',
        'female_count' => 'integer',
    ];

    public function exposures(): HasMany
    {
        return $this->hasMany(Exposure::class)->orderBy('sort_order');
    }

    public function contestants(): HasMany
    {
        return $this->hasMany(Contestant::class)->orderBy('number');
    }

    public function judges(): HasMany
    {
        return $this->hasMany(Judge::class)->orderBy('number');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function maleContestantCount(): int
    {
        if ($this->type !== 'double') {
            return $this->contestants()->count();
        }

        if ($this->relationLoaded('contestants')) {
            return $this->contestants->filter(function ($contestant) {
                return str_starts_with($contestant->name, 'Mr ');
            })->count();
        }

        return $this->contestants()->where('name', 'like', 'Mr %')->count();
    }

    public function femaleContestantCount(): int
    {
        if ($this->type !== 'double') {
            return 0;
        }

        if ($this->relationLoaded('contestants')) {
            return $this->contestants->filter(function ($contestant) {
                return str_starts_with($contestant->name, 'Ms ');
            })->count();
        }

        return $this->contestants()->where('name', 'like', 'Ms %')->count();
    }

    public function getTypeLabel(): string
    {
        if ($this->type === 'single') {
            return 'Single Contestant';
        }

        if ($this->type === 'double') {
            return 'Double Contestant';
        }

        return 'Group Contestant';
    }

    public function activeExposure(): ?Exposure
    {
        return $this->exposures()->where('is_locked', false)->orderBy('sort_order')->first();
    }
}
