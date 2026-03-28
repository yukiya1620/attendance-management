@extends('layouts.app')

@section('content')
  <div class="container staff-page">
    <div class="page-head">
      <span class="page-bar" aria-hidden="true"></span>
      <h1 class="page-title page-title--left">スタッフ一覧</h1>
    </div>

    <div class="card card--table staff-table-card">
      <table class="table table--model staff-table">
        <thead>
          <tr>
            <th class="col-center">名前</th>
            <th class="col-center">メールアドレス</th>
            <th class="col-center">月次勤怠</th>
          </tr>
        </thead>
        <tbody>
          @forelse($staffs as $staff)
            <tr>
              <td class="col-center">{{ $staff->name }}</td>
              <td class="col-center">{{ $staff->email }}</td>
              <td class="col-center">
                <a class="table-action" href="{{ route('staff.attendance', ['user' => $staff->id]) }}">詳細</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="col-center muted">スタッフがいません</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection