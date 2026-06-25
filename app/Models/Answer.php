<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'question_id',
        'selected_option_id',
        'selected_option_ids',
        'nat_value',
        'descriptive_text',
        'manual_score',
        'manual_feedback',
        'is_marked_review',
        'visited',
        'time_spent_sec',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'nat_value' => 'decimal:4',
            'manual_score' => 'decimal:2',
            'is_marked_review' => 'boolean',
            'visited' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
