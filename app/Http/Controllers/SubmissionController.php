<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSubmissionRequest;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{

    public function createSubmission(CreateSubmissionRequest $request, $projectId)
    {

        $project = Project::where(['id' => $projectId, 'is_deleted' => false])->first();

        if (!$project) return ErrorResponse('Invalid Project Selected');

        $weekSubmission = Submission::where(['project_id' => $projectId, 'submission_week' => $request->week_number, 'is_deleted' => false])->first();

        if ($weekSubmission) return ErrorResponse('Weekly Submission Has Already Been Made');

        $studentId = DB::table('roles')->where(['role_name' => 'Student'])->first()->role_id;

        $user = User::where(['id' => $request->submitted_by, 'role_id' => $studentId, 'is_disabled' => false])->first();

        if (!$user) return ErrorResponse('Submitting User Is Not An Active Student');

        $team = Team::where(['id' => $project->team_id, 'is_deleted' => false])->first();

        if (!$team) return ErrorResponse('Team Not Authorized For Project');

        $team_members = json_decode($team->team_members, true);

        if (!in_array($request->submitted_by, $team_members)) return ErrorResponse('Submitting Student Does Not Belong To Project Team');

        $submission = new Submission([
            'project_id' => $project->id,
            'submitter_id' => $user->id,
            'submission_title' => $request->submission_title,
            'submission_url' => $request->submitted_url,
            'submission_comment' => $request->submission_comment,
            'submission_attachments' => $request->submitted_file,
            'submission_week' => $request->week_number,
            'panelist_feedback' => json_encode(array()),
            'submission_score' => json_encode(array())
        ]);

        $submission->save();

        return SuccessResponse('Submission Created Successfully', $submission);
    }
}
