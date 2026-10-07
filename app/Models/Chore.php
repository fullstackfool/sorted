<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chore extends Model
{
    /** @use HasFactory<\Database\Factories\ChoreFactory> */
    use HasFactory, SoftDeletes;

    public const ONCE = 'once';

    public const AFTER = 'after';

    public const ON = 'on';

    protected $fillable = [
        'title',
        'description',
        'points',
        'schedule',
        'every',
        'unit',
        'rule',
        'starts_on',
        'next_due_on',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'points'      => 'integer',
            'every'       => 'integer',
            'starts_on'   => DateOnly::class,
            'next_due_on' => DateOnly::class,
            'finished_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }
}
