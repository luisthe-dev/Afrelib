<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCohortRequest;
use App\Models\Cohort;
use App\Models\Team;
use App\Models\User;
use App\Models\chat;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CohortController extends Controller
{

    public function getAllCohorts()
    {
        $cohorts = Cohort::where(['is_deleted' => false])->paginate(25);

        foreach ($cohorts as $cohort) {

            $cohort_mentors = json_decode($cohort->cohort_mentors, true);
            $cohort->mentors = sizeof($cohort_mentors);

            $cohort_panelists = json_decode($cohort->cohort_panelists, true);
            $cohort->panelists = sizeof($cohort_panelists);

            $cohort_teams = json_decode($cohort->cohort_teams, true);
            $cohort->teams = sizeof($cohort_teams);

            $cohort_students = 0;
            foreach ($cohort_teams as $cohort_team) {
                $team = Team::where(['id' => $cohort_team, 'is_deleted' => false])->first();

                if (!$team) continue;

                $team_students = json_decode($team->team_members, true);

                $cohort_students = $cohort_students + sizeof($team_students);
            }
            $cohort->students = $cohort_students;
        }

        return $cohorts;
    }

    public function addPanelist(Request $request, $cohortId)
    {

        $request->validate([
            'panelist_ids' => 'required|array'
        ]);

        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();
        if (!$cohort) return ErrorResponse('Cohort With Id: ' . $cohortId . ' Does Not Exist');

        $panelistId = DB::table('roles')->where(['role_name' => 'Panelist'])->first()->role_id;

        $cohortPanelists = json_decode($cohort->cohort_panelists, true);

        foreach ($request->panelist_ids as $panelist) {
            $user = User::where(['id' => $panelist, 'role_id' => $panelistId])->first();
            if (!$user) return ErrorResponse('Panelist With Id: ' . $panelist . ' Does Not Exist');
            if (!in_array($panelist, $cohortPanelists)) array_push($cohortPanelists, $panelist);
        }

        $cohort->cohort_panelists = json_encode($cohortPanelists);

        $cohort->save();

    
        return SuccessResponse('Panelists Updated Successfully');
    }

    public function removePanelist(Request $request, $cohortId)
    {

        $request->validate([
            'panelist_ids' => 'required|array'
        ]);

        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();
        if (!$cohort) return ErrorResponse('Cohort With Id: ' . $cohortId . ' Does Not Exist');

        $cohortPanelists = json_decode($cohort->cohort_panelists, true);

        foreach ($request->panelist_ids as $panelistKey => $panelist) {
            if (!in_array($panelist, $cohortPanelists)) {
                array_splice($cohortPanelists, $panelistKey, 1);
            }
        }

        $cohort->cohort_panelists = json_encode($cohortPanelists);

        $cohort->save();

        return SuccessResponse('Panelists Updated Successfully');
    }

    public function updateCohort(Request $request, $cohortId)
    {

        $request->validate([
            'cohort_name' => 'required|string',
            'cohort_description' => 'required|string'
        ]);

        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();
        if (!$cohort) return ErrorResponse('Cohort With Id: ' . $cohortId . ' Does Not Exist');

        $cohort->cohort_name = $request->cohort_name;
        $cohort->cohort_description = $request->cohort_description;

        $cohort->save();

        return SuccessResponse('Cohort Successfully Updated', $cohort);
    }

    public function createCohort(CreateCohortRequest $request)
    {
        $mentors = $request->mentorIds; 
        $teams = $request->teamIds;
        $panelists = $request->panelistIds;

        $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;
        $panelistId = DB::table('roles')->where(['role_name' => 'Panelist'])->first()->role_id;

        $mentorsData = array();
        $panelistData = array();
        $teamsData = array();

        foreach ($mentors as $mentor) {
            $user = User::where(['id' => $mentor, 'role_id' => $mentorId])->first();
            if (!$user) return ErrorResponse('Mentor With Id: ' . $mentor . ' Does Not Exist');
            array_push($mentorsData, $user);
        }

        foreach ($panelists as $panelist) {
            $user = User::where(['id' => $panelist, 'role_id' => $panelistId])->first();
            if (!$user) return ErrorResponse('Panelist With Id: ' . $panelist . ' Does Not Exist');
            array_push($panelistData, $user);
        }

        foreach ($teams as $team) {
            $single = Team::where(['id' => $team])->first();
            if (!$single) return  ErrorResponse('Team With Id: ' . $team . ' Does Not Exist');
            array_push($teamsData, $single);
        }

        $uniqueId = false;

        while (!$uniqueId) {
            $cohortId = generateRandom();

            $checkCohort = Cohort::where(['cohort_id' => $cohortId])->count();

            if ($checkCohort === 0) $uniqueId = true;
        }

        $cohort = new Cohort([
            'cohort_id' => $cohortId,
            'cohort_name' => $request->cohort_name,
            'cohort_description' => $request->cohort_description,
            'cohort_teams' => json_encode($request->teamIds),
            'cohort_mentors' => json_encode($request->mentorIds),
            'cohort_panelists' => json_encode($request->panelistIds),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        $cohort->save();

        $cohort->mentors = $mentorsData;
        $cohort->panelists = $panelistData;
        $cohort->students = $teamsData;


        // Add cohort to chat 

        $team = json_encode($request->teamIds);
          // Pulling teams from the cohorts table 
          $team_id =json_decode($team);

          // Pulling mentors from the cohorts table
          $cohort = json_encode($request->mentorIds); 
         $cohort_mentors =json_decode( $cohort);

          // Pulling mentors from the panelists table 
          $panelist = json_encode($request->panelistIds);
         $cohort_panelists =json_decode($panelist);


        for($i=0; $i < count($team_id); $i++)
        {
            $chatId= $cohortId;
             // Getting team members using ID 
             $teamMembersID = Team::where('id', $team_id[$i])->get();
             $getteamMembersID =json_decode($teamMembersID[0]->team_members, true);
             $getteamMentor= $teamMembersID[0]->team_mentor;

             // return response()->json([$teamMembersID[0]->team_mentor]);


             // Processing the users of the system 
             for($a=0; $a < count($getteamMembersID); $a++){

                 // Checking if user don't already exist in chat 
                 $chatUser= Chat::where('userId', $getteamMembersID[$a])->get();
                 
                 // Getting all Panelist 
                 $Role= Role::where('role_name', 'Panelist')->get();

                 // Getting users details from table and creating chat
                
                 $user= User::where('id',$getteamMembersID[$a])->where('role_id', '!=' , "Panelist")->get();
                
                 // return response()->json([substr($cohort_id, 0, 2)]);
                 $chat= new Chat;
                 $chat->chatId =  $chatId;
                 $chat->chatName = $request->cohort_name;
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
      
                     // Getting users details from table and creating chat
                 
                  $user= User::where('id',$getteamMentor)->get();
                 
                  // return response()->json([substr($cohort_id, 0, 2)]);
                  $chat= new Chat;
                  $chat->chatId =  $chatId;
                  $chat->chatName = $request->cohort_name;
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

            // Getting users details from table and creating chat
           
            $user= User::where('id',$cohort_mentors[$m])->get();
           
            // return response()->json([substr($cohort_id, 0, 2)]);
            $chat= new Chat;
            $chat->chatId =  $chatId;
            $chat->chatName = $request->cohort_name;
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
            $chat->chatName = $request->cohort_name;
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
         $chat->chatName = $request->cohort_name;
         $chat->chatDescription = 'Welcome to a new group chat';
         $chat->chatType = 'Cohort Group';
         $chat->userId = "admin".$admin[0]->id;
         $chat->firstName = $admin[0]->first_name;
         $chat->lastName = $admin[0]->last_name;
         $chat->email = $admin[0]->email;
         $chat->save();

        }

        return SuccessResponse('Cohort Created Successfully', $cohort);
    }

    public function deleteCohort($cohortId)
    {

        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();
        if (!$cohort) return ErrorResponse('Cohort With Id: ' . $cohortId . ' Does Not Exist');

        $cohort->is_deleted = true;

        $cohort->save();

        return SuccessResponse('Cohort Deleted Successfully');
    }
}
