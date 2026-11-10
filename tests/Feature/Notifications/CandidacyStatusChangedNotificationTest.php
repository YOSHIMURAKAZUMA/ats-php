<?php

namespace Tests\Feature\Notifications;

use App\Enums\CandidacyStatus;
use App\Models\Candidacy;
use App\Notifications\CandidacyStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

/**
 * 選考ステータス変更の通知メールの内容(REQ-011)
 */
class CandidacyStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_contains_job_posting_title_without_leading_space(): void
    {
        $candidacy = Candidacy::factory()->create();
        $notification = new CandidacyStatusChangedNotification(
            $candidacy,
            CandidacyStatus::FirstInterview,
            'recruiter@example.com',
        );

        $mail = $notification->toMail(new AnonymousNotifiable);

        $this->assertSame('【選考状況のお知らせ】'.$candidacy->jobPosting->title, $mail->subject);
    }
}
