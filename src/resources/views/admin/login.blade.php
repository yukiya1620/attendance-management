@extends('layouts.guest')

@section('content')
<div style="max-width: 720px; margin: 80px auto;">
  <h1 style="text-align:center; margin-bottom:60px;">管理者ログイン</h1>

  @if ($errors->any())
    <ul style="color:red; margin-bottom:20px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  @endif

  <form method="POST" action="{{ route('admin.login.store') }}">
    @csrf

    <div style="margin-bottom:32px;">
      <label for="email" style="display:block; margin-bottom:8px; font-weight:bold;">メールアドレス</label>
      <input
        id="email"
        type="email"
        name="email"
        value="{{ old('email') }}"
        style="width:100%; padding:12px; border:1px solid #999;"
      >
    </div>

    <div style="margin-bottom:48px;">
      <label for="password" style="display:block; margin-bottom:8px; font-weight:bold;">パスワード</label>
      <input
        id="password"
        type="password"
        name="password"
        style="width:100%; padding:12px; border:1px solid #999;"
      >
    </div>

    <button
      type="submit"
      style="width:100%; padding:14px 0; background:#000; color:#fff; border:none; font-size:16px; cursor:pointer;"
    >
      管理者ログインする
    </button>
  </form>
</div>
@endsection