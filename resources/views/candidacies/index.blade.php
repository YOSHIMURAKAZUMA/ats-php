@extends('layouts.app')

@section('content')
    <h1>応募者一覧</h1>
    <p>選考ステータスごとに応募者を確認できます。カードを選ぶと詳細を表示します。</p>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    {{-- Vue(カンバン)のマウント先。データは data-board 属性でJSONとして渡す --}}
    <div id="candidacy-board" data-board="{{ json_encode($board, JSON_UNESCAPED_UNICODE) }}"></div>

    @vite(['resources/js/candidacy-board.js'])
@endsection
