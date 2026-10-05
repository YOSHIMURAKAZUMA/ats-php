<?php

namespace App\Http\Requests;

use App\Models\Candidacy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * 面接評価入力の認可(権限マトリクス:面接官・担当分のみ)。
     * CandidacyPolicy@evaluate で判定する。
     */
    public function authorize(): bool
    {
        $candidacy = Candidacy::find($this->route('id'));

        return $candidacy !== null && $this->user()->can('evaluate', $candidacy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // 対象ラウンドは選考の現在ステータスから決まるため、
        // 現在のラウンドのみを in ルールとして動的に組み立てる(画面を開いた後のステータス変更を検知する)
        $id = $this->route('id');
        $candidacy = $id ? Candidacy::find($id) : null;
        $round = $candidacy?->status->round();

        return [
            'round' => ['required', 'integer', Rule::in($round !== null ? [$round] : [])],
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'round.required' => '対象ラウンドの指定が正しくありません',
            'round.integer' => '対象ラウンドの指定が正しくありません',
            'round.in' => '選考ステータスが変更されたため、評価を保存できませんでした。画面を開き直してください。',
            'score.required' => '評価スコアを選択してください',
            'score.integer' => '評価スコアは1~5の範囲で入力してください',
            'score.between' => '評価スコアは1~5の範囲で入力してください',
            'comment.string' => '所感の形式が正しくありません',
            'comment.max' => '所感は1000文字以内で入力してください',
        ];
    }
}
