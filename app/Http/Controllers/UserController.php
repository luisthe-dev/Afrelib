<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Mail\SignUpMail;
use App\Models\Cohort;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\groupChat;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{

    public function getActiveAccount(Request $request)
    {
        $user = $request->user();

        return SuccessResponse('User Details Fetched', $user);
    }

    public function updateActiveUser(Request $request)
    {

        $user = $request->user();

        $User = User::Where(['id' => $user->id])->first();

        if (!$user) ErrorResponse('Invalid User Selected');

        $User->update($request->all());

        $User->save();

        return SuccessResponse('User Updated Successfully');
    }

    public function allUsers()
    {
        $users = User::paginate(25);

        foreach ($users as $user) {
            $user_role = $user->role_id;
            $user_role_name = Role::where(['role_id' => $user_role])->first();

            if (!$user_role_name) {
                $user->role_name = null;
            } else {
                $user->role_name = $user_role_name->role_name;
            }
        }

        return $users;
    }

    public function sudentsNotInTeams()
    {

        $studentId = Role::where(['role_name' => 'Student'])->first()->role_id;
        $returnStudents = array();

        User::where(['role_id' => $studentId])->chunkById(200, function ($students) use (&$returnStudents) {

            foreach ($students as $student) {
                $teams = Team::where([['team_members', 'like', '%' . $student->id . '%']])->get();

                if (sizeof($teams) == 0) array_push($returnStudents, $student);

                foreach ($teams as $team) {
                    $teamStudents = json_decode($team->team_members, true);

                    if (in_array($student->id, $teamStudents)) continue;

                    array_push($returnStudents, $student);
                }
            }
        });


        return SuccessResponse('Students Fetched Successfully', $returnStudents);
    }

    public function getMentorMentees($mentorId)
    {

        $studentId = Role::where(['role_name' => 'Student'])->first()->role_id;

        $mentorsId = Role::where(['role_name' => 'Mentor'])->first()->role_id;

        $mentor  = User::where(['id' => $mentorId, 'role_id' => $mentorsId])->first();

        if (!$mentor) return ErrorResponse('Invalid Mentor Id Provided');

        $mentorTeams = Team::where(['team_mentor' => $mentorId, 'is_deleted' => false])->get();

        $mentees = array();

        foreach ($mentorTeams as $mentorTeam) {

            $mentorTeamStudents = json_decode($mentorTeam->team_members, true);

            foreach ($mentorTeamStudents as $mentorTeamStudent) {
                $student = User::where(['id' => $mentorTeamStudent, 'role_id' => $studentId])->first();

                if (!$student) continue;

                array_push($mentees, $student);
            }
        }

        return SuccessResponse('Mentees Fetched Successfully', array('mentor' => $mentor, 'mentorTeams' => $mentorTeams, 'mentees' => $mentees));
    }

    public function disableUser($userId)
    {
        $user = User::find($userId);

        if (!$user) return ErrorResponse('Error Fetching User');

        $user->is_disabled = true;
        $user->save();

        return SuccessResponse('User Successfully Disabled', $user);
    }

    public function enableUser($userId)
    {
        $user = User::find($userId);

        if (!$user) return ErrorResponse('Error Fetching User');

        $user->is_disabled = false;
        $user->save();

        return SuccessResponse('User Successfully Enabled', $user);
    }

    public function roleUsers($role_id)
    {
        $users = User::where(['role_id' => $role_id])->paginate(25);

        foreach ($users as $user) {
            $user_role = $user->role_id;
            $user_role_name = Role::where(['role_id' => $user_role])->first();

            if (!$user_role_name) {
                $user->role_name = null;
            } else {
                $user->role_name = $user_role_name->role_name;
            }
        }

        return $users;
    }

    public function createUser(CreateUserRequest $request)
    {

        if ($request->date_of_birth) $request->validate([
            'date_of_birth' => 'date'
        ]);

        $role = Role::where(['role_id' => $request->role_id])->first();

        if (!$role) return ErrorResponse('Invalid Role Selected');

        $user = User::where(['email' => $request->email])->count();

        if ($user > 0) return ErrorResponse('User with Email Already Exists');

        $user = new User([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'date_of_birth' => $request->date_of_birth,
            'password' => Hash::make($request->last_name),
            'role_id' => $role->role_id
        ]);

        if ($role->role_name == 'Student') {
            $request->validate([
                'school_name' => 'required|string'
            ]);

            $user->school_name = $request->school_name;
        }

        $user->save();

        $user->role_name = $role->role_name;

        if ($role->role_name == 'Mentor') {
            // Adding mentor to group chat
            $group_id = base64_encode("admin" . "mentor");

            // Adding new mentor to the group chat

            $gchat = new groupChat;
            $gchat->team_id = $group_id;
            $gchat->team_name = "Mentor" . $group_id;
            $gchat->participant = $request->first_name;
            $gchat->userId = "Mentor" . rand(0000, 9999);
            $gchat->role = "Mentor";
            $gchat->save();
        }

        // Adding panelist to group chat
        if ($role->role_name == 'Panelist') {
            $panel_id = base64_encode("Panelist");

            // Adding new panelist to the group chat

            $gchat = new groupChat;
            $gchat->team_id = $panel_id;
            $gchat->team_name = "Panelist" . rand(0000, 9999);
            $gchat->participant = $request->first_name;
            $gchat->userId = "Panelist" . rand(0000, 9999);
            $gchat->role = "Panelist";
            $gchat->save();
        }

        try {
            Mail::to($user->email)->send(new SignUpMail($user));
        } catch (Exception $err) {
            return ErrorResponse('Account Created. Error Sending Mail', $err);
        }


        return SuccessResponse('User Created Successfully', $user);
    }

    public function loginUser(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $User = User::where(['email' => $request->email])->first();


        if (!$User) return ErrorResponse('Invalid Login Parameters');

        if (!Hash::check($request->password, $User->password)) return ErrorResponse('Invalid Login Parameters');

        $role = Role::where(['role_id' => $User->role_id])->first();

        if (!$role) return ErrorResponse('Error Confirming User Identity');

        $teams = Team::where([['team_members', 'like', '%' . $User->id . '%']])->get();

        $userTeam = null;

        foreach ($teams as $team) {
            $teamStudents = json_decode($team->team_members, true);

            if (in_array($User->id, $teamStudents)) $userTeam = $team;
        }

        $userCohort = null;

        if ($userTeam) {
            $cohorts = Cohort::where([['cohort_teams', 'like', '%' . $userTeam->id . '%']])->get();

            foreach ($cohorts as $cohort) {
                $cohortTeams = json_decode($cohort->cohort_teams, true);

                if (in_array($userTeam->id, $cohortTeams)) $userCohort = $cohort;
            }
        }

        if (!$userTeam) {
            $teams = Team::where(['team_mentor' =>  $User->id])->get();
            $userTeam = $teams;
        }

        $UserToken = $User->createToken('User Access Token', ['User', $role->role_name]);

        $accessToken = $UserToken->accessToken;
        $accessToken->expires_at = Carbon::now()->addWeeks(6);

        $accessToken->save();

        $User->team = $userTeam;
        $User->cohort = $userCohort;
        $User->role_name = $role->role_name;

        $responseData = [
            'access_token' => explode('|', $UserToken->plainTextToken)[1],
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $UserToken->accessToken->expires_at
            )->toDateTimeString(),
            'UserDetails' => $User
        ];

        return SuccessResponse('User Logged In Successfully', $responseData);
    }

    public function resetUserPassword($userId)
    {

        $user = User::find($userId);

        if (!$user) return ErrorResponse('User Does Not Exist');

        $newPassword = generateRandom();

        $user->password = Hash::make($newPassword);
        $user->save();

        return SuccessResponse('User Password Reset Successfully', $user);
    }
}
