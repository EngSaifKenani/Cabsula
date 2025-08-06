<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Get the supplier from the route
        $supplier = $this->route('supplier');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
            'phone' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'tax_number' => 'nullable|string|max:255',
            'commercial_register' => 'nullable|string|max:255',
            // Also allow updating the many-to-many relationship
            'manufacturer_ids' => 'sometimes|required|array',
            'manufacturer_ids.*' => 'required|exists:manufacturers,id'
        ];
    }
}
