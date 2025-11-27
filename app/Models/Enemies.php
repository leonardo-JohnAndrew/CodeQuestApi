<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enemies extends Model
{
    //
    protected $table = "enemies";
    protected $fillable = [
        'difficulty',
        'is_Boss',
        'hp',
        'abilities'
    ];
}
