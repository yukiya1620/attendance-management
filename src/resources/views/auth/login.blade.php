@extends('layouts.guest')

@section('content')
<div class="auth-page">
  <div class="auth-container">
    <h1 class="auth-title">ログイン</h1>

    @if ($errors->any())
      <div class="auth-errors">
        <ul>
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form">
      @csrf

      <div class="auth-group">
        <label for="email" class="auth-label">メールアドレス</label>
        <input
          id="email"
          type="email"
          name="email"
          value="{{ old('email') }}"
          class="auth-input"
        >
      </div>

      <div class="auth-group auth-group-password">
        <label for="password" class="auth-label">パスワード</label>
        <input
          id="password"
          type="password"
          name="password"
          class="auth-input"
        >
      </div>
      
      <div class="auth-group auth-group-button">
        <button type="submit" class="auth-button">ログインする</button>
      </div>
    </form>

    <p class="auth-link-wrap">
      <a href="{{ route('register') }}" class="auth-link">会員登録はこちら</a>
    </p>
  </div>
</div>
@endsection