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

    public function problem_attempts(){
         return $this->belongsTo(ProblemAttempts::class ,'problem_id', 'id'); 
    }
    public function user(){
         return $this->belongsTo(User::class ,'user_id', 'id'); 
    }
}
