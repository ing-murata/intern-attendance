<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Carbon;

class GoogleApiService
{
    protected $client;

    public function getCalendarService(): Calendar
    {
        return new Calendar($this->client);
    }

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->client->refreshToken(env('GOOGLE_REFRESH_TOKEN'));
    }

    /**
     * @return array{status: ?string, work_time: ?string}
     */
    public function getAttendance($calendarId, string $role): array
    {
        $service = new Calendar($this->client);
        $now = now();
        $optParams = [
            'timeMin' => $now->startOfDay()->toRfc3339String(),
            'timeMax' => $now->endOfDay()->toRfc3339String(),
            'singleEvents' => true,
            'eventTypes' => ['default', 'outOfOffice', 'workingLocation'],
        ];

        $events = $service->events->listEvents($calendarId, $optParams);

        $status = null;
        $workTime = null;

        foreach ($events->getItems() as $event) {
            // 不在予定がある場合、ステータスを予定名に上書き
            if ($event->eventType === 'outOfOffice') {
                $status = $event->getSummary() ?: '不在';
                $workTime = $this->formatWorkingHours($event, '-');
            } 
            // 勤務地イベントがある場合（インターンのみ）
            elseif ($event->eventType === 'workingLocation' && $role === 'インターン') {
                $props = $event->getWorkingLocationProperties();
                $status = ($props && $props->getType() === 'homeOffice') ? 'リモート' : '出社';
                $workTime = $this->formatWorkingHours($event, ' ~ ');
            }
        }

        return [
            'status' => $status,
            'work_time' => $workTime,
        ];
    }

    private function formatWorkingHours(Event $event, string $separator = ' ~ '): ?string
    {
        $start = $event->getStart();
        $end = $event->getEnd();
        if (! $start || ! $end) {
            return null;
        }

        $startStr = $start->getDateTime();
        $endStr = $end->getDateTime();
        if (! $startStr || ! $endStr) {
            return null;
        }

        $startAt = Carbon::parse($startStr)->timezone('Asia/Tokyo');
        $endAt = Carbon::parse($endStr)->timezone('Asia/Tokyo');

        return $startAt->format('G:i').$separator.$endAt->format('G:i');
    }
}
