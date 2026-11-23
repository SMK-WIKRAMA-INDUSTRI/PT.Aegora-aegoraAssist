<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestBooking extends Model
{
    protected $fillable = [
        'booking_code',
        'session_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'room_type',
        'check_in_date',
        'check_out_date',
        'guest_count',
        'special_requests',
        'status',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'guest_count' => 'integer',
    ];
}
