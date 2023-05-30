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

        $projectSubmissions = Submission::where(['project_id' => $projectId])->orderByDesc('created_at')->get();

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
                if ($submissionFeedback['evaluated'] == true) {
                    return ErrorResponse('Score Can Not Be Updated');
                } else {
                    $submissionFeedback['score'] = $request->score;
                    $submissionFeedback['evaluated'] = true;
                }
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

    public function getSingleSubmission($submissionId)
    {

        $submission = Submission::where(['id' => $submissionId])->first();

        if (!$submission) return ErrorResponse('Invalid Submission Selected');

        $submissionFeedbacks = json_decode($submission->panelist_feedback, true);

        $feedbacks = array();
        $average_score = 0;
        $score_total = 0;

        foreach ($submissionFeedbacks as $submissionFeedback) {
            $feedbackPanelist = $submissionFeedback['panelist_id'];
            $submissionFeedback['panelist'] = User::where(['id' => $feedbackPanelist])->first();

            $score_total = $score_total + $submissionFeedback['score'];

            array_push($feedbacks, $submissionFeedback);
        }

        if ($score_total != 0) $average_score = $score_total / sizeof($submissionFeedbacks);

        $submission->panelist_feedback = $feedbacks;
        $submission->average_score = $average_score;

        return SuccessResponse('Submission Fetched Successfully', $submission);
    }


    public function getCohortSubmissions($cohortId)
    {
        $cohort = Cohort::where(['cohort_id' => $cohortId, 'is_deleted' => false])->first();

        if (!$cohort) return ErrorResponse('Cohort Does Not Exist');

        $projects = Project::where(['cohort_id' => $cohortId])->orderByDesc('created_at')->get();

        $week1 = array();
        $week2 = array();
        $week3 = array();
        $week4 = array();
        $week5 = array();
        $week6 = array();
        $week7 = array();

        foreach ($projects as $project) {
            $submissions = Submission::where(['project_id' => $project->id])->orderByDesc('created_at')->get();

            if (sizeof($submissions) < 1) continue;

            foreach ($submissions as $submission) {
                $submission->project = $project;

                $feedbacks = array();

                $submissionFeedbacks = json_decode($submission->panelist_feedback, true);

                foreach ($submissionFeedbacks as $submissionFeedback) {
                    $feedbackPanelist = $submissionFeedback['panelist_id'];
                    $submissionFeedback['panelist'] = User::where(['id' => $feedbackPanelist])->first();

                    array_push($feedbacks, $submissionFeedback);
                }

                $submission->panelist_feedback = $feedbacks;
                $weekNumber = "week$submission->submission_week";
                array_push($$weekNumber, $submission);
            }
        }

        $weekData = array(
            'Week 1' => $week1,
            'Week 2' => $week2,
            'Week 3' => $week3,
            'Week 4' => $week4,
            'Week 5' => $week5,
            'Week 6' => $week6,
            'Week 7' => $week7,
        );

        return SuccessResponse('Submissions Fetched Successfully', $weekData);
    }
}
