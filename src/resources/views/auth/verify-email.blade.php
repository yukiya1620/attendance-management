@extends('layouts.guest')

@section('content')
<div class="verify-page">
  <div class="verify-container">
    <p class="verify-message">
      登録していただいたメールアドレスに認証メールを送付しました。<br>
      メール認証を完了してください。
    </p>

    <div class="verify-mailhog">
      <a href="http://localhost:8025" target="_blank" rel="noopener" class="verify-button">
        認証はこちらから
      </a>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="verify-resend-form">
      @csrf
      <button type="submit" class="verify-resend-button">
        認証メールを再送する
      </button>
    </form>
  </div>
</div>
@endsection