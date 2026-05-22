<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\GoogleApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyAttendance extends Command
{
    protected $signature = 'app:notify-attendance';

    public function handle(GoogleApiService $service): int
    {
        $calendars = Calendar::where('is_active', true)->orderBy('role')->orderBy('user_name')->get();
        $webhookUrl = config('services.slack.webhook_url');

        if ($calendars->isEmpty() || !$webhookUrl) {
            return self::SUCCESS;
        }

        $today = now()->timezone('Asia/Tokyo');
        $members = $calendars->map(function ($calendar) use ($service, $today) {
            try {
                return [
                    'name' => $calendar->user_name,
                    'role' => $calendar->role,
                    'events' => $service->getEvents($calendar->calendar_id, $today->copy()->startOfDay(), $today->copy()->endOfDay())->getItems(),
                ];
            } catch (Throwable) {
                return null;
            }
        })->filter();

        $text = view('slack.attendance', [
            'today' => $today,
            'members' => $members,
        ])->render();

        if (trim($text)) {
            Http::post($webhookUrl, ['text' => $text]);
        }

        return self::SUCCESS;
    }
}
