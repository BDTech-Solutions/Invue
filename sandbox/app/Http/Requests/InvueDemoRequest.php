<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InvueDemoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3'],
            'role' => ['required', 'string', 'in:Admin,Editor,Viewer'],
            'terms' => ['accepted'],
            'bio' => ['nullable', 'string', 'max:20'],
            'plan' => ['required', 'string', 'in:Free,Pro,Enterprise'],
            'avatar' => ['nullable', 'file', 'image', 'max:2048'],
            'age' => ['required', 'integer', 'min:18', 'max:120'],
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['string', 'in:Design,Dev,Marketing'],
            'links' => ['array', 'max:2'],
            'links.*.url' => ['required', 'url'],
        ];
    }
}
