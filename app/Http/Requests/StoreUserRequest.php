<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'distinct', Rule::enum(UserRole::class)],
            // REQ-013:10文字以上、英大文字・英小文字・数字をそれぞれ1文字以上含む
            'password' => [
                'required', 'string', 'min:10', 'max:255', 'confirmed',
                'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '氏名は必須です',
            'name.max' => '氏名は100文字以内で入力して下さい',
            'email.required' => 'メールアドレスは必須です',
            'email.email' => 'メールアドレスの形式が正しくありません',
            'email.max' => 'メールアドレスは255文字以内で入力して下さい',
            'email.unique' => 'このメールアドレスは既に使用されています',
            'roles.required' => '役割を1つ以上選択して下さい',
            'roles.array' => '役割を1つ以上選択して下さい',
            'roles.min' => '役割を1つ以上選択して下さい',
            'roles.*.integer' => '役割の指定が正しくありません',
            'roles.*.distinct' => '役割の指定が正しくありません',
            'roles.*.enum' => '役割の指定が正しくありません',
            'password.required' => 'パスワードは必須です',
            'password.min' => 'パスワードは10文字以上で入力して下さい',
            'password.max' => 'パスワードは255文字以内で入力して下さい',
            'password.confirmed' => 'パスワード(確認)が一致しません',
            'password.regex' => 'パスワードは英大文字・英小文字・数字をそれぞれ1文字以上含めて下さい',
        ];
    }
}
