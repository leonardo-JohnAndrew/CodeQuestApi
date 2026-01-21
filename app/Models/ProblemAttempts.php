<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProblemAttempts extends Model
{
    // 
    protected $table = "problem_attempts"; 

    protected $fillable = [
        "user_id", 
        "code_problem_id", 
        "is_correct", 
        "category"

    ];
     
    public function code_problem(){
        return $this->belongsTo(CodeProblems::class ,"problem_id", 'id'); 
    }

}
