<?php

namespace App\Http\Requests;

use App\Enums\CandidacyStatus;
use App\Enums\UserRole;
use App\Models\Candidacy;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ManageInterviewersRequest extends FormRequest
{
    /** authorize() で取得した対象選考(after() のチェックでも使う) */
    private ?Candidacy $candidacy = null;

    /**
     * 面接官アサインの認可(権限マトリクス:採用担当者・管理者)。
     * CandidacyPolicy@manageInterviewers で判定する。
     */
    public function authorize(): bool
    {
        $this->candidacy = Candidacy::find($this->route('id'));

        return $this->candidacy !== null && $this->user()->can('manageInterviewers', $this->candidacy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'round' => ['required', 'integer', Rule::in(CandidacyStatus::interviewRounds())],
            'interviewer_ids' => ['required', 'array', 'min:1'],
            'interviewer_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'round.required' => '対象の面接を指定してください',
            'round.in' => '対象の面接の指定が正しくありません',
            'interviewer_ids.required' => '面接官を1名以上選択してください',
            'interviewer_ids.array' => '面接官の指定形式が正しくありません',
            'interviewer_ids.min' => '面接官を1名以上選択してください',
            'interviewer_ids.*.integer' => '面接官の指定が正しくありません',
            'interviewer_ids.*.distinct' => '同じ面接官が重複して指定されています',
            'interviewer_ids.*.exists' => '指定されたユーザーが存在しません',
        ];
    }

    /**
     * 追加バリデーション。
     * ① 指定された各ユーザーが interviewer ロールを持つこと
     *    (ルールだけでは表現できないため、設計書「入力チェック定義」の interviewer_ids.* 備考)
     * ② 新たに追加する面接官が有効なユーザーであること(REQ-013:無効化ユーザー)。
     *    既にこのラウンドにアサイン済みの面接官は、後から無効化されていてもそのまま残せる。
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return; // 単項目エラーがある時は先に進めない
                }

                $ids = array_map('intval', $this->input('interviewer_ids', []));

                // ① 面接官ロールの保有
                $interviewerIds = User::whereIn('id', $ids)
                    ->whereHas('roles', fn ($q) => $q->where('role', UserRole::Interviewer))
                    ->pluck('id')
                    ->all();

                if (! empty(array_diff($ids, $interviewerIds))) {
                    $validator->errors()->add('interviewer_ids', '指定されたユーザーの中に面接官でない人が含まれています');

                    return;
                }

                // ② 新たに追加する面接官が有効であること
                $alreadyAssignedIds = $this->candidacy->interviewers()
                    ->wherePivot('round', $this->integer('round'))
                    ->pluck('users.id')
                    ->all();

                $inactiveNames = User::whereIn('id', array_diff($ids, $alreadyAssignedIds))
                    ->where('is_active', false)
                    ->pluck('name')
                    ->all();

                if (! empty($inactiveNames)) {
                    $validator->errors()->add(
                        'interviewer_ids',
                        '無効化されたユーザーは面接官に追加できません('.implode('、', $inactiveNames).')'
                    );
                }
            },
        ];
    }
}
