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
        'name',
        'email',
        'ticket_number',
    ];

    protected $casts = [
        'is_full_table' => 'boolean',
        'table_number' => 'integer',
        'seat_number' => 'integer',
    ];
}
