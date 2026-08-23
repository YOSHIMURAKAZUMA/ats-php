@extends('layouts.app')

@section('title', '求人票編集')

@section('content')
  <h1>求人票編集</h1>

  <form action="{{ route('job-postings.update', $jobPosting) }}" method="POST">
    @csrf
    @method('PUT')

    <div>
      <label for="title">求人タイトル</label>
      <input type="text" name="title" id="title" value="{{ old('title', $jobPosting->title) }}">
      @error('title')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="description">業務内容・応募条件
      </label><br>
      <textarea name="description" id="description" cols="60" rows="10">{{ old('description', $jobPosting->description) }}</textarea>
      @error('description')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <button type="submit">更新する</button>
      <a href="{{ route('job-postings.index') }}">キャンセル</a>
    </div>
  </form>
@endsection
