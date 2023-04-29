<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_title' => 'required|string',
            'team_id' => 'required|numeric',
            'cohort_id' => 'required|string',
            'project_description' => 'required|string'
        ];
    }
}
