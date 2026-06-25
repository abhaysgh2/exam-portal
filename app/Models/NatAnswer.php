<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NatAnswer extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['question_id', 'correct_value', 'tolerance'];

    protected function casts(): array
    {
        return [
            'correct_value' => 'decimal:4',
            'tolerance' => 'decimal:4',
        ];
    }
}
