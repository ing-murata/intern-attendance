<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $fillable = [
        'user_name',
        'calendar_id',
        'is_active',
    ];
}
