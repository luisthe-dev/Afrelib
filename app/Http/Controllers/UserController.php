<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Mail\SignUpMail;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{

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

        try {
            Mail::to($user->email)->send(new SignUpMail($user));
        } catch (Exception $err) {
            return ErrorResponse('Error Sending Mail');
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

        if (!$userTeam) {
            $teams = Team::where(['team_mentor' =>  $User->id])->get();
            $userTeam = $teams;
        }

        $UserToken = $User->createToken('User Access Token', ['User', $role->role_name]);

        $accessToken = $UserToken->accessToken;
        $accessToken->expires_at = Carbon::now()->addWeeks(6);

        $accessToken->save();

        $User->team = $userTeam;
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
