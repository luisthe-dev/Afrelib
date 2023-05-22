<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Team;
use App\Models\Role;

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

    public function mentordashboard(Request $request)
    {

        // $submissi = DB::table('submissions')->get();
        // $re= json_decode($submissi[0]->panelist_feedback);
        // $sum= $re[0];

        // return response()->json([$sum]);

           $today = Carbon::today()->toDateString();

        // Getting sumission deadline date 
        $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)
        ->get();

        $user = Auth::user(); // Get the authenticated user
        
        // Retrieve the mentor ID from the user model
        $mentorId = $user->id;

        // Getting mentor id 
        // $mentorsId = Role::where(['role_name' => 'Mentor'])->first()->role_id;
        $mentorTeams = Team::where(['team_mentor' => $mentorId, 'is_deleted' => false])->get();

          // Get the current week
          $currentWeek = $weekNumber[0]->week_number;
          $previousWeek = $currentWeek - 1;
// Fetch submissions from the database
$submissions = DB::table('submissions')->get();

$results = [];

foreach ($submissions as $submission) {
    // Decode the JSON values in the panelist_feedback column
    $feedback = json_decode($submission->panelist_feedback, true);

    // Get the scores for the current week
    $currentScores = isset($feedback[$currentWeek]) ? $feedback[$currentWeek] : [];

    // Get the scores for the previous week
    $previousScores = isset($feedback[$previousWeek]) ? $feedback[$previousWeek] : [];

    // Sum up the scores for the current week
    $currentTotalPoints = array_sum($currentScores);

    // Sum up the scores for the previous week
    $previousTotalPoints = array_sum($previousScores);

    // Calculate the current ranking
    $currentRanking = DB::table('submissions AS s1')
        ->join('submissions AS s2', function ($join) use ($currentWeek) {
            $join->on('s1.project_id', '=', 's2.project_id')
                ->where('s2.submission_week', '=', $currentWeek);
        })
        ->where('s1.submission_week', '=', $currentWeek)
        ->groupBy('s1.project_id')
        ->orderByRaw('SUM(JSON_EXTRACT(s2.panelist_feedback, CONCAT("$.", CAST(' . $currentWeek . ' AS CHAR)))) DESC')
        ->pluck('s1.project_id')
        ->search($submission->project_id) + 1;

    // Calculate the previous ranking
    $previousRanking = DB::table('submissions AS s1')
        ->join('submissions AS s2', function ($join) use ($previousWeek) {
            $join->on('s1.project_id', '=', 's2.project_id')
                ->where('s2.submission_week', '=', $previousWeek);
        })
        ->where('s1.submission_week', '=', $previousWeek)
        ->groupBy('s1.project_id')
        ->orderByRaw('SUM(JSON_EXTRACT(s2.panelist_feedback, CONCAT("$.", CAST(' . $previousWeek . ' AS CHAR)))) DESC')
        ->pluck('s1.project_id')
        ->search($submission->project_id) + 1;

    // Add the results to the array
    $results[] = [
        'team_id' => 'team' . rand(),
        'current_total_points' => $currentTotalPoints,
        'previous_total_points' => $previousTotalPoints,
        'current_ranking' => $currentRanking,
        'previous_ranking' => $previousRanking,
    ];
}


        // tttttt
                    
        //     // Retrieve the current week's results
        //     $submission = DB::table('submissions')
        //     ->groupBy('project_id')
        //     ->selectRaw("CONCAT('team', FLOOR(RAND() * 1000)) AS team_id")
        //     ->selectRaw("SUM(CASE WHEN submission_week = $currentWeek THEN panelist_feedback ELSE 0 END) AS current_total_points")
        //     ->selectRaw("SUM(CASE WHEN submission_week = $previousWeek THEN panelist_feedback ELSE 0 END) AS previous_total_points")
        //     ->selectRaw("ROW_NUMBER() OVER (ORDER BY SUM(CASE WHEN submission_week = $currentWeek THEN panelist_feedback ELSE 0 END) DESC) AS current_ranking")
        //     ->selectRaw("ROW_NUMBER() OVER (ORDER BY SUM(CASE WHEN submission_week = $previousWeek THEN panelist_feedback ELSE 0 END) DESC) AS previous_ranking")
        //     ->get();
        //     // Convert the results to an array
        //     $results = $submission->toArray();
                    
            // return response()->json(['results' => $results]);

        return response()->json([
            "submission_deadline_date" => $weekNumber[0]->week_end,
            "current_week" => $weekNumber[0]->week_number,
            "num_mentees" => $mentorTeams->count(),
            "team_points" => $results

        ]);
    }


}
