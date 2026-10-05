@extends('layouts.app')

@section('content')
  <h1>応募者詳細</h1>

  <p><a href="{{ route('candidacies.index') }}">←  応募者一覧に戻る</a></p>

  <!-- 応募者情報 -->
  <h2>応募者情報</h2>
  <table>
    <tr>
      <th>氏名</th>
      <td>{{ $candidacy->candidate->name }}</td>
    </tr>
    <tr>
      <th>メール</th>
      <td>{{ $candidacy->candidate->email }}</td>
    </tr>
    <tr>
      <th>電話</th>
      <td>{{ $candidacy->candidate->phone ?? '未登録' }}</td>
    </tr>
    <tr>
      <th>対象求人</th>
      <td>{{ $candidacy->jobPosting->title }}</td>
    </tr>
    <tr>
      <th>エントリー日</th>
      <td>{{ $candidacy->created_at->format('Y-m-d') }}</td>
    </tr>
    <tr>
      <th>現在のステータス</th>
      <td>{{ $candidacy->status->label() }}</td>
    </tr>
  </table>

  <!-- 面接評価の入力(REQ-009)。現在のラウンドを担当する面接官のみ表示 -->
  @can('evaluate', $candidacy)
    <p><a href="{{ route('candidacies.evaluations.create', $candidacy->id) }}">面接評価を入力する</a></p>
  @endcan

  <!-- ステータス変更(REQ-006/011) -->
  @can('updateStatus', $candidacy)
    <h2>ステータス変更</h2>

    @if (empty($allowedStatuses))
      <p>このステータスからは変更できません(終端ステータス)</p>
    @else

      @error('status')
        <p>{{ $message }}</p>
      @enderror

      <form id="status-form" method="POST" action="{{ route('candidacies.update-status', $candidacy->id) }}">
        @csrf
        @method('PATCH')

        <label for="status">変更先ステータス</label>
        <select id="status" name="status" required>
          <option value="">選択してください</option>
          @foreach ($allowedStatuses as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
          @endforeach
        </select>

        <!-- 確認ポップアップで選択した値を送るための隠しフィールド -->
        <input type="hidden" id="send-notification" name="send_notification" value="1">

        <button type="button" id="open-confirm">更新する</button>
      </form>

      <!-- 確認ポップアップ(REQ-011) -->
      <div id="confirm-overlay" style="display:none"></div>

      <div id="confirm-modal" style="display:none">
        <h3>{{ $candidacy->candidate->name }} のステータス変更の確認</h3>

        <p>選考ステータスを変更します</p>
        <p>
          {{ $candidacy->status->label() }} →
          <span id="confirm-to-label"></span>
        </p>

        <p>
          <label>
            <input type="checkbox" id="confirm-send" checked>
            応募者にメール通知を送信する
          </label>
        </p>
        <p>CC: {{ auth()->user()->name }}({{ auth()->user()->email }})</p>
        <p>(ステータス変更を行った採用担当者が自動でCCされます)</p>
        <p>※チェックを外すと通知メールは送信されません(ステータス変更自体は確定します)</p>

        <button type="button" id="cancel-confirm">キャンセル</button>
        <button type="button" id="submit-confirm">変更する</button>
      </div>

      <script>
        (function () {
          const form = document.getElementById('status-form');
          const select = document.getElementById('status');
          const overlay = document.getElementById('confirm-overlay');
          const modal = document.getElementById('confirm-modal')
          const toLabel = document.getElementById('confirm-to-label');
          const sendCheckbox = document.getElementById('confirm-send');
          const sendHidden = document.getElementById('send-notification');

          const openModal = () => {
            if (select.value === '') {
              alert('変更先ステータスを選択してください。');
              return;
            }
            // 選択中のオプションのラベルを確認文言に反映
            toLabel.textContent = select.options[select.selectedIndex].text;
            overlay.style.display = 'block';
            modal.style.display = 'block';
          };

          const closeModal = () => {
            overlay.style.display = 'none';
            modal.style.display = 'none';
          };

          document.getElementById('open-confirm').addEventListener('click', openModal);
          document.getElementById('cancel-confirm').addEventListener('click', closeModal);
          overlay.addEventListener('click', closeModal);

          document.getElementById('submit-confirm').addEventListener('click', () => {
            // チェックボックスの状態を hidden に移してから送信する
            sendHidden.value = sendCheckbox.checked ? '1' : '0';
            form.submit();
          });
        })();
      </script>
    @endif
  @endcan

  <!-- 面接官アサイン(REQ-008) -->
  @can('manageInterviewers', $candidacy)
    <h2>面接官アサイン</h2>

    @if ($currentRound === null)
      <p>現在のステータスでは面接官アサインを行えません</p>
    @else
      @error('interviewer_ids')
        <p>{{ $message }}</p>
      @enderror
      @error('interviewer_ids.*')
        <p>{{ $message }}</p>
      @enderror
      @error('round')
        <p>{{ $message }}</p>
      @enderror

      <p>対象: {{ $candidacy->status->label() }}(複数選択可)</p>

      <form id="interviewers-form" method="POST" action="{{ route('candidacies.interviewers', $candidacy->id) }}">
        @csrf
        @method('PUT')

        <input type="hidden" name="round" value="{{ $currentRound }}">

        <!-- 選択中の面接官(chip)。hidden で interviewer_ids[] を送る -->
        <div id="assigned-chips"></div>

        <!-- +追加のプルダウン -->
        <select id="add-interviewer">
          <option value="">+ 追加</option>
        </select>

        <p>
          <button type="submit">更新する</button>
        </p>
      </form>

      <script>
        (function () {
          // 現在アサインされている面接官
          const assigned = @json($assignedInterviewers);
          // 面接官候補(全員)
          const candidates = @json($interviewerCandidates);

          const chipsEl = document.getElementById('assigned-chips');
          const addSelect = document.getElementById('add-interviewer');

          // 選択中の面接官IDの配列(この配列が送信内容の正)
          let selectedIds = assigned.map((u) => u.id);

          const nameOf = (id) => {
            const found = candidates.find((u) => u.id === id);
            return found ? found.name : '(不明なユーザー)';
          };

          // chip と hidden input を描画し直す
          const renderChips = () => {
            chipsEl.innerHTML = '';

            if (selectedIds.length === 0) {
              const p = document.createElement('p');
              p.textContent = '面接官が選択されていません。';
              chipsEl.appendChild(p);
            }

            selectedIds.forEach((id) => {
              const span = document.createElement('span');
              span.textContent = nameOf(id) + ' ';

              const removeBtn = document.createElement('button');
              removeBtn.type = 'button';
              removeBtn.textContent = '×';
              removeBtn.addEventListener('click', () => {
                selectedIds = selectedIds.filter((v) => v !== id);
                renderChips();
                renderAddOptions();
              });

              // 送信用の hidden input
              const hidden = document.createElement('input');
              hidden.type = 'hidden';
              hidden.name = 'interviewer_ids[]';
              hidden.value = id;

              span.appendChild(removeBtn);
              span.appendChild(hidden);
              chipsEl.appendChild(span);
            });
          };

          // +追加の選択肢を、未選択の面接官だけで作り直す
          const renderAddOptions = () => {
            addSelect.innerHTML = '<option value="">+ 追加</option>';

            candidates
              .filter((u) => !selectedIds.includes(u.id))
              .forEach((u) => {
                const option = document.createElement('option');
                option.value = u.id;
                option.textContent = u.name;
                addSelect.appendChild(option);
              });
          };

          addSelect.addEventListener('change', () => {
            const id = Number(addSelect.value);
            if (id) {
              selectedIds.push(id);
              renderChips();
              renderAddOptions();
            }
          });

          renderChips();
          renderAddOptions();
        })();
      </script>
    @endif
  @endcan

  <!-- 履歴書(REQ-017) -->
  <h2>履歴書</h2>
  <p>
    <a href="{{ route('candidacies.resume', $candidacy->id) }}" target="_blank" rel="noopener">
      PDFを確認する
    </a>
  </p>

  <!-- ステータス変更履歴 -->
  <h2>ステータス変更履歴</h2>
  @if ($candidacy->statusHistories->isEmpty())
    <p>変更履歴はありません。</p>
  @else
    <table>
      <thead>
        <tr>
          <th>変更履歴</th>
          <th>変更前</th>
          <th>変更後</th>
          <th>変更者</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($candidacy->statusHistories as $history)
          <tr>
            <td>{{ $history->changed_at->format('Y-m-d H:i') }}</td>
            <td>{{ $history->from_status?->label() ?? '-' }}</td>
            <td>{{ $history->to_status->label() }}</td>
            <td>{{ $history->changedBy->name }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <!-- 登録済みの面接評価(REQ-010) -->
  <h2>登録済みの面接評価(選択すると所感を表示)</h2>
  @if (empty($evaluations))
    <p>登録済みの面接評価はありません</p>
  @else
    <table>
      <thead>
        <tr>
          <th>評価者</th>
          <th>ラウンド</th>
          <th>スコア</th>
          <th>登録日時</th>
          <th>所感</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($evaluations as $evaluation)
          <tr>
            <td>{{ $evaluation['interviewerName'] }}</td>
            <td>{{ $evaluation['roundLabel'] }}</td>
            <td>{{ $evaluation['score'] }}</td>
            <td>{{ $evaluation['createdAt'] }}</td>
            <td><button type="button" data-evaluation-id="{{ $evaluation['id'] }}">表示</button></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <h3 id="comment-title">所感コメント</h3>
    <p>※同ラウンド他者の所感は、自分の評価提出後に表示されます</p>
    <p id="comment-body" style="white-space: pre-wrap">一覧の「表示」を押すと、所感コメントがここに表示されます。</p>

    <script>
      (function () {
        //サーバー側で閲覧可否を判定済み(閲覧できない所感は comment が null)
        const evaluations = @json($evaluations);

        const titleEl = document.getElementById('comment-title');
        const bodyEl = document.getElementById('comment-body');

        const showComment = (id) => {
          const evaluation = evaluations.find((e) => e.id === id);
          if (!evaluation) {
            return;
          }

          titleEl.textContent = `所感コメント(選択中: ${evaluation.interviewerName} / ${evaluation.roundLabel})`;

          if (!evaluation.canViewComment) {
            bodyEl.textContent = 'あなたの評価提出後に表示されます。';
          } else if (evaluation.comment === null) {
            bodyEl.textContent = '(所感の入力はありません)';
          } else {
            bodyEl.textContent = evaluation.comment;
          }
        };

        document.querySelectorAll('[data-evaluation-id]').forEach((button) => {
          button.addEventListener('click', () => showComment(Number(button.dataset.evaluationId)));
        });
      })();
    </script>
  @endif
@endsection
