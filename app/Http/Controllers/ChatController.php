<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use Carbon\Carbon;
use App\Models\groupChat;
use App\Models\chat;
use App\Models\ChatMessages;
use App\Models\User;
use App\Models\Cohort;
use App\Models\Team;
use App\Models\Role;
use App\Models\unreadMessage;
use App\Models\support;


class ChatController extends Controller
{
    //

    // Creating group chat for all participants 
    public function creategroupChat(Request $request)
    {
        $team_id= $request->team_id;
        $no_of_participant= count($request->participants);

        $team_name= Team::where('team_id', $team_id)->get();

        if($team_name->count() <= 0)
        {
            return response()->json(['Team name could not be found. Please confirm team id again']); 
        }

        if($team_name->count() > 0)
        {
            $name_of_team = $team_name[0]->team_name;
        }


        for($i=0; $i<$no_of_participant; $i++){

             $chat= new chat;

            $chat->chatId = $request->team_id;

             $chat->chatName = $name_of_team;

             $chat->chatDescription = "Welcome to the Group Chat";

             $chat->chatType = "Team";

               $chat->userId = $request->participants[$i];

                 $user= user::where('id',$chat->userId)->get();
            
                 if($user->count() > 0)
                 {
                    $chat->firstName = $user[0]->first_name;
                    $chat->lastName = $user[0]->last_name;
                    $chat->email = $user[0]->email;
    
                 }

                 if($user->count() <= 0)
                 {
                    $chat->firstName = "Not found";
                    $chat->lastName = "Not found";
                    $chat->email = "Not found";
    
                 }
              
                $chat->save();

        }

        // Adding all admins to chat 
        $admin = admin::all();
       if($admin->count() > 0)
       {
        for($a=0; $a <= $no_of_participant; $a++){

            $chats= new chat;

            $chats->chatId = $request->team_id;

             $chats->chatName = $name_of_team;

             $chats->chatDescription = "Welcome to the Group Chat";

             $chats->chatType = "Team";

             $chats->userId = $admin[$a]->id;

             $chats->firstName = $admin[$a]->first_name;
             $chats->lastName = $admin[$a]->last_name;
             $chats->email = $admin[$a]->email;

             $chats->save();

        }
       }

        

        return response()->json(['status' => 'Success', 'message' => 'Group chat created and team members added successfully.']);

    }


    // Adding user to existing group chat 
    public function adduser(Request $request, $chatId, $userId)
    {
       $chat= groupChat::where('team_id', $chatId)->get();

    //    Check if Group Chat already exists in database 
       if($chat->count() <= 0 ){
        return response()->json(['status' => 'Failed', 'message' => 'Group chat does not exist']);
       }

       $user= User::where('id', $userId)->get();

    //    Check if user already exists in database 
       if($user->count() <= 0){
        return response()->json(['status' => 'Failed', 'message' => 'User does not exist']);
       }

    //    Inserting new user to the group chat 
         $new_user= new groupChat;
         $new_user->team_id = $chat[0]->team_id;
         $new_user->team_name = $chat[0]->team_name;
         $new_user->participant = $user[0]->first_name;
         $new_user->role = $chat[0]->role;
         $new_user->userId= "User";

         $new_user->save();

        //  Getting all participants details 

        $gchat= groupChat::where('team_id', $chatId)->select('participant','role','userId')->get();
         
         return response()->json(['status' => '200 OK','Content-Type' => 'application/json',
         "details" => ["chatId"=> $chat[0]->team_id,
         "name"=> $chat[0]->team_name,
         "participants"=> $gchat]]);


        // return response()->json([$chatId, $userId ]);
    }

    // Deleting user from chat 
    public function deleteuser($chatId, $userId)
    {
        $deleteuser= chat::where('chatId', $chatId)->where('userId', $userId)->delete();

        return response()->json(['Success' => 'true', 'message' => 'User successfully removed from group chat']);

    }
    // Adding new user to chat 
    public function addnewusertochat($chatId, $userId)
    {
        $user = chat::where('chatId', $chatId)->where('userId', $userId)->get();

        if($user->count() > 0)
        {
            return response()->json(['User is already in this group chat']);
        }
        else{
            $user_info = User::where('id',$userId)->get();
            
            // Checking user 
            if($user_info->count() <= 0){
                return response()->json(['The user you are trying to adding is not in the records']);
            }

            $chat_info = chat::where('chatId', $chatId)->get();
            // Checking chat info 
            if($chat_info->count() <= 0){
                return response()->json(['The chat id entered does not exist']);
            }
            
            $chat = new chat;
            $chat->chatId = $chatId;
            $chat->chatName = $chat_info[0]->chatName;
            $chat->chatDescription = $chat_info[0]->chatDescription;
            $chat->chatType = $chat_info[0]->chatType;
            $chat->userId = $userId;
            $chat->firstName = $user_info[0]->first_name;
            $chat->lastName = $user_info[0]->last_name;
            $chat->email = $user_info[0]->email;

            $chat->save();

            return response()->json(['Success' => 'true', 'message' => 'New user added successfully to the group chat']);

        }

    }

    // Adding user to existing chat(latrer use) 
    public function addtoexistchat($teamId, $userId)
    {
        // Deleting user from existing chat 
        $exist_chat = chat::where('userId', $userId)->delete();

        // Getting team members with team id 
        $teamMembers = Team::select('team_members')
        ->where('team_id', $teamId)
        ->get()
        ->pluck('team_members')
        ->map(function ($teamMembers) {
            return json_decode($teamMembers);
        })
        ->first();

        if(empty($teamMembers))
        {
            return response()->json(["Could not find team with the team_id"]);
        }
   
        // Getting chat_id 
        $chat_info =  chat::whereIn('userId', $teamMembers)
        ->get();
        if($chat_info->count() <= 0)
        {
            return response()->json(["The team you are moving this user does not have a group chat available"]);
        }
       

        // Getting user info 
        $user_info = User::where('id',$userId)->get();
        
            // Checking user 
            if($user_info->count() <= 0){
                return response()->json(['The user you are trying to adding is not in the records']);
            }
        
            $chat = new chat;
            $chat->chatId = $chat_info[0]->chatId;
            $chat->chatName = $chat_info[0]->chatName;
            $chat->chatDescription = $chat_info[0]->chatDescription;
            $chat->chatType = $chat_info[0]->chatType;
            $chat->userId = $userId;
            $chat->firstName = $user_info[0]->first_name;
            $chat->lastName = $user_info[0]->last_name;
            $chat->email = $user_info[0]->email;

            $chat->save();

        // // Adding user to the chat 

        return response()->json(["User was successfully moved to new group chat"]);

    }


    // Deleting Group chat 
    public function deletechat($chatId)
    {
        $deletechat = chat::where('chatId', $chatId)->delete();

        return response()->json(['Success' => 'true', 'message' => 'Group chat successfully deleted']);
    }

    public function createchat(Request $request)
    {
        $no_of_users= count($request->userIds);

        $chat_id= rand(0000,9999);
        for($i=0; $i < $no_of_users; $i++){
            $chat= new chat;

            $chat->chatId = $chat_id;
            $chat->chatName = $request->chatName;
            $chat->chatDescription = $request->chatDescription;
            $chat->chatType = $request->chatType;
            $chat->userId = $request->userIds[$i];

            // return response()->json([$request->userIds[$i]]);

            $user= user::where('id',$chat->userId)->get();
            
            $chat->firstName = $user[0]->first_name;
            $chat->lastName = $user[0]->last_name;
            $chat->email = $user[0]->email;

            $chat->save();
     
        }
        
        $getchat= chat::where('chatId', $chat_id)->get();

        $user= chat::where('chatId', $chat_id)->select('firstName', 'lastName', 'email')->get();

        
        return response()->json(["chatId"=> $getchat[0]->chatId,"chatName" => $getchat[0]->chatName,
        "chatDescription" => $getchat[0]->chatDescription, "chatType" => $getchat[0]->chatType,
        "users"=> [
            $user
        ]]);

    }

    // Delete all chats 
    public function deleteallChat()
    {
        DB::table('chats')->truncate();

        return response()->json("All chats deleted");
    }

    
    public function getGroupChat($user_id){
        // $request->validate([
        //     'email' => 'required|email',
        //     'password' => 'required|string'
        // ]);

        $profile_image= User::where('id',$user_id)->get();

        if($profile_image->count() <= 0)
        {
            $profile_image_url= "No Profile Image Found";
        }

        if($profile_image->count() > 0)
        {
            $profile_image_url= $profile_image[0]->profile_image;
        }

        $groupuser= chat::where('userId', $user_id)->select('chatId','chatName','chatDescription','chatType','userId','firstName')->paginate(40);

        if($groupuser->count() <= 0 ){
            return response()->json(['Status' => 'failed', 'message' => 'User does not exist']);
        }

        $combinedResults = [];

        foreach ($groupuser as $group) {
            $chat_id = $group->chatId;
        
            $lastMessage = ChatMessages::where('chatId', $chat_id)
                ->orderByDesc('created_at')->first(['senderName', 'content', 'mediaType', 'timestamp']);

            $unread = ChatMessages::where('chatId', $chat_id)
                ->orderByDesc('created_at')->where("status", "UnRead");
        
            if ($lastMessage) {
                $combinedResults[] = array_merge($group->toArray(),['Unread Messages' => $unread->count()], ['lastMessage' => $lastMessage]);
            } else {
                $combinedResults[] = $group->toArray();
            }
        }
        
        
        // Return the chats as JSON
        // return response()->json($chats);
        // $groupuser->unread= $chats;

        return response()->json(["total"=> $groupuser->count(),
                                "Profile Image" => $profile_image_url,
                                "per_page" => "40",
                                "current_page" => "1",
                                "last_page" => $groupuser->count(),
                                "data" => 
                                    $combinedResults
                                    
                                ]);
            }


        // Getting unread messages
        public function view($user_id){
  // Getting all unread messages 
       // Get the chats with the count of user IDs where status is unread
       $unread = DB::table('unreadmessages')
       ->select('chatId', DB::raw('COUNT(userId) as unreadmessages'))->where('userId', $user_id)
       ->where('status', 'unread')
       ->groupBy('chatId')
       ->get();

       if($unread->count() == 0){
           return response()->json(["data" => [
               "unreadmessages" => 0 ]]);
       }

       return response()->json([$unread]);

        } 
    

     // Creating group chat for panelist 
     public function panelist(Request $request)
     {
         $panel_id= base64_encode("Panelist");

         $role= Role::where('role_name', 'Panelist')->get();

        //  $groupchat= groupChat::where('role','Panelist')->get();

            $user= user::where('role_id',$role[0]->role_id)->get();
 
            for($i=0; $i<$user->count(); $i++){
               
                if($user->count() <= 0){
                    return response()->json(['status' => 'Error', 'message' => 'No Panelist Found']);
                }
                else{

                         // $request->participants[$i]
                $gchat= new groupChat;
                $gchat->team_id = $panel_id;
                $gchat->team_name = "Panelist";
                $gchat->participant = $user[$i]->first_name;
                $gchat->userId = $user[$i]->id;
                $gchat->role= "Panelist";
                $gchat->save();

                $chat= new chat;
                $chat->chatId = 9999;
                $chat->chatName = "Panelist";
                $chat->chatDescription = "Welcome to Panelist Group Chat";

                $chat->chatType = "Panelist";
                $chat->userId = $user[$i]->id;
                $chat->firstName = $user[$i]->first_name;
                $chat->lastName = $user[$i]->last_name;
                $chat->email = $user[$i]->email;
               
                if($user->count() <= 0)
                {
                   $chat->firstName = "Not found";
                   $chat->lastName = "Not found";
                   $chat->email = "Not found";
   
                }
             
                $chat->save();

    
                }
    
            }
         
        //  else{

            // $user= user::where('role_id',$role[0]->role_id)->get();
 
            // for($i=0; $i<$user->count(); $i++){
               
            //     if($user->count() <= 0){
            //         return response()->json(['status' => 'Error', 'message' => 'No Panelist Found']);
            //     }
            //     else{
            //         $groupchat= groupChat::where('participant',$user[0]->first_name)->get();

            //         if($groupchat->count() <=0){
            //             // $request->participants[$i]
                                
            //             $groupchat->team_id = $panel_id;
            //             $groupchat->team_name = "Panelist" . $request->panel_id;
            //             $groupchat->participant = $user[0]->first_name;
            //             $groupchat->userId = "Panelist" . $i+rand(0000,9999);
            //             $groupchat->role= "Panelist";
            //             $groupchat->save();

                       
            //             $chat= new chat;
            //             $chat->chatId = $panel_id;
            //             $chat->chatName = "Panelist";
            //             $chat->chatDescription = "Welcome to Panelist Group Chat";
        
            //             $chat->chatType = "Panelist";
            //             $chat->userId = $user[0]->id;
            //             $chat->firstName = $user[0]->first_name;
            //             $chat->lastName = $user[0]->last_name;
            //             $chat->email = $user[0]->email;
                       
            //             if($user->count() <= 0)
            //             {
            //                $chat->firstName = "Not found";
            //                $chat->lastName = "Not found";
            //                $chat->email = "Not found";
           
            //             }

            //            $chat->save();
        
                    
         
        
         return response()->json(['status' => 'Success', 'message' => 'Group chat created and team members added successfully.']);
 
     }

     
     public function groupAdminMentor(Request $request)
     {
        $group_id= base64_encode("admin"."mentor");

        $admin = admin::all();
        $role= Role::where('role_name', 'Panelist')->get();

        // return response()->json([$role[0]->role_id]);
    
        $user= User::where('role_id',$role[0]->role_id)->get();

        // return response()->json([$users->count()]);
    
         // Adding all Mentor to the group chat

        //  if($user->count() > 0){
            // return response()->json(["test"]);
            for($p=0; $p <= $user->count(); $p++)
            {
                $email = User::where('email', $user[0]->email)->get();

                // return response()->json([$emails[0]->email]);

                $gchat= new groupChat;
                $gchat->team_id = $group_id;
                $gchat->team_name = "Mentor" . $group_id;
                $gchat->participant = $user[$p]->first_name;
                $gchat->userId = $email[$p]->id;
                $gchat->role= "Mentor";
                $gchat->save();


                $chat= new chat;
                $chat->chatId = 8888;
                $chat->chatName = "Admin and Mentor";
                $chat->chatDescription = "Welcome to Admin and Mentor Group Chat";

                $chat->chatType = "AdminMentor";
                $chat->userId = $email[$p]->id;
                $chat->firstName = $user[$p]->first_name;
                $chat->lastName = $user[$p]->last_name;
                $chat->email = $user[$p]->email;
             
                $chat->save();

                if($admin->count() > 0){
                    for($a=0; $a <= $admin->count(); $a++)
                    {        
                    $email = admin::where('email', $admin[$a]->email)->get();
                    $chat= new chat;
                    $chat->chatId = 8888;
                    $chat->chatName = "Admin and Mentor";
                    $chat->chatDescription = "Welcome to Admin and Mentor Group Chat";

                    $chat->chatType = "AdminMentor";
                    $chat->userId = $email[$a]->id;
                    $chat->firstName = $admin[$a]->first_name;
                    $chat->lastName = $admin[$a]->last_name;
                    $chat->email = $admin[$a]->email;
                
                    $chat->save();

                    return response()->json(['status' => 'Success', 'message' => 'Group chat successfully created for both mentors and admin']);


                }
            }

        }

    //     if($admin->count() > 0){
            
    //         return response()->json([$email[0]->email]);
         
        
    // }



        // Adding all admin to the group chat 
      
    //  $users[2]->email
   
 
       
     }


     public function createCohortChat($cohort_id){
       
        $validateCohort= Cohort::where('cohort_id', $cohort_id)->get();

        // Checking if cohort id exist in database 
        if($validateCohort->count() <= 0)
        {
            return response()->json(['error' => 'Cohort ID does not exist'], 404);
        }

        $chatId= rand(0000,9999). rand(0000,9999);

        // Check if chat already exists 
        $validateChat= chat::where('chatId', $chatId)->get();

        // If chat does not exist 
        if($validateChat->count() <= 0 )
        {
            // Pulling teams from the cohorts table 
           $team_id =json_decode($validateCohort[0]->cohort_teams, true);

             // Pulling mentors from the cohorts table 
            $cohort_mentors =json_decode($validateCohort[0]->cohort_mentors, true);

             // Pulling mentors from the panelists table 
            $cohort_panelists =json_decode($validateCohort[0]->cohort_panelists, true);


           for($i=0; $i < count($team_id); $i++)
           {
                // Getting team members using ID 
                $teamMembersID = Team::where('id', $team_id[$i])->get();
                $getteamMembersID =json_decode($teamMembersID[0]->team_members, true);
                $getteamMentor= $teamMembersID[0]->team_mentor;

                // return response()->json([$teamMembersID[0]->team_mentor]);


                // Processing the users of the system 
                for($a=0; $a < count($getteamMembersID); $a++){

                    // Checking if user don't already exist in chat 
                    $chatUser= Chat::where('userId', $getteamMembersID[$a])->get();
                    
                    // if($chatUser->count() > 0 )
                    // {
                    //     return response()->json(['error' => 'User is already in the group chat'], 404);
                    // }

                    // Getting all Panelist 
                    $Role= Role::where('role_name', 'Panelist')->get();

                    // Getting users details from table and creating chat
                   
                    $user= User::where('id',$getteamMembersID[$a])->where('role_id', '!=' , "Panelist")->get();
                   
                    // return response()->json([substr($cohort_id, 0, 2)]);
                    $chat= new Chat;
                    $chat->chatId =  $chatId;
                    $chat->chatName = $validateCohort[0]->cohort_name;
                    $chat->chatDescription = 'Welcome to a new group chat';
                    $chat->chatType = 'Cohort Group';
                    $chat->userId = "user" . $getteamMembersID[$a];
                    $chat->firstName = $user[0]->first_name;
                    $chat->lastName = $user[0]->last_name;
                    $chat->email = $user[0]->email;
                    $chat->save();

                    // End processing users 
                }

                 // Processing mentors on teams table 
                 for($t = 0; $t< $teamMembersID->count(); $t++)
                 {
                    //    if($t < $teamMembersID->count())
                    //    {
                        // Checking if user don't already exist in chat 
                       $teamMentor= Chat::where('userId', $getteamMentor)->get();
                     
                    //    if($teamMentor->count() > 0 )
                    //    {
                    //        return response()->json(['error' => 'Team Mentor is already in the group chat'], 404);
                    //    }
 
                        // Getting users details from table and creating chat
                    
                     $user= User::where('id',$getteamMentor)->get();
                    
                     // return response()->json([substr($cohort_id, 0, 2)]);
                     $chat= new Chat;
                     $chat->chatId =  $chatId;
                     $chat->chatName = $validateCohort[0]->cohort_name;
                     $chat->chatDescription = 'Welcome to a new group chat';
                     $chat->chatType = 'Cohort Group';
                     $chat->userId = "tmentor".$teamMembersID[0]->team_mentor;
                     $chat->firstName = $user[0]->first_name;
                     $chat->lastName = $user[0]->last_name;
                     $chat->email = $user[0]->email;
                     $chat->save();
   
                    //    }
                 }


                 for($m = 0; $m < count($cohort_mentors); $m++)
            {
               // Checking if mentor don't already exist in chat 
            //    $chatMentors = Chat::where('userId', $cohort_mentors[$m])->get();
                    
            //    if($chatMentors->count() > 0 )
            //    {
            //        return response()->json(['error' => 'Cohort Mentor is already in the group chat'], 404);
            //    }

               // Getting users details from table and creating chat
              
               $user= User::where('id',$cohort_mentors[$m])->get();
              
               // return response()->json([substr($cohort_id, 0, 2)]);
               $chat= new Chat;
               $chat->chatId =  $chatId;
               $chat->chatName = $validateCohort[0]->cohort_name;
               $chat->chatDescription = 'Welcome to a new group chat';
               $chat->chatType = 'Cohort Group';
               $chat->userId = "cmentor".$cohort_mentors[$m];
               $chat->firstName = $user[0]->first_name;
               $chat->lastName = $user[0]->last_name;
               $chat->email = $user[0]->email;
               $chat->save();

            }

            for($p = 0; $p < count($cohort_panelists); $p++)
            {
               // Getting users details from table and creating chat
              
               $user= User::where('id',$cohort_panelists[$p])->get();
              
               // return response()->json([substr($cohort_id, 0, 2)]);
               $chat= new Chat;
               $chat->chatId =  $chatId;
               $chat->chatName = $validateCohort[0]->cohort_name;
               $chat->chatDescription = 'Welcome to a new group chat';
               $chat->chatType = 'Cohort Group';
               $chat->userId = "cpanel".$cohort_panelists[$p];
               $chat->firstName = $user[0]->first_name;
               $chat->lastName = $user[0]->last_name;
               $chat->email = $user[0]->email;
               $chat->save();

            }

            $admin= admin::all();
            $chat= new Chat;
            $chat->chatId =  $chatId;
            $chat->chatName = $validateCohort[0]->cohort_name;
            $chat->chatDescription = 'Welcome to a new group chat';
            $chat->chatType = 'Cohort Group';
            $chat->userId = "admin".$admin[0]->id;
            $chat->firstName = $admin[0]->first_name;
            $chat->lastName = $admin[0]->last_name;
            $chat->email = $admin[0]->email;
            $chat->save();

           }

        //    Getting details of last entered result 
                $lastChat= Chat::where('chatId', $chatId)->get();
                $lastUserUpload= Chat::where('created_at', $lastChat[0]->created_at)->get();

                // Return results 
                 return response()->json([$lastUserUpload], 200);
        }


            // If chat exists
            if($validateChat->count() > 0 )
            {
             // Pulling teams from the cohorts table 
           $team_id =json_decode($validateCohort[0]->cohort_teams, true);

           // Pulling mentors from the cohorts table 
          $cohort_mentors =json_decode($validateCohort[0]->cohort_mentors, true);

           // Pulling mentors from the panelists table 
          $cohort_panelists =json_decode($validateCohort[0]->cohort_panelists, true);

         for($i=0; $i < count($team_id); $i++)
         {
              // Getting team members using ID 
              $teamMembersID = Team::where('id', $team_id[$i])->get();
              $getteamMembersID =json_decode($teamMembersID[0]->team_members, true);
              $getteamMentor= $teamMembersID[0]->team_mentor;

              // return response()->json([$teamMembersID[0]->team_mentor]);


              // Processing the users of the system 
              for($a=0; $a < count($getteamMembersID); $a++){

                  // Checking if user don't already exist in chat 
                  $chatUser= Chat::where('userId', $getteamMembersID[$a])->get();
                  
                  if($chatUser->count() > 0 )
                  {
                      return response()->json(['error' => 'User is already in the group chat'], 404);
                  }

                  // Getting users details from table and creating chat
                 
                  $user= User::where('id',$getteamMembersID[$a])->get();
                 
                  // return response()->json([substr($cohort_id, 0, 2)]);
                //   $chat= new Chat;
                  $validateChat->chatId =  $chatId;
                  $validateChat->chatName = 'Cohort' . $cohort_id;
                  $validateChat->chatDescription = 'Welcome to a new group chat';
                  $validateChat->chatType = 'Cohort Group';
                  $validateChat->userId = "user".$getteamMembersID[$a];
                  $validateChat->firstName = $user[0]->first_name;
                  $validateChat->lastName = $user[0]->last_name;
                  $validateChat->email = $user[0]->email;
                  $validateChat->save();

                  // End processing users 
              }

               // Processing mentors on teams table 
               for($t = 0; $t< $teamMembersID->count(); $t++)
               {
                      // Checking if user don't already exist in chat 
                     $teamMentor= Chat::where('userId', $getteamMentor)->get();
                   

                      // Getting users details from table and creating chat
                  
                   $user= User::where('id',$getteamMentor)->get();
              
                   $validateChat->chatId =  $chatId;
                   $validateChat->chatName = 'Cohort' . $cohort_id;
                   $validateChat->chatDescription = 'Welcome to a new group chat';
                   $validateChat->chatType = 'Cohort Group';
                   $validateChat->userId = "tmentor".$teamMembersID[0]->team_mentor;
                   $validateChat->firstName = $user[0]->first_name;
                   $validateChat->lastName = $user[0]->last_name;
                   $validateChat->email = $user[0]->email;
                   $validateChat->save();
 
               }


               for($m = 0; $m < count($cohort_mentors); $m++)
          {
           
             // Getting users details from table and creating chat
            
             $user= User::where('id',$cohort_mentors[$m])->get();
            
             $validateChat->chatId =  $chatId;
             $validateChat->chatName = 'Cohort' . $cohort_id;
             $validateChat->chatDescription = 'Welcome to a new group chat';
             $validateChat->chatType = 'Cohort Group';
             $validateChat->userId = "cmentor".$cohort_mentors[$m];
             $validateChat->firstName = $user[0]->first_name;
             $validateChat->lastName = $user[0]->last_name;
             $validateChat->email = $user[0]->email;
             $validateChat->save();

          }

          for($p = 0; $p < count($cohort_panelists); $p++)
          {
           
             // Getting users details from table and creating chat
            
             $user= User::where('id',$cohort_panelists[$p])->get();
            
             $validateChat->chatId =  $chatId;
             $validateChat->chatName = 'Cohort' . $cohort_id;
             $validateChat->chatDescription = 'Welcome to a new group chat';
             $validateChat->chatType = 'Cohort Group';
             $validateChat->userId = "cpanel".$cohort_panelists[$p];
             $validateChat->firstName = $user[0]->first_name;
             $validateChat->lastName = $user[0]->last_name;
             $validateChat->email = $user[0]->email;
             $validateChat->save();

          }

          $admin= admin::all();
          $chat= new Chat;
          $chat->chatId =  $chatId;
          $chat->chatName = 'Cohort' . $cohort_id;
          $chat->chatDescription = 'Welcome to a new group chat';
          $chat->chatType = 'Cohort Group';
          $chat->userId = "admin".$admin[0]->id;
          $chat->firstName = $admin[0]->first_name;
          $chat->lastName = $admin[0]->last_name;
          $chat->email = $admin[0]->email;
          $chat->save();

         }

      //    Getting details of last entered result 
              $lastChat= Chat::where('chatId', $chatId)->get();
              $lastUserUpload= Chat::where('created_at', $lastChat[0]->created_at)->get();

              // Return results 
               return response()->json([$lastUserUpload], 200);

        }

    }

    public function support(Request $request)
    {
      
        $support = new support;
        $support->title = $request->title;
        $support->description = $request->description;
        $support->file = $request->file;
        $support->status = "Opened";

        $support->save();

         // Return results 
         return response()->json(["Success" => "Your feedback was successfully received"], 200);

    }

    public function getsupport()
    {
        $support = support::get();
        return response()->json($support);
    }

    public function updatesupport($id)
    {
        $support = support::find($id);
        $support->status = "Closed";
        $support->save();

           // Return results 
           return response()->json(["Success" => "Status updated successfully"], 200);


    }
 
}


