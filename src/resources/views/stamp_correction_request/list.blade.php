@extends('layouts.app')

@section('content')
<div class="container request-page">
  <div class="page-head">
    <span class="page-bar" aria-hidden="true"></span>
    <h1 class="page-title page-title--left">申請一覧</h1>
  </div>

  @if(session('success'))
    <p class="request-message request-message--success">{{ session('success') }}</p>
  @endif

  @if(session('error'))
    <p class="request-message request-message--error">{{ session('error') }}</p>
  @endif

  <div class="request-tabs-wrap">
    <div class="request-tabs">
      <a
        href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}"
        class="request-tab {{ $status === 'pending' ? 'is-active' : '' }}"
      >
        承認待ち
      </a>
      <a
        href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}"
        class="request-tab {{ $status === 'approved' ? 'is-active' : '' }}"
      >
        承認済み
      </a>
    </div>
  </div>
  <div class="card card--table request-table-card">
    <table class="table request-table">
      <thead>
        <tr>
          <th class="col-center">状態</th>
          <th class="col-center">名前</th>
          <th class="col-center">対象日時</th>
          <th class="col-center request-col-reason">申請理由</th>
          <th class="col-center">申請日時</th>
          <th class="col-center">詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse($requests as $r)
          @php
            $label = match($r->status) {
              'pending'  => '承認待ち',
              'approved' => '承認済み',
              default    => $r->status,
            };
          @endphp
          <tr>
            <td class="col-center">{{ $label }}</td>
            <td class="col-center">{{ optional(optional($r->attendance)->user)->name ?? '' }}</td>
            <td class="col-center">
              {{ optional($r->attendance)->work_date ? \Carbon\Carbon::parse($r->attendance->work_date)->format('Y/m/d') : '' }}
            </td>
            <td class="col-center request-col-reason">{{ $r->requested_note }}</td>
            <td class="col-center">{{ $r->created_at->format('Y/m/d') }}</td>
            <td class="col-center">
              @if($r->attendance)
                <a class="table-action" href="{{ route('stamp_correction_request.show', ['stampCorrectionRequest' => $r->id]) }}">
                  詳細
                </a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="col-center muted">データがありません</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection