<?php

namespace App\Notifications;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApprovalActivityNotification extends Notification
{
    use Queueable;

    public function __construct(private ApprovalRequest $approval, private string $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = str($this->approval->action_type)->replace('.', ' ')->headline()->toString();

        return [
            'business_id' => $this->approval->business_id,
            'approval_id' => $this->approval->public_id,
            'event' => $this->event,
            'action_type' => $this->approval->action_type,
            'amount' => $this->approval->amount,
            'message' => $this->event === 'requested'
                ? $label.' needs your review.'
                : $label.' was '.$this->approval->status->value.'.',
        ];
    }
}
