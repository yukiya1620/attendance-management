<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index() {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->with('breaks')
            ->first();

        $status = $attendance?->status ?? 'off';

        return view('attendance.index', [
            'now' => Carbon::now(),
            'attendance' => $attendance,
            'status' => $status,
        ]);
    }
    
    public function clockIn() {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['status' => 'off']
        );
        
        if ($attendance->clock_in_at) {
            return back()->withErrors(['出勤は1日1回のみです。']);
        }
        
        $attendance->update([
            'clock_in_at' => $now,
            'status' => 'working',
        ]);
        
        return redirect()->route('attendance.index')->with('message', '出勤しました。');
    }
    
    public function breakIn() {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        
        $attendance = Attendance::where('user_id', $user->id)
          ->where('work_date', $today)
          ->first();
          
        if (!$attendance || !$attendance->clock_in_at) {
            return back()->withErrors(['出勤していません。']);
        }
        if ($attendance->clock_out_at) {
            return back()->withErrors(['退勤後は操作できません。']);
        }
        if ($attendance->status !== 'working') {
            return back()->withErrors(['休憩に入れません。']);
        }
        
        // 休憩中レコードが既にある（＝break_end_at null）なら二重休憩防止
        $openBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end_at')
            ->first();
        if ($openBreak) {
            return back()->withErrors(['すでに休憩中です。']);
        }
        
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $now,
            'break_end_at' => null,
        ]);
        
        $attendance->update(['status' => 'breaking']);
        
        return redirect()->route('attendance.index')->with('message', '休憩に入りました。');
    }
    
    public function breakOut() {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        
        $attendance = Attendance::where('user_id', $user->id)
           ->where('work_date', $today)
           ->first();
           
        if (!$attendance || !$attendance->clock_in_at) {
            return back()->withErrors(['出勤していません。']);
        }
        if ($attendance->clock_out_at) {
            return back()->withErrors(['退勤後は操作できません。']);
        }
        if ($attendance->status !== 'breaking') {
            return back()->withErrors(['休憩戻はできません。']);
        }
        
        $openBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end_at')
            ->latest('break_start_at')
            ->first();
            
        if (!$openBreak) {
            return back()->withErrors(['休憩データが見つかりません。']);
        }
        
        $openBreak->update(['break_end_at' => $now]);
        $attendance->update(['status' => 'working']);
        
        return redirect()->route('attendance.index')->with('message', '休憩から戻りました。');
    }
    
    public function clockOut() {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();
            
        if (!$attendance || !$attendance->clock_in_at) {
            return back()->withErrors(['出勤していません。']);
        }
        if ($attendance->clock_out_at) {
            return back()->withErrors(['退勤は1日1回のみです。']);
        }
        if ($attendance->status === 'breaking') {
            return back()->withErrors(['休憩中は退勤できません。']);
        }
        
        $attendance->update([
            'clock_out_at' => $now,
            'status' => 'done',
        ]);
        
        return redirect()->route('attendance.index')->with('message', '退勤しました。');
    }

    public function list()
    {
        $user = Auth::user();
        
        // 管理者: 日次勤怠一覧
        if ($user->role === 'admin') {
            $dateParam = request('date');
            $baseDate = $dateParam
                ? Carbon::createFromFormat('Y-m-d', $dateParam)->startOfDay(): Carbon::today();
                
            $attendances = Attendance::with(['user', 'breaks'])
                ->whereDate('work_date', $baseDate->toDateString())
                ->whereHas('user', function ($q) {
                    $q->where('role', '!=', 'admin');
                })
                ->orderBy('user_id')
                ->get();
                
            return view('attendance.list', [
                'isAdmin'     => true,
                'baseDate'    => $baseDate,
                'prevDate'    => $baseDate->copy()->subDay(),
                'nextDate'    => $baseDate->copy()->addDay(),
                'attendances' => $attendances,
            ]);
        }
        
        // 一般ユーザー: 月次勤怠一覧
        $month = request('month');
        $base  = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : Carbon::now()->startOfMonth();
        
        $start = $base->copy()->startOfMonth();
        $end   = $base->copy()->endOfMonth();
        
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->with('breaks')
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->work_date)->toDateString());
            
        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->copy();
        }
        
        return view('attendance.list', [
            'isAdmin'     => false,
            'baseMonth'   => $base,
            'prevMonth'   => $base->copy()->subMonthNoOverflow(),
            'nextMonth'   => $base->copy()->addMonthNoOverflow(),
            'days'        => $days,
            'attendances' => $attendances,
        ]);
    }
    
    public function detail($id)
    {
        $user = Auth::user();
        
        $query = Attendance::where('id', $id)->with('breaks');
        
        // 一般ユーザーは自分の勤怠だけ
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        
        $attendance = $query->firstOrFail();
        
        $pendingRequest = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $isPending = !is_null($pendingRequest);
            
        return view('attendance.detail', [
            'attendance'     => $attendance,
            'breaks'         => $attendance->breaks->sortBy('break_start_at')->values(),
            'pendingRequest' => $pendingRequest,
            'isPending'      => $isPending,
        ]);
    }

    public function adminRequestCorrection($id)
    {
        $user = Auth::user();
        abort_if($user->role !== 'admin', 403);
        
        $attendance = Attendance::where('id', $id)
            ->with('breaks', 'user')
            ->firstOrFail();
            
        $existsPending = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();
            
        if ($existsPending) {
            return back()->withErrors(['承認待ちのため修正はできません。']);
        }
        
        $data = request()->validate([
           'requested_clock_in_at' => ['nullable', 'date_format:H:i'],
           'requested_clock_out_at' => ['nullable', 'date_format:H:i'],
           'requested_note' => ['required', 'string'],
           'breaks.*.start' => ['nullable', 'date_format:H:i'],
           'breaks.*.end' => ['nullable', 'date_format:H:i'],
        ], [
            'requested_note.required' => '備考を記入してください',
        ]);
        
        $date = Carbon::parse($attendance->work_date)->toDateString();
        
        $clockIn = !empty($data['requested_clock_in_at'])
            ? Carbon::parse($date . ' ' . $data['requested_clock_in_at'] . ':00')
            : null;
            
        $clockOut = !empty($data['requested_clock_out_at'])
            ? Carbon::parse($date . ' ' . $data['requested_clock_out_at'] . ':00')
            : null;
            
        if ($clockIn && $clockOut && $clockIn->gt($clockOut)) {
            return back()
                ->withErrors(['requested_clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です'])
                ->withInput();
        }
        
        $breaks = $data['breaks'] ?? [];
        
        foreach ($breaks as $index => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;
            
            $breakStart = !empty($start)
                ? Carbon::parse($date . ' ' . $start . ':00')
                : null;
                
            $breakEnd = !empty($end)
                ? Carbon::parse($date . ' ' . $end . ':00')
                : null;
                
            if ($breakStart && $clockOut && $breakStart->gt($clockOut)) {
                return back()
                   ->withErrors(["breaks.$index.start" => '休憩時間が不適切な値です'])
                   ->withInput();
            }
            
            if ($breakEnd && $clockOut && $breakEnd->gt($clockOut)) {
                return back()
                   ->withErrors(["breaks.$index.end" => '休憩時間もしくは退勤時間が不適切な値です'])
                   ->withInput();
            }
            
            if ($breakStart && $breakEnd && $breakStart->gt($breakEnd)) {
                return back()
                   ->withErrors(["breaks.$index.start" => '休憩時間が不適切な値です'])
                   ->withInput();
            }
        }
        
        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user->id,
            'requested_clock_in_at' => $clockIn,
            'requested_clock_out_at' => $clockOut,
            'requested_break1_start' => !empty($breaks[0]['start']) ? Carbon::parse($date . ' ' . $breaks[0]['start'] . ':00') : null,
            'requested_break1_end' => !empty($breaks[0]['end']) ? Carbon::parse($date . ' ' . $breaks[0]['end'] . ':00') : null,
            'requested_break2_start' => !empty($breaks[1]['start']) ? Carbon::parse($date . ' ' . $breaks[1]['start'] . ':00') : null,
            'requested_break2_end' => !empty($breaks[1]['end']) ? Carbon::parse($date . ' ' . $breaks[1]['end'] . ':00') : null,
            'requested_note' => $data['requested_note'],
            'status' => 'pending',
        ]);
        
        return redirect()->route('attendance.detail', $attendance->id)
           ->with('message', '修正申請を送信しました。');
    }
    
    public function requestCorrection($id)
    {
        $user = Auth::user();
        abort_if($user->role === 'admin', 403);
        
        $attendance = Attendance::where('id', $id)
           ->where('user_id', $user->id)
           ->with('breaks')
           ->firstOrFail();
           
        $existsPending = StampCorrectionRequest::where('attendance_id', $attendance->id)
           ->where('status', 'pending')
           ->exists();
           
        if ($existsPending) {
            return back()->withErrors(['承認待ちのため修正はできません。']);
        }
        
        $data = request()->validate([
            'requested_clock_in_at' => ['nullable', 'date_format:H:i'],
            'requested_clock_out_at' => ['nullable', 'date_format:H:i'],
            'requested_note' => ['required', 'string'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
        ], [
            'requested_note.required' => '備考を記入してください',
        ]);
        
        $date = Carbon::parse($attendance->work_date)->toDateString();
        
        $clockIn = !empty($data['requested_clock_in_at'])
           ? Carbon::parse($date . ' ' . $data['requested_clock_in_at'] . ':00')
           : null;
           
        $clockOut = !empty($data['requested_clock_out_at'])
           ? Carbon::parse($date . ' ' . $data['requested_clock_out_at'] . ':00')
           : null;
           
        if ($clockIn && $clockOut && $clockIn->gt($clockOut)) {
            return back()
               ->withErrors(['requested_clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です'])
               ->withInput();
        }
        
        $breaks = $data['breaks'] ?? [];
        
        foreach ($breaks as $index => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;
            
            $breakStart = !empty($start)
               ? Carbon::parse($date . ' ' . $start . ':00')
               : null;
               
            $breakEnd = !empty($end)
               ? Carbon::parse($date . ' ' . $end . ':00')
               : null;
               
            if ($breakStart && $clockOut && $breakStart->gt($clockOut)) {
                return back()
                   ->withErrors(["breaks.$index.start" => '休憩時間が不適切な値です'])
                   ->withInput();
            }
            
            if ($breakEnd && $clockOut && $breakEnd->gt($clockOut)) {
                return back()
                   ->withErrors(["breaks.$index.end" => '休憩時間もしくは退勤時間が不適切な値です'])
                   ->withInput();
            }
            
            if ($breakStart && $breakEnd && $breakStart->gt($breakEnd)) {
                return back()
                   ->withErrors(["breaks.$index.start" => '休憩時間が不適切な値です'])
                   ->withInput();
            }
        }
        
        StampCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => $clockIn,
            'requested_clock_out_at' => $clockOut,
            'requested_break1_start' => !empty($breaks[0]['start']) ? Carbon::parse($date . ' ' . $breaks[0]['start'] . ':00') : null,
            'requested_break1_end' => !empty($breaks[0]['end']) ? Carbon::parse($date . ' ' . $breaks[0]['end'] . ':00') : null,
            'requested_break2_start' => !empty($breaks[1]['start']) ? Carbon::parse($date . ' ' . $breaks[1]['start'] . ':00') : null,
            'requested_break2_end' => !empty($breaks[1]['end']) ? Carbon::parse($date . ' ' . $breaks[1]['end'] . ':00') : null,
            'requested_note' => $data['requested_note'],
            'status' => 'pending',
        ]);
        
        return redirect()->route('attendance.detail', $attendance->id)
           ->with('message', '修正申請を送信しました。');
    }

}
