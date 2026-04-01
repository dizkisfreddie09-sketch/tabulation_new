<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exposure extends Model
{
    protected $fillable = ['contest_id', 'name', 'sort_order', 'is_final', 'carry_over_percentage', 'top_n', 'is_locked'];

    protected function casts(): array
    {
        return [
            'is_final' => 'boolean',
            'is_locked' => 'boolean',
            'carry_over_percentage' => 'decimal:2',
        ];
    }

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criteria::class)->orderBy('sort_order');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
