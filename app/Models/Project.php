<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'cohort_id',
        'team_id',
        'project_title',
        'project_description',
        'status',
        'is_deleted'
    ];

    protected $hidden = [
        'team_id',
        'cohort_id',
        'is_deleted'
    ];
}
