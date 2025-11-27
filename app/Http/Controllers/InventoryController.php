<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Powerups;
use App\Models\User;
use Exception;
use GuzzleHttp\Promise\Create;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class InventoryController extends Controller
{
    //get all power ups send to fe unity 
    public function getPowerups()
    {
         $powerups = Powerups::all(); 

          return $powerups? $powerups : response()->json(['No powerups in database']);  
        
    }
     

    // add to inventory 
    public function addInventory(Powerups $itemId, Request $request){ 
       $user_id =  FacadesAuth::user()->id; 

       if(!$user_id){
         return \response()->json([
             'Message' => 'Unknown user'
         ], 401); 
       }
       // checking for inventory 
       $findItem =  Inventory::where('powerup_id',$itemId->id)->first(); 
        if($findItem !== null){
              $findItem->quantity = $findItem->quantity + 1;  
              $findItem->save(); 

              return response()->json([
                "message"=>"Update Item Success"
              ]); 
        }else {
              // create 
            try {
                 $addItem = Inventory::create([
                   'user_id'=>$user_id, 
                   'powerup_id'=> $itemId->id, 
                   'quantity'=> $request->quantity
                 ]); 
                 
                if(!$addItem){
                  return \response()->json([
                     "message" => "Error Add to Inventory"
                  ]); 
                }
                return \response()->json([
                  "message" => "Added to Inventory Success"
                ]); 
            } catch (\Throwable $th) {
                //throw $th;
                return $th; 
            }
        }
     
    }
    
    public function getInventory()
    {
         $userid = FacadesAuth::user()->id; 
         return Inventory::with('user')
          ->where('user_id',$userid)
          ->get();  
    }
    public function removeItem(Powerups $itemId ){
        $findItem = Inventory::where('powerup_id', $itemId->id)->first(); 
        $userid = FacadesAuth::user()->id; 

        if($userid && $findItem){
        try {
            //code 
            
             $findItem->quantity = $findItem->quantity - 1;
             if(!$findItem){
                return response()->json([
                 "message" => 'Error Minus'
                ]); 
             }  
             $findItem->save();
            $findItem = Inventory::where('powerup_id', $itemId->id)->first();
            if($findItem->quantity == 0){
                $findItem = Inventory::where('powerup_id', $itemId->id)->delete();
            }

                return \response()->json([
                "message"=> "Successfully Subtract"
             ]); 
        } catch (\Throwable $th) {
            //throw $th;
            return $th;  
        }
        }
    }
    public function  updateItem(Powerups $itemId , Request $request)
    {
       $userid = FacadesAuth::user()->id; 
       $findItem = Inventory::where('powerup_id', $itemId->id); 
       
        if($userid && $findItem){
            if($request->quantity == 0){
                $findItem = Inventory::where('powerup_id', $itemId->id)->delete();
                 
                
            }
        }
    }
    
    
}
