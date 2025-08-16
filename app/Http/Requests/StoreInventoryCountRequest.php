<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryCountRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحًا له بإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        // هنا يمكنك وضع منطق الصلاحيات، مثلاً:
        // return $this->user()->can('create', InventoryCount::class);
        return true; // الآن نسمح للجميع مؤقتاً
    }

    /**
     * الحصول على قواعد التحقق التي تنطبق على الطلب.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'count_date' => 'required|date',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.drug_id' => 'required|integer|exists:drugs,id',
            'details.*.batch_id' => 'required|integer|exists:batches,id',
            'details.*.system_quantity' => 'required|integer|min:0',
            'details.*.counted_quantity' => 'required|integer|min:0',
            'details.*.reason' => 'nullable|string|max:255',
        ];
    }
}
