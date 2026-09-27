<?php

namespace App\Http\Requests;

use App\Models\Candidacy;
use App\Services\CandidacyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidacyStatusRequest extends FormRequest
{
    /**
     * ステータス変更の認可(権限マトリクス:採用担当者・管理者)。
     * CandidacyPolicy@updateStatus で判定する。
     */
    public function authorize(): bool
    {
        $candidacy = Candidacy::find($this->route('id'));

        return $candidacy !== null && $this->user()->can('updateStatus', $candidacy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // ルート {id} から対象選考を取得し、現在ステータスから
        // 遷移可能なコードのみを in ルールとして動的に組み立てる(設計書「入力チェック定義」)
        $id = $this->route('id');
        $candidacy = $id ? Candidacy::find($id) : null;
        $allowed = $candidacy instanceof Candidacy ? CandidacyService::allowedNextValues($candidacy->status) : [];

        return [
            'status' => ['required', 'integer', Rule::in($allowed)],
            'send_notification' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => '変更先ステータスは必須です',
            'status.integer' => '変更先ステータスの指定が正しくありません',
            'status.in' => 'そのステータスへは変更できません',
            'send_notification.required' => 'メール通知の送信要否を指定してください',
            'send_notification.boolean' => 'メール通知の送信要否の指定が正しくありません',
        ];
    }
}
