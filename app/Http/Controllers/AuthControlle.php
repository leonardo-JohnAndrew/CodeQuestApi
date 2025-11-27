<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Rules\PasswordValidation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;
use League\Config\Exception\ValidationException;
use PhpParser\Node\Stmt\TryCatch;

class AuthControlle extends Controller
{
    // Facebook login endpoint
    public function facebookLogin(Request $request)
    {
        $token = $request->input('facebook_token');

        // Call Facebook Graph API
        $fbResponse = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email',
            'access_token' => $token
        ]);

        if ($fbResponse->failed()) {
            return response()->json(['error' => 'Invalid Facebook token'], 401);
        }

        $fbUser = $fbResponse->json();

        // Check if user exists or needs to be created
        $user = User::where('facebook_id', $fbUser['id'])->first();

        $isNew = false;

        if (!$user) {
            $user = User::create([
                'facebook_id' => $fbUser['id'],
                'username' => $fbUser['name'],
                'email' => $fbUser['email'] ?? $fbUser['id'] . '@facebook.local',
                'password' => bcrypt(Str::random(16))
            ]);

            $user->profile()->create([
                'level' => 'Beginner',
                'avatar_url' => null
            ]);

            $isNew = true;
        }

        // Create access token
        $apiToken = $user->createToken('fb-login')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $apiToken,
            'is_new_user' => $isNew
        ]);
    }
      public function register(Request $request)
    {
        // 'email',
        // 'password',
        // 'email',
        // 'username',
        // 'facebook_id',
        // 'preffered_language',
        // 'is_admin',
        // 'is_blacklisted'
        //return $request; 
        try{

            $request->validate([
                'email' => 'required|email',
                'username' => 'required', 
                'password' => ['required', new PasswordValidation('strong')], 
            ]);
           
        }catch (ValidationException $e){
          return response()->json([
            'message'=> 'Validation Error', 
            'errors'=> $e
          ], 422); 
        }
        // create the model or the data ; 
       try {
         $user = User::create([
                'email' => $request->email, 
                'username' => $request->username, 
                'password' => Hash::make($request->password)
         ]);
          if($user){
             $token = $user->createToken($request->email); 
             if($token){
                return response()->json([
                  'message' => 'Created Success', 
                  'username' => $user->username, 
                  'id' => $user->user_id, 
                  'access_token' => $token->plainTextToken 
                ],200); 
             }
          }
       } catch (\Throwable $th) {
        //throw $th;
            return $th; 
       }
    
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
}
