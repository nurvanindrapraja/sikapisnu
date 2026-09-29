<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'parent_code',
        'name',
        'level',
    ];
}
