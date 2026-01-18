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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type_slug' => 'required|string|exists:document_types,slug', // Client sends 'nda', 'proposal', 'invoice'
            'content' => 'required|array', // JSON content
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:draft,sent,archived',
        ];
    }
}
