<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', '採用情報')</title>
</head>
<body>
  <header>
    <a href="{{ route('public.jobs.index') }}">採用情報トップ</a>
  </header>

  <hr>

  <main>
    @yield('content')
  </main>
</body>
</html>
