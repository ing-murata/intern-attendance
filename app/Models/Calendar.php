<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $fillable = [
        'team_name',
        'calendar_id',
        'slack_webhook_url',
        'is_active',
    ];
}
