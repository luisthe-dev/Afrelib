<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\chat;
use App\Models\ChatMessages;



class MessageController extends Controller
{
    //
    public function sendMessage(Request $request, $chat_id)
    {
         // Check if the user is authenticated
    if (!$request->user()) {
        return response()->json(['error' => 'You are currently not authenticated'], 404);
    }

    // Check chat id id it exists 
    $chat= chat::where('chatId', $chat_id)->get();
    if($chat->count() <=0)
    {
        return response()->json(['error' => 'Chat ID does not exist'], 404);
    }

        // Validator 
        $rules = [
            'content' => 'required',
            'mediaType' => 'required',
            'senderId' => 'required',
            'senderName' => 'required',
            'timestamp' => 'required',
            // Add more required fields here as needed
        ];
    
        $messages = [
            'content.required' => 'Content is required',
            'mediaType.required' => 'Media Type is required',
            'senderId.required' => 'Sender ID is required',
            'senderName.required' => 'Sender Name is required',
            'timestamp.required' => 'Timestamp is required',
            // Add more custom error messages here as needed
        ];
    
        $validator = Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 404);
        }
        

        if ($request->mediaType != "text" || $request->mediaType != "emoji") {
            // Saving image file

            
            $validator = Validator::make($request->all(), [
                'mediaUrl' => 'required|image'
            ]);
        
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
            
        
            // $imageData = file_get_contents($request->mediaUrl);
            // $fileName = basename($request->mediaUrl);
            // Storage::put('fileimages/'.$fileName, $imageData);

            $file = $request->file('mediaUrl');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs('public/images', $fileName);

        } 

        $rand= $chat_id. rand(0000,9999);

        $SaveMessage=  new ChatMessages;
        $SaveMessage->messageId= $rand;
        $SaveMessage->chatId= $chat_id;
        $SaveMessage->content= $request->content;
        $SaveMessage->mediaType= $request->mediaType;
        $SaveMessage->mediaUrl= $filePath;
        $SaveMessage->senderId= $request->senderId;
        $SaveMessage->senderName= $request->senderName;
        $SaveMessage->timestamp= $request->timestamp;
        $SaveMessage->save();

        
        return response()->json([
            "messageId" => $rand,
            "chatId" => $chat_id,
            "content" => $request->content,
            "mediaType" => $request->mediaType,
            "mediaUrl" => $filePath,
            "senderId" => $request->senderId,
            "senderName" => $request->senderName,
            "timestamp" => $request->timestamp

        ]);

        // event(new ChatMessage(["messageId" => $rand,
        // "chatId" => $chat_id,
        // "content" => $request->content,
        // "mediaType" => $request->mediaType,
        // "mediaUrl" => $filePath,
        // "senderId" => $request->senderId,
        // "senderName" => $request->senderName,
        // "timestamp" => $request->timestamp]));
    
        // WebSocketsRouter::broadcastToChannel("chat.{$chat_id}", [
        //     'type' => 'message',
        //     'message' => $message,
        // ]);
    
        // return response()->json(['success' => true]);
    }

    public function retrieveMessage($chat_id){
             // Check if the user is authenticated
    // if (!$request->user()) {
    //     return response()->json(['error' => 'You are currently not authenticated'], 404);
    // }
        // Check chat id id it exists 
        $chat= chat::where('chatId', $chat_id)->get();
        if($chat->count() <=0)
        {
            return response()->json(['error' => 'Chat ID does not exist'], 404);
        }

        $message= ChatMessages::where('chatId', $chat_id)->paginate(10);
        return response()->json([$message], 200);
    }


}
