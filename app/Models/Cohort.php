<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cohort extends Model
{
    use HasFactory;

    protected $fillable = [
        'cohort_id',
        'cohort_name',
        'cohort_description',
        'cohort_teams',
        'cohort_mentors',
        'cohort_panelists',
        'start_date',
        'end_date',
        'status',
        'is_deleted'
    ];

    protected $hidden = [
        'cohort_teams',
        'cohort_mentors',
        'cohort_panelists',
        'is_deleted'
    ];
}
