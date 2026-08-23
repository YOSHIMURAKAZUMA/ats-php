@extends('layouts.app')

@section('title', '求人票一覧')

@section('content')
  <h1>求人票一覧</h1>

  <p><a href="{{ route('job-postings.create') }}">+ 新規登録</a></p>

  <table border="1">
    <thead>
      <tr>
        <th>タイトル</th>
        <th>ステータス</th>
        <th>作成日</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($jobPostings as $jobPosting)
        <tr>
          <td>{{ $jobPosting->title }}</td>
          <td>{{ $jobPosting->status->label() }}</td>
          <td>{{ $jobPosting->created_at->format('Y-m-d') }}</td>
          <td>
            <a href="{{ route('job-postings.edit', $jobPosting) }}">編集</a>

            {{-- 下書き → 公開する --}}
            @if ($jobPosting->status === App\Enums\JobPostingStatus::Draft)
              <form action="{{ route('job-postings.updateStatus', $jobPosting) }}" method="POST" style="display:inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ App\Enums\JobPostingStatus::Published->value }}">
                <button type="submit">公開する</button>
              </form>
            @endif

            {{-- 公開 → 非公開にする(下書きへ差し戻し) --}}
            @if ($jobPosting->status === App\Enums\JobPostingStatus::Published)
              <form action="{{ route('job-postings.updateStatus', $jobPosting) }}" method="POST" style="display:inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ App\Enums\JobPostingStatus::Draft->value }}">
                <button type="submit">非公開にする</button>
              </form>

              {{-- 公開 → 募集終了 --}}
              <form action="{{ route('job-postings.updateStatus', $jobPosting) }}" method="POST" style="display:inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ App\Enums\JobPostingStatus::Closed->value }}">
                <button type="submit">募集終了にする</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4">求人票がありません。</td></tr>
      @endforelse
    </tbody>
  </table>
@endsection
