<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSubmissionRequest;
use App\Models\Cohort;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
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
            'panelist_feedback' => json_encode(array())
        ]);

        $submission->save();

        return SuccessResponse('Submission Created Successfully', $submission);
    }

    public function getProjectSubmissions(Request $request, $projectId)
    {

        $panelistsId = DB::table('roles')->where(['role_name' => 'Panelist'])->first()->role_id;

        $panelist = $request->user();

        if ($panelist->role_id != $panelistsId) return ErrorResponse('Panelist Does Not Exist');

        $project = Project::where(['id' => $projectId, 'is_deleted' => false])->first();

        if (!$project) return ErrorResponse('Invalid Project Selected');

        $projectCohort = Cohort::where(['cohort_id' => $project->cohort_id, 'is_deleted' => false])->first();

        $cohortPanelists = json_decode($projectCohort->cohort_panelists, true);

        if (!in_array($panelist->id, $cohortPanelists)) return ErrorResponse('Panelist Can Not Access Project');

        $projectSubmissions = Submission::where(['project_id' => $projectId])->get();

        $evaluatedSubmissions = array();
        $nonevaluatedSubmissions = array();

        foreach ($projectSubmissions as $projectSubmission) {
            $panelistFeedbacks = json_decode($projectSubmission->panelist_feedback, true);
            $evaluated = false;
            foreach ($panelistFeedbacks as $panelistFeedback) {
                if ($panelistFeedback['panelist_id'] != $panelist->id) continue;

                if ($panelistFeedback['evaluated'] === true) {
                    $evaluated = true;
                    array_push($evaluatedSubmissions, $projectSubmission);
                }
            }
            if ($evaluated == false) array_push($nonevaluatedSubmissions, $projectSubmission);
        }

        return SuccessResponse('Submissions Fetched Successfully', array('panelist' => $panelist, 'evaluatedSubmissions' => $evaluatedSubmissions, 'nonEvaluatedSubmissions' => $nonevaluatedSubmissions));
    }

    public function updatePanelistFeedback(Request $request, $action, $submissionId)
    {

        if ($action == 'comment') {
            $request->validate([
                'comment' => 'required|string'
            ]);
        } else if ($action == 'score') {
            $request->validate([
                'score' => 'required|numeric'
            ]);
        }

        $panelistsId = DB::table('roles')->where(['role_name' => 'Panelist'])->first()->role_id;

        $panelist = $request->user();

        if ($panelist->role_id != $panelistsId) return ErrorResponse('Panelist Does Not Exist');

        $submission = Submission::where(['id' => $submissionId])->first();

        if (!$submission) return ErrorResponse('Submission Does Not Exist');

        $submissionFeedbacks = json_decode($submission->panelist_feedback, true);

        $submissionUpdated = false;

        foreach ($submissionFeedbacks as $submissionKey => $submissionFeedback) {

            if ($submissionFeedback['panelist_id'] != $panelist->id) continue;

            if ($action == 'comment') {
                $submissionFeedback['comment'] = $request->comment;
            } else if ($action == 'score') {
                $submissionFeedback['score'] = $request->score;
                $submissionFeedback['evaluated'] = true;
            }
            $submissionFeedbacks[$submissionKey] = $submissionFeedback;

            $submissionUpdated = true;
        }

        if (!$submissionUpdated) {

            $newFeedback = array(
                'panelist_id' => $panelist->id,
                'comment' => '',
                'score' => 0,
                'evaluated' => false
            );

            if ($action == 'comment') {
                $newFeedback['comment'] = $request->comment;
            } else if ($action == 'score') {
                $newFeedback['score'] = $request->score;
                $newFeedback['evaluated'] = true;
            }

            array_push($submissionFeedbacks, $newFeedback);
        }

        $submission->panelist_feedback = json_encode($submissionFeedbacks);

        $submission->save();

        return SuccessResponse('Action Performed Successfully');
    }
}
