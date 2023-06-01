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
use App\Http\Controllers\UpdatesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OpenAIController;
use Illuminate\Support\Facades\Route;
// use App\WebSocket\MyWebSocketHandler;
// use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
// use BeyondCode\LaravelWebSockets\Server\Router;
// use BeyondCode\LaravelWebSockets\WebSockets\WebSocket;


Route::post('status', [Controller::class, 'getStatus']);

Route::get('roles', [RoleController::class, 'getRoles']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('project')->group(function () {
        Route::get('', [ProjectController::class, 'getAllProjects']);

        Route::middleware(['ability:Panelist'])->group(function () {
            Route::get('panelist', [ProjectController::class, 'getPanelistProjects']);
            Route::prefix('submission')->group(function () {
                Route::put('{action}/{submissionId}', [SubmissionController::class, 'updatePanelistFeedback']);
            });
        });

        Route::post('create', [ProjectController::class, 'createProject']);
        Route::put('{projectId}', [ProjectController::class, 'updateProject']);
        Route::get('{teamId}', [ProjectController::class, 'getTeamProjects']);
        Route::get('cohort/{cohortId}', [ProjectController::class, 'getCohortProjects']);

        Route::prefix('submission')->group(function () {
            Route::middleware(['ability:Student'])->group(function () {
                Route::post('{projectId}', [SubmissionController::class, 'createSubmission']);
            });
            Route::get('cohort/{cohortId}', [SubmissionController::class, 'getCohortSubmissions']);
            Route::get('panelist/{projectId}', [SubmissionController::class, 'getProjectSubmissions']);
            Route::get('single/{submissionId}', [SubmissionController::class, 'getSingleSubmission']);
        });
    });

    Route::get('mentees/{mentorId}', [UserController::class, 'getMentorMentees']);
    Route::get('cohort/deadline/{cohortId}', [CohortController::class, 'getCohortDeadlines']);

    Route::get('leaderboard/{cohort_id}', [CohortController::class, 'getTeamsLeaderBoard']);

    Route::post('upload/{uploadType}', [Controller::class, 'uploadFile']);

    Route::get('criteria/{cohort_id}', [CohortController::class, 'getCriteria']);

    Route::get('user', [UserController::class, 'getActiveAccount']);
    Route::put('user', [UserController::class, 'updateActiveUser']);
    Route::put('user/password', [UserController::class, 'updateUserPassword']);
    Route::post('user/search', [UserController::class, 'searchUsers']);

    Route::post('chat', [OpenAIController::class, 'requestPrompt']);
    Route::get('chat/history', [OpenAIController::class, 'getUserSearchHistory']);
});

Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {

    Route::get('students/unmatched', [UserController::class, 'sudentsNotInTeams']);

    Route::get('cohorts', [CohortController::class, 'getAllCohorts']);
    Route::post('cohort', [CohortController::class, 'createCohort']);
    Route::get('cohorts/enable', [CohortController::class, 'enableCohorts']);
    Route::get('cohort/{cohortId}', [CohortController::class, 'getSingleCohort']);
    Route::put('cohort/{cohortId}', [CohortController::class, 'updateCohort']);
    Route::delete('cohort/{cohortId}', [CohortController::class, 'deleteCohort']);
    Route::put('cohort/{cohortId}/panelist/add', [CohortController::class, 'addPanelist']);
    Route::put('cohort/{cohortId}/panelist/remove', [CohortController::class, 'removePanelist']);
    Route::put('cohort/deadline/{cohortId}', [CohortController::class, 'updateDeadlineDate']);

    Route::get('teams', [TeamController::class, 'getAllTeams']);
    Route::get('team/{teamId}', [TeamController::class, 'getSingleTeam']);
    Route::get('team/cohort/{cohortId}', [TeamController::class, 'getTeamsInCohort']);
    Route::post('cohort/{cohortId}/team', [TeamController::class, 'createTeam']);
    Route::put('cohort/{cohortId}/{team_id}', [TeamController::class, 'addTeamToCohort']);

    Route::post('team/create', [TeamController::class, 'createTeam']);
    Route::put('team/{teamId}/mentor', [TeamController::class, 'updateMentor']);
    Route::delete('team/{teamId}', [TeamController::class, 'deleteTeam']);

    Route::post('update', [UpdatesController::class, 'createUpdates']);

    Route::post('criteria/{cohort_id}', [CohortController::class, 'createCriteria']);
});

Route::get('update', [UpdatesController::class, 'getAllUpdates']);

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
Route::post('chat/{chat_id}/message', [MessageController::class, 'sendMessage']);

// Retrieving messages to users
Route::get('chat/{chat_id}/messages', [MessageController::class, 'retrieveMessage']);

// Getting message status receipt
Route::get('chat/{chat_id}/messagereciept', [MessageController::class, 'Messagereciept']);

// Getting total number of unread messages
Route::get('chat/{chat_id}/unread', [MessageController::class, 'UnreadMessages']);


// Route::get('chat/{chat_id}/message', [MessageController::class, 'sendMessage']);
// WebSocketsRouter::webSocket('chat/{chat_id}/message', [MessageController::class, 'sendMessage']);
// Authenticating Messages
Route::middleware(['auth:sanctum'])->group(function () {
    // Sending messages to users
    Route::post('chat/{chat_id}/message', [MessageController::class, 'sendMessage']);

    // Retrieving messages to users
    Route::get('chat/{chat_id}/messages', [MessageController::class, 'retrieveMessage']);

    // Getting total number of unread messages
    Route::get('chat/{chat_id}/unread', [MessageController::class, 'UnreadMessages']);

    // Getting total number of individual unread messages
    Route::get('chat/{chatId}/individualUnread', [MessageController::class, 'IndividualunreadMessages']);

    // Retrieving all memebers of a group chat using teamID
    Route::get('chat/{chat_id}/members', [MessageController::class, 'groupchatMembers']);

    // Returning all unread messages back to read
    Route::get('chat/{chat_id}/user/{userId}/read', [MessageController::class, 'readchat']);

    // Retrieving panelist dashboard
    // Route::get('dashboard/panelist', [DashboardController::class, 'panelistdashboard']);

    // Retrieving unread messages
    Route::get('viewmessages/{user_id}', [ChatController::class, 'view']);

    // Sending support data to database
    Route::post('support', [ChatController::class, 'support']);

    // Retrieve student dashboard data
    // Route::get('dashboard/student', [DashboardController::class, 'studentdashboard']);

    // // Retrieve panlist dashbboard data
    // Route::get('dashboard/panelist', [DashboardController::class, 'panelistdashboard']);

    // // Retrieve mentor dashbboard data
    // Route::get('dashboard/mentor', [DashboardController::class, 'mentordashboard']);


    // Returning all unread messages back to read
    Route::get('chat/{chat_id}/user/{userId}/read', [MessageController::class, 'readchat']);
});

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
// Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {

// Delete user from group chat
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
        Route::post('cohort/{cohort_id}/add', [ChatController::class, 'createCohortChat']);
    });

    // Sending support data to database
    Route::get('getsupport', [ChatController::class, 'getsupport']);

    // Updating support data to database
    Route::patch('support/{id}', [ChatController::class, 'updatesupport']);

    Route::prefix('dashboard')->group(function () {

        // Retrieve admin dashboard data
        Route::get('admin', [DashboardController::class, 'admindashboard']);
    });
});

Route::prefix('dashboard')->group(function () {

    // Retrieve admin dashboard data
    Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->get('admin', [DashboardController::class, 'admindashboard']);

    // Retrieve student dashboard data
    // Route::middleware(['auth:sanctum', 'ability:Student'])->get('student', [DashboardController::class, 'studentdashboard']);
    Route::middleware(['auth:sanctum'])->get('student', [DashboardController::class, 'studentdashboard']);

    // Retrieve panlist dashbboard data
    Route::middleware(['auth:sanctum', 'ability:Panelist'])->get('panelist', [DashboardController::class, 'panelistdashboard']);

    // Retrieve mentor dashbboard data
    Route::middleware(['auth:sanctum', 'ability:Mentor'])->get('mentor', [DashboardController::class, 'mentordashboard']);

});
