<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Methods in the Controller
 * 1. register
 * 2. login
 * 3. logout
 * 4. profile
 * 
 * ROUTES
 * ===============================
 * Route::post('/login', [AuthController::class, 'login']);
 * Route::post('/register', [AuthController::class, 'register']);
 * 
 * Route::middleware('auth:sanctum')->group(function () {
 *    Route::post('/logout', [AuthController::class, 'logout']);
 * });
 * 
 */

class AuthController extends Controller
{
   /**
    * Request json
    *===================================================
    * api : http://127.0.0.1:8000/api/register
    * 
    * {
    *    "name": "John Doe",
    *    "email": "john@example.com",
    *    "password": "password123"
    *    "password_confirmation": "password123",
    *    "role": "admin"
    * }
    *
    *
    * Response json
    *===================================================
    *{
    *    "message": "Login successful",
    *    "user": {
    *        "name": "John Doe",
    *        "email": "john@example.com",
    *     },
    *     "token": "6|ZnbuzkfFlf24RMaOESiHue7jTsgTEeM1rQkJzB8J8669bfe1"
    *   }
    */
   public function register(Request $request)
   {
       try {
           $request->validate([
               'name' => 'required|string|max:255',
               'email' => 'required|string|email|max:255|unique:users',
               'password' => ['required', 'confirmed', Password::defaults()]
           ]);

           $user = User::create([
               'name' => $request->name,
               'email' => $request->email,
               'password' => Hash::make($request->password)
           ]);

           $token = $user->createToken('auth-token');

           return response()->json([
               'message' => 'User registered successfully',
               'user' => $user,
               'token' => $token->plainTextToken
           ], 201);

       } catch (ValidationException $e) {
           return response()->json([
               'message' => 'Validation failed',
               'errors' => $e->errors()
           ], 422);
       } catch (\Exception $e) {
           return response()->json([
               'message' => 'An error occurred during registration',
               'error' => $e->getMessage()
           ], 500);
       }
   }


    /**
    * Request json
    * api : [POST] http://127.0.0.1:8000/api/login
    * 
    * {
    *    "email": "john@example.com",
    *    "password": "password123"
    * }
    *
    *
    * Response json
    *{
    *    "message": "Login successful",
    *    "token": "5|MLy0JxtIXaa368j3WTzV3TQVMT2ite9c4fbkADDucb12b532",
    *    "user": {
    *        "name": "John Doe",
    *        "email": "john@example.com",
    *        "email_verified_at": null,
    *        "role": "agency_admin"
    *   }
    */
   public function login(Request $request)
   {
      try {
         $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token');

        return response()->json([
            'message' => 'Login successful',
            'token' => $token->plainTextToken,
            'user' => $user
        ]);

      } catch (ValidationException $e) {
         return response()->json([
             'message' => 'Invalid credentials',
             'errors' => $e->errors()
         ], 422);
     } catch (\Exception $e) {
         return response()->json([
             'message' => 'An error occurred during login',
             'error' => $e->getMessage()
         ], 500);
     }

   }

    /**
    * Request json
    * api :[POST] http://127.0.0.1:8000/api/logout
    * Authorization: Bearer <Token>
    *
    *
    * Response json
    *{
    *    "message": "Successfully logged out"
    *   }
    */   
   public function logout(Request $request)
   {
       try {
           $request->user()->currentAccessToken()->delete();
           
           return response()->json([
               'message' => 'Successfully logged out'
           ]);
       } catch (\Exception $e) {
           return response()->json([
               'message' => 'An error occurred during logout',
               'error' => $e->getMessage()
           ], 500);
       }
   }

   // Optional: Get authenticated user profile
   public function profile(Request $request)
   {
       return response()->json([
           'user' => $request->user()
       ]);
   }
}
