<?php

namespace App\Http\Controllers;

use App\Models\Powerups;
use Illuminate\Http\Request;

class PowerupController extends Controller
{
    //show 
    public function showAll(){
         $itemlist = Powerups::all(); 
         return $itemlist? $itemlist : \response()->json([
                  "message" => "No Item in database"
         ]) ;  
    }
}
