@extends('layouts.app')

@section('content')
<div class="container attendance-page">
  <div class="page-head">
    <span class="page-bar" aria-hidden="true"></span>
    <h1 class="page-title page-title--left">
      @if($isAdmin)
        {{ $baseDate->format('Y年n月j日') }}の勤怠
      @else
        勤怠一覧
      @endif
    </h1>
  </div>

  @if($isAdmin)
    <div class="date-nav-card">
      <a href="{{ route('attendance.list', ['date' => $prevDate->format('Y-m-d')]) }}" class="date-nav-link">
        ← 前日
      </a>

      <div class="date-nav-current">
        <span class="date-nav-icon">📅</span>
        <span>{{ $baseDate->format('Y/m/d') }}</span>
      </div>

      <a href="{{ route('attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}" class="date-nav-link">
        翌日 →
      </a>
    </div>

    <div class="card card--table">
      <table class="table table--model attendance-table">
        <thead>
          <tr>
            <th class="col-center">名前</th>
            <th class="col-center">出勤</th>
            <th class="col-center">退勤</th>
            <th class="col-center">休憩</th>
            <th class="col-center">合計</th>
            <th class="col-center">詳細</th>
          </tr>
        </thead>
        <tbody>
          @forelse($attendances as $a)
            @php
              $clockIn = $a->clock_in_at ? \Carbon\Carbon::parse($a->clock_in_at)->format('H:i') : '';
              $clockOut = $a->clock_out_at ? \Carbon\Carbon::parse($a->clock_out_at)->format('H:i') : '';

              $breakMinutes = 0;
              foreach ($a->breaks as $b) {
                if ($b->break_start_at && $b->break_end_at) {
                  $breakMinutes += \Carbon\Carbon::parse($b->break_end_at)->diffInMinutes(\Carbon\Carbon::parse($b->break_start_at));
                }
              }

              $breakStr = $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '';

              $workMinutes = 0;
              if ($a->clock_in_at && $a->clock_out_at) {
                $workMinutes = \Carbon\Carbon::parse($a->clock_out_at)->diffInMinutes(\Carbon\Carbon::parse($a->clock_in_at)) - $breakMinutes;
              }
              $workStr = $workMinutes ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60) : '';
            @endphp
            <tr>
              <td class="col-center">{{ $a->user->name ?? '' }}</td>
              <td class="col-center">{{ $clockIn }}</td>
              <td class="col-center">{{ $clockOut }}</td>
              <td class="col-center">{{ $breakStr }}</td>
              <td class="col-center">{{ $workStr }}</td>
              <td class="col-center">
                <a class="table-action" href="{{ route('attendance.detail', ['id' => $a->id]) }}">詳細</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="col-center muted">勤怠データがありません</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @else
    <div class="date-nav-card">
      <a href="{{ route('attendance.list', ['month' => $prevMonth->format('Y-m')]) }}" class="date-nav-link">
        ← 前月
      </a>

      <div class="date-nav-current">
        <span class="date-nav-icon">📅</span>
        <span>{{ $baseMonth->format('Y/m') }}</span>
      </div>

      <a href="{{ route('attendance.list', ['month' => $nextMonth->format('Y-m')]) }}" class="date-nav-link">
        翌月 →
      </a>
    </div>

    <div class="card card--table">
      <table class="table table--model attendance-table">
        <thead>
          <tr>
            <th>日付</th>
            <th class="col-center">出勤</th>
            <th class="col-center">退勤</th>
            <th class="col-center">休憩</th>
            <th class="col-center">合計</th>
            <th class="col-center">詳細</th>
          </tr>
        </thead>
        <tbody>
          @foreach($days as $day)
            @php
              $key = $day->toDateString();
              $a = $attendances[$key] ?? null;

              $clockIn = $a?->clock_in_at ? \Carbon\Carbon::parse($a->clock_in_at)->format('H:i') : '';
              $clockOut = $a?->clock_out_at ? \Carbon\Carbon::parse($a->clock_out_at)->format('H:i') : '';

              $breakMinutes = 0;
              if ($a) {
                foreach ($a->breaks as $b) {
                  if ($b->break_start_at && $b->break_end_at) {
                    $breakMinutes += \Carbon\Carbon::parse($b->break_end_at)->diffInMinutes(\Carbon\Carbon::parse($b->break_start_at));
                  }
                }
              }
              $breakStr = $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes,60), $breakMinutes%60) : '';

              $workMinutes = 0;
              if ($a && $a->clock_in_at && $a->clock_out_at) {
                $workMinutes = \Carbon\Carbon::parse($a->clock_out_at)->diffInMinutes(\Carbon\Carbon::parse($a->clock_in_at)) - $breakMinutes;
              }
              $workStr = $workMinutes ? sprintf('%d:%02d', intdiv($workMinutes,60), $workMinutes%60) : '';
            @endphp
            <tr>
              <td>{{ $day->format('m/d') }}（{{ ['日','月','火','水','木','金','土'][$day->dayOfWeek] }}）</td>
              <td class="col-center">{{ $clockIn }}</td>
              <td class="col-center">{{ $clockOut }}</td>
              <td class="col-center">{{ $breakStr }}</td>
              <td class="col-center">{{ $workStr }}</td>
              <td class="col-center">
                @if($a)
                  <a class="table-action" href="{{ route('attendance.detail', ['id' => $a->id]) }}">詳細</a>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection