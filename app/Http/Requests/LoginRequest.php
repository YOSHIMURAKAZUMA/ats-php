<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        $user = Auth::user();

        // 無効化は管理者が意図して設定した状態のため、役割の有無より先に判定し、本人に正しい理由を伝える(REQ-013)
        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'このアカウントは無効化されています。',
            ]);
        }

        // 役割を1つも持たないユーザーはログイン不可(REQ-019)
        if ($user->primaryRole() === null) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'このアカウントには役割が設定されていません。管理者にお問い合わせください。',
            ]);
        }

        $this->session()->regenerate();
    }
}
