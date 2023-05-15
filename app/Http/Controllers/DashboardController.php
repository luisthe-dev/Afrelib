<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Totals number of submissions 
        $submissions = DB::table('submissions')->select('submission_week', \DB::raw('count(*) as total'))->groupBy('submission_week')->get(); 
        

        return response()->json([
            "total_teams" => $teams->count(),
            "total_students" => $students->count(),
            "total_mentors" => $mentors->count(),
            "total_panelists" => $panelists->count(),
            "total_submissions_in_week" => $submissions->count(),

        ]);

    }

    public function panelistdashboard()
    {
        
    }
}
