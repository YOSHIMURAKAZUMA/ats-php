@extends('layouts.public')

@section('title', $jobPosting->title . ' への応募')

@section('content')
  <h1>{{ $jobPosting->title }}</h1>

  <form method="POST" action="{{ route('public.entries.store', $jobPosting->id) }}" enctype="multipart/form-data">
    @csrf

    <div>
      <label for="name">氏名 <span>*</span></label><br>
      <input type="text" id="name" name="name" value="{{ old('name') }}">
      @error('name')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="email">メールアドレス <span>*</span></label><br>
      <input type="text" name="email" id="email">
      @error('email')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="phone">電話番号(任意)</label><br>
      <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
      @error('phone')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="resume">履歴書(PDF) <span>*</span></label><br>
      <input type="file" id="resume" name="resume" accept="application/pdf">
      @error('resume')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <button type="submit">応募する</button>
      <a href="{{ route('public.jobs.show', $jobPosting->id) }}">戻る</a>
    </div>
  </form>
@endsection
