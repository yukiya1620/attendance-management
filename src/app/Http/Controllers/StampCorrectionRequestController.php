<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StampCorrectionRequest;

class StampCorrectionRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $status = request('status', 'pending');

        if (!in_array($status, ['pending', 'approved'], true)) {
            $status = 'pending';
        }

        $query = StampCorrectionRequest::with(['attendance'])
            ->where('status', $status)
            ->orderByDesc('created_at');

        // 一般ユーザーは自分の申請だけ、管理者は全件
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $requests = $query->get();

        return view('stamp_correction_request.list', compact('status', 'requests'));
    }

    public function show(StampCorrectionRequest $stampCorrectionRequest)
    {
        $scr =$stampCorrectionRequest->load(['attendance.user']);
        return view('stamp_correction_request.show', compact('scr'));
    }

    public function approve(StampCorrectionRequest $stampCorrectionRequest)
    {
        $user = Auth::user();
        abort_if($user->role !== 'admin', 403);

        if ($stampCorrectionRequest->status !== 'pending') {
            return back()->with('error', 'この申請は既に処理済みです。');
        }
        
        if (!$stampCorrectionRequest->attendance) {
            return back()->with('error', '対象の勤怠データが存在しません。');
        }
        
        DB::transaction(function () use ($stampCorrectionRequest) {
            $attendance = $stampCorrectionRequest->attendance;
            
            $attendance->update([
                'clock_in_at'  => $stampCorrectionRequest->requested_clock_in_at,
                'clock_out_at' => $stampCorrectionRequest->requested_clock_out_at,
                'note'         => $stampCorrectionRequest->requested_note,
                'status'       => $stampCorrectionRequest->requested_clock_out_at ? 'done' : 'working',
            ]);
            
           $requestedBreaks = collect([
                [
                    'break_start_at' => $stampCorrectionRequest->requested_break1_start,
                    'break_end_at'   => $stampCorrectionRequest->requested_break1_end,
                ],
                [
                    'break_start_at' => $stampCorrectionRequest->requested_break2_start,
                    'break_end_at'   => $stampCorrectionRequest->requested_break2_end,
                ],
            ])->filter(function ($break) {
                return $break['break_start_at'] || $break['break_end_at'];
            })->values();
            
            if ($requestedBreaks->isNotEmpty()) {
                $attendance->breaks()->delete();
                
                foreach ($requestedBreaks as $break) {
                    $attendance->breaks()->create($break);
                }
            }
            
            $stampCorrectionRequest->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });
        
        return redirect()
            ->route('stamp_correction_request.list', ['status' => 'approved'])
            ->with('success', '申請を承認しました。');
    }
}