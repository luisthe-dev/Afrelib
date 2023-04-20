<?php

use App\Http\Controllers\CohortController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/status', [Controller::class, 'getStatus']);

Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {
    Route::get('cohorts', [CohortController::class, 'getAllCohorts']);
    Route::post('cohort', [CohortController::class, 'createCohort']);
    Route::get('cohort/{cohortId}', [CohortController::class, 'getSingleCohort']);
    Route::put('cohort/{cohortId}', [CohortController::class, 'updateCohort']);
    Route::delete('cohort/{cohortId}', [CohortController::class, 'deleteCohort']);
    Route::put('cohort/{cohortId}/panelist/add', [CohortController::class, 'addPanelist']);
    Route::put('cohort/{cohortId}/panelist/remove', [CohortController::class, 'removePanelist']);

    Route::get('teams', [TeamController::class, 'getAllTeams']);
    Route::get('team/{teamId}', [TeamController::class, 'getSingleTeam']);
    Route::get('team/cohort/{cohortId}', [TeamController::class, 'getTeamsInCohort']);
    Route::post('cohort/{cohortId}/team', [TeamController::class, 'createTeam']);
    Route::put('cohort/{cohortId}/{team_id}', [TeamController::class, 'addTeamToCohort']);

    Route::post('team/create', [TeamController::class, 'createTeam']);
    Route::put('team/{teamId}/mentor', [TeamController::class, 'updateMentor']);
    Route::delete('team/{teamId}', [TeamController::class, 'deleteTeam']);
});

Route::get('roles', [RoleController::class, 'getRoles']);

Route::prefix('user')->group(function () {
    Route::post('signin', [UserController::class, 'loginUser']);

    Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {
        Route::patch('{userId}/disable', [UserController::class, 'disableUser']);
        Route::patch('{userId}/enable', [UserController::class, 'enableUser']);
    });
});

Route::prefix('users')->group(function () {
    Route::get('', [UserController::class, 'allUsers']);
    Route::get('{role_id}', [UserController::class, 'roleUsers']);
});

Route::prefix('admin')->group(function () {
    Route::post('signup', [AdminController::class, 'createAdmin']);
    Route::post('signin', [AdminController::class, 'loginAdmin']);


    Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {

        Route::get('all', [AdminController::class, 'getAllAdmins']);

        Route::prefix('user')->group(function () {
            Route::post('create', [UserController::class, 'createUser']);
            Route::patch('{userId}/password/reset', [UserController::class, 'resetUserPassword']);
        });

        Route::prefix('role')->group(function () {
            Route::post('', [RoleController::class, 'createRole']);
            Route::put('{roleId}', [RoleController::class, 'editRole']);
            Route::delete('{roleId}', [RoleController::class, 'deleteRole']);
        });
    });
});
