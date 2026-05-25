<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\GoogleApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyWeeklyReport extends Command
{
    protected $signature = 'app:notify-weekly-report';

    public function handle(GoogleApiService $service): int
    {
        $calendars = Calendar::where('is_active', true)
            ->orderBy('role')
            ->orderBy('user_name')
            ->get();

        $webhookUrl = config('services.slack.webhook_url');
        if ($calendars->isEmpty() || empty($webhookUrl)) {
            return self::SUCCESS;
        }

        $start = Carbon::now()->next(Carbon::SATURDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $members = [];
        foreach ($calendars as $calendar) {
            try {
                $events = $service->getEvents($calendar->calendar_id, $start, $end);
                $members[] = [
                    'name' => $calendar->user_name,
                    'role' => $calendar->role,
                    'events' => $events->getItems(),
                ];
            } catch (Throwable $e) {
                // Ignore failures
            }
        }

        Http::post(config('services.slack.webhook_url'), [
            'text' => view('slack.weekly_report', [
                'members' => $members,
                'start' => $start,
            ])->render()
        ]);

        return self::SUCCESS;
    }
}
