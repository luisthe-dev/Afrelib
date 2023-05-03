<?php

use App\Http\Controllers\CohortController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;
// use App\WebSocket\MyWebSocketHandler;
// use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
// use BeyondCode\LaravelWebSockets\Server\Router;
// use BeyondCode\LaravelWebSockets\WebSockets\WebSocket;


Route::post('/status', [Controller::class, 'getStatus']);

Route::get('roles', [RoleController::class, 'getRoles']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('project')->group(function () {
        Route::get('', [ProjectController::class, 'getAllProjects']);

        Route::middleware(['ability:Panelist'])->group(function () {
            Route::get('panelist', [ProjectController::class, 'getPanelistProjects']);
            Route::prefix('submission')->group(function () {
                Route::get('panelist/{projectId}', [SubmissionController::class, 'getProjectSubmissions']);
                Route::put('{action}/{submissionId}', [SubmissionController::class, 'updatePanelistFeedback']);
                Route::get('single/{submissionId}', [SubmissionController::class, 'getSingleSubmission']);
            });
        });

        Route::post('create', [ProjectController::class, 'createProject']);
        Route::put('/{projectId}', [ProjectController::class, 'updateProject']);
        Route::get('{teamId}', [ProjectController::class, 'getTeamProjects']);
        Route::get('cohort/{cohortId}', [ProjectController::class, 'getCohortProjects']);

        Route::prefix('submission')->group(function () {
            Route::post('{projectId}', [SubmissionController::class, 'createSubmission']);
            Route::get('cohort/{cohortId}', [SubmissionController::class, 'getCohortSubmissions']);
        });
    });
});

Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {

    Route::post('upload/{uploadType}', [Controller::class, 'uploadFile']);

    Route::get('students/unmatched', [UserController::class, 'sudentsNotInTeams']);

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



// Chat System



// Creating Group chat and adding participants to the chat
Route::prefix('group-chat')->group(function () {
    Route::post('team', [ChatController::class, 'creategroupChat']);
    Route::post('panel', [ChatController::class, 'panelist']);
    Route::post('adminMentor', [ChatController::class, 'groupAdminMentor']);
});

// Getting all group chats belonging to a particular user
Route::prefix('chat')->group(function () {
    Route::get('user/{user_id}/groups', [ChatController::class, 'getGroupChat']);
});



// Sending messages to users
Route::post('chat/{chat_id}/message', [MessageController::class, 'sendMessage'])->middleware('auth:api');

// Retrieving messages to users
Route::get('chat/{chat_id}/messages', [MessageController::class, 'retrieveMessage']);

// Route::get('chat/{chat_id}/message', [MessageController::class, 'sendMessage']);
// WebSocketsRouter::webSocket('chat/{chat_id}/message', [MessageController::class, 'sendMessage']);

// Route::post('chat/{chat_id}/message', function () {
//     $controllerClass = request('MessageController');
//     $webSocketController = WebSocketsRouter::withController($controllerClass);
// });


// Route::post('chat/{chat_id}/message', function ($chat_id) {
//     $controllerClass = 'App\Http\Controllers\MessageController';
//     $webSocketController = WebSocketsRouter::withController($controllerClass);

//     // Set the chat ID on the controller
//     $messageController = new $controllerClass();
//     $messageController->chat_id = $chat_id;
//     $webSocketController->setController($messageController);

//     return response()->json(['message' => 'WebSocket connection established']);
// });

// Router::webSocket('chat/{chat_id}/message', function ($webSocket) {
//     $webSocket->on('ChatMessages', function ($eventData) {
//         // Get the controller class name from the request
//         $controllerClass = request('MessageController');

//         // Instantiate your WebSocket controller
//         $webSocketController = app()->make($controllerClass);

//         // Call the method on your controller that handles the event
//         call_user_func([$webSocketController, 'sendMessage'], $eventData);
//     });
// });

// $router = new Router();

// $router->webSocket('chat/{chat_id}/message', function ($webSocket) {
//     $webSocket->on('ChatMessages', function ($eventData) {
//         // Instantiate your WebSocket controller
//         $webSocketController = Router::withController('App\Http\Controllers\MessageController');

//         // Call the method on your controller that handles the event
//         $webSocketController->handleYourEvent($eventData);
//     });
// });

// Router::webSocket('/chat/{chat_id}/message', function ($webSocket) {
//     $webSocket->on('ChatMessages', function ($eventData) {
//         // Instantiate your WebSocket controller
//         $webSocketController = Router::withController('App\Http\Controllers\MessageController');

//         // Call the method on your controller that handles the event
//         $webSocketController->handleYourEvent($eventData);
//     });
// });

// Admin Priviledges
Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {

    // Delete user from group chat
    Route::delete('group-chats/{chatId}/members/{userId}', [ChatController::class, 'deleteuser']);

    Route::prefix('chat')->group(function () {
        // Adding user to existing group chat
        Route::put('{chatId}/addUser/{userId}', [ChatController::class, 'adduser']);

        // Allow admin create chat and add users
        Route::post('admin-chat', [ChatController::class, 'createchat']);

        // Automatically adding all users from a cohort to a particular group
        Route::POST('cohort/{cohort_id}/add', [ChatController::class, 'createCohortChat']);
    });
});
