<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Notifications\ApprovalActivityNotification;

class NotifyApprovalParticipantsAction
{
    public function reviewers(ApprovalRequest $approval): void
    {
        User::query()
            ->whereHas('memberships', fn ($query) => $query->where('business_id', $approval->business_id)->where('is_active', true))
            ->whereKeyNot($approval->requested_by)
            ->get()
            ->filter(fn (User $user) => $user->hasPermissionInBusiness(PermissionName::ApprovalsDecide, $approval->business_id))
            ->each->notify(new ApprovalActivityNotification($approval, 'requested'));
    }

    public function requester(ApprovalRequest $approval): void
    {
        $approval->requester->notify(new ApprovalActivityNotification($approval, 'decided'));
    }
}
