<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:notify-weekly-reminder')->weekly()->thursdays()->at('18:00');
Schedule::command('app:notify-weekly-report')->weekly()->fridays()->at('18:00');
