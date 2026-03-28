<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    public function definition()
    {
        $date = Carbon::today();
        return [
            'attendance_id' => Attendance::factory(),
            'break_start_at' => (clone $date)->setTime(12, 0),
            'break_end_at' => (clone $date)->setTime(13, 0),
        ];
    }

    public function forAttendance(Attendance $attendance, int $startH, int $startM, int $endH, int $endM)
    {
        $date = Carbon::parse($attendance->work_date);
        return $this->state(function () use ($attendance, $date, $startH, $startM, $endH, $endM) {
            return [
                'attendance_id' => $attendance->id,
                'break_start_at' => (clone $date)->setTime($startH, $startM),
                'break_end_at' => (clone $date)->setTime($endH, $endM),
            ];
        });
    }
}
