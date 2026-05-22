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
     * @return \Google\Service\Calendar\Events
     */
    public function getEvents($calendarId, \Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        $service = new Calendar($this->client);
        $optParams = [
            'timeMin' => $start->toRfc3339String(),
            'timeMax' => $end->toRfc3339String(),
            'singleEvents' => true,
            'eventTypes' => ['default', 'outOfOffice', 'workingLocation'],
        ];

        return $service->events->listEvents($calendarId, $optParams);
    }
}
