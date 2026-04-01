<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Judge extends Model
{
    protected $fillable = ['contest_id', 'name', 'number', 'access_code'];

    protected $casts = [
        'number' => 'integer',
    ];

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
