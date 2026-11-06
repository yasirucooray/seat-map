<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'side',
        'table_number',
        'seat_number',
        'is_full_table',
        'is_temporary',
        'is_checked_in',
        'checked_in_at',
        'name',
        'email',
        'ticket_number',
    ];

    protected $casts = [
        'is_full_table' => 'boolean',
        'is_temporary' => 'boolean',
        'is_checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
        'table_number' => 'integer',
        'seat_number' => 'integer',
    ];

    public function scopePermanent($query)
    {
        return $query->where('is_temporary', false);
    }

    public function scopeTemporary($query)
    {
        return $query->where('is_temporary', true);
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('is_checked_in', true);
    }
}
