<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCohortRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cohort_name' => 'required|string',
            'cohort_description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'mentorIds' => 'array',
            'panelistIds' => 'array',
            'teamIds' => 'array'
        ];
    }
}
