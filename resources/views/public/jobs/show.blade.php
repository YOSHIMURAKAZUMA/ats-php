@extends('layouts.public')

@section('title', $jobPosting->title)

@section('content')
  <h1>{{ $jobPosting->title }}</h1>

  <h2>業務内容・応募条件</h2>
  <div style="white-space: pre-wrap;">{{ $jobPosting->description}}</div>

  <hr>

  <p>
    {{-- 応募フォーム(SCR-03)はタスク10で実装予定のため、現時点では無効表示 --}}
    <button type="button" disabled>この求人に応募する(準備中)</button>
    <a href="{{ route('public.jobs.index') }}">一覧に戻る</a>
  </p>
@endsection
