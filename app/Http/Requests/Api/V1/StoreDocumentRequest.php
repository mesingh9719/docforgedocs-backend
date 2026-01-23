<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        \Illuminate\Support\Facades\Log::info('StoreDocumentRequest Input:', $this->all());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type_slug' => 'required|string', // Temporarily removed exists check to debug
            'content' => 'required|array', // JSON content
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:draft,sent,archived',
        ];
    }
}
