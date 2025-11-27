<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Powerups extends Model
{
    //
    public function Inventory()
    {
        return $this->hasMany(Inventory::class, 'powerup_id', 'id');
    }
    public function User(){
        return $this->hasMany(User::class , 'user_id', 'id'); 
    }
}
