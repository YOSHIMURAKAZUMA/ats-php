@extends('layouts.app')

@section('title', 'ユーザー登録')

@section('content')
  <h1>ユーザー登録</h1>

  <form action="{{ route('users.store') }}" method="POST">
    @csrf

    <div>
      <label for="name">氏名</label>
      <input type="text" name="name" id="name" value="{{ old('name') }}">
      @error('name')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="email">メールアドレス</label>
      <input type="email" name="email" id="email" value="{{ old('email') }}">
      @error('email')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <fieldset>
      <legend>役割(1つ以上選択)</legend>
      @foreach (App\Enums\UserRole::cases() as $role)
        <label>
          <input type="checkbox" name="roles[]" value="{{ $role->value }}"
            @checked(in_array($role->value, old('roles', [])))>
          {{ $role->label() }}
        </label>
      @endforeach
      @error('roles')
        <p role="alert">{{ $message }}</p>
      @enderror
      @error('roles.*')
        <p role="alert">{{ $message }}</p>
      @enderror
    </fieldset>

    <div>
      <label for="password">パスワード</label>
      <input type="password" name="password" id="password">
      <p>※10文字以上で、英大文字・英小文字・数字をそれぞれ1文字以上含めて下さい</p>
      @error('password')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="password_confirmation">パスワード(確認)</label>
      <input type="password" name="password_confirmation" id="password_confirmation">
    </div>

    <div>
      <button type="submit">登録する</button>
      <a href="{{ route('users.index') }}">キャンセル</a>
    </div>
  </form>
@endsection
