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
        $calendars = Calendar::where('is_active', true)->get();
        if ($calendars->isEmpty()) {
            return self::SUCCESS;
        }

        $groups = $this->collectSchedules($calendars, $service->getCalendarService());

        if (array_sum(array_map('count', $groups)) > 0) {
            Http::post(config('services.slack.webhook_url'), [
                'text' => view('slack.weekly_report', ['groups' => array_filter($groups)])->render()
            ]);
        }

        return self::SUCCESS;
    }

    private function collectSchedules($calendars, $service): array
    {
        $groups = ['社員' => [], 'インターン' => []];
        $timeRange = [
            'timeMin' => Carbon::now()->addWeek()->startOfWeek()->toRfc3339String(),
            'timeMax' => Carbon::now()->addWeek()->endOfWeek()->toRfc3339String(),
        ];

        foreach ($calendars as $calendar) {
                $events = $service->events->listEvents($calendar->calendar_id, array_merge($timeRange, [
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                    'eventTypes' => ['outOfOffice', 'workingLocation'],
                ]));

                $schedules = [];
                foreach ($events->getItems() as $event) {
                    $startDt = Carbon::parse($event->getStart()->getDateTime());
                    $endDt = Carbon::parse($event->getEnd()->getDateTime());
                    $time = $startDt->translatedFormat('n/j(D) H:i-') . $endDt->format('H:i');
                    
                    $status = null;
                    if ($event->eventType === 'outOfOffice') {
                        $status = $event->getSummary() ?: '不在';
                    } elseif ($event->eventType === 'workingLocation' && $calendar->role === 'インターン') {
                        $props = $event->getWorkingLocationProperties();
                        $status = ($props && $props->getType() === 'homeOffice') ? 'リモート' : '出社';
                    }

                    if ($status) {
                        $schedules[] = ['start' => $startDt, 'text' => $time . ' ' . $status];
                    }
                }

                if (!empty($schedules)) {
                    usort($schedules, fn($a, $b) => $a['start'] <=> $b['start']);
                    $groups[$calendar->role][] = [
                        'name' => $calendar->user_name,
                        'schedule' => implode("\n", array_column($schedules, 'text')),
                    ];
                }
        }
        return $groups;
    }
}
