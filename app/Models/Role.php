<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'role_name',
        'is_deleted'
    ];

    protected $hidden = [
        'is_deleted'
    ];
}
