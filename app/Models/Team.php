<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'team_name',
        'team_description',
        'team_members',
        'team_mentor',
        'is_deleted'
    ];

    protected $hidden = [
        'team_members',
        'team_mentor'
    ];
}
