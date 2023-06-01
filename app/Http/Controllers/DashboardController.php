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
        $teams = DB::table('teams')->where('is_deleted', 0)->get();

        if($teams->count() <= 0)
        {
            $total_teams = 0;
        }
        else{
            $total_teams = $teams->count();
        }
 
        // Getting Students role id 
        $student_roles= DB::table('roles')->where('role_name', 'Student')->get(); 

        // Getting Mentor role id 
        $mentor_roles= DB::table('roles')->where('role_name', 'Mentor')->get(); 

        // Getting Panelist role id 
        $panelist_roles= DB::table('roles')->where('role_name', 'Panelist')->get(); 
        
        // Total number of students
        $students= DB::table('users')->where('role_id', $student_roles[0]->role_id)->where('is_disabled', 0)->get(); 

        if($students->count() <= 0)
        {
            $total_students = 0;
        } else{
            $total_students = $students->count();
        }

        // Total number of mentors
        $mentors= DB::table('users')->where('role_id', $mentor_roles[0]->role_id)->where('is_disabled', 0)->get(); 

        if($mentors->count() <= 0)
        {
            $total_mentors = 0;
        }
        else{
            $total_mentors = $mentors->count();
        }


        // Total number of panelists
        $panelists= DB::table('users')->where('role_id', $panelist_roles[0]->role_id)->where('is_disabled', 0)->get(); 

        if($panelists->count() <= 0)
        {
            $total_panelists = 0;
        }
        else{
            $total_panelists = $panelists->count();
        }


        $today = Carbon::today()->toDateString();

        // Getting sumission deadline date 
        $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)
        ->get();
      

        if($weekNumber->count() == 0)
        {
            $week_number = 0;
            // return response()->json([$weekNumber->count()]);

        }
        else{
            $week_number = $weekNumber[0]->week_number;
        }


      // Getting total submissions 
        $submission = DB::table('submissions')->where('submission_week', $week_number)->where('is_deleted', 0)->get();

        if($submission->count() <= 0)
        {
            $total_submission = 0;
            
        }
        else{
            $total_submission = $submission->count();
        }

        return response()->json([
            "total_teams" => $teams->count(),
            "total_students" => $students->count(),
            "total_mentors" => $mentors->count(),
            "total_panelists" => $panelists->count(),
            "total_submissions_in_week" => $submission->count(),

        ]);

    }

    public function panelistdashboard(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $panel_id = $request->user()->id; // Replace with the desired user ID

        $cohort_id = DB::table('cohorts')
        ->where(function ($query) use ($panel_id) {
            $query->whereRaw("FIND_IN_SET(?, REPLACE(REPLACE(cohort_panelists, '[', ''), ']', ''))", [$panel_id]);
        })
        ->value('cohort_id');

        $cohortTeams = DB::table('cohorts')
            ->where('cohort_id', $cohort_id)
            ->value('cohort_teams');

        $cohortTeamsArray = [];
        if ($cohortTeams) {
            $cohortTeamsArray = json_decode($cohortTeams);
        }

        // return response()->json($cohortTeamsArray);

        
            $teamMembers = DB::table('teams')
            ->whereIn('id', $cohortTeamsArray)
            ->pluck('team_members')
            ->toArray();

            if($teamMembers == "")
            {
                $teamMembers = 0;
            }

            $allTeamMembers = [];
            foreach ($teamMembers as $members) {
            $membersArray = json_decode($members, true);
            $allTeamMembers = array_merge($allTeamMembers, $membersArray);
            }

            $totalRecords = DB::table('submissions')
            ->whereIn('submitter_id', $allTeamMembers)
            ->count();
           
         

        // Getting sumission deadline date 
        $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)->where('cohort_id', $cohort_id)
        ->get();

        if($weekNumber->isEmpty())
        {
            return response()->json([
                "submission_deadline_date" => 0,
                "current_week" => 0,
                "num_teams_in_cohort" => 0,
                "num_submissions" => 0
    
            ]);
        }

        // Getting total teams         
        $cohortTeamsCounts = DB::table('cohorts')->where('cohort_id',$cohort_id)->where('is_deleted', 0)->pluck('cohort_teams')
        ->map(function ($cohortTeams) {
            $decodedArray = json_decode($cohortTeams);
            return count($decodedArray);
        })
        ->map(function ($count) {
            return (int) $count;
        })
        ->first();

        // Getting total submissions 
        $submission = DB::table('submissions')->where('submission_week', $weekNumber[0]->week_number)->where('is_deleted', 0)->get();
        
        return response()->json([
            "submission_deadline_date" => $weekNumber[0]->week_end,
            "current_week" => $weekNumber[0]->week_number,
            "num_teams_in_cohort" => $cohortTeamsCounts,
            "num_submissions" => $totalRecords

        ]);
        
    }

    // Student dashboard 
    public function studentdashboard(Request $request)
    {
        $userId = $request->user()->id; // Replace with the desired user ID

        $team_id = DB::table('teams')
        ->where(function ($query) use ($userId) {
            $query->whereRaw("FIND_IN_SET(?, REPLACE(REPLACE(team_members, '[', ''), ']', ''))", [$userId]);
        })
        ->value('id');

        if($team_id == "" ){
            $team_id = 0;
        }


        $cohort_id = DB::table('cohorts')
        ->where(function ($query) use ($team_id) {
            $query->whereRaw("FIND_IN_SET(?, REPLACE(REPLACE(cohort_teams, '[', ''), ']', ''))", [$team_id]);
        })
        ->value('cohort_id');
    
        if($cohort_id == "" ){
            $cohort_id = 0;
        }
        // lWqvQKNds0g8

        // return response()->json($cohort_id);
        // $memberIds will contain the IDs of the teams where the user is a member

        $team = team::where('team_members', $request->user()->id)->get();
        // return response()->json([$request->user()->id]);

        $today = Carbon::today()->toDateString();
      
        // Getting sumission deadline date 
        $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)->where('cohort_id', $cohort_id)
        ->get();
     
        // return response()->json([$weekNumber->count()]);
         // Checking for submission deadline date 
         if($weekNumber->count() <= 0){
            $weekset = 0;
            $deadline = 0;       
        }

        if($weekNumber->count() > 0){
            $weekset = $weekNumber[0]->week_number;
      
            $deadline = $weekNumber[0]->week_end;
            }
        // return response()->json([$weekNumber[0]->week_number]);
        
        $submission = DB::table('submissions')->where('submission_week', $weekset)->where('is_deleted', 0)->get();

        if($submission->isEmpty()){
            // return response()->json(["test"]);
            $sub_count = 0;
            if($deadline == 0)
            {
                $deadline = 0;
            }else{
                $deadline = $weekNumber[0]->week_end;
            }
     
        
        }

        if($submission->count() > 0)
        {
            $sub_count = $submission->count();
            $deadline = $weekNumber[0]->week_end;
        }
          // Checking for submission 
        //   if($submission->count() <= 0){
        //     return response()->json(["No submission have been recorded for this week"], 202);
        // }

        // // Getting leaderpoint 
        // $leader_point = DB::table('submissions')->where('submission_week', $weekset)->sum('panelist_feedback');

        $feedbackData = DB::table('submissions')
    ->where('submission_week', $weekset)->where('is_deleted', 0)->pluck('panelist_feedback');

    if($feedbackData->count() <= 0)
    {
        $sum = 0;
    }

    if($feedbackData->count() > 0)
    {
        $sum = 0;

        foreach ($feedbackData as $feedback) {
            $feedbackArray = json_decode($feedback, true); // Assuming the column stores JSON data
            
            foreach ($feedbackArray as $item) {
                $sum += $item['score'];
            }
        }

    }


        return response()->json([
            "submission_deadline_date" => $deadline,
            "current_week" => $weekset,
            "total_submissions_made" => $sub_count,
            "team_leaderboard_point" => $sum

        ]);

    }

    public function mentordashboard(Request $request)
    {
        $teamMentor = $request->user()->id; // Replace with the desired user ID

            $teamId = DB::table('teams')
        ->where('team_mentor', $teamMentor)
        ->pluck('id');

       
        $cohortId = DB::table('cohorts')
        ->where(function ($query) use ($teamId) {
            foreach ($teamId as $id) {
                $query->orWhere('cohort_teams', 'like', '%['.$id.',%')
                    ->orWhere('cohort_teams', 'like', '%,'.$id.',%')
                    ->orWhere('cohort_teams', 'like', '%,'.$id.']%');
            }
        })
        ->value('cohort_id');


        $today = Carbon::today()->toDateString();
        $weekNumber = DB::table('weekly_deadline')->where('cohort_id', $cohortId )->whereDate('week_start', '<=', $today)
        ->whereDate('week_end', '>=', $today)
        ->get();

        if($weekNumber->isEmpty()){
            $user = Auth::user(); // Get the authenticated user
        
            // Retrieve the mentor ID from the user model
            $mentorId = $request->user()->id;
    
            // Getting mentor id 
            // $mentorsId = Role::where(['role_name' => 'Mentor'])->first()->role_id;
            $mentorTeams = Team::where(['team_mentor' => $mentorId, 'is_deleted' => false])->where('is_deleted', 0)->get();

            return response()->json([
                "submission_deadline_date" => 0,
                "current_week" => 0,
                "num_mentees" => $mentorTeams->count(),
                "team_points" => 0
    
            ]);
        // Get the current week
        $currentWeek = 0;
        $previousWeek = $currentWeek - 1;
        $weekNumber[0]->week_end = 0;
        $weekNumber[0]->week_number = 0;
        }

        if($weekNumber->count() > 0){

        // Get the current week
        $currentWeek = $weekNumber[0]->week_number;
        $previousWeek = $currentWeek - 1;
            }


        // $submissi = DB::table('submissions')->get();
        // $re= json_decode($submissi[0]->panelist_feedback);
        // $sum= $re[0];
        $submissions = DB::table('submissions')->where('submission_week', $currentWeek)->where('is_deleted', 0)->get(); // Retrieve all submissions from the 'submissions' table

        if($submissions->isEmpty())
        {
            

            $user = Auth::user(); // Get the authenticated user
        
            // Retrieve the mentor ID from the user model
            $mentorId = $user->id;
    
            // Getting mentor id 
            // $mentorsId = Role::where(['role_name' => 'Mentor'])->first()->role_id;
            $mentorTeams = Team::where(['team_mentor' => $mentorId, 'is_deleted' => false])->where('is_deleted', 0)->get();
    
            if($currentWeek == ""){
                return response()->json([
                    "submission_deadline_date" => 0,
                    "current_week" => 0,
                    "num_mentees" => $mentorTeams->count(),
                    "team_points" => 0
        
                ]);
            }

            if($currentWeek != ""){
                return response()->json([
                    "submission_deadline_date" => $weekNumber[0]->week_end,
                    "current_week" => 0,
                    "num_mentees" => $mentorTeams->count(),
                    "team_points" => 0
        
                ]);
            }

           


        }else{
            $results = [];

            foreach ($submissions as $submission) {
                $feedbackData = json_decode($submission->panelist_feedback, true); // Assuming the column stores JSON data
            
                $sum = 0;
                foreach ($feedbackData as $item) {
                    $sum += $item['score'];
                }
            
                // Retrieve previous week's submissions
                $previousSubmissions = DB::table('submissions')->where('submission_week', $previousWeek)->where('is_deleted', 0)->get();
            
                $previousTotalPoints = 0;
                foreach ($previousSubmissions as $previousSubmission) {
                    $previousFeedbackData = json_decode($previousSubmission->panelist_feedback, true);
            
                    foreach ($previousFeedbackData as $item) {
                        $previousTotalPoints += $item['score'];
                    }
                }
            
                $result = [
                    'team_id' => 'team' . $submission->project_id,
                    'current_total_points' => $sum,
                    'previous_total_points' => $previousTotalPoints,
                ];
            
                $results[] = $result;
            }
            
            // Sort the results by current_total_points and previous_total_points in descending order
            usort($results, function ($a, $b) {
                return $b['current_total_points'] <=> $a['current_total_points'];
            });
            
            // Add current_ranking to the results
            $currentRanking = 1;
            foreach ($results as &$result) {
                $result['current_ranking'] = $currentRanking;
                $currentRanking++;
            }
            
            // Sort the results by previous_total_points in descending order
            usort($results, function ($a, $b) {
                return $b['previous_total_points'] <=> $a['previous_total_points'];
            });
            
            // Add previous_ranking to the results
            $previousRanking = 1;
            foreach ($results as &$result) {
                $result['previous_ranking'] = $previousRanking;
                $previousRanking++;
            }
            

        }

       
            // Add previous ranking to the results 

            // return response()->json([$results]);



        // // Getting sumission deadline date 
        // $weekNumber = DB::table('weekly_deadline')->whereDate('week_start', '<=', $today)
        // ->whereDate('week_end', '>=', $today)
        // ->get();

        // Getting mentor id 
        // $mentorsId = Role::where(['role_name' => 'Mentor'])->first()->role_id;
        $teamMembers = DB::table('teams')
    ->where('team_mentor', $teamMentor)
    ->pluck('team_members')
    ->map(function ($members) {
        return json_decode($members, true);
    })
    ->toArray();

        $totalMemebers = count($teamMembers[0]);

        // return response()->json($totalMemebers);

        $mentorTeams = Team::where(['team_mentor' =>        $teamMentor, 'is_deleted' => false])->where('is_deleted', 0)->get();

        return response()->json([
            "submission_deadline_date" => $weekNumber[0]->week_end,
            "current_week" => $weekNumber[0]->week_number,
            "num_mentees" => $totalMemebers,
            "team_points" => $results

        ]);
    }


}
