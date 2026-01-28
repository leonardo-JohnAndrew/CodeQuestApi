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
    public function store(Request $request)
    {
        $request->validate([
            'completedLevels' => 'required|array'
        ]);

        $progress = Profile::updateOrCreate(
            ['user_id' => Auth::id()],
            ['completed_levels' => $request->completedLevels]
        );

        return response()->json([
            'message' => 'Progress saved',
            'completedLevels' => $progress->completed_levels
        ]);
    }

     public function checkAchievements()
{ 
    $userId = Auth::user()->id; 
    $profile = Profile::where('user_id', $userId)->first();

    if (!$profile) {
        return response()->json([
            'message' => 'Profile not found'
        ], 404);
    }

    $completedLevels = $profile->completed_levels ?? [];
    $completedCount = count($completedLevels);

    $easy = 0;
    $intermediate = 0;
    $hard = 0;
    $complete = 0;

    if ($completedCount > 7) {
        $easy = 1;
    }

    if ($completedCount > 15) {
        $intermediate = 1;
    }

    if ($completedCount > 23) {
        $hard = 1;
        $complete = 1;
    }

    $profile->update([
        'easy_achievement' => $easy,
        'intermediate_achievement' => $intermediate,
        'hard_achievement' => $hard,
        'complete_achievement' => $complete,
    ]);

    return response()->json([
        'message' => 'Achievements updated',
        'completed_levels_count' => $completedCount,
        'achievements' => [
            'easy' => $easy,
            'intermediate' => $intermediate,
            'hard' => $hard,
            'complete' => $complete,
        ]
    ]);
}

}
