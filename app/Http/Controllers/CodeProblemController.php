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
    public function checkSolution(Request $request)
    {
        $request->validate([
            'problem_id' => 'required|integer|exists:code_problems,id',
            'code' => ['required', 'string', function ($attr, $value, $fail) {
                if (trim($value) === '') {
                    $fail('Code cannot be empty.');
                }
            }],

        ]);

        // Get problem from DB
        $problem = CodeProblems::find($request->problem_id);
        $expectedOutput = trim($problem->expected_output);
        $userCode = trim($request->code);

        // Ensure code is valid Java class format
        // No modifications – Piston accepts multiline or inline
        $pistonRequest = [
            'language' => 'java',
            'version'  => '15.0.2',
            'files' => [
                [
                    // THE FILE NAME MUST BE EXACTLY THIS:
                    'name'    => "Main.java",
                    'content' => $userCode
                ]
            ]
        ];

        // Send request to Piston
        $response = Http::withOptions(['verify' => false])
            ->post("https://emkc.org/api/v2/piston/execute", $pistonRequest);

        if ($response->failed()) {
            return response()->json([
                'error'   => 'Execution failed',
                'status'  => $response->status(),
                'message' => $response->json()['message'] ?? 'Unknown error',
            ], $response->status());
        }

        $result = $response->json();

        // Extract outputs
        $output   = trim($result['run']['output'] ?? '');
        $stderr   = trim($result['run']['stderr'] ?? '');
        $exitCode = $result['run']['code'] ?? null;

        return response()->json([
            'correct'   => ($output === $expectedOutput),
            'output'    => $output,
            'expected'  => $expectedOutput,
            'stderr'    => $stderr,
            'exit_code' => $exitCode
        ]);
    }


    // ---------------------------------------------------
    // CHECK CODE STRUCTURE USING JAVA ANALYZER
    // ---------------------------------------------------
    public function checkCodeStructure(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $sourceDir   = base_path('demo/src/main/java');
        $packagePath = $sourceDir . '/com/codechecker/JavaAnalyzer';
        $targetDir   = base_path('demo/target/classes');

        if (!File::exists($packagePath)) File::makeDirectory($packagePath, 0755, true);
        if (!File::exists($targetDir))   File::makeDirectory($targetDir, 0755, true);

        $userCode = trim($request->code);

        // Ensure package exists
        if (!preg_match('/^\s*package\s+com\.codechecker\.JavaAnalyzer\s*;/', $userCode)) {
            $userCode = "package com.codechecker.JavaAnalyzer;\n\n" . $userCode;
        }

        // Extract class name
        preg_match('/public\s+class\s+(\w+)/', $userCode, $matches);
        $className = $matches[1] ?? 'UserProgram';

        // Save temporary file
        $tempFile = $packagePath . "/$className.java";
        File::put($tempFile, $userCode);

        // Compile
        $compileCmd = "javac -d " . escapeshellarg($targetDir) . " " . escapeshellarg($tempFile) . " 2>&1";
        exec($compileCmd, $compileOutput, $compileReturn);

        if ($compileReturn !== 0) {
            File::delete($tempFile);
            return response()->json([
                'correct'        => false,
                'error'          => 'Compilation failed',
                'compile_output' => $compileOutput
            ], 400);
        }

        // Run analyzer through Maven
        $mavenDir  = base_path("demo");
        $pomFile   = $mavenDir . "/pom.xml";
        $mainClass = "com.codechecker.JavaAnalyzer.CodeAnalyzer";

        // Pass FILE PATH, not class name
        $analyzerCmd = sprintf(
            'mvn -f "%s" exec:java -Dexec.mainClass="%s" -Dexec.classpathScope=compile -Dexec.args=%s 2>&1',
            $pomFile,
            $mainClass,
            escapeshellarg($tempFile)
        );

        $analyzerOutput = [];
        $analyzerReturn = $this->execWithTimeout($analyzerCmd, $analyzerOutput, 30);

        File::delete($tempFile);
        File::delete("$packagePath/$className.class");

        // Timeout
        if ($analyzerReturn === -1) {
            return response()->json([
                'error' => 'Analyzer timed out',
                'cmd'   => $analyzerCmd
            ], 500);
        }

        // Parse analyzer output
        $str = implode('', $analyzerOutput);
        if (preg_match('/\{.*\}/s', $str, $matches)) {
            $str = $matches[0];
        } else {
            $str = '{}';
        }

        $report = [];
        foreach (explode(",", trim($str, "{}")) as $pair) {
            if (str_contains($pair, "=")) {
                [$k, $v] = explode("=", $pair);
                $report[trim($k)] = trim($v) === "true";
            }
        }

        return response()->json([
            'categories' => $report
        ]);
    }

    // ---------------------------------------------------
    // EXECUTE COMMAND WITH TIMEOUT
    // ---------------------------------------------------
    private function execWithTimeout($cmd, &$output, $timeout)
    {
        $start = microtime(true);

        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            return -1;
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = [];

        while (true) {
            $status  = proc_get_status($process);
            $running = $status['running'];

            $output[] = stream_get_contents($pipes[1]);
            $output[] = stream_get_contents($pipes[2]);

            if (!$running) break;

            if (microtime(true) - $start > $timeout) {
                proc_terminate($process, 9);
                proc_close($process);
                return -1;
            }

            usleep(50000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }
}
