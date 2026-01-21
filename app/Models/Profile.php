<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'profiles';
    protected $fillable = [
        'user_id',
        'level',
        'character_name',
        'completed_levels',
    ];
    protected $casts = [
        'completed_levels' => 'array'
    ];
    //
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
