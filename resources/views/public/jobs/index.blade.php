@extends('layouts.public')

@section('title', '採用情報 - 募集中の求人一覧')

@section('content')
  <h1>採用情報 - 募集中の求人一覧</h1>

  @forelse ($jobPostings as $jobPosting)
    <div>
      <h2>
        <a href="{{ route('public.jobs.show', $jobPosting->id) }}">
          {{ $jobPosting->title }}
        </a>
      </h2>
      <p><a href="{{ route('public.jobs.show', $jobPosting->id) }}">詳細を見る</a></p>
    </div>
    <hr>
  @empty
    <p>現在、募集中の求人はありません。</p>
  @endforelse
@endsection
