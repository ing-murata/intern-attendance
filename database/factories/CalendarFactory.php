<?php

namespace Database\Factories;

use App\Models\Calendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Calendar>
 */
class CalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_name' => env('CALENDAR_USER_NAME', fake()->name()),
            'calendar_id' => env('CALENDAR_ID', fake()->unique()->safeEmail()),
            'role' => env('CALENDAR_ROLE', 'インターン'),
            'is_active' => filter_var(env('CALENDAR_IS_ACTIVE', true), FILTER_VALIDATE_BOOL),
        ];
    }
}
