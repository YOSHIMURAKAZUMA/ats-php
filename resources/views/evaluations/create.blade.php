@extends('layouts.app')

@section('title', '面接評価入力')

@section('content')
  <h1>面接評価入力</h1>

  <p><a href="{{ route('candidacies.show', $candidacy->id) }}">← 応募者詳細に戻る</a></p>

  <p>対象応募者: {{ $candidacy->candidate->name }}({{ $candidacy->status->label() }})</p>

  @if ($evaluation !== null)
    <p>入力済みの評価があります。({{ $evaluation->updated_at->format('Y-m-d H:i') }} 更新)。保存すると内容が上書きされます。</p>
  @endif

  <form method="POST" action="{{ route('candidacies.evaluations.store', $candidacy->id) }}">
    @csrf

    <!-- 対象ラウンド(画面を開いた時点の現在ラウンド。保存時に変更れていないかを検証する) -->
    <input type="hidden" name="round" value="{{ $round }}">
    @error('round')
      <p role="alert">{{ $message }}</p>
    @enderror

    <div>
      <p>評価者</p>
      <p>{{ auth()->user()->name }}(ログイン中)</p>
    </div>

    <fieldset>
      <legend>評価スコア</legend>
      @foreach (range(1, 5) as $score)
        <label>
          <input type="radio" name="score" value="{{ $score }}" @checked((int) old('score', $evaluation?->score) === $score)>
          {{ $score }}
        </label>
      @endforeach
      @error('score')
        <p role="alert">{{ $message }}</p>
      @enderror
    </fieldset>

    <div>
      <label for="comment">所感コメント</label><br>
      <textarea name="comment" id="comment" rows="10" cols="60" maxlength="1000" placeholder="面接での所感を入力(1000文字以内)">{{ old('comment', $evaluation?->comment) }}</textarea>
      @error('comment')
        <p role="alert">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <button type="submit">保存する</button>
      <a href="{{ route('candidacies.show', $candidacy->id) }}">キャンセル</a>
    </div>
  </form>

  <p>※このラウンドを担当する他の面接官の所感は、あなたの評価保存後に応募者詳細画面で閲覧できるようになります。</p>
@endsection
