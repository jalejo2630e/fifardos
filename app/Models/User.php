<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Laravel\Sanctum\HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function securityQuestions()
    {
        return $this->hasMany(UserSecurityQuestion::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function getAvatarUrlAttribute(): string
    {
        if (!$this->avatar) {
            $initials = collect(explode(' ', $this->name))
                ->map(fn($w) => mb_substr($w, 0, 1))
                ->take(2)
                ->join('');
            return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&color=f97316&background=1a1a2e&bold=true';
        }

        if (str_starts_with($this->avatar, 'http') || str_starts_with($this->avatar, '/')) {
            return $this->avatar;
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar);
        }

        return $this->avatar;
    }
}
