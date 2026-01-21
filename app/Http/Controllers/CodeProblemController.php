<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 60);

use App\Models\CodeProblems;
use Illuminate\Http\Request;
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

        return CodeProblems::where('difficulty', $request->difficulty)
            ->inRandomOrder()
            ->limit(5)
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

            return response()->json([
                'piston_correct' => $matchesExpected,
                'correct' => $matchesExpected && $aiCorrect,
                'ai_reply' => $aiReply,
                'output' => $output
            ]);
        } else {
            // Non-writing code mode
            return response()->json([
                'correct'   => $matchesExpected,
                'output'    => $output,
                'expected'  => $expectedOutput,
                'stderr'    => $stderr,
                'exit_code' => $exitCode
            ]);
        }
    }
}
