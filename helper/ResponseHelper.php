<?php

function SuccessResponse($message, $data = [], $status = 200)
{
    $responseData = ['message' => $message];

    if ($data != []) $responseData['responseData'] = $data;

    return response()->json($responseData, $status);
}

function ErrorResponse($message, $data = [], $status = 400)
{
    $responseData = ['message' => $message];

    if ($data != []) $responseData['errorData'] = $data;

    return response()->json($responseData, $status);
}
