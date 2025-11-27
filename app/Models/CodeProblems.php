<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeProblems extends Model
{
    //
    protected $table =  "code_problems";
    protected $fillable = [
        'difficulty',
        'category',
        'problem',
        'solution',
        'solution_blocks',
        'decoy_blocks',
        'is_auto_generated'
    ];
    protected $casts = [
        'solution_blocks' => 'array',
        'decoy_blocks' => 'array',
    ];
}
