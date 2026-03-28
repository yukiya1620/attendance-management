<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAttendanceController extends Controller
{
    public function show(Request $request, User $user)
    {

        $month = $request->query('month', now()->format('Y-m'));

        $base  = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $base->copy()->startOfMonth();
        $end   = $base->copy()->endOfMonth();

        $attendances = Attendance::query()
            ->with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->work_date)->toDateString());

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->copy();
        }

        return view('attendance.staff_monthly', [
            'user'        => $user,
            'baseMonth'   => $base,
            'prevMonth'   => $base->copy()->subMonthNoOverflow(),
            'nextMonth'   => $base->copy()->addMonthNoOverflow(),
            'days'        => $days,
            'attendances' => $attendances,
        ]);
    }

    public function exportCsv(Request $request, User $user): StreamedResponse
    {

        $month = $request->query('month', now()->format('Y-m'));
        $base  = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $base->copy()->startOfMonth();
        $end   = $base->copy()->endOfMonth();

        $attendances = Attendance::query()
            ->with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->work_date)->toDateString());

        $fileName = 'attendance_' . $user->id . '_' . $base->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($start, $end, $attendances) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩合計', '勤務時間']);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $key = $date->toDateString();
                $attendance = $attendances->get($key);

                $clockIn = $attendance?->clock_in_at?->format('H:i') ?? '';
                $clockOut = $attendance?->clock_out_at?->format('H:i') ?? '';

                $breakMinutes = 0;
                if ($attendance) {
                    foreach ($attendance->breaks as $break) {
                        if ($break->break_start_at && $break->break_end_at) {
                            $breakMinutes += $break->break_start_at->diffInMinutes($break->break_end_at);
                        }
                    }
                }

                $breakTime = '';
                if ($breakMinutes > 0) {
                    $hours = floor($breakMinutes / 60);
                    $minutes = $breakMinutes % 60;
                    $breakTime = sprintf('%d:%02d', $hours, $minutes);
                }

                $workTime = '';
                if ($attendance && $attendance->clock_in_at && $attendance->clock_out_at) {
                    $totalMinutes = $attendance->clock_in_at->diffInMinutes($attendance->clock_out_at) - $breakMinutes;
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                    $workTime = sprintf('%d:%02d', $hours, $minutes);
                }

                fputcsv($handle, [
                    $date->format('Y-m-d'),
                    $clockIn,
                    $clockOut,
                    $breakTime,
                    $workTime,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}