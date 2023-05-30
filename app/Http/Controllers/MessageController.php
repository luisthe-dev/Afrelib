<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use App\Events\ChatMessages as ChatMessagess; 
use App\Events\SendChatMessage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\chat;
use App\Models\groupChat;
use App\Models\ChatMessages;
use App\Models\unreadmessage;



class MessageController extends Controller
{
    //
    public function sendMessage(Request $request, $chat_id)
    {
        // event(new \App\Events\SendChatMessage());
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

        // Validator 
        $rules = [
            // 'content' => 'required',
            'mediaType' => 'required',
            'senderId' => 'required',
            'senderName' => 'required',
            'timestamp' => 'required',
            // Add more required fields here as needed
        ];
    
        $messages = [
            // 'content.required' => 'Content is required',
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
        

        // if ($request->mediaType != "text" || $request->mediaType != "emoji") {
            // Saving image file

            
            // $validator = Validator::make($request->all(), [
            //     'mediaUrl' => 'required|image'
            // ]);
        
            // if ($validator->fails()) {
            //     return response()->json(['error' => $validator->errors()], 422);
            // }
            
        
            // $imageData = file_get_contents($request->mediaUrl);
            // $fileName = basename($request->mediaUrl);
            // Storage::put('fileimages/'.$fileName, $imageData);

            // $file = $request->file('mediaUrl');
            // $fileName = $file->getClientOriginalName();
            // $filePath = $file->storeAs('public/images', $fileName);


        // } 
        if($request->content == ""){
            $request->content = "No content found";
        }

        $rand= $chat_id. rand(0000,9999);
        if(!$request->mediaUrl){
            $request->mediaUrl = "No file found";
        }
      

        $SaveMessage=  new ChatMessages;
        $SaveMessage->messageId= $rand;
        $SaveMessage->chatId= $chat_id;
        $SaveMessage->content= $request->content;
        $SaveMessage->mediaType= $request->mediaType;
        $SaveMessage->mediaUrl= $request->mediaUrl;
        $SaveMessage->senderId= $request->senderId;
        $SaveMessage->senderName= $request->senderName;
        $SaveMessage->timestamp= $request->timestamp;
        $SaveMessage->status= "UnRead";
        $SaveMessage->save();


        $chat= chat::where('chatId', $chat_id)->where('userId', '!=', $request->senderId)->get();
        for($i=0; $i<$chat->count(); $i++){
            $unreadMessage = new unreadmessage;
            $unreadMessage->chatId = $chat_id;
            $unreadMessage->messageId = $rand;
            $unreadMessage->userId = $chat[$i]->userId;
            $unreadMessage->username = $chat[$i]->firstName . ' ' .$chat[$i]->lastName;
            $unreadMessage->status = 'Unread';
            $unreadMessage->save();
        }
        
        event(new SendChatMessage( $chat_id));
        
        return response()->json([
            "messageId" => $rand,
            "chatId" => $chat_id,
            "content" => $request->content,
            "mediaType" => $request->mediaType,
            "mediaUrl" => $request->mediaUrl,
            "senderId" => $request->senderId,
            "senderName" => $request->senderName,
            "timestamp" => $request->timestamp,
            "status" => $SaveMessage->status

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
        $chat= ChatMessages::where('chatId', $chat_id)->get();
        $chatdecrip= chat::where('chatId', $chat_id)->get();
        // $lastmessage= ChatMessages::where('chatId', $chat_id)->last();
        if($chat->count() <=0)
        {
            return response()->json(['error' => 'No message as been sent by this group'], 404);
        }

        $messages = ChatMessages::where('chatId', $chat_id)
        ->orderByDesc('created_at')
        ->paginate(40);
    
    $combinedResults = [];
    
    foreach ($messages as $message) {
        $sender = User::find($message->senderId);
    
        if ($sender) {
            $combinedResults[] = array_merge($message->toArray(), ['profile_image' => $sender->profile_image]);
            // $combinedResults[] = [
            //     'message' => $message,
            //     'profile_image' => $sender->profile_image,
            // ];
        }
    }
    
        // return response()->json([$user_detail]);

        $messagestatus= ChatMessages::where('chatId', $chat_id)->where('status', 'UnRead')->get();

        if($messagestatus->count() > 0){
            for($i=0; $i < $messagestatus->count(); $i++){
                $messagestatus[$i]->status = "Read";
                $messagestatus[$i]->save();
            }
        
        }
        $lastMessage = ChatMessages::where('chatId', $chat_id)
        ->orderByDesc('created_at')
        ->value('content');

        // event(new SendChatMessage( $chat_id));
        return response()->json(["Chat Description" => $chatdecrip[0]->chatDescription, "Messages" => $combinedResults],  200);
        
    }

    

    public function UnreadMessages($chat_id)
    {
        $chatmessages= ChatMessages::where('chatId', $chat_id)->get();
        
        if($chatmessages->count() <= 0 ){
            return response()->json(['error' => 'Chat ID does not exist'], 404);
        }

        $messagestatus= ChatMessages::where('chatId', $chat_id)->where('status', 'UnRead')->get();

        return response()->json(['Unread Messages' => $messagestatus->count()], 200);

   

        // if($messagestatus->count() > 0){
        //     for($i=0; $i < $messagestatus->count(); $i++){
        //         $messagestatus[$i]->status = "Read";
        //         $messagestatus[$i]->save();
        //     }
           
        //     return response()->json(['Unread Messages' => $messagestatus->count()], 200);
        // }
        // elseif($messagestatus->count() <= 0){

        //     return response()->json(['Unread Messages' => 0], 200);
        // }
        // else{
        //     return response()->json(['error' => 'Could not process query'], 404);
        // }
        

    }

    public function groupchatMembers($chat_id)
    {
        $ChatMembers = chat::where('chatId',$chat_id)->get();

        if($ChatMembers->count() > 0){
            return response()->json([$ChatMembers], 202);
        }else{
            return response()->json(['error' => 'No members found in this group chat'], 404);
        }

    }
    public function IndividualunreadMessages($chatId){
        $unreadMessage= unreadMessage::where('chatId', $chatId)->get();

        if($unreadMessage->count() > 0){
            return response()->json([$unreadMessage], 202);
        }
        else{
            return response()->json(['Could not find chat Id in unread messages'], 404);
        }
        
    }

    public function readchat($chat_id, $userId)
    {
        // Checking chat ID 
        $chatId= unreadMessage::where('chatId', $chat_id)->get();
        
        if($chatId->count() < 0){
            return response()->json(['Could not find chat ID'], 404);
        }

        // Checking User ID 
        $user_Id= unreadMessage::where('userId', $userId)->get();
        
        if($user_Id->count() < 0){
            return response()->json(['Could not find User ID'], 404);
        }

        // Updating the status of messages
        $unread = unreadMessage::where('chatId', $chat_id)->where('userId', $userId)->get();
 
        for($i=0; $i < $unread->count(); $i++){
            $unread[$i]->status = "Read";
            $unread[$i]->save();
        }
    
    }


}
