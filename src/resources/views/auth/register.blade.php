@extends('layouts.guest')

@section('content')
<div class="auth-page">
  <div class="auth-container">
    <h1 class="auth-title">会員登録</h1>

    @if ($errors->any())
      <div class="auth-errors">
        <ul>
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="auth-form">
      @csrf

      <div class="auth-group">
        <label for="name" class="auth-label">名前</label>
        <input
          id="name"
          type="text"
          name="name"
          value="{{ old('name') }}"
          class="auth-input"
        >
      </div>

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

      <div class="auth-group">
        <label for="password" class="auth-label">パスワード</label>
        <input
          id="password"
          type="password"
          name="password"
          class="auth-input"
        >
      </div>

      <div class="auth-group">
        <label for="password_confirmation" class="auth-label">パスワード確認</label>
        <input
          id="password_confirmation"
          type="password"
          name="password_confirmation"
          class="auth-input"
        >
      </div>

      <button type="submit" class="auth-button">登録する</button>
    </form>

    <p class="auth-link-wrap">
      <a href="{{ route('login') }}" class="auth-link">ログインはこちら</a>
    </p>
  </div>
</div>
@endsection