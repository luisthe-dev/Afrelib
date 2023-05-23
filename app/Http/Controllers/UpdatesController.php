<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUpdateRequest;
use App\Models\Updates;

class UpdatesController extends Controller
{

    public function createUpdates(CreateUpdateRequest $request)
    {

        $update = new Updates([
            'update_week' => $request->week,
            'update_title' => $request->title,
            'update_description' => $request->body,
        ]);

        $update->save();

        return SuccessResponse('Update Created Successfully', $update);
    }

    public function getAllUpdates()
    {
        return Updates::orderByDesc('created_at')->paginate(50);
    }
}
