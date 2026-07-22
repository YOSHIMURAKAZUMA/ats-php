<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン - 採用管理システム</title>
</head>
<body>
  <h1>採用管理システム ログイン</h1>

  @if ($errors->any())
    <div style="color: red;">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  <form action="{{ route('login') }}" method="post">
    @csrf

    <div>
      <label for="email">メールアドレス</label>
      <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="email@example.com" required autofocus>
    </div>

    <div>
      <label for="password">パスワード</label>
      <input type="password" name="password" id="password" required>
    </div>

    <button type="submit">ログイン</button>
  </form>
</body>
</html>
