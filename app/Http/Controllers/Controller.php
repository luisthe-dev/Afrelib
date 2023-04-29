<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function getStatus(Request $request)
    {

        if ($request->passkey !== 1941) return ErrorResponse('Invalid Key');

        return SuccessResponse('Success');
    }


    public function uploadFile(Request $request, $uploadType)
    {

        if (!$request->hasFile('uploadFile')) return ErrorResponse('No File Selected For Upload');

        $filePath = $request->file('uploadFile')->store($uploadType, 'public');

        return SuccessResponse('File Uploaded Successfully', array('url' => '/storage/' . $filePath));
    }
}
