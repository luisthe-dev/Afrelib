<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Models\Cohort;
use App\Models\Project;
use App\Models\Team;

class ProjectController extends Controller
{

    public function createProject(CreateProjectRequest $request)
    {

        $cohort = Cohort::where(['cohort_id' => $request->cohort_id, 'is_deleted' => false])->get();
        $team = Team::where(['id' => $request->team_id, 'is_deleted' => false])->get();

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
}
