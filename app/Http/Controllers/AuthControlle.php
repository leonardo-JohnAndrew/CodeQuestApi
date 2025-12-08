<?php

namespace App\Http\Controllers;

use App\Mail\MailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Rules\PasswordValidation;
use Carbon\Carbon;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\Fluent\Concerns\Has;
use League\Config\Exception\ValidationException;
use PhpParser\Error;
use PhpParser\Node\Stmt\TryCatch;

class AuthControlle extends Controller
{
    // Facebook login endpoint
    // public function facebookLogin(Request $request)
    // {
    //     $token = $request->input('facebook_token');

    //     // Call Facebook Graph API
    //     $fbResponse = Http::get('https://graph.facebook.com/me', [
    //         'fields' => 'id,name,email',
    //         'access_token' => $token
    //     ]);

    //     if ($fbResponse->failed()) {
    //         return response()->json(['error' => 'Invalid Facebook token'], 401);
    //     }

    //     $fbUser = $fbResponse->json();

    //     // Check if user exists or needs to be created
    //     $user = User::where('facebook_id', $fbUser['id'])->first();

    //     $isNew = false;

    //     if (!$user) {
    //         $user = User::create([
    //             'facebook_id' => $fbUser['id'],
    //             'username' => $fbUser['name'],
    //             'email' => $fbUser['email'] ?? $fbUser['id'] . '@facebook.local',
    //             'password' => bcrypt(Str::random(16))
    //         ]);

    //         $user->profile()->create([
    //             'level' => 'Beginner',
    //             'avatar_url' => null
    //         ]);

    //         $isNew = true;
    //     }

    //     // Create access token
    //     $apiToken = $user->createToken('fb-login')->plainTextToken;

    //     return response()->json([
    //         'user' => $user,
    //         'token' => $apiToken,
    //         'is_new_user' => $isNew
    //     ]);
    // }
      public function register(Request $request)
    {

        try{

            $request->validate([
                'email' => 'required|email',
                'username' => 'required', 
                'password' => ['required', new PasswordValidation('strong')], 
            ]);
           
            
        }catch (ValidationException $e){
          return response()->json([
            'message'=> 'Validation Error'." ".$e->getMessage(), 
          ], 422); 
        }

        $code = rand(100000, 999999);
        $expire = Carbon::now()->addMinute(); // 1 minute
        // email send 
        DB::table('verification_code')->updateOrInsert(
            ['email' => $request->email],
            [
                'code' => $code,
                'expires_at' => $expire,
                "created_at" => now(),
                "updated_at" => now()
            ]
        );

        try {
            Mail::to($request->email)->send(new MailVerification([
                'title' => 'CodeQuest Verification',
                'body' => $code,
                'username' => $request->username, 
                 'email'=> $request->email
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Email not sent',
                 'dad' =>  $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Code sent'
        ]);

    }
     // login 
    public function login(Request $request){
        $request->validate([
            'email' => 'email', 
            'password' => 'required'
        ]); 
      
        try {
            // 
            $user = User::where('email' , $request->email) 
            ->first(); 

            if(!$user || !Hash:: check($request->password ,$user->password)){
                return response()->json([
                   'message' => 'Provided Credential is incorrect'
                ]); 
                 
            }

            ///$token 
            $token = $user->createToken($user->username); 

            return response()->json([
                 'user' => $user->username, 
                  'access_token' => $token->plainTextToken
            ], 200); 
        } catch (\Throwable $th) {
            //throw $th;
            return $th; 
        }
    }

    public function sendMailVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        $record = DB::table('verification_code')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'No code found'], 404);
        }

        if (Carbon::now()->gt($record->expires_at)) {
            return response()->json(['message' => 'Code expired'], 401);
        }

        if ($record->code != $request->code) {
            return response()->json(['message' => 'Code not match'], 401);
        }

      
        // User::create([
        //     'email' => $request->email,
        //     'password' => bcrypt($request->password)
        // ]);
        DB::table('verification_code')->where('email', $request->email)->delete();


        return response()->json([
            'status' => 'success',
            'message' => 'Account created'
        ]);
    }
}

