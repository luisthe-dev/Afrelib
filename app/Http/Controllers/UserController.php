<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\groupChat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function allUsers()
    {
        $users = User::paginate(25);

        foreach ($users as $user) {
            $user_role = $user->role_id;
            $user_role_name = Role::where(['role_id' => $user_role])->first();

            if (!$user_role_name) {
                $user->role_name = null;
            } else {
                $user->role_name = $user_role_name->role_name;
            }
        }

        return $users;
    }

    public function roleUsers($role_id)
    {
        $users = User::where(['role_id' => $role_id])->paginate(25);

        foreach ($users as $user) {
            $user_role = $user->role_id;
            $user_role_name = Role::where(['role_id' => $user_role])->first();

            if (!$user_role_name) {
                $user->role_name = null;
            } else {
                $user->role_name = $user_role_name->role_name;
            }
        }

        return $users;
    }

    public function createUser(CreateUserRequest $request)
    {

        if ($request->date_of_birth) $request->validate([
            'date_of_birth' => 'date'
        ]);

        $role = Role::where(['role_id' => $request->role_id])->first();

        if (!$role) return ErrorResponse('Invalid Role Selected');

        $user = User::where(['email' => $request->email])->count();

        if ($user > 0) return ErrorResponse('User with Email Already Exists');

        $user = new User([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'date_of_birth' => $request->date_of_birth,
            'password' => Hash::make($request->last_name),
            'role_id' => $role->role_id
        ]);

        if ($role->role_name == 'Student') {
            $request->validate([
                'school_name' => 'required|string'
            ]);

            $user->school_name = $request->school_name;
        }

        $user->save();

        $user->role_name = $role->role_name;

        if ($role->role_name == 'Mentor'){
            // Adding mentor to group chat 
            $group_id= base64_encode("admin"."mentor");

            // Adding new mentor to the group chat 

                $gchat= new groupChat;
                $gchat->team_id = $group_id;
                $gchat->team_name = "Mentor" . $group_id;
                $gchat->participant = $request->first_name;
                $gchat->userId = "Mentor" . rand(0000,9999);
                $gchat->role= "Mentor";
                $gchat->save();
        }

                 // Adding panelist to group chat 
        if ($role->role_name == 'Panelist'){
            $panel_id= base64_encode("Panelist");

            // Adding new panelist to the group chat 

                $gchat= new groupChat;
                $gchat->team_id = $panel_id;
                $gchat->team_name = "Panelist" . $group_id;
                $gchat->participant = $request->first_name;
                $gchat->userId = "Panelist" . rand(0000,9999);
                $gchat->role= "Panelist";
                $gchat->save();
        }
      


        return SuccessResponse('User Created Successfully', $user);
    }

    public function loginUser(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $User = User::where(['email' => $request->email])->first();


        if (!$User) return ErrorResponse('Invalid Login Parameters');

        if (!Hash::check($request->password, $User->password)) return ErrorResponse('Invalid Login Parameters');

        $role = Role::where(['role_id' => $User->role_id])->first();

        if (!$role) return ErrorResponse('Error Confirming User Identity');

        $UserToken = $User->createToken('User Access Token', ['User', $role->role_name]);

        $accessToken = $UserToken->accessToken;
        $accessToken->expires_at = Carbon::now()->addWeeks(6);

        $accessToken->save();

        $User->role_name = $role->role_name;

        $responseData = [
            'access_token' => explode('|', $UserToken->plainTextToken)[1],
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $UserToken->accessToken->expires_at
            )->toDateTimeString(),
            'UserDetails' => $User
        ];

        return SuccessResponse('User Logged In Successfully', $responseData);
    }
}
