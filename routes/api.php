<?php

use App\Http\Controllers\CohortController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
// use App\WebSocket\MyWebSocketHandler;
// use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
// use BeyondCode\LaravelWebSockets\Server\Router;
// use BeyondCode\LaravelWebSockets\WebSockets\WebSocket;


Route::post('/status', [Controller::class, 'getStatus']);


Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {
    Route::get('cohorts', [CohortController::class, 'getAllCohorts']);
    Route::post('cohort', [CohortController::class, 'createCohort']);
    Route::put('cohort/{cohortId}', [CohortController::class, 'updateCohort']);
    Route::delete('cohort/{cohortId}', [CohortController::class, 'deleteCohort']);
    Route::put('cohort/{cohortId}/panelist/add', [CohortController::class, 'addPanelist']);
    Route::put('cohort/{cohortId}/panelist/remove', [CohortController::class, 'removePanelist']);

    Route::get('teams', [TeamController::class, 'getAllTeams']);
    Route::get('team/{teamId}', [TeamController::class, 'getSingleTeam']);
    Route::get('team/cohort/{cohortId}', [TeamController::class, 'getTeamsInCohort']);
    Route::post('cohort/{cohortId}/team', [TeamController::class, 'createTeam']);
    Route::put('team/{teamId}/mentor', [TeamController::class, 'updateMentor']);
    Route::delete('team/{teamId}', [TeamController::class, 'deleteTeam']);
});

Route::get('roles', [RoleController::class, 'getRoles']);

Route::prefix('user')->group(function () {
    Route::post('signin', [UserController::class, 'loginUser']);
});

Route::prefix('users')->group(function () {
    Route::get('', [UserController::class, 'allUsers']);
    Route::get('{role_id}', [UserController::class, 'roleUsers']); 
});

Route::prefix('admin')->group(function () {
    Route::post('signup', [AdminController::class, 'createAdmin']);
    Route::post('signin', [AdminController::class, 'loginAdmin']);


    Route::middleware(['auth:sanctum', 'ability:superiorAdmin'])->group(function () {
        Route::post('user/create', [UserController::class, 'createUser']);

        Route::post('role', [RoleController::class, 'createRole']);
        Route::put('role/{roleId}', [RoleController::class, 'editRole']);
        Route::delete('role/{roleId}', [RoleController::class, 'deleteRole']);
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
        Route::get('dashboard/panelist', [DashboardController::class, 'panelistdashboard']);


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
    Route::middleware(['auth:sanctum','ability:superiorAdmin'])->group(function () {

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

    // Retrieve admin dashboard data 
    Route::get('dashboard/admin', [DashboardController::class, 'admindashboard']);
    
    });


