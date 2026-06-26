<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'description',
        'category',
        'created_by',
        'duration_minutes',
        'total_marks',
        'pass_marks',
        'start_time',
        'end_time',
        'max_candidates',
        'status',
        'instructions',
        'randomize_questions',
        'randomize_options',
        'allow_review',
        'show_results_after',
        'results_release_at',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'results_release_at' => 'datetime',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'allow_review' => 'boolean',
            'total_marks' => 'decimal:2',
            'pass_marks' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('order_index');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ExamGroup::class, 'exam_group_exam')->withTimestamps();
    }
}
