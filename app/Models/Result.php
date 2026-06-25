<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'exam_id',
        'user_id',
        'raw_score',
        'final_score',
        'total_correct',
        'total_wrong',
        'total_unattempted',
        'total_questions',
        'accuracy_pct',
        'time_taken_sec',
        'percentile',
        'rank',
        'grade',
        'is_pass',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'accuracy_pct' => 'decimal:2',
            'percentile' => 'decimal:2',
            'is_pass' => 'boolean',
            'computed_at' => 'datetime',
        ];
    }
}
