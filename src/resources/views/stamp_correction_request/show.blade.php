@extends('layouts.app')

@section('content')
<div class="container approval-detail-page">
  <div class="page-head">
    <span class="page-bar" aria-hidden="true"></span>
    <h1 class="page-title page-title--left">勤怠詳細</h1>
  </div>

  @if (session('message'))
    <p class="request-message request-message--success">{{ session('message') }}</p>
  @endif

  @if ($errors->any())
    <ul class="request-error-list">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  @endif
  
  @php
  $requestData = $scr;
  $attendance = $requestData->attendance ?? null;

  $breakList = collect($attendance?->breaks ?? []);
  $break1 = $breakList->get(0);
  $break2 = $breakList->get(1);

  $userName = optional($attendance?->user)->name ?? Auth::user()->name;

  $workDate = $attendance && $attendance->work_date
    ? \Carbon\Carbon::parse($attendance->work_date)
    : null;

  $clockIn = $requestData?->requested_clock_in_at
    ? \Carbon\Carbon::parse($requestData->requested_clock_in_at)->format('H:i')
    : ($attendance && $attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '');

  $clockOut = $requestData?->requested_clock_out_at
    ? \Carbon\Carbon::parse($requestData->requested_clock_out_at)->format('H:i')
    : ($attendance && $attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '');

  $break1Start = $requestData?->requested_break1_start
    ? \Carbon\Carbon::parse($requestData->requested_break1_start)->format('H:i')
    : ($break1 && $break1->break_start_at ? \Carbon\Carbon::parse($break1->break_start_at)->format('H:i') : '');

  $break1End = $requestData?->requested_break1_end
    ? \Carbon\Carbon::parse($requestData->requested_break1_end)->format('H:i')
    : ($break1 && $break1->break_end_at ? \Carbon\Carbon::parse($break1->break_end_at)->format('H:i') : '');

  $break2Start = $requestData?->requested_break2_start
    ? \Carbon\Carbon::parse($requestData->requested_break2_start)->format('H:i')
    : ($break2 && $break2->break_start_at ? \Carbon\Carbon::parse($break2->break_start_at)->format('H:i') : '');

  $break2End = $requestData?->requested_break2_end
    ? \Carbon\Carbon::parse($requestData->requested_break2_end)->format('H:i')
    : ($break2 && $break2->break_end_at ? \Carbon\Carbon::parse($break2->break_end_at)->format('H:i') : '');

  $note = $requestData?->requested_note ?? $attendance?->note ?? '';
  $requestStatus = $requestData?->status ?? 'approved';
  @endphp

  <div class="approval-detail-card">
    <div class="detail-row">
      <div class="detail-label">名前</div>
      <div class="detail-value">{{ $userName }}</div>
    </div>

    <div class="detail-row detail-row--date">
      <div class="detail-label">日付</div>
      <div class="detail-value detail-value--split">
        <span>{{ $workDate ? $workDate->format('Y年') : '' }}</span>
        <span>{{ $workDate ? $workDate->format('n月j日') : '' }}</span>
      </div>
    </div>

    <div class="detail-row">
      <div class="detail-label">出勤・退勤</div>
      <div class="detail-value detail-value--time">
        <span>{{ $clockIn }}</span>
        <span>〜</span>
        <span>{{ $clockOut }}</span>
      </div>
    </div>

    <div class="detail-row">
      <div class="detail-label">休憩</div>
      <div class="detail-value detail-value--time">
        <span>{{ $break1Start }}</span>
        <span>〜</span>
        <span>{{ $break1End }}</span>
      </div>
    </div>

    <div class="detail-row">
      <div class="detail-label">休憩2</div>
      <div class="detail-value detail-value--time">
        @if($break2Start || $break2End)
          <span>{{ $break2Start }}</span>
          <span>〜</span>
          <span>{{ $break2End }}</span>
        @endif
      </div>
    </div>

    <div class="detail-row detail-row--textarea">
      <div class="detail-label">備考</div>
      <div class="detail-value">{{ $note }}</div>
    </div>
  </div>

  <div class="approval-action">
    @if(Auth::user()->role === 'admin' && $scr->status === 'pending')
      <form method="POST" action="{{ route('stamp_correction_request.approve', ['stampCorrectionRequest' => $scr->id]) }}">
        @csrf
        <button type="submit" class="approval-button">承認</button>
      </form>
    @elseif($scr->status === 'approved')
      <button type="button" class="approval-button approval-button--done" disabled>承認済み</button>
    @endif
  </div>
</div>
@endsection