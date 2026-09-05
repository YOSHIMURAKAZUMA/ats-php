@extends('layouts.public')

@section('title', $jobPosting->title)

@section('content')
  <h1>{{ $jobPosting->title }}</h1>

  <h2>業務内容・応募条件</h2>
  <div style="white-space: pre-wrap;">{{ $jobPosting->description}}</div>

  <hr>

  <p>
    <a href="{{ route('public.entries.create', $jobPosting->id) }}">この求人に応募する</a>
    <a href="{{ route('public.jobs.index') }}">一覧に戻る</a>
  </p>
@endsection
