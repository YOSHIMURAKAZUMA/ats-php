<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * ログインの連続失敗の上限回数と、上限に達した後にロックする秒数
     * (設計書「エラーハンドリング定義」429)
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

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
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        // 認証情報が正しければ、失敗回数をリセットする
        RateLimiter::clear($this->throttleKey());

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

    /**
     * 連続失敗が上限に達していれば、ログインを受け付けない
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => '試行回数が上限に達しました。しばらく時間をおいてから再度お試しください。',
        ]);
    }

    /**
     * 失敗回数を数える単位(メールアドレス + IPアドレス)
     *
     * メールアドレスは小文字にそろえ、大文字・小文字を変えて回数をすり抜けられないようにする
     */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
