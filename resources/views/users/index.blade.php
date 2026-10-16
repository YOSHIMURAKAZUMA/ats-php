@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
  <h1>ユーザー管理</h1>

  @can('create', App\Models\User::class)
    <p><a href="{{ route('users.create') }}">+ 新規ユーザー登録</a></p>
  @endcan

  @error('user')
    <p role="alert">{{ $message }}</p>
  @enderror

  <table border="1">
    <thead>
      <tr>
        <th>氏名</th>
        <th>メールアドレス</th>
        <th>役割(複数可)</th>
        <th>状態</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($users as $user)
        <tr>
          <td>{{ $user->name }}</td>
          <td>{{ $user->email }}</td>
          <td>{{ $user->roleLabels() }}</td>
          <td>{{ $user->is_active ? '有効' : '無効' }}</td>
          <td>
            @can('update', $user)
              <a href="{{ route('users.edit', $user) }}">編集</a>
            @endcan

            @if ($user->is_active)
              @can('deactivate', $user)
                <form action="{{ route('users.deactivate', $user) }}" method="POST" style="display:inline"
                  onsubmit="return confirm(@js("「{$user->name}」を無効化しますか？ログイン中の場合は即時にログアウトされます。"))">
                  @csrf
                  @method('PATCH')
                  <button type="submit">無効化</button>
                </form>
              @endcan
            @else
              @can('activate', $user)
                <form action="{{ route('users.activate', $user) }}" method="POST" style="display:inline"
                  onsubmit="return confirm(@js("「{$user->name}」を再有効化しますか？"))">
                  @csrf
                  @method('PATCH')
                  <button type="submit">再有効化</button>
                </form>
              @endcan
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5">ユーザーがいません。</td></tr>
      @endforelse
    </tbody>
  </table>

  <p>※役割はチェックボックスで複数選択可能(例:採用担当者と面接官を兼務)</p>
@endsection
