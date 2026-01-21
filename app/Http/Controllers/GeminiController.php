<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

class GeminiController extends Controller
{
    public function ask(Request $request, GeminiService $gemini)
    {
        $prompt = $request->input('prompt');

        $result = $gemini->generateText($prompt);
         
       //   return response()-> json($result); 
        return response()->json([
            'reply' => $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No response'
        ]);
    }
}
