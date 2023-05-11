<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdminRequest;
use App\Models\Admin;
use App\Models\groupChat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{

    public function getAllAdmins()
    {
        return Admin::all();
    }

    public function createAdmin(CreateAdminRequest $request)
    {

        $newAdmin = new Admin([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
 
        $newAdmin->save();

        // Adding admin to group chat 
        $group_id= base64_encode("admin"."mentor");

        // Adding new admin to the group chat 
       
            $gchat= new groupChat;
            $gchat->team_id = $group_id;
            $gchat->team_name = "Admin" . $group_id;
            $gchat->participant = $request->first_name;
            $gchat->userId = "Admin" . rand(0000,9999);
            $gchat->role= "Admin";
            $gchat->save();

    
        return SuccessResponse('Admin Created Successfully', $newAdmin);
    }

    public function loginAdmin(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        $admin = Admin::where(['email' => $request->email])->first();

        if (!$admin) return ErrorResponse('Invalid Login Parameters');

        if (!Hash::check($request->password, $admin->password)) return ErrorResponse('Invalid Login Parameters');

        $UserToken = $admin->createToken('Admin Access Token', ['superiorAdmin']);

        $accessToken = $UserToken->accessToken;
        $accessToken->expires_at = Carbon::now()->addWeeks(6);

        $accessToken->save();

        $responseData = [
            'access_token' => explode('|', $UserToken->plainTextToken)[1],
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $UserToken->accessToken->expires_at
            )->toDateTimeString(),
            'adminDetails' => $admin
        ];

        return SuccessResponse('Admin Logged In Successfully', $responseData);
    }
}
