<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', '採用管理システム')</title>
</head>
<body>
  <header>
    <nav>
      <a href="{{ route('job-postings.index') }}">求人票一覧</a>
      <a href="{{ url('/candidacies') }}">応募者一覧</a>
      @if (auth()->user()->hasRole(App\Enums\UserRole::Admin))
        <a href="{{ url('/users') }}">ユーザー管理</a>
      @endif
    </nav>
    <div>
      <span>ログイン中: {{ auth()->user()->name }}</span>
      <form action="{{ route('logout') }}" method="post" style="display:inline">
        @csrf
        <button type="submit">ログアウト</button>
      </form>
    </div>
  </header>

  <hr>

  {{-- フラッシュメッセージ(登録・更新の成功通知) --}}
  @if (session('status'))
    <p role="status">{{ session('status') }}</p>
  @endif

  <main>
    @yield('content')
  </main>
</body>
</html>
