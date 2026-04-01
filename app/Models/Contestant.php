<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contestant extends Model
{
    protected $fillable = ['contest_id', 'number', 'name', 'name2', 'team_name'];

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

    public function getDisplayName(): string
    {
        if ($this->contest->type === 'double' && $this->name2) {
            return $this->name . ' & ' . $this->name2;
        }
        if ($this->contest->type === 'group' && $this->team_name) {
            return $this->team_name . ' (' . $this->name . ')';
        }
        return $this->name;
    }
}
