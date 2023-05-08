<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Carbon\Carbon;
use App\Models\groupChat;
use App\Models\chat;
use App\Models\User;
use App\Models\Cohort;
use App\Models\Team;
use App\Models\Role;

class ChatController extends Controller
{
    //

    // Creating group chat for all participants 
    public function creategroupChat(Request $request)
    {
        $team_id= $request->team_id;
        $no_of_participant= count($request->participants);

        for($i=0; $i<$no_of_participant; $i++){
            $gchat= new groupChat;
            $gchat->team_id = $request->team_id;
            $gchat->team_name = "Team" . $request->team_id;
            $gchat->participant = $request->participants[$i];
            $gchat->userId = "User" . $i+1;
            $gchat->role= "Member";
            $gchat->save();

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
        $deleteuser= groupChat::where('team_id', $chatId)->where('userId', $userId)->get();
        $deleteuser[0]->delete();
        return response()->json(['Success' => 'true', 'message' => 'User successfully removed from group chat']);

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

    public function getGroupChat($user_id){
        // $request->validate([
        //     'email' => 'required|email',
        //     'password' => 'required|string'
        // ]);

        $groupuser= chat::where('userId', $user_id)->select('chatId','chatName','chatType','userId','firstName')->paginate(10);

        if($groupuser->count() <= 0 ){
            return response()->json(['Status' => 'failed', 'message' => 'User does not exist']);
        }


        return response()->json(["total"=> $groupuser->count(),
                                "per_page" => "10",
                                "current_page" => "1",
                                "last_page" => $groupuser->count(),
                                "data" => [
                                    $groupuser
                                    
                                ]]);
            }
    

     // Creating group chat for panelist 
     public function panelist(Request $request)
     {
         $panel_id= $request->panel_id . rand(0000,9999);

         $role= Role::where('role_name', 'Panelist')->get();

         $groupchat= groupChat::where('role','Panelist')->get();

         if($groupchat->count() <= 0)
         {
            $user= user::where('role_id',$role[0]->role_id)->get();
 
            for($i=0; $i<$user->count(); $i++){
               
                if($user->count() <= 0){
                    return response()->json(['status' => 'Error', 'message' => 'No Panelist Found']);
                }
                else{
                         // $request->participants[$i]
                $gchat= new groupChat;
                $gchat->team_id = $panel_id;
                $gchat->team_name = "Panelist" . $request->panel_id;
                $gchat->participant = $user[0]->first_name;
                $gchat->userId = "Panelist" . $i+rand(0000,9999);
                $gchat->role= "Panelist";
                $gchat->save();
    
                }
    
            }
         }
         else{

            $user= user::where('role_id',$role[0]->role_id)->get();
 
            for($i=0; $i<$user->count(); $i++){
               
                if($user->count() <= 0){
                    return response()->json(['status' => 'Error', 'message' => 'No Panelist Found']);
                }
                else{
                    $groupchat= groupChat::where('participant',$user[0]->first_name)->get();

                    if($groupchat->count() <=0){
                        // $request->participants[$i]
                                
                        $groupchat->team_id = $panel_id;
                        $groupchat->team_name = "Panelist" . $request->panel_id;
                        $groupchat->participant = $user[0]->first_name;
                        $groupchat->userId = "Panelist" . $i+rand(0000,9999);
                        $groupchat->role= "Panelist";
                        $groupchat->save();
                    }
                }
    
            }

         }
        
         return response()->json(['status' => 'Success', 'message' => 'Group chat created and team members added successfully.']);
 
     }

     
     public function groupAdminMentor(Request $request)
     {
        $group_id= $request->groupid . rand(0000,9999);

        $admin = admin::all();
        $user= user::where('role_id','mentor')->get();

        // return response()->json([$user->count()]);

        // Adding all admin to the group chat 
        if($admin->count() > 0){
        for($a=0; $a <= $admin->count(); $a++)
        {
            $gchat= new groupChat;
            $gchat->team_id = $group_id;
            $gchat->team_name = "Admin" . $group_id;
            $gchat->participant = $admin[0]->first_name;
            $gchat->userId = "Admin" . $a+1;
            $gchat->role= "Admin";
            $gchat->save();

        }
    }
        // Adding all panelist to the group chat

        if($user->count() > 0){
            for($p=0; $p <= $user->count(); $p++)
            {
                $gchat= new groupChat;
                $gchat->team_id = $group_id;
                $gchat->team_name = "Mentor" . $group_id;
                $gchat->participant = $user[0]->first_name;
                $gchat->userId = "Mentor" . $i+1;
                $gchat->role= "Mentor";
                $gchat->save();
    
            }
        }

          return response()->json(['status' => 'Success', 'message' => 'Group chat successfully created for both mentors and admin']);

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
                    
                    if($chatUser->count() > 0 )
                    {
                        return response()->json(['error' => 'User is already in the group chat'], 404);
                    }

                    // Getting users details from table and creating chat
                   
                    $user= User::where('id',$getteamMembersID[$a])->get();
                   
                    // return response()->json([substr($cohort_id, 0, 2)]);
                    $chat= new Chat;
                    $chat->chatId =  $chatId;
                    $chat->chatName = 'Cohort' . $cohort_id;
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
                     $chat->chatName = 'Cohort' . $cohort_id;
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
               $chat->chatName = 'Cohort' . $cohort_id;
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
               $chat->chatName = 'Cohort' . $cohort_id;
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
 
}


