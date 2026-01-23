<?php

namespace App\Http\Controllers;

use App\Mail\MailVerification;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Rules\PasswordValidation;
use Carbon\Carbon;
use DateTime;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Validation\Rule;
//use League\Config\Exception\ValidationException;
use PhpParser\Error;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Component\Mime\Message;

use function PHPUnit\Framework\returnArgument;

class AuthControlle extends Controller
{
   

     // protected stpass; 
      public function register(Request $request)
    {

        try{

            $request->validate([
                'email' => 'required|email|unique:users,email',
                'username' => 'required', 
                'character' => 'required', 
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
                'password' =>  Hash::make($request->password),
                "updated_at" => now(),
                
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
            'email' => 'email|required', 
            'password' => 'required'
        ]); 
        
        try {
            // 
            $user = User::with('profile')
                ->where('email', $request->email)
                ->first();
                
                
            if(!$user || !Hash:: check($request->password ,$user->password)){
                return response()->json([
                   'message' => 'Provided Credential is incorrect'
                ], 401); 
                 
            }

            ///$token 
            $token = $user->createToken($user->username); 
            
            return response()->json([
                 'user' => $user->username, 
                  'access_token' => $token->plainTextToken, 
                    'characterName' => $user->profile->character_name, 
                    'message' => "successLogin"
                ], 200); 
            } catch (\Throwable $th) {
            //throw $th;
            return $th; 
        }
    }
    public function SendCode(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    // Check if user exists
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'Account not exist'
        ], 404); // fits frontend easily
    }

    // Generate verification code
    $code = rand(100000, 999999);
    $expire = Carbon::now()->addMinute();

    DB::table('verification_code')->updateOrInsert(
        ['email' => $request->email],
        [
            'code' => $code,
            'expires_at' => $expire,
            'password' => null, // not needed here
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    // Send email
    try {
        Mail::to($request->email)->send(new MailVerification([
            'title' => 'CodeQuest Change Password Verification',
            'body' => $code,
            'username' => $user->username,
            'email' => $request->email
        ]));

        return response()->json([
            'message' => 'Code sent successfully'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'message' => 'Email not sent'
        ], 500);
    }
}

     //check Match 
     public function CheckCode(Request $request){
      $request->validate([
             "code" => 'required|integer', 
             "email"=> 'required|email' 
      ]);  
      
         try {
          //code...
          $record = DB::table('verification_code')
          ->where('email', $request->email)
          ->orderByDesc('id')
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

         $deleted = DB::table('verification_code')->where('email', $request->email)->delete();
         if($deleted){
            return response()->json([
              "message" => "match"
            ],200); 
         }
    
      
    } catch (\Throwable $th) {
          //throw $th;
        return response()->json($th); 
    }

     }


    public function sendMailVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'code' => 'required|integer', 
            'character' => 'required',
            'username' => 'required', 
           
        ]);
        
        $record = DB::table('verification_code')
        ->where('email', $request->email)
        ->orderByDesc('id')
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

      try {
          //code...
          $record = DB::table('verification_code')
          ->where('email', $request->email)
          ->orderByDesc('id')
          ->first();
          $user =   User::create([
            'email' => $request->email,
            'password' => $record->password,
            'username' => $request->username,
            'email_verified_at' => now()
        ]);
        if($user){
            Profile::create([
                'user_id' =>  $user->id, 
                'level' => "Beginner", 
                  'character_name' => $request->character, 
                  
            ]);
            $token = $user->createToken('api-token')->plainTextToken;
                DB::table('verification_code')->where('email', $request->email)->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Account created',
                    'token' => $token
                ], 200);
        }
        
      } catch (\Throwable $th) {
          //throw $th;
        return response()->json($th); 
    }
}

     // change Password 
    public function changePassword(Request $request)
  {
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'new_password' => ['required', new PasswordValidation('strong')],
        'confirm_password' => 'required|same:new_password',
    ]);

    //email send 

    $user = User::where('email', $request->email)->first();

    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'message' => 'Password changed successfully'
    ], 200);
  }

     //update the userProfile 


public function changeProfile(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'email' => [
            'nullable',
            'email',
            Rule::unique('users', 'email')->ignore($request->user_id)
        ],
        'username' => [
            'nullable',
            Rule::unique('users', 'username')->ignore($request->user_id)
        ],
        'password' => [
            'nullable',
            new PasswordValidation('strong')
        ]
    ]);

    $user = User::find($request->user_id);

    if ($request->email) {
        $user->email = $request->email;
    }

    if ($request->username) {
        $user->username = $request->username;
    }

    if ($request->password) {
        $user->password = Hash::make($request->password);
    }

    $user->save();

    return response()->json([
        'message' => 'Profile updated successfully'
    ], 200);
}


}

 