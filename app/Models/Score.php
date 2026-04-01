<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = ['contest_id', 'exposure_id', 'contestant_id', 'judge_id', 'criteria_id', 'score', 'is_final_score_only'];

    protected $casts = [
        'is_final_score_only' => 'boolean',
    ];

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function exposure(): BelongsTo
    {
        return $this->belongsTo(Exposure::class);
    }

    public function contestant(): BelongsTo
    {
        return $this->belongsTo(Contestant::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(Criteria::class);
    }
}
