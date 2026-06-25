<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['question_id', 'text', 'image_url', 'is_correct', 'order_index'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
