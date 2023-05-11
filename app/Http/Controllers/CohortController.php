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

    public function getSingleCohort($cohortId)
    {
        $cohort = Cohort::where(['is_deleted' => false, 'cohort_id' => $cohortId])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');

        $studentId = DB::table('roles')->where(['role_name' => 'Student'])->first()->role_id;
        $panelistId = DB::table('roles')->where(['role_name' => 'Panelist'])->first()->role_id;
        $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;

        $mentors = array('data' => array(), 'count' => 0);
        $panelists = array('data' => array(), 'count' => 0);
        $teams = array('data' => array(), 'count' => 0);
        $students = array('data' => array(), 'count' => 0);

        $cohort_mentors = json_decode($cohort->cohort_mentors, true);
        foreach ($cohort_mentors as $mentor) {
            $single = User::where(['id' => $mentor, 'role_id' => $mentorId])->first();
            if (!$single) continue;
            array_push($mentors['data'], $single);
        }

        $cohort_panelists = json_decode($cohort->cohort_panelists, true);
        $cohort->panelists = sizeof($cohort_panelists);
        foreach ($cohort_panelists as $panelist) {
            $single = User::where(['id' => $panelist, 'role_id' => $panelistId])->first();
            if (!$single) continue;
            array_push($panelists['data'], $single);
        }

        $cohort_teams = json_decode($cohort->cohort_teams, true);

        $cohort_students = 0;
        foreach ($cohort_teams as $cohort_team) {
            $team = Team::where(['id' => $cohort_team, 'is_deleted' => false])->first();

            if (!$team) continue;

            $team_students = json_decode($team->team_members, true);
            foreach ($team_students as $student) {
                $single = User::where(['id' => $student, 'role_id' => $studentId])->first();
                if (!$single) continue;
                array_push($students['data'], $single);
            }
            array_push($teams['data'], $team);
            $cohort_students = $cohort_students + sizeof($team_students);
        }

        $mentors['count'] = sizeof($cohort_mentors);
        $panelists['count'] = sizeof($cohort_panelists);
        $teams['count'] = sizeof($cohort_teams);
        $students['count'] = $cohort_students;

        $cohort->mentors = $mentors;
        $cohort->panelists = $panelists;
        $cohort->teams = $teams;
        $cohort->students = $students;


        return SuccessResponse('Cohort Details Fetched Successfully', $cohort);
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
            $teamExist = Cohort::where([['cohort_teams', 'like', '%' . $team . '%']])->get();

            foreach ($teamExist as $single) {
                $singleTeams = json_decode($single->cohort_teams, true);

                foreach ($singleTeams as $teams) {
                    if ($teams->id == $team->id) return ErrorResponse('Team With Id: ' . $team . ' Already Belongs To A Cohort');
                }
            }
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
