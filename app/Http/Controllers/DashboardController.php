<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    //

    // Retrieving data for admin dashboard 
    public function admindashboard()
    {
        // Total number of teams 
        $teams = DB::table('teams')->get();
 
        // Getting Students role id 
        $student_roles= DB::table('roles')->where('role_name', 'Student')->get(); 

        // Getting Mentor role id 
        $mentor_roles= DB::table('roles')->where('role_name', 'Mentor')->get(); 

        // Getting Panelist role id 
        $panelist_roles= DB::table('roles')->where('role_name', 'Panelist')->get(); 
        
        // Total number of students
        $students= DB::table('users')->where('role_id', $student_roles[0]->role_id)->get(); 

        // Total number of mentors
        $mentors= DB::table('users')->where('role_id', $mentor_roles[0]->role_id)->get(); 

        // Total number of panelists
        $panelists= DB::table('users')->where('role_id', $panelist_roles[0]->role_id)->get(); 

        $today = Carbon::today()->toDateString();

        // Getting sumission deadline date 
        $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)
        ->get();

      // Getting total submissions 
        $submission = DB::table('submissions')->where('submission_week', $weekNumber[0]->week_number)->get();
        

        return response()->json([
            "total_teams" => $teams->count(),
            "total_students" => $students->count(),
            "total_mentors" => $mentors->count(),
            "total_panelists" => $panelists->count(),
            "total_submissions_in_week" => $submission->count(),

        ]);

    }

    public function panelistdashboard()
    {
        $today = Carbon::today()->toDateString();

        // Getting sumission deadline date 
        $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)
        ->get();

        // Getting total teams         
        $cohortTeamsCounts = DB::table('cohorts')->where('cohort_id',$weekNumber[0]->cohort_id)->pluck('cohort_teams')
        ->map(function ($cohortTeams) {
            $decodedArray = json_decode($cohortTeams);
            return count($decodedArray);
        })
        ->map(function ($count) {
            return (int) $count;
        })
        ->first();

        // Getting total submissions 
        $submission = DB::table('submissions')->where('submission_week', $weekNumber[0]->week_number)->get();
        
        return response()->json([
            "submission_deadline_date" => $weekNumber[0]->week_end,
            "current_week" => $weekNumber[0]->week_number,
            "num_teams_in_cohort" => $cohortTeamsCounts,
            "num_submissions" => $submission->count()

        ]);


        
    }

    // Student dashboard 
    public function studentdashboard()
    {
        $today = Carbon::today()->toDateString();

        // Getting sumission deadline date 
        $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)
        ->get();

        // return response()->json([$weekNumber[0]->week_number]);
        
        $submission = DB::table('submissions')->where('submission_week', $weekNumber[0]->week_number)->get();

        // Checking for submission deadline date 
        if($weekNumber->count() <= 0){
            return response()->json(["No submission deadline date has been set for this week"], 202);
        }

          // Checking for submission 
          if($submission->count() <= 0){
            return response()->json(["No submission have been recorded for this week"], 202);
        }

        // Getting leaderpoint 
        $leader_point = DB::table('submissions')->where('submission_week', $weekNumber[0]->week_number)->sum('panelist_feedback');

        return response()->json([
            "submission_deadline_date" => $weekNumber[0]->week_end,
            "current_week" => $weekNumber[0]->week_number,
            "total_submissions_made" => $submission->count(),
            "team_leaderboard_point" => $leader_point

        ]);

    }

    public function mentordashboard()
    {
        
    }


}
