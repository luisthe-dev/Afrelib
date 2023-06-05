<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\groupChat;
use App\Models\chat;
use App\Models\Admin;
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

        $email = User::where(['email' => $request->email])->get();
        
        // return response()->json([$email[0]->id]);


        if ($role->role_name == 'Mentor'){
            // Adding mentor to group chat 
            $group_id= base64_encode("admin"."mentor");

            // Adding new mentor to the group chat 

                $chat= new chat;
                $chat->chatId = 8888;
                $chat->chatName = "Admin and Mentor"; 
                $chat->chatDescription = "Welcome to Admin and Mentor Group Chat";

                $chat->chatType = "AdminMentor";
                $chat->userId = $email[0]->id;
                $chat->firstName = $request->first_name;
                $chat->lastName = $request->last_name;
                $chat->email = $request->email;
             
                $chat->save();

                $mentor_details = User::where("role_id", $role->role_id)->get();
                    if($mentor_details->count() > 0)
                    {
                        for($m=0; $m < $mentor_details->count(); $m++){
                    
                            $checkuser = chat::where("userId", $mentor_details[$m]->id)->where("chatName", "Admin and Mentor")->get();
            
                            if($checkuser->count() <= 0)
                            {
                                $chat= new chat;
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

                  if($admin_details->count() > 0)
                  {
                    for($t=0; $t < $admin_details->count(); $t++){
                    
                        $checkuser = chat::where("userId", $admin_details[$t]->id)->where("chatName", "Admin and Mentor")->where("email", $admin_details[$t]->email)->get();
        
                        if($checkuser->count() <= 0)
                        {
                            $chat= new chat;
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


                  }
        }

                 // Adding panelist to group chat 
        if ($role->role_name == 'Panelist'){

            $panel_id= base64_encode("Panelist");
            // Adding new panelist to the group chat 

                // $gchat= new groupChat;
                // $gchat->team_id = $panel_id;
                // $gchat->team_name = "Panelist" . rand(0000,9999);
                // $gchat->participant = $request->first_name;
                // $gchat->userId = "Panelist" . rand(0000,9999);
                // $gchat->role= "Panelist";
                // $gchat->save();

                $chat= new chat;
                $chat->chatId = 9999;
                $chat->chatName = "Panelist";
                $chat->chatDescription = "Welcome to Panelist Group Chat";

                $chat->chatType = "Panelist";
                $chat->userId = $email[0]->id;
                $chat->firstName = $request->first_name;
                $chat->lastName = $request->last_name;
                $chat->email = $request->email;
             
                $chat->save();


                $panelist_details = User::where("role_id", $role->role_id)->get();
              
              if($panelist_details->count() > 0)
              {
                for($p=0; $p < $panelist_details->count(); $p++){
                    
                    $checkuser = chat::where("userId", $panelist_details[$p]->id)->where("chatName", "Panelist")->get();
    
                    if($checkuser->count() <= 0)
                    {
                        $chat= new chat;
                        $chat->chatId = 9999;
                        $chat->chatName = "Panelist";
                        $chat->chatDescription = "Welcome to Panelist Group Chat";
        
                        $chat->chatType = "Panelist";
                        $chat->userId = $panelist_details[$p]->id;
                        $chat->firstName = $panelist_details[$p]->first_name;
                        $chat->lastName = $panelist_details[$p]->last_name;
                        $chat->email = $panelist_details[$p]->email;
                     
                        $chat->save();
        
                    }
                    
                               }

              }
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
