@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
  <h1>ユーザー管理</h1>

  @can('create', App\Models\User::class)
    <p><a href="{{ route('users.create') }}">+ 新規ユーザー登録</a></p>
  @endcan

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
            {{-- 編集(Step 8)・無効化/再有効化(Step 9)をここに追加する --}}
          </td>
        </tr>
      @empty
        <tr><td colspan="5">ユーザーがいません。</td></tr>
      @endforelse
    </tbody>
  </table>

  <p>※役割はチェックボックスで複数選択可能(例:採用担当者と面接官を兼務)</p>
@endsection
