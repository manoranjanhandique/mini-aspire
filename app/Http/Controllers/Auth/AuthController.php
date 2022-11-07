<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\LoanMaster;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function checkAuth(Request $request)
    {
        // Initialize Response
        $response = [];
        // Get Username and Password from POST Request
        $username = htmlentities(trim($request['username'])) ?? '';
        $password = htmlentities(trim($request['password'])) ?? '';

        // Check if Username and Password are not empty
        if ($username == "" || $password == "") {
            $response['status'] = 'N';
            $response['message'] = 'Username or Password is empty';
            header('content-type: application/json');
            print_r(json_encode($response));
            exit();
        }

        // Get User from Database against the username
        $user= User::where('name', $request->username)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                $response['status'] = 'N';
                $response['message'] = 'These credentials do not match our records.';
                header('content-type: application/json');
                print_r(json_encode($response));
                exit();
            }

        $token = $user->createToken('aspire',['role:'.$user->role.''])->plainTextToken;

        if($token != '') {
            
            $response['status'] = 'Y';
            $response['username'] = $user->name;
            $response['message'] = 'Login Successful';
            $response['token'] = $token;
        } else {
            $response = [
                'status' => 'N',
                'message' => 'Error in generating token',
            ];
        }
        return response()->json($response,200);

    }
    public function logout(){
        auth()->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
    public function admin_logout(){
        auth()->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
     
}
