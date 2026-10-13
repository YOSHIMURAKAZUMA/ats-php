<?php

namespace App\Notifications;

use App\Enums\CandidacyStatus;
use App\Models\Candidacy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidacyStatusChangedNotification extends Notification
{
    /**
     * 選考ステータス変更を応募者へ知らせるメール通知(REQ-011)。
     * 変更後ステータスは選考の現在値ではなく引数で受け取る
     * (再送時に、その後さらにステータスが進んでいても、変更当時の内容で送るため)。
     */
    public function __construct(
        private readonly Candidacy $candidacy,
        private readonly CandidacyStatus $toStatus,
        private readonly string $ccEmail,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $jobPostingTitle = $this->candidacy->jobPosting->title;

        return (new MailMessage)
            ->subject(" 【選考状況のお知らせ】{$jobPostingTitle}")
            ->cc($this->ccEmail)
            ->view(['text' => 'mail.candidacy-status-changed'], [
                'candidateName' => $this->candidacy->candidate->name,
                'jobPostingTitle' => $jobPostingTitle,
                'statusLabel' => $this->toStatus->label(),
            ]);
    }
}
