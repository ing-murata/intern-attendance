<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Carbon;

class GoogleApiService
{
    protected $client;

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
    public function getAttendance($calendarId): array
    {
        $service = new Calendar($this->client);
        $now = now();
        $optParams = [
            'timeMin' => $now->startOfDay()->toRfc3339String(),
            'timeMax' => $now->endOfDay()->toRfc3339String(),
            'singleEvents' => true,
            'eventTypes' => ['workingLocation', 'outOfOffice'],
        ];

        $events = $service->events->listEvents($calendarId, $optParams);

        $status = null;
        $workTime = null;
        foreach ($events->getItems() as $event) {
            if ($event->eventType === 'workingLocation') {
                $status = '出社';
                $workTime = $this->formatWorkingHours($event);
            }

            if ($event->eventType === 'outOfOffice') {
                $status = '不在/休暇';
                $workTime = null;
            }
        }

        return [
            'status' => $status,
            'work_time' => $workTime,
        ];
    }

    private function formatWorkingHours(Event $event): ?string
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

        return $startAt->format('H:i').'〜'.$endAt->format('H:i');
    }
}
