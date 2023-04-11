<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTeamRequest;
use App\Models\Cohort;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{

    public function getAllTeams()
    {
        $teams = Team::where(['is_deleted' => false])->paginate(25);

        $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;

        foreach ($teams as $team) {
            $team_members = json_decode($team->team_members, true);

            $mentor = User::where(['id' => $team->team_mentor, 'role_id' => $mentorId])->first();
            $team->mentor = $mentor;

            $team->students = sizeof($team_members);
        }

        return $teams;
    }

    public function getSingleTeam($teamId)
    {
        $team = Team::where(['id' => $teamId, 'is_deleted' => false])->first();

        if (!$team) return ErrorResponse('Team Does Not Exist');

        $studentId = DB::table('roles')->where(['role_name' => 'Student'])->first()->role_id;
        $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;

        $students = array();

        $mentor = User::where(['id' => $team->team_mentor, 'role_id' => $mentorId])->first();
        if (!$mentor) return ErrorResponse('Error Fetching Mentor Details');

        $teamStudents = json_decode($team->team_members, true);

        foreach ($teamStudents as $student) {
            $single = User::where(['id' => $student, 'role_id' => $studentId])->first();
            if (!$single) return ErrorResponse('Error Fetching Students Details');
            array_push($students, $single);
        }

        $team->students = $students;
        $team->mentor = $mentor;

        return SuccessResponse('Team Details Fetched Successfully', $team);
    }

    public function getTeamsInCohort($cohortId)
    {

        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Invalid Cohort Id');

        $cohortTeams = json_decode($cohort->cohort_teams, true);

        $allTeams = array();

        foreach ($cohortTeams as $cohortTeam) {

            $team = Team::where(['id' => $cohortTeam, 'is_deleted' => false])->first();

            if (!$team) return ErrorResponse('Invalid Team With Id :' . $cohortTeam);

            array_push($allTeams, $team);
        }

        $cohort->teams = $allTeams;

        return SuccessResponse('Cohort Teams Fetxhed Succssfully', $cohort);
    }

    public function createTeam(CreateTeamRequest $request, $cohortId)
    {

        $students = $request->studentIds;
        $studentData = array();

        $studentId = DB::table('roles')->where(['role_name' => 'Student'])->first()->role_id;
        $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;

        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Cohort With Id: ' . $cohortId . ' Does Not Exist');

        foreach ($students as $student) {
            $single = User::where(['id' => $student, 'role_id' => $studentId])->first();
            if (!$single) return ErrorResponse('Student With Id: ' . $student . ' Does Not Exist');
            array_push($studentData, $single);
        }

        $mentor = User::where(['id' => $request->mentorId, 'role_id' => $mentorId])->first();
        if (!$mentor) return ErrorResponse('Mentor With Id: ' . $request->mentorId . ' Does Not Exist');

        $uniqueId = false;

        while (!$uniqueId) {
            $teamId = generateRandom();

            $checkTeam = Cohort::where(['cohort_id' => $teamId])->count();

            if ($checkTeam === 0) $uniqueId = true;
        }

        $team = new Team([
            'team_id' => $teamId,
            'team_name' => $request->team_name,
            'team_description' => $request->team_description,
            'team_members' => json_encode($request->studentIds),
            'team_mentor' => $request->mentorId
        ]);

        $team->save();

        $cohortTeams = json_decode($cohort->cohort_teams, true);
        array_push($cohortTeams, $team->id);

        $cohortMentors = json_decode($cohort->cohort_mentors, true);
        if (!in_array($request->mentorId, $cohortMentors)) array_push($cohortMentors, $request->mentorId);

        $cohort->cohort_teams = json_encode($cohortTeams);
        $cohort->cohort_mentors = json_encode($cohortMentors);

        $cohort->save();

        $team->students = $studentData;
        $team->mentor = $mentor;

        return SuccessResponse('Team Created Successfully', $team);
    }

    public function updateMentor(Request $request, $teamId)
    {
        $request->validate([
            'mentorId' => 'required|integer'
        ]);

        $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;

        $mentor = User::where(['role_id' => $mentorId, 'id' => $request->mentorId])->first();
        if (!$mentor) return ErrorResponse('Invalid Mentor Id');

        $team = Team::where(['id' => $teamId, 'is_deleted' => false])->first();
        if (!$team) return ErrorResponse('Invalid Team Id');

        $team->team_mentor = $request->mentorId;
        $team->save();

        $team->mentor = $mentor;

        return SuccessResponse('Team Mentor Updated Successfully', $team);
    }

    public function deleteTeam($teamId)
    {
        $team = Team::where(['id' => $teamId, 'is_deleted' => false])->first();

        $cohorts = Cohort::where([['cohort_teams', 'like', '%' . $teamId . '%'], 'is_deleted' => false])->get();

        foreach ($cohorts as $cohort) {

            $cohortTeams = json_decode($cohort->cohort_teams, true);

            foreach ($cohortTeams as $cohortTeamKey => $cohortTeam) {
                if ($cohortTeam == $teamId) {
                    array_splice($cohortTeams, $cohortTeamKey, 1);
                }
            }

            $cohort->cohort_teams = $cohortTeams;

            $cohort->save();
        }

        $team->is_deleted = true;

        $team->save();

        return SuccessResponse('Team Deleted Successfully');
    }
}
