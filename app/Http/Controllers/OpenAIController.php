<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OpenAIController extends Controller
{

    public function requestPrompt(Request $request)
    {

        $request->validate([
            'prompt' => 'required|string'
        ]);

        $promptText = $request->prompt;

        $user = $request->user();

        $first_name = $user->first_name;

        $searches = DB::table('search_history')->where('user_id', $user->id)->get();

        $messageContent = array();

        foreach ($searches as $search) {

            array_push($messageContent, array("role" => 'user', "content" => $search->search, "name" => $first_name));

            array_push($messageContent, json_decode($search->gpt_response, true));
        }

        array_push($messageContent, array("role" => 'user', "content" => $promptText, "name" => $first_name));

        $request = Http::withHeaders(array(
            'Authorization' => 'Bearer ' . getenv('OPEN_AI_API_KEY'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ))->post('https://api.openai.com/v1/chat/completions', array(
            "model" => "gpt-3.5-turbo",
            "messages" => $messageContent,
            "temperature" => 0.9,
            "n" => 1

        ));

        DB::table('search_history')->insert([
            'user_id' => $user->id,
            'search' => $promptText,
            'gpt_response' => json_encode($request->object()->choices[0]->message),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);



        return $request->object();
    }

    public function getUserSearchHistory(Request $request)
    {

        $user = $request->user();

        $searchHistory = DB::table('search_history')->where('user_id', $user->id)->get();

        return SuccessResponse('Search History Fetched Successfully', $searchHistory);
    }
}
