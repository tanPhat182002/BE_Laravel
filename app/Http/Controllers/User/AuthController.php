<?php

namespace App\Http\Controllers\User;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

use App\Http\Controllers\Controller;


class AuthController extends Controller
{
     // API trả về URL để redirect đến Google
     public function getGoogleSignInUrl()
     {
         try {
             $url = Socialite::driver('google')
                 ->stateless()
                 ->redirect()
                 ->getTargetUrl();
 
             return response()->json([
                 'url' => $url
             ]);
         } catch (Exception $e) {
             return response()->json([
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     // API xử lý callback từ Google
     public function handleGoogleCallback()
     {
         try {
             $googleUser = Socialite::driver('google')
                 ->stateless()
                 ->user();
             $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();
 
             if (!$user) {
                 $user = User::create([
                     'name' => $googleUser->name,
                     'email' => $googleUser->email,
                     'google_id' => $googleUser->id,
                     'password' => bcrypt(Str::random(16)),
                    
                 ]);
             } else if (!$user->google_id) {
                 $user->google_id = $googleUser->id;
                 $user->save();
             }
 
             $token = $user->createToken('auth_token')->plainTextToken;
 
             return response()->json([
                 'access_token' => $token,
                 'token_type' => 'Bearer',
                 'user' => $user
             ]);
 
         } catch (Exception $e) {
             return response()->json([
                 'error' => $e->getMessage()
             ], 500);
         }
     }
     public function getFacebookSignInUrl()
     {
         try {
             $url = Socialite::driver('facebook')
                 ->stateless()
                 ->redirect()
                 ->getTargetUrl();
     
             return response()->json([
                 'url' => $url
             ]);
         } catch (Exception $e) {
             return response()->json([
                 'error' => $e->getMessage()
             ], 500);
         }
     }
     
     // API xử lý callback từ Facebook
     public function handleFacebookCallback()
     {
         try {
            
             $facebookUser = Socialite::driver('facebook')
                 ->stateless()
                 ->user();
                 
     
             $user = User::where('facebook_id', $facebookUser->id)
                         ->orWhere('email', $facebookUser->email)
                         ->first();
     
             if (!$user) {
                 $user = User::create([
                     'name' => $facebookUser->name,
                     'email' => $facebookUser->email,
                     'facebook_id' => $facebookUser->id,
                     'password' => bcrypt(Str::random(16)),
                 ]);
             } else if (!$user->facebook_id) {
                 $user->facebook_id = $facebookUser->id;
                 $user->save();
             }
     
             $token = $user->createToken('auth_token')->plainTextToken;
     
             return response()->json([
                 'access_token' => $token,
                 'token_type' => 'Bearer',
                 'user' => $user
             ]);
     
         } catch (Exception $e) {
             return response()->json([
                 'error' => $e->getMessage()
             ], 500);
         }
     }
}