<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BusinessUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return false;
        }

        // Owner is always authorized
        if ($business->user_id === $user->id) {
            return true;
        }

        // Check for specific permission
        $permissions = $user->resolvePermissions();

        // Handle wildcard permission (Owner or Super Admin logic if applicable)
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array('settings.manage', $permissions);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'bank_details' => ['nullable', 'array'],
            'default_invoice_notes' => ['nullable', 'string'],
            'industry' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'], // Max 2MB
            'favicon' => ['nullable', 'image', 'mimes:ico,png,jpg,jpeg,svg', 'max:1024'], // Max 1MB
            // New Settings Fields
            'invoice_prefix' => ['nullable', 'string', 'max:255'],
            'invoice_terms' => ['nullable', 'string'],
            'tax_label' => ['nullable', 'string', 'max:255'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'currency_country' => ['nullable', 'string', 'max:255'],
        ];
    }
}
