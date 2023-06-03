<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTeamRequest;
use App\Mail\JoinedTeam;
use App\Models\Cohort;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TeamController extends Controller
{

    public function getAllTeams()
    {
        $teams = Team::where(['is_deleted' => false])->orderByDesc('created_at')->paginate(50);

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

    public function createTeam(CreateTeamRequest $request, $cohortId = null)
    {

        $students = $request->studentIds;
        $studentData = array();

        $studentId = DB::table('roles')->where(['role_name' => 'Student'])->first()->role_id;
        $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;

        if ($cohortId) {
            $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();
            if (!$cohort) return ErrorResponse('Cohort With Id: ' . $cohortId . ' Does Not Exist');
        }

        foreach ($students as $student) {
            $single = User::where(['id' => $student, 'role_id' => $studentId])->first();
            if (!$single) return ErrorResponse('Student With Id: ' . $student . ' Does Not Exist');
            $teamExist = Team::where([['team_members', 'like', '%' . $student . '%']])->get();

            foreach ($teamExist as $team) {
                $teamMembers = json_decode($team->team_members, true);

                if (in_array($student, $teamMembers)) return ErrorResponse('Student With Id: ' . $student . ' Already Belongs To A Team');
            }
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

        if ($cohortId) $this->addTeamToCohort($cohortId, $team->id);

        $team->students = $studentData;
        $team->mentor = $mentor;



        foreach ($students as $student) {
            $single = User::where(['id' => $student, 'role_id' => $studentId])->first();
            try {
                Mail::to($single->email)->send(new JoinedTeam($single, $team));
            } catch (Exception $err) {
                dd($err);
                return ErrorResponse('Error Sending Mail', $err);
            }
        }

        try {
            Mail::to($mentor->email)->send(new JoinedTeam($mentor, $team));
        } catch (Exception $err) {
            dd($err);
            return ErrorResponse('Error Sending Mail', $err);
        }

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

    public function addTeamToCohort($cohortId, $team_id)
    {
        $cohort = Cohort::where(['cohort_id' => $cohortId])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');

        $team = Team::where(['id' => $team_id, 'is_deleted' => false])->first();

        if (!$team) return ErrorResponse('Team Does Not Exist');

        $teamExist = Cohort::where([['cohort_teams', 'like', '%' . $team_id . '%']])->orderByDesc('created_at')->get();

        foreach ($teamExist as $single) {
            $singleTeams = json_decode($single->cohort_teams, true);

            foreach ($singleTeams as $singleTeam) {
                if ($singleTeam == $team_id) return ErrorResponse('Team Already Belongs To A Cohort');
            }
        }

        $cohortTeams = json_decode($cohort->cohort_teams, true);
        array_push($cohortTeams, $team->id);
        $cohort->cohort_teams = json_encode($cohortTeams);
        $cohort->save();

        return SuccessResponse('Team Has Been Added To Cohort Successfully');
    }

    public function transferStudent(Request $request)
    {

        $request->validate([
            'student_id' => 'required|numeric',
            'team_id' => 'required|numeric'
        ]);

        $studentId = DB::table('roles')->where(['role_name' => 'Student'])->first()->role_id;

        $student = User::where(['id' => $request->student_id, 'role_id' => $studentId])->first();

        if (!$student) return ErrorResponse('Invalid Student Id');

        $team = Team::where(['id' => $request->team_id, 'is_deleted' => false])->first();

        if (!$team) return ErrorResponse('Invalid Team Id');

        $teams = Team::where([['team_members', 'like', '%' . $student->id . '%']])->get();

        foreach ($teams as $single) {
            $singleMembers = json_decode($single->team_members, true);

            if (!in_array($student->id, $singleMembers)) continue;

            if ($single->id === $team->id) return ErrorResponse('Student Already Belongs To Team');

            $studentKey = array_search($student->id, $singleMembers);

            array_splice($singleMembers, $studentKey, 1);
            $single->team_members = json_encode($singleMembers);

            $single->save();

            break;
        }

        $teamMembers = json_decode($team->team_members, true);
        array_push($teamMembers, $student->id);
        $team->team_members = json_encode($teamMembers);

        $team->save();

        return SuccessResponse('Student Transferred Successfully');
    }

    public function deleteTeam($teamId)
    {
        $team = Team::where(['id' => $teamId, 'is_deleted' => false])->first();

        $cohorts = Cohort::where([['cohort_teams', 'like', '%' . $teamId . '%'], 'is_deleted' => false])->orderByDesc('created_at')->get();

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
