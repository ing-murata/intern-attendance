<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $calendarRows = $this->calendarSeedRowsFromEnv();
        if ($calendarRows === []) {
            $calendarData = Calendar::factory()->make()->toArray();
            Calendar::query()->updateOrCreate([
                'calendar_id' => $calendarData['calendar_id'],
            ], $calendarData);

            return;
        }

        foreach ($calendarRows as $calendarRow) {
            Calendar::query()->updateOrCreate([
                'calendar_id' => $calendarRow['calendar_id'],
            ], $calendarRow);
        }
    }

    /**
     * @return array<int, array{user_name: string, calendar_id: string, role: string, is_active: bool}>
     */
    private function calendarSeedRowsFromEnv(): array
    {
        $raw = env('CALENDAR_SEED_JSON');
        if (blank($raw)) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded) || ! array_is_list($decoded)) {
            return [];
        }

        $rows = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $userName = trim((string) ($item['user_name'] ?? ''));
            $calendarId = trim((string) ($item['calendar_id'] ?? ''));
            if ($userName === '' || $calendarId === '') {
                continue;
            }

            $rows[] = [
                'user_name' => $userName,
                'calendar_id' => $calendarId,
                'role' => trim((string) ($item['role'] ?? '')) ?: 'インターン',
                'is_active' => filter_var($item['is_active'] ?? true, FILTER_VALIDATE_BOOL),
            ];
        }

        return $rows;
    }
}
