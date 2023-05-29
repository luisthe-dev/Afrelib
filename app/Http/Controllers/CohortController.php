<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCohortRequest;
use App\Models\Cohort;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CohortController extends Controller
{

    public function getAllCohorts()
    {
        $cohorts = Cohort::where(['is_deleted' => false])->orderByDesc('created_at')->paginate(50);

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

    public function getTeamsLeaderBoard($cohort_id)
    {

        $cohort = Cohort::where(['cohort_id' => $cohort_id, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');

        $cohort_teams = json_decode($cohort->cohort_teams, true);

        $cohort_leaderboard = array();

        foreach ($cohort_teams as $cohort_team) {

            $teamScore = 0;
            $team = Team::where(['id' => $cohort_team, 'is_deleted' => false])->first();

            if (!$team) continue;

            $team_projects = Project::where(['team_id' => $cohort_team, 'is_deleted' => false])->orderByDesc('created_at')->get();

            foreach ($team_projects as $team_project) {
                $submissions = Submission::where(['project_id' => $team_project->id])->orderByDesc('created_at')->get();
                $submissionScore = 0;
                foreach ($submissions as $submission) {
                    $panelistFeedbacks = json_decode($submission->panelist_feedback, true);
                    $feedbackScore = 0;
                    foreach ($panelistFeedbacks as $panelistFeedback) {
                        $feedbackScore = $feedbackScore + $panelistFeedback['score'];
                    }
                    if ($feedbackScore != 0) $feedbackScore = $feedbackScore / sizeof($panelistFeedbacks);
                    $submissionScore = $submissionScore + $feedbackScore;
                }
                $teamScore = $teamScore + $submissionScore;
            }

            if ($teamScore != 0) $teamScore = $teamScore / sizeof($team_projects);

            $team->aggregrate_score = $teamScore;

            array_push($cohort_leaderboard, $team);
        }

        $cohort_leaderboard = collect($cohort_leaderboard)->sortByDesc('aggregrate_score');

        return SuccessResponse('Leaderboard Fetched Successfully', $cohort_leaderboard);
    }

    public function createCriteria(Request $request, $cohort_id)
    {

        $request->validate([
            'criteria_text' => 'required|string'
        ]);

        $cohort = Cohort::where(['cohort_id' => $cohort_id, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');

        $criteria = DB::table('evaluation_crit')->where(['cohort_id' => $cohort_id])->first();

        if (!$criteria) {
            $criteria = DB::table('evaluation_crit')->updateOrInsert([
                'cohort_id' => $cohort_id,
                'criteria_text' => $request->criteria_text,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        } else {
            DB::table('evaluation_crit')->where(['cohort_id' => $cohort_id])->update([
                'criteria_text' => $request->criteria_text,
                'updated_at' => Carbon::now()
            ]);
        }

        return SuccessResponse('Criteria Updated Successfully');
    }

    public function getCriteria($cohort_id)
    {
        $criteria = DB::table('evaluation_crit')->where(['cohort_id' => $cohort_id])->first();

        if (!$criteria) return ErrorResponse('Cohort Has No Evaluation Criteria');

        return SuccessResponse('Criteria Fetched Successfully', $criteria);
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

    public function enableCohorts()
    {

        $today = Carbon::now();

        Cohort::where([['start_date', '<=', $today], 'status' => 'Inactive', 'is_deleted' => false])->chunk(100, function ($cohorts) {
            foreach ($cohorts as $cohort) {

                $cohortTeams = json_decode($cohort->cohort_teams, true);
                $cohortMentors = json_decode($cohort->cohort_mentors, true);
                $cohortPanelists = json_decode($cohort->cohort_panelists, true);

                foreach ($cohortTeams as $cohortTeam) {
                    $team = Team::where(['is_deleted' => false, 'id' => $cohortTeam])->first();
                    if (!$team) continue;

                    $teamMembers = json_decode($team->team_members, true);
                    foreach ($teamMembers as $teamMember) {
                        $user = User::where(['is_disabled' => false, 'id' => $teamMember])->first();

                        $user->status = 'Active';
                        $user->save();
                    }
                    $user = User::where(['is_disabled' => false, 'id' => $team->team_mentor])->first();

                    $user->status = 'Active';
                    $user->save();
                }

                foreach ($cohortMentors as $cohortMentor) {
                    $user = User::where(['is_disabled' => false, 'id' => $cohortMentor])->first();

                    $user->status = 'Active';
                    $user->save();
                }

                foreach ($cohortPanelists as $cohortPanelist) {
                    $user = User::where(['is_disabled' => false, 'id' => $cohortPanelist])->first();

                    $user->status = 'Active';
                    $user->save();
                }


                $startDate = Carbon::parse($cohort->start_date)->format('Y-m-d');
                $endDate = Carbon::parse($cohort->end_date)->format('Y-m-d');

                $weekNumber = 1;

                $currentStart = $startDate;
                $currentDate = $startDate;


                while ($currentDate <= $endDate) {

                    $current = Carbon::createFromFormat('Y-m-d', $currentDate)->format('l');

                    if ($current == 'Friday') {

                        DB::table('weekly_deadline')->insert([
                            'cohort_id' => $cohort->cohort_id,
                            'week_number' => $weekNumber,
                            'week_start' => $currentStart,
                            'week_end' => $currentDate,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                        $weekNumber = $weekNumber + 1;
                    }

                    if ($current == 'Monday') {
                        $currentStart = $currentDate;
                    }

                    $currentDate = Carbon::createFromFormat('Y-m-d', $currentDate)->addDay()->format('Y-m-d');
                }

                $cohort->status = 'Active';

                $cohort->save();
            }
        });
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

    public function updateDeadlineDate(Request $request, $cohortId)
    {

        $request->validate([
            'new_end_date' => 'required|date',
            'week_number' => 'required|numeric'
        ]);

        $weekDets = DB::table('weekly_deadline')->where(['week_number' => $request->week_number, 'cohort_id' => $cohortId])->first();

        $weekDets->week_end = $request->new_end_date;

        DB::table('weekly_deadline')->where(['week_number' => $request->week_number, 'cohort_id' => $cohortId])->update([
            'week_end' => $request->new_end_date,
            'updated_at' => Carbon::now()
        ]);

        return SuccessResponse('Weekly Deadline Updated Successfully', $weekDets);
    }

    public function getCohortDeadlines($cohortId)
    {

        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');

        $deadlines = DB::table('weekly_deadline')->where(['cohort_id' => $cohortId])->get();

        if ($deadlines->count() < 1) return ErrorResponse('Cohort Has No Deadline Or Has Not Been Activated');

        return SuccessResponse('Deadlines Fetched Successfully', $deadlines);
    }
}
