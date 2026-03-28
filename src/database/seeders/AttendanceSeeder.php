<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use App\Models\StampCorrectionRequestBreak;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('role', 'admin')->first();
        $users = User::where('role', 'user')->get();

        $start = Carbon::create(2026, 2, 1)->startOfDay();
        $end   = Carbon::create(2026, 3, 15)->startOfDay();

        foreach ($users as $user) {
            foreach (CarbonPeriod::create($start, $end) as $date) {
                if ($date->isWeekend()) {
                    continue;
                }

                $day = (int) $date->format('d');

                $clockInMinute = match ($day % 5) {
                    0 => 55,
                    1 => 0,
                    2 => 5,
                    3 => 10,
                    default => 15,
                };

                $clockOutMinute = match ($day % 4) {
                    0 => 30,
                    1 => 45,
                    2 => 50,
                    default => 55,
                };

                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'work_date' => $date->toDateString(),
                    ],
                    [
                        'clock_in_at' => (clone $date)->setTime(9, $clockInMinute),
                        'clock_out_at' => (clone $date)->setTime(18, $clockOutMinute),
                        'note' => null,
                        'status' => 'done',
                    ]
                );

                $attendance->breaks()->delete();
                StampCorrectionRequest::where('attendance_id', $attendance->id)->delete();

                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => (clone $date)->setTime(12, 0),
                    'break_end_at' => (clone $date)->setTime(13, 0),
                ]);

                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => (clone $date)->setTime(15, 0),
                    'break_end_at' => (clone $date)->setTime(15, 15),
                ]);

                if ($day % 7 === 0) {
                    AttendanceBreak::create([
                        'attendance_id' => $attendance->id,
                        'break_start_at' => (clone $date)->setTime(16, 30),
                        'break_end_at' => (clone $date)->setTime(16, 40),
                    ]);
                }

                if ($date->month === 2 && $day === 10) {
                    $pending = StampCorrectionRequest::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'requested_clock_in_at' => (clone $date)->setTime(9, 30),
                        'requested_clock_out_at' => (clone $date)->setTime(18, 30),
                        'requested_note' => '電車遅延のため',
                        'status' => 'pending',
                    ]);

                    StampCorrectionRequestBreak::create([
                        'stamp_correction_request_id' => $pending->id,
                        'break_start_at' => (clone $date)->setTime(12, 15),
                        'break_end_at' => (clone $date)->setTime(12, 45),
                    ]);
                }

                if ($date->month === 2 && $day === 20) {
                    $approved = StampCorrectionRequest::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'requested_clock_in_at' => (clone $date)->setTime(9, 10),
                        'requested_clock_out_at' => (clone $date)->setTime(18, 10),
                        'requested_note' => '打刻漏れのため',
                        'status' => 'approved',
                        'approved_by' => $admin?->id,
                        'approved_at' => now(),
                    ]);

                    StampCorrectionRequestBreak::create([
                        'stamp_correction_request_id' => $approved->id,
                        'break_start_at' => (clone $date)->setTime(12, 5),
                        'break_end_at' => (clone $date)->setTime(13, 0),
                    ]);
                }

                if ($date->month === 3 && $day === 5) {
                    $pending = StampCorrectionRequest::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'requested_clock_in_at' => (clone $date)->setTime(8, 45),
                        'requested_clock_out_at' => (clone $date)->setTime(17, 45),
                        'requested_note' => '私用のため',
                        'status' => 'pending',
                    ]);

                    StampCorrectionRequestBreak::create([
                        'stamp_correction_request_id' => $pending->id,
                        'break_start_at' => (clone $date)->setTime(12, 0),
                        'break_end_at' => (clone $date)->setTime(12, 50),
                    ]);
                }

                if ($date->month === 3 && $day === 12) {
                    $approved = StampCorrectionRequest::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'requested_clock_in_at' => (clone $date)->setTime(9, 20),
                        'requested_clock_out_at' => (clone $date)->setTime(18, 20),
                        'requested_note' => '体調不良のため',
                        'status' => 'approved',
                        'approved_by' => $admin?->id,
                        'approved_at' => now(),
                    ]);

                    StampCorrectionRequestBreak::create([
                        'stamp_correction_request_id' => $approved->id,
                        'break_start_at' => (clone $date)->setTime(12, 10),
                        'break_end_at' => (clone $date)->setTime(13, 0),
                    ]);
                }
            }
        }
    }
}