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
        'name',
        'email',
        'ticket_number',
    ];

    protected $casts = [
        'is_full_table' => 'boolean',
        'is_temporary' => 'boolean',
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
}
