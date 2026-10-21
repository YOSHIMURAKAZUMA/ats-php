<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 認証必須の画面はブラウザにキャッシュさせない(REQ-018)
 *
 * ログアウト後にブラウザの「戻る」で画面が再表示されないよう、
 * レスポンスに Cache-Control: no-store を付ける
 */
class AuthPageCacheTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('authPageProvider')]
    public function test_auth_page_is_not_stored_in_browser_cache(string $routeName): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route($routeName));

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    /**
     * ルートの書き方が異なる3種類から1つずつ選ぶ
     *
     * @return array<string, array{string}>
     */
    public static function authPageProvider(): array
    {
        return [
            'group with role:recruiter,admin' => ['job-postings.index'],
            'group with role:admin' => ['users.index'],
            'route with role middleware' => ['candidacies.index'],
        ];
    }

    /**
     * 公開ページ(応募者向け)は対象外
     */
    public function test_public_page_is_not_affected(): void
    {
        $response = $this->get(route('public.jobs.index'));

        $response->assertOk();
        $this->assertStringNotContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
