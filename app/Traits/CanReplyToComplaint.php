<?php

namespace App\Traits;

trait CanReplyToComplaint
{
    public function complaintReplies()
    {
        return $this->morphMany(\App\Models\ComplaintReply::class, 'replier');
    }
}
