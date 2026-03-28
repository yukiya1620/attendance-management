<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>勤怠管理</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="{{ auth()->check() && auth()->user()->role === 'admin' ? 'is_admin' : 'is_user' }}">

<header class="site-header">
  <div class="header-inner">
    <a class="header-logo" href="{{ url('/') }}">
      <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH">
    </a>

    @auth
      <nav class="header-nav">
        @php($isAdmin = auth()->user()->role === 'admin')

        @if($isAdmin)
          <a class="nav-link" href="{{ route('attendance.list') }}">勤怠一覧</a>
          <a class="nav-link" href="{{ route('staff.list') }}">スタッフ一覧</a>
          <a class="nav-link" href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}">申請一覧</a>

          <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="nav-button">ログアウト</button>
          </form>
        @else
          <a class="nav-link" href="{{ route('attendance.index') }}">勤怠</a>
          <a class="nav-link" href="{{ route('attendance.list') }}">勤怠一覧</a>
          <a class="nav-link" href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}">申請</a>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-button">ログアウト</button>
          </form>
        @endif
      </nav>
    @endauth
  </div>
</header>

<main>
  @yield('content')
</main>

</body>
</html>