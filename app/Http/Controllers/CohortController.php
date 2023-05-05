<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCohortRequest;
use App\Models\Cohort;
use App\Models\Team;
use App\Models\User;
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

        
        // Adding panelist to group chat 
        $panel_id= $request->panel_id . rand(0000,9999);

        // $role= Role::where('role_name', 'Panelist')->get();

        $groupchat= groupChat::where('role','Panelist')->get();

        if($groupchat->count() <= 0)
        {
           $user= user::where('role_id',$panelistId)->get();

           for($i=0; $i<$user->count(); $i++){         
            
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
        else{

           $user= user::where('role_id',$panelistId)->get();

           for($i=0; $i<$user->count(); $i++){
                               
                       $groupchat->team_id = $panel_id;
                       $groupchat->team_name = "Panelist" . $request->panel_id;
                       $groupchat->participant = $user[0]->first_name;
                       $groupchat->userId = "Panelist" . $i+rand(0000,9999);
                       $groupchat->role= "Panelist";
                       $groupchat->save();
       
           }

        }

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
