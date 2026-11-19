<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the authenticated user is the Editor account allowed to move seat allocations.
     */
    public function isEditor(): bool
    {
        return strtolower(trim((string)$this->email)) === 'editor2026.kalabara@gmail.com';
    }

    /**
     * Check if the authenticated user is the Volunteer account (QR Scanner & Seat Map Check only, no edit access).
     */
    public function isVolunteer(): bool
    {
        return strtolower(trim((string)$this->email)) === 'volunteer.kalabara@gmail.com';
    }

    /**
     * Check if the user has edit/write permissions (Admin & Editor can edit, Volunteer is view & scan only).
     */
    public function canEdit(): bool
    {
        return !$this->isVolunteer();
    }
}
