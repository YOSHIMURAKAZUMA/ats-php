@extends('layouts.app')

@section('title', '求人票登録')

@section('content')
  <h1>求人票登録</h1>

  <form action="{{ route('job-postings.store') }}" method="POST">
    @csrf

    <div>
      <label for="title">求人タイトル</label>
      <input type="text" name="title" id="title" value="{{ old('title') }}">
      @error('title')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="description">業務内容・応募条件</label><br>
      <textarea name="description" id="description" rows="10" cols="60">{{ old('description') }}</textarea>
        @error('description')
          <p role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div>
      <button type="submit">登録する</button>
      <a href="{{ route('job-postings.index') }}">キャンセル</a>
    </div>
  </form>

  <p>※登録直後のステータスは「下書き」になります(REQ-001)</p>
@endsection
