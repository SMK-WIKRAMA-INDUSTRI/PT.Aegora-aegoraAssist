<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HousekeepingTicket extends Model
{
    protected $fillable = [
        'ticket_code',
        'session_id',
        'room_number',
        'items',
        'status',
        'notes',
    ];
}
