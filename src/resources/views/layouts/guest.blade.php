<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>勤怠管理</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a class="header-logo" href="{{ url('/') }}">
      <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH">
    </a>
  </div>
</header>

<main>
  @yield('content')
</main>

</body>
</html>