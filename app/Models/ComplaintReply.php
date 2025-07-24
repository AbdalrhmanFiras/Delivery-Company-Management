<?php

namespace App\Models;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Model;

class ComplaintReply extends Model
{
    protected $guarded = ['id'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function replier()
    {
        return $this->morphTo();
    }
}
