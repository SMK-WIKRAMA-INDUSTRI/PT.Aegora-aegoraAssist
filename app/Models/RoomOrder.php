<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomOrder extends Model
{
    protected $fillable = [
        'order_code',
        'session_id',
        'room_number',
        'type',
        'items_json',
        'total_amount',
        'billed_to_folio',
        'status',
    ];

    protected $casts = [
        'items_json' => 'array',
        'billed_to_folio' => 'boolean',
        'total_amount' => 'decimal:2',
    ];
}
