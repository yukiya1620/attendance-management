@extends('layouts.app')

@section('content')
  <div class="container staff-monthly-page">
    <div class="page-head">
      <span class="page-bar" aria-hidden="true"></span>
      <h1 class="page-title page-title--left">{{ $user->name }}さんの勤怠</h1>
    </div>

    <div class="monthly-nav-card">
      <a href="{{ route('staff.attendance', ['user' => $user->id, 'month' => $prevMonth->format('Y-m')]) }}" class="monthly-nav-link">
        ← 前月
      </a>

      <div class="monthly-nav-current">
        <span class="monthly-nav-icon">📅</span>
        <span>{{ $baseMonth->format('Y/m') }}</span>
      </div>

      <a href="{{ route('staff.attendance', ['user' => $user->id, 'month' => $nextMonth->format('Y-m')]) }}" class="monthly-nav-link">
        翌月 →
      </a>
    </div>

    <div class="card card--table monthly-table-card">
      <table class="table monthly-table">
        <thead>
          <tr>
            <th class="col-center">日付</th>
            <th class="col-center">出勤</th>
            <th class="col-center">退勤</th>
            <th class="col-center">休憩</th>
            <th class="col-center">合計</th>
            <th class="col-center">詳細</th>
          </tr>
        </thead>
        <tbody>
          @php
            $daysInMonth = $baseMonth->daysInMonth;
            $map = $attendances;
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
          @endphp

          @for($i=1; $i<=$daysInMonth; $i++)
            @php
              $date = $baseMonth->copy()->day($i);
              $key = $date->toDateString();
              $a = $map->get($key);

              $clockIn = $a?->clock_in_at?->format('H:i') ?? '';
              $clockOut = $a?->clock_out_at?->format('H:i') ?? '';

              $breakMinutes = 0;
              if ($a) {
                foreach ($a->breaks as $b) {
                  if ($b->break_start_at && $b->break_end_at) {
                    $breakMinutes += \Carbon\Carbon::parse($b->break_end_at)->diffInMinutes(\Carbon\Carbon::parse($b->break_start_at));
                  }
                }
              }

              $breakStr = $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '';

              $workMinutes = 0;
              if ($a && $a->clock_in_at && $a->clock_out_at) {
                $workMinutes = \Carbon\Carbon::parse($a->clock_out_at)->diffInMinutes(\Carbon\Carbon::parse($a->clock_in_at)) - $breakMinutes;
              }

              $workStr = $workMinutes ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60) : '';
              $week = $weekdays[$date->dayOfWeek];
            @endphp
            <tr>
              <td class="col-center">{{ $date->format('m/d') }}({{ $week }})</td>
              <td class="col-center">{{ $clockIn ?: '-' }}</td>
              <td class="col-center">{{ $clockOut ?: '-' }}</td>
              <td class="col-center">{{ $breakStr ?: '-' }}</td>
              <td class="col-center">{{ $workStr ?: '-' }}</td>
              <td class="col-center">
                @if($a)
                  <a class="table-action" href="{{ route('admin.attendance.detail', ['id' => $a->id]) }}">詳細</a>
                @else
                  -
                @endif
              </td>
            </tr>
          @endfor
        </tbody>
      </table>
    </div>

    <div class="monthly-export">
      <a
        href="{{ route('staff.attendance.csv', ['user' => $user->id, 'month' => $baseMonth->format('Y-m')]) }}"
        class="monthly-export-button"
      >
        CSV出力
      </a>
    </div>
  </div>
@endsection