<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criteria extends Model
{
    protected $table = 'criteria';

    protected $fillable = ['exposure_id', 'name', 'percentage', 'min_score', 'max_score', 'sort_order'];

    public function exposure(): BelongsTo
    {
        return $this->belongsTo(Exposure::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
