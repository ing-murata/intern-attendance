<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\GoogleApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyAttendance extends Command
{
    protected $signature = 'app:notify-attendance';

    public function handle(GoogleApiService $service): int
    {
        $calendars = Calendar::where('is_active', true)
            ->orderBy('role')
            ->orderBy('user_name')
            ->get();

        $webhookUrl = config('services.slack.webhook_url');

        $today = now()->timezone('Asia/Tokyo');

        $members = [];
        $startOfDay = $today->copy()->startOfDay();
        $endOfDay = $today->copy()->endOfDay();

        foreach ($calendars as $calendar) {
            try {
                $events = $service->getEvents($calendar->calendar_id, $startOfDay, $endOfDay);
                $status = null;
                $workTime = null;

                foreach ($events->getItems() as $event) {
                    if ($event->eventType === 'outOfOffice') {
                        $status = $event->getSummary() ?: '不在';
                        break; // 不在を最優先
                    }

                    if ($event->eventType === 'workingLocation' && $calendar->role === 'インターン') {
                        $type = $event->getWorkingLocationProperties()?->getType();
                        $status = ($type === 'homeOffice') ? 'リモート' : '出社';

                        $start = $event->getStart()?->getDateTime();
                        $end = $event->getEnd()?->getDateTime();
                        if ($start && $end) {
                            $startDt = Carbon::parse($start)->timezone('Asia/Tokyo');
                            $endDt = Carbon::parse($end)->timezone('Asia/Tokyo');
                            $workTime = $startDt->format('H:i') . '-' . $endDt->format('H:i');
                        }
                    }
                }

                if ($status === null) {
                    $this->line("  <fg=gray>- {$calendar->user_name}（{$calendar->role}）: データなし（スキップ）</>");
                    continue;
                }

                $members[] = [
                    'name' => $calendar->user_name,
                    'role' => $calendar->role,
                    'attendance' => [
                        'status' => $status,
                        'work_time' => $workTime,
                    ],
                ];

                $this->line("  <fg=green>[OK]</> {$calendar->user_name}（{$calendar->role}）: " . ($workTime ? "{$workTime} " : "") . $status);
            } catch (Throwable $e) {
                $this->line("  <fg=red>[NG]</> {$calendar->user_name}（{$calendar->role}）: {$e->getMessage()}");
            }
        }

        Http::post($webhookUrl, [
            'text' => view('slack.attendance', [
                'today' => $today,
                'members' => $members,
            ])->render()
        ]);

        return self::SUCCESS;
    }
}
