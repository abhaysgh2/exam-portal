<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    use HasUuids;

    protected $fillable = [
        'question_bank_id',
        'section_id',
        'exam_id',
        'type',
        'text',
        'image_url',
        'explanation',
        'difficulty',
        'topic',
        'subtopic',
        'marks',
        'negative_marks',
        'order_index',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'decimal:2',
            'negative_marks' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('order_index');
    }

    public function natAnswer(): HasOne
    {
        return $this->hasOne(NatAnswer::class);
    }
}
