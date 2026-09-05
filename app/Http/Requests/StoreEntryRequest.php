<?php

namespace App\Http\Requests;

use App\Models\Candidacy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'resume' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '氏名は必須です',
            'name.max' => '氏名は100文字以内で入力してください',
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレスを入力してください',
            'email.max' => 'メールアドレスは255文字以内で入力してください',
            'phone.regex' => '電話番号は数字とハイフンのみで入力してください',
            'phone.max' => '電話番号は20文字以内で入力してください',
            'resume.required' => '履歴書(PDF)は必須です',
            'resume.file' => '履歴書はファイルをアップロードしてください',
            'resume.mimes' => '履歴書はPDF形式でアップロードしてください',
            'resume.extensions' => '履歴書はPDF形式でアップロードしてください',
            'resume.max' => '履歴書は5MB以内でアップロードしてください',
        ];
    }

    /**
     * 追加バリデーション(重複エントリー判定)。
     * ルールだけでは表現できない「同一求人 × 同一メール」の重複をここで検証する。
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return; // 単項目エラーがあるときは重複判定までは進めない
                }

                $jobPostingId = (int) $this->route('id');
                $email = $this->input('email');

                $exists = Candidacy::where('job_posting_id', $jobPostingId)->whereHas('candidate', fn ($q) => $q->where('email', $email))->exists();

                if ($exists) {
                    $validator->errors()->add('email', '既にこの求人へ応募済みです');
                }
            },
        ];
    }
}
