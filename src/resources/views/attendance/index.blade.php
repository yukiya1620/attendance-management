@extends('layouts.app')

@section('content')
@php
  \Carbon\Carbon::setLocale('ja');

  $map = [
    'off' => '勤務外',
    'working' => '出勤中',
    'breaking' => '休憩中',
    'done' => '退勤済',
  ];

  $statusLabel = $map[$status] ?? $status;
@endphp

<div class="attendance-page">
  <div class="attendance-container">
    <div class="attendance-status">{{ $statusLabel }}</div>

    <div class="attendance-date" id="current-date">
      {{ $now->isoFormat('Y年M月D日(ddd)') }}
    </div>

    <div class="attendance-time" id="current-time">
      {{ $now->format('H:i') }}
    </div>

    @if ($status === 'off')
      <div class="attendance-actions attendance-actions--single">
        <form method="POST" action="{{ route('attendance.clockIn') }}">
          @csrf
          <button type="submit" class="attendance-button attendance-button--primary">
            出勤
          </button>
        </form>
      </div>
    @endif

    @if ($status === 'working')
      <div class="attendance-actions attendance-actions--double">
        <form method="POST" action="{{ route('attendance.clockOut') }}">
          @csrf
          <button type="submit" class="attendance-button attendance-button--primary">
            退勤
          </button>
        </form>

        <form method="POST" action="{{ route('attendance.breakIn') }}">
          @csrf
          <button type="submit" class="attendance-button attendance-button--secondary">
            休憩入
          </button>
        </form>
      </div>
    @endif

    @if ($status === 'breaking')
      <div class="attendance-actions attendance-actions--single">
        <form method="POST" action="{{ route('attendance.breakOut') }}">
          @csrf
          <button type="submit" class="attendance-button attendance-button--secondary">
            休憩戻
          </button>
        </form>
      </div>
    @endif

    @if ($status === 'done')
      <p class="attendance-message">お疲れ様でした。</p>
    @endif

    @if (session('message'))
      <p class="attendance-flash attendance-flash--success">{{ session('message') }}</p>
    @endif

    @if ($errors->any())
      <ul class="attendance-flash attendance-flash--error">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateEl = document.getElementById('current-date');
    const timeEl = document.getElementById('current-time');

    if (!dateEl || !timeEl) return;

    const weekDays = ['日', '月', '火', '水', '木', '金', '土'];

    function updateDateTime() {
        const now = new Date();

        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const date = now.getDate();
        const day = weekDays[now.getDay()];

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        dateEl.textContent = `${year}年${month}月${date}日(${day})`;
        timeEl.textContent = `${hours}:${minutes}`;
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);
});
</script>
@endsection