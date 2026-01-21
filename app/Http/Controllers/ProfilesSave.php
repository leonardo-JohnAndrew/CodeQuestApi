<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Profile;
use Nette\Utils\Json;

class ProfilesSave extends Controller
{
    //show controller 
    public function show()
    {
        //user id 
        Auth::user()->id;

        // match sa profile table 
       return Profile::where('user_id', Auth::user()->id)->first();
    } 

    // levelshowCompletedOnly 

    public function levelshowCompletedOnly()
    {
        $profile = Profile::where('user_id', Auth::user()->id)->first();
        return $profile ?  response()->json([
            'completedLevels' => $profile->completed_levels
        ]) : response()->json([]);
    }
}
