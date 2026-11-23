<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatDocument extends Model
{
    protected $fillable = [
        'session_id',
        'file_name',
        'file_path',
        'extracted_text',
    ];
}
