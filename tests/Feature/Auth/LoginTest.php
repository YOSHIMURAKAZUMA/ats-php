<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    /**
     * ログイン後は役割に応じた初期画面へ遷移する(REQ-019)
     * 複数の役割を持つ場合は admin > recruiter > interviewer の優先順で決まる
     *
     * @param  array<UserRole>  $roles
     */
    #[DataProvider('initialPageProvider')]
    public function test_user_is_redirected_to_initial_page_by_role(array $roles, string $routeName): void
    {
        // 準備
        $user = User::factory()->withRoles(...$roles)->create();

        // 実行
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // 検証
        $response->assertRedirect(route($routeName));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 役割の全組み合わせ(7通り)と、期待する初期画面
     *
     * @return array<string, array{array<UserRole>, string}>
     */
    public static function initialPageProvider(): array
    {
        return [
            // 役割が1つ
            'admin' => [[UserRole::Admin], 'users.index'],
            'recruiter' => [[UserRole::Recruiter], 'job-postings.index'],
            'interviewer' => [[UserRole::Interviewer], 'candidacies.index'],
            // 役割が2つ(優先順位の高い方)
            'admin and recruiter' => [[UserRole::Recruiter, UserRole::Admin], 'users.index'],
            'admin and interviewer' => [[UserRole::Interviewer, UserRole::Admin], 'users.index'],
            'recruiter and interviewer' => [[UserRole::Interviewer, UserRole::Recruiter], 'job-postings.index'],
            // 役割が3つ
            'all roles' => [[UserRole::Interviewer, UserRole::Recruiter, UserRole::Admin], 'users.index'],
        ];
    }

    /**
     * 未ログインで認証必須ページにアクセスした場合、ログイン後は元のページへ戻る
     *
     * 元のページは役割に関係なく優先されるため、役割は1つで確認する。
     * 初期画面(求人票一覧)と元のページ(応募者一覧)が異なる採用担当者を使い、
     * intended が効いたことを判別できるようにしている
     */
    public function test_user_is_redirected_to_intended_page_after_login(): void
    {
        $user = User::factory()->recruiter()->create();

        // 未ログインで応募者一覧にアクセスすると、ログイン画面へ誘導される
        $this->get(route('candidacies.index'))
            ->assertRedirect(route('login'));

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // 採用担当者の初期画面(求人票一覧)ではなく、元のページへ戻る
        $response->assertRedirect(route('candidacies.index'));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->recruiter()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
        $this->assertGuest();
    }

    /**
     * 未登録のメールアドレスでも、パスワード誤りと同じ文言を返す(ユーザー列挙攻撃の対策)
     */
    public function test_unregistered_email_shows_same_message_as_wrong_password(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'unregistered@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
        $this->assertGuest();
    }

    /**
     * 無効化されたユーザーはログインできない(REQ-013)
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->interviewer()->inactive()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'このアカウントは無効化されています。',
        ]);
        $this->assertGuest();
    }

    /**
     * 無効かつ役割なしの場合は、無効の文言を優先する(判定順:無効 → 役割なし)
     */
    public function test_inactive_message_takes_precedence_over_no_role_message(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'このアカウントは無効化されています。',
        ]);
        $this->assertGuest();
    }

    /**
     * 役割を1つも持たないユーザーはログインできない(REQ-019)
     */
    public function test_user_without_role_cannot_login(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'このアカウントには役割が設定されていません。管理者にお問い合わせください。',
        ]);
        $this->assertGuest();
    }

    public function test_email_and_password_are_required(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    /**
     * ログアウト後はログイン画面へ遷移し、認証必須の画面にはアクセスできない(REQ-018)
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->recruiter()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get(route('job-postings.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * 5回連続で失敗すると、6回目は正しいパスワードでもロックされる(設計書「エラーハンドリング定義」429)
     */
    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->recruiter()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->attemptLogin($user->email, 'wrong-password');
        }

        // 5回目まではロックされず、通常の失敗の文言
        $this->attemptLogin($user->email, 'wrong-password')
            ->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);

        // 6回目は正しいパスワードでもロックされる
        $response = $this->attemptLogin($user->email, 'password');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => '試行回数が上限に達しました。しばらく時間をおいてから再度お試しください。',
        ]);
        $this->assertGuest();
    }

    public function test_login_is_available_again_after_lockout_expires(): void
    {
        $user = User::factory()->recruiter()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin($user->email, 'wrong-password');
        }

        // ロック時間(60秒)が過ぎるまで時間を進める
        $this->travel(61)->seconds();

        $this->attemptLogin($user->email, 'password')
            ->assertRedirect(route('job-postings.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        $user = User::factory()->recruiter()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->attemptLogin($user->email, 'wrong-password');
        }
        $this->attemptLogin($user->email, 'password');
        $this->post(route('logout'));

        // 成功で回数がリセットされていれば、さらに4回失敗してもロックされない(リセットされなければ合計8回)
        for ($i = 0; $i < 4; $i++) {
            $this->attemptLogin($user->email, 'wrong-password');
        }

        $this->attemptLogin($user->email, 'password')
            ->assertRedirect(route('job-postings.index'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * ロックはメールアドレスとIPアドレスの組ごと。
     * 他人がそのメールアドレスで失敗を繰り返しても、本人は別のIPからログインできる
     */
    public function test_lockout_does_not_affect_other_ip_address(): void
    {
        $user = User::factory()->recruiter()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin($user->email, 'wrong-password', '203.0.113.1');
        }

        $this->attemptLogin($user->email, 'password', '198.51.100.1')
            ->assertRedirect(route('job-postings.index'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 大文字・小文字を変えたメールアドレスでも、同じものとして回数を数える
     */
    public function test_lockout_ignores_email_case(): void
    {
        $user = User::factory()->recruiter()->create(['email' => 'recruiter@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin('Recruiter@Example.com', 'wrong-password');
        }

        $this->attemptLogin('recruiter@example.com', 'password')
            ->assertSessionHasErrors([
                'email' => '試行回数が上限に達しました。しばらく時間をおいてから再度お試しください。',
            ]);
        $this->assertGuest();
    }

    /**
     * ログイン画面からのログインを送信する(IPアドレスを指定できる)
     */
    private function attemptLogin(string $email, string $password, string $ip = '127.0.0.1'): TestResponse
    {
        return $this->from(route('login'))
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('login'), [
                'email' => $email,
                'password' => $password,
            ]);
    }
}
