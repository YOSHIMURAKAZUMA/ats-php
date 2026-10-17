<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
