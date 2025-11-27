<?php

namespace App\Http\Controllers;

use App\Models\Enemies;
use Illuminate\Http\Request;

class EnemiesController extends Controller
{
    //
    public function getEnemies(Request $request)
    {
        $request->validate([
            'difficulty' => 'required|in:Easy,Medium,Hard'
        ]);

        $Enemies = Enemies::where('difficulty', 'Easy')->get();

        // response 
        try {
            //code...

            return response()->json($Enemies);
        } catch (\Throwable $th) {
            return $th;
        }
    }
}
