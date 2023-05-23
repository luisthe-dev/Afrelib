<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{

    public function getRoles()
    {
        return Role::orderByDesc('created_at')->get();
    }

    public function createRole(Request $request)
    {

        $request->validate([
            'role_name' => 'required|string|min:3|unique:roles,role_name'
        ]);

        $uniqueId = false;

        while (!$uniqueId) {
            $roleId = generateRandom();

            $checkRole = Role::where(['role_id' => $roleId])->count();

            if ($checkRole === 0) $uniqueId = true;
        }

        $newRole = new Role([
            'role_id' => $roleId,
            'role_name' => $request->role_name
        ]);

        $newRole->save();

        return SuccessResponse('Role Created Successfully', $newRole);
    }

    public function editRole(Request $request, String $roleId)
    {

        $request->validate([
            'role_name' => 'required|string|min:3'
        ]);

        $role = Role::where(['role_id' => $roleId])->first();

        if (!$role) return ErrorResponse('Invalid Role Id');

        $role->role_name = $request->role_name;

        $role->save();

        return SuccessResponse('Role Updated Successfully', $role);
    }

    public function deleteRole(String $roleId)
    {


        $role = Role::where(['role_id' => $roleId])->first();

        if (!$role) return ErrorResponse('Invalid Role Id');

        $role->delete();

        return SuccessResponse('Role Deleted Successfully');
    }
}
