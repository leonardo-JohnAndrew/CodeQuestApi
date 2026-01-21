<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 60);

use App\Models\CodeProblems;
use App\Models\ProblemAttempts;
use App\Models\Profile;
use App\Models\User;
use Carbon\Traits\ToStringFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class CodeProblemController extends Controller
{

    
    // ---------------------------------------------------
    // GET APPRORIATE ENEMY 
    // ---------------------------------------------------
    private function AnalyePlayerStatus(){
        
    }
    // ---------------------------------------------------
    // GET RANDOM PROBLEMS
    // ---------------------------------------------------
    public function getProblems(Request $request)
    {
        $request->validate([
            'difficulty' => 'required|in:Easy,Medium,Hard'
        ]);

        $userId = Auth::id();

        // kunin lahat ng problem IDs na nasagutan na ng user
        $answeredProblemIds = ProblemAttempts::where('user_id', $userId)
            ->pluck('code_problem_id');

        return CodeProblems::where('difficulty', $request->difficulty)
            ->whereNotIn('id', $answeredProblemIds)
            // ->inRandomOrder()
            ->limit(1)
            ->get();
    }

    // ---------------------------------------------------
    // CHECK SOLUTION USING PISTON API
    // ---------------------------------------------------
    // public function checkSolution(Request $request)
    // {

    //     $request->validate([
    //         'problem_id' => 'required|integer|exists:code_problems,id',
    //         'mode' => 'required',
    //         'code' => ['required', 'string', function ($attr, $value, $fail) {
    //             if (trim($value) === '') {
    //                 $fail('Code cannot be empty.');
    //             }
    //         }],

    //     ]);

    //     // Get problem from DB
    //     $problem = CodeProblems::find($request->problem_id);
    //     $expectedOutput = trim($problem->expected_output);
    //     $userCode = trim($request->code);


    //     // No modifications – Piston accepts multiline or inline
    //     $pistonRequest = [
    //         'language' => 'java',
    //         'version'  => '15.0.2',
    //         'files' => [
    //             [
    //                 // THE FILE NAME MUST BE EXACTLY THIS:
    //                 'name'    => "Main.java",
    //                 'content' => $userCode
    //             ]
    //         ]
    //     ];

    //     // Send request to Piston
    //     $response = Http::withOptions(['verify' => false])
    //         ->post("https://emkc.org/api/v2/piston/execute", $pistonRequest);

    //     if ($response->failed()) {
    //         return response()->json([
    //             'error'   => 'Execution failed',
    //             'status'  => $response->status(),
    //             'message' => $response->json()['message'] ?? 'Unknown error',
    //         ], $response->status());
    //     }

    //     $result = $response->json();

    //     // Extract outputs
    //     $output   = trim($result['run']['output'] ?? '');
    //     $stderr   = trim($result['run']['stderr'] ?? '');
    //     $exitCode = $result['run']['code'] ?? null;

    //     if($request->mode == "writingCode"){
    //           // call the checkCodeStructure 
    //          if($this->checkCodeStructure($request->code) == true){
    //             function normalize($str)
    //             {
    //                 return trim(preg_replace('/\s+/', ' ', $str));
    //             }

    //             $correct = normalize($output) === normalize($expectedOutput);
    //             return \response()->json([
    //                  'correct'   => $correct
    //              ]);     
    //                 }else {
    //               return \response()->json([
    //                        "correct" => false
    //               ]); 
    //          }
    //     }else {

    //         return response()->json([
    //             'correct'   => ($output === $expectedOutput),
    //             'output'    => $output,
    //             'expected'  => $expectedOutput,
    //             'stderr'    => $stderr,
    //             'exit_code' => $exitCode
    //         ]);
    //     }

    // }
    public function checkSolution(Request $request)
    {
        $request->validate([
            'problem_id' => 'required|integer|exists:code_problems,id',
            'mode' => 'required',
            'code' => ['required', 'string', function ($attr, $value, $fail) {
                if (trim($value) === '') $fail('Code cannot be empty.');
            }],
        ]);

        // Get problem from DB
        $problem = CodeProblems::find($request->problem_id);
        $category = $problem->category; 
        $expectedOutput = trim($problem->expected_output);
        $userCode = trim($request->code);
      
        // Piston request
        $pistonRequest = [
            'language' => 'java',
            'version'  => '15.0.2',
            'files' => [
                [
                    'name'    => "Main.java",
                    'content' => $userCode
                    ]
            ]
        ];
        
        $response = Http::withOptions(['verify' => false])
            ->post("https://emkc.org/api/v2/piston/execute", $pistonRequest);
            
            if ($response->failed()) {
                return response()->json([
                    'error'   => 'Execution failed',
                    'status'  => $response->status(),
                    'message' => $response->json()['message'] ?? 'Unknown error',
                ], $response->status());
            }
            
            $result   = $response->json();
            $output   = trim($result['run']['output'] ?? '');
            $stderr   = trim($result['run']['stderr'] ?? '');
            $exitCode = $result['run']['code'] ?? null;
            
            // Normalize output
            $normalize = fn($str) => trim(preg_replace('/\s+/', ' ', $str));
            $matchesExpected = $normalize($output) === $normalize($expectedOutput);
            
            if ($request->mode == "writingCode") {
                // -------------------------------
                // GEMINI AI EVALUATION
                // -------------------------------
                $requirements = $problem->code_requirements ?: "Category: {$problem->category}. Problem: {$problem->problem}";
                
                $apiKey = config('services.gemini.key');
                $geminiPrompt = <<<PROMPT
                You are an expert coding evaluator AI.
                
                Problem Description:
                {$requirements}
                
Player Code (Java):
{$userCode}

Check if the code follows the requirements and correctly solves the problem.
Reply ONLY with "Correct" or "Incorrect" and a short explanation.
PROMPT;

$geminiResponse = Http::post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $geminiPrompt]]
                        ]
                        ]
                        ]
                        )->json();
                        
                        $aiReply = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'Incorrect';
                        $aiCorrect = stripos($aiReply, 'Correct') !== false;
                        //add to addAttempts function 
                        
                        $this->addToAttempts($request->problem_id , $category , $matchesExpected && $aiCorrect); 
                        return response()->json([
                            'piston_correct' => $matchesExpected,
                            'correct' => $matchesExpected && $aiCorrect,
                            'ai_reply' => $aiReply,
                            'output' => $output
                        ]);
                    } else {
                        // Non-writing code mode
                        $this->addToAttempts($request->problem_id , $category , $matchesExpected); 
            return response()->json([
                'correct'   => $matchesExpected,
                'output'    => $output,
                'expected'  => $expectedOutput,
                'stderr'    => $stderr,
                'exit_code' => $exitCode
            ]);
        }
    }

    // function na nagdd sa problem attempts 
     public function addToAttempts($probId ,$category , $isCorrect ){
       //   return $probId .$category .$isCorrect; 
        // "user_id", 
        // "code_problem_id", 
        // "isCorrect", 
        // "category"
        ProblemAttempts::create([
        "user_id" => Auth::id(), // returns the currently logged-in user's ID, 
        "code_problem_id" => $probId, 
        "is_correct"=> $isCorrect , 
        "category"=> $category
        ]);
        
     }
 // ---------------------------------------------------
// WEAKNESS AND STRENGTH ANALYSIS
// ---------------------------------------------------
private function getStrengthWeaknessData($userId)
{
    $attempts = ProblemAttempts::where('user_id', $userId)->get();

    if ($attempts->isEmpty()) {
        return [
            'strengths' => [],
            'weaknesses' => [],
            'details' => []
        ];
    }

    $categoryStats = $attempts->groupBy('category')->map(function ($categoryAttempts) {
        $total = $categoryAttempts->count();
        $correct = $categoryAttempts->where('is_correct', true)->count();
        $successRate = ($total > 0) ? ($correct / $total) * 100 : 0;

        return [
            'total_attempts' => $total,
            'correct_attempts' => $correct,
            'success_rate' => round($successRate, 2)
        ];
    });

    return [
        'strengths' => $categoryStats->filter(fn($s) => $s['success_rate'] >= 70)->keys()->values(),
        'weaknesses' => $categoryStats->filter(fn($s) => $s['success_rate'] < 70)->keys()->values(),
        'details' => $categoryStats
    ];
}

  //all counts of the game and Give the number of win and loses base on the problemAttempts


 private function getPlayerGameStats($userId)
{
    $stats = DB::table('problem_attempts')
        ->selectRaw('
            COUNT(*) as total_games,
            SUM(is_correct = 1) as wins,
            SUM(is_correct = 0) as losses
        ')
        ->where('user_id', $userId)
        ->first();

    return [
        'total_games' => $stats->total_games ?? 0,
        'wins' => $stats->wins ?? 0,
        'losses' => $stats->losses ?? 0,
    ];
}

  

// Profile Retrieve 
  // ---------------------------------------------------
// PLAYER PROFILE API
// ---------------------------------------------------
public function profile()
{
    $userId = Auth::id();
    if (!$userId) {
        return response()->json(['error' => 'User not authenticated'], 401);
    }

    // User table
    $user = User::find($userId);

    // Profile table
    $profile = Profile::where('user_id', $userId)->first();

    // Stats
    $strengthWeakness = $this->getStrengthWeaknessData($userId);
    $gameStats = $this->getPlayerGameStats($userId);

    return response()->json([
        'weakness'   => $strengthWeakness['weaknesses'],
        'strength'  => $strengthWeakness['strengths'],

        'TotalGame' => $gameStats['total_games'],
        'Win'       => $gameStats['wins'],
        'Lose'      => $gameStats['losses'],

        'Email'     => $user->email,
        'Username'  => $user->username,
        'Level'     => $profile->level ?? 1
    ]);
}


}
