<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week' => 'required|numeric',
            'title' => 'required|string',
            'body' => 'required|string'
        ];
    }
}
