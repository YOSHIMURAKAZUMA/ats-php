@extends('layouts.public')

@section('title', '応募完了')

@section('content')
  <h1>応募を受け付けました</h1>

  <p>「{{ $jobPosting->title }}」へのご応募ありがとうございました。
  </p>
  <p>選考結果については、担当者よりご連絡いたします。</p>

  <p><a href="{{ route('public.jobs.index') }}">求人一覧に戻る</a></p>
@endsection
