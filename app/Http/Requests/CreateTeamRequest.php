<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'team_name' => 'required|string|unique:teams,team_name',
            'team_description' => 'required|string',
            'studentIds' => 'required|array',
            'mentorId' => 'required|integer'
        ];
    }
}
