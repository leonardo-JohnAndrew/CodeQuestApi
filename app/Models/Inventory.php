<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ReturnTypeWillChange;

class Inventory extends Model
{
    //
     protected $table = "inventory"; 
     protected $fillable = [
        'quantity', 
        'user_id',
        'powerup_id', 
     ];
    public function powerup()
    {
        return $this->belongsTo(Powerups::class, "powerup_id", 'id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id','id'); 
    }
}
