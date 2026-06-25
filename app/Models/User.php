<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;

    protected $fillable = [
        'email',
        'password',
        'full_name',
        'role',
        'enrollment_no',
        'institute',
        'avatar_url',
        'is_active',
        'is_suspended',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'is_suspended' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
