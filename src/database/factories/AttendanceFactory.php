<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $date = Carbon::today();

        $clockIn = (clone $date)->setTime(9, 0);
        $clockOut = (clone $date)->setTime(18, 0);

        return [
            'user_id' => User::factory(),
            'work_date' => $date->toDateString(),
            'clock_in_at' => $clockIn,
            'clock_out_at' => $clockOut,
            'note' => $this->faker->optional()->sentence(),
            'status' => 'done',
        ];
    }

    public function forDate(Carbon $date)
    {
        return $this->state(function () use ($date) {
            return [
                'work_date' => $date->toDateString(),
                'clock_in_at' => (clone $date)->setTime(9, 0),
                'clock_out_at' => (clone $date)->setTime(18, 0),
                'status' => 'done',
            ];
        });
    }

    public function working(Carbon $date = null)
    {
        $date = $date ?: Carbon::today();
        return $this->state(function () use ($date) {
            return [
                'work_date' => $date->toDateString(),
                'clock_in_at' => (clone $date)->setTime(9, 0),
                'clock_out_at' => null,
                'status' => 'working',
            ];
        });
    }
}
