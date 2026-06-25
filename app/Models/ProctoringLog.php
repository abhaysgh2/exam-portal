<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProctoringLog extends Model
{
    use HasUuids;

    public const CREATED_AT = 'logged_at';
    public const UPDATED_AT = null;

    protected $fillable = ['session_id', 'event_type', 'severity', 'details', 'screenshot_url', 'action', 'action_note'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'logged_at' => 'datetime',
        ];
    }
}
