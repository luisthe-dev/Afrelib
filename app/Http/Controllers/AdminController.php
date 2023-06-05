<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdminRequest;
use App\Models\Admin;
use App\Models\groupChat;
use App\Models\chat;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{

    public function getAllAdmins()
    {
        return Admin::orderByDesc('created_at')->get();
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

        // return response()->json([$request->email]);

        $email = Admin::where('email', $request->email)->get();

        // Adding admin to group chat
        $group_id = base64_encode("admin" . "mentor");

        // Adding new admin to the group chat

        $chat = new chat;
        $chat->chatType = "AdminMentor";
        $chat->userId = $email[0]->id;
        $chat->firstName = $request->first_name;
        $chat->lastName = $request->last_name;
        $chat->email = $request->email;

        $chat->save();

        $role = Role::where("role_name", "Mentor")->get();
        $mentor_details = User::where("role_id", $role[0]->role_id)->get();

        if ($mentor_details->count() > 0) {
            for ($m = 0; $m < $mentor_details->count(); $m++) {

                $checkuser = chat::where("userId", $mentor_details[$m]->id)->where("chatName", "Admin and Mentor")->get();

                if ($checkuser->count() <= 0) {
                    $chat = new chat;
                    $chat->chatId = 8888;
                    $chat->chatName = "Admin and Mentor";
                    $chat->chatDescription = "Welcome to Admin and Mentor Group Chat";

                    $chat->chatType = "AdminMentor";
                    $chat->userId = $mentor_details[$m]->id;
                    $chat->firstName = $mentor_details[$m]->first_name;
                    $chat->lastName = $mentor_details[$m]->last_name;
                    $chat->email = $mentor_details[$m]->email;

                    $chat->save();
                }
            }
        }

        $admin_details = Admin::all();

        for ($t = 0; $t < $admin_details->count(); $t++) {

            $checkuser = chat::where("userId", $admin_details[$t]->id)->where("chatName", "Admin and Mentor")->where("email", $admin_details[$t]->email)->get();

            if ($checkuser->count() <= 0) {
                $chat = new chat;
                $chat->chatId = 8888;
                $chat->chatName = "Admin and Mentor";
                $chat->chatDescription = "Welcome to Admin and Mentor Group Chat";

                $chat->chatType = "AdminMentor";
                $chat->userId = $admin_details[$t]->id;
                $chat->firstName = $admin_details[$t]->first_name;
                $chat->lastName = $admin_details[$t]->last_name;
                $chat->email = $admin_details[$t]->email;

                $chat->save();
            }
        }



        // Adding new admin to the group chat

        $gchat = new groupChat;
        $gchat->team_id = $group_id;
        $gchat->team_name = "Admin" . $group_id;
        $gchat->participant = $request->first_name;
        $gchat->userId = $email[0]->id;
        $gchat->role = "Admin";
        $gchat->save();

        $gchat = new groupChat;
        $gchat->team_id = $group_id;
        $gchat->team_name = "Admin" . $group_id;
        $gchat->participant = $request->first_name;
        $gchat->userId = "Admin" . rand(0000, 9999);
        $gchat->role = "Admin";
        $gchat->save();


        $chat = new chat;
        $chat->chatId = 8888;
        $chat->chatName = "Admin and Mentor";
        $chat->chatDescription = "Welcome to Admin and Mentor Group Chat";

        $chat->chatType = "AdminMentor";
        $chat->userId = "Admin" . rand(0000, 9999);
        $chat->firstName = $request->first_name;
        $chat->lastName = $request->last_name;
        $chat->email = $request->email;

        $chat->save();

        $chat->chatType = "AdminMentor";
        $chat->userId = $email[0]->id;
        $chat->firstName = $request->first_name;
        $chat->lastName = $request->last_name;
        $chat->email = $request->email;

        $chat->save();

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
