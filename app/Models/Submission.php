<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'submitter_id',
        'submission_title',
        'submission_url',
        'submission_comment',
        'submission_attachments',
        'submission_week',
        'panelist_feedback'
    ];
}
