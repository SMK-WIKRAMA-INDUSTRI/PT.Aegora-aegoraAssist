<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrgentEscalation extends Model
{
    protected $fillable = [
        'escalation_code',
        'session_id',
        'room_number',
        'issue_summary',
        'sentiment',
        'priority',
        'status',
    ];
}
