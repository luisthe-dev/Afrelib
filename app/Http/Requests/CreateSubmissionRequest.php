<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubmissionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submitted_by' => 'required|numeric',
            'week_number' => 'required|numeric',
            'submission_title' => 'required|string',
            'submitted_file' => 'required|url',
            'submitted_url' => 'required|string',
            'submission_comment' => 'required|string'
        ];
    }
}
