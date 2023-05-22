<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OpenAIController extends Controller
{
    // protected $OpenAI = OpenAI::client(getenv('OPEN_AI_API_KEY'));

    public function requestPrompt(Request $request)
    {

        $promptText = $request->prompt;

        $user = $request->user();

        $first_name = $user->first_name;

        $request = Http::withHeaders(array(
            'Authorization' => 'Bearer ' . getenv('OPEN_AI_API_KEY'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ))->post('https://api.openai.com/v1/chat/completions', array(
            "model" => "gpt-3.5-turbo",
            "messages" => array(array("role" => 'user', "content" => $promptText, "name" => $first_name)),
            "temperature" => 0.9,
            "n" => 2

        ));

        return $request->object();
    }
}
