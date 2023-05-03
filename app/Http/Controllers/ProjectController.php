<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Models\Cohort;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{

    public function getAllProjects()
    {
        $projects = Project::where(['is_deleted' => false])->paginate(25);

        foreach ($projects as $project) {
            $submissions = Submission::where(['project_id' => $project->id])->get();

            $project->submissions = $submissions;

            $teamId = $project->team_id;
            $team = Team::where(['id' => $teamId, 'is_deleted' => false])->first();

            if (!$team) continue;

            $studentId = DB::table('roles')->where(['role_name' => 'Student'])->first()->role_id;
            $mentorId = DB::table('roles')->where(['role_name' => 'Mentor'])->first()->role_id;
            $students = array();

            $mentor = User::where(['id' => $team->team_mentor, 'role_id' => $mentorId])->first();
            if (!$mentor) $mentor = array();

            $teamStudents = json_decode($team->team_members, true);

            foreach ($teamStudents as $student) {
                $single = User::where(['id' => $student, 'role_id' => $studentId])->first();
                if (!$single) continue;
                array_push($students, $single);
            }

            $team->students = $students;
            $team->mentor = $mentor;
            $project->team = $team;
        }

        return SuccessResponse('All Projects Fetched Successfully', $projects);
    }

    public function createProject(CreateProjectRequest $request)
    {

        $cohort = Cohort::where(['cohort_id' => $request->cohort_id, 'is_deleted' => false])->first();
        $team = Team::where(['id' => $request->team_id, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');
        if (!$team) return ErrorResponse('Team Does Not Exist');

        $project = new Project([
            'cohort_id' => $request->cohort_id,
            'team_id' => $request->team_id,
            'project_title' => $request->project_title,
            'project_description' => $request->project_description,
        ]);

        $project->save();

        return SuccessResponse('Team Project Created Successfully', $project);
    }

    public function updateProject(Request $request, $projectId)
    {

        $request->validate([
            'project_title' => 'required|string',
            'project_description' => 'required|string'
        ]);

        $project = Project::where(['id' => $projectId, 'is_deleted' => false])->first();

        if (!$project) return ErrorResponse('Invalid Project Selected');

        $project->project_title = $request->project_title;
        $project->project_description = $request->project_description;

        $project->save();

        return SuccessResponse('Project Details Updated Successfully', $project);
    }

    public function getTeamProjects($teamId)
    {

        $projects = Project::where(['team_id' => $teamId, 'is_deleted' => false])->get();

        foreach ($projects as $project) {
            $submissions = Submission::where(['project_id' => $project->id])->get();

            $project->submissions = $submissions;
        }

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

        return SuccessResponse('Projects Fetched Successfully', array('team' => $team, 'projects' => $projects));
    }

    public function getCohortProjects($cohortId)
    {
        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');

        $cohortTeams = json_decode($cohort->cohort_teams, true);

        $teams = array();

        foreach ($cohortTeams as $teamId) {

            $team = Team::where(['id' => $teamId, 'is_deleted' => false])->first();

            if (!$team) return ErrorResponse('Team Does Not Exist');

            $projects = Project::where(['team_id' => $teamId, 'is_deleted' => false])->get();

            foreach ($projects as $project) {
                $submissions = Submission::where(['project_id' => $project->id])->get();

                $project->submissions = $submissions;
            }

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
            $team->projects = $projects;

            array_push($teams, $team);
        }

        $cohort->teams = $teams;

        return SuccessResponse('Cohort Projects Fetched Successfully', $cohort);
    }

    public function getPanelistProjects(Request $request)
    {

        $panelistsId = DB::table('roles')->where(['role_name' => 'Panelist'])->first()->role_id;

        $panelist = $request->user();

        if ($panelist->role_id != $panelistsId) return ErrorResponse('Panelist Does Not Exist');

        $panelistProjects = array();

        $cohorts = Cohort::where([['cohort_panelists', 'like', '%' . $panelist->id . '%']])->get();

        foreach ($cohorts as $cohort) {

            $cohortPanelists = json_decode($cohort->cohort_panelists, true);

            if (!in_array($panelist->id, $cohortPanelists)) continue;

            $cohortProjects = Project::where(['cohort_id' => $cohort->cohort_id, 'is_deleted' => false])->get();

            foreach ($cohortProjects as $cohortProject) {
                $projectSubmissions = Submission::where(['project_id' => $cohortProject->id])->get();
                if (sizeof($projectSubmissions) < 1) continue;
                $cohortProject->submissions = $projectSubmissions;
            }

            array_push($panelistProjects, $cohortProjects);
        }

        $panelist->cohort_projects = $panelistProjects;

        return SuccessResponse('Panelist Attached Projects Fetched Successfully', $panelist);
    }
}
