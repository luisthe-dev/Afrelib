<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Updates extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_week',
        'update_title',
        'update_description',
    ];
}
