<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:notify-weekly-reminder')->weekly()->thursdays()->at('18:00');
