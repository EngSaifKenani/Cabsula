<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Set to true to allow requests. Add authorization logic here later.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'phone' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'tax_number' => 'nullable|string|max:255',
            'commercial_register' => 'nullable|string|max:255',
            // Validation for the many-to-many relationship
            'manufacturer_ids' => 'required|array',
            'manufacturer_ids.*' => 'required|exists:manufacturers,id'
        ];
    }
}
