@extends('layouts.app')

@section('content')
<div class="container attendance-edit-page">
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
    $breakList = collect($breaks ?? []);
    $workDate = \Carbon\Carbon::parse($attendance->work_date);
    $userName = $attendance->user->name ?? Auth::user()->name;
  @endphp

  <form method="POST" action="{{ Auth::user()->role === 'admin'
      ? route('admin.attendance.requestCorrection', $attendance->id)
      : route('attendance.requestCorrection', $attendance->id) }}">
    @csrf

    <div class="attendance-edit-card">
      <div class="edit-row">
        <div class="edit-label">名前</div>
        <div class="edit-value edit-value--name">{{ $userName }}</div>
      </div>

      <div class="edit-row">
        <div class="edit-label">日付</div>
        <div class="edit-value edit-value--date">
          <span>{{ $workDate->format('Y年') }}</span>
          <span>{{ $workDate->format('n月j日') }}</span>
        </div>
      </div>

      <div class="edit-row">
        <div class="edit-label">出勤・退勤</div>
        <div class="edit-value edit-value--time">
          <input
            class="time-input"
            type="time"
            name="requested_clock_in_at"
            value="{{ old('requested_clock_in_at', $attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '') }}"
            {{ $isPending ? 'readonly disabled' : '' }}
          >
          <span class="time-separator">〜</span>
          <input
            class="time-input"
            type="time"
            name="requested_clock_out_at"
            value="{{ old('requested_clock_out_at', $attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '') }}"
            {{ $isPending ? 'readonly disabled' : '' }}
          >
        </div>
      </div>

      @foreach ($breakList as $index => $break)
        <div class="edit-row">
          <div class="edit-label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</div>
          <div class="edit-value edit-value--time">
            <input
              class="time-input"
              type="time"
              name="breaks[{{ $index }}][start]"
              value="{{ old("breaks.$index.start", $break && $break->break_start_at ? \Carbon\Carbon::parse($break->break_start_at)->format('H:i') : '') }}"
              {{ $isPending ? 'readonly disabled' : '' }}
            >
            <span class="time-separator">〜</span>
            <input
              class="time-input"
              type="time"
              name="breaks[{{ $index }}][end]"
              value="{{ old("breaks.$index.end", $break && $break->break_end_at ? \Carbon\Carbon::parse($break->break_end_at)->format('H:i') : '') }}"
              {{ $isPending ? 'readonly disabled' : '' }}
            >
          </div>
        </div>
      @endforeach

      <div class="edit-row edit-row--textarea">
        <div class="edit-label">備考</div>
        <div class="edit-value">
          <textarea
            class="note-textarea"
            name="requested_note"
            rows="4"
            {{ $isPending ? 'readonly' : '' }}
          >{{ old('requested_note', $attendance->note ?? '') }}</textarea>
        </div>
      </div>
    </div>

    @if($isPending)
      <p class="edit-pending-message">*承認待ちのため修正はできません。</p>
    @else
      <div class="edit-action">
        <button type="submit" class="edit-submit-button">修正</button>
      </div>
    @endif
  </form>
</div>
@endsection