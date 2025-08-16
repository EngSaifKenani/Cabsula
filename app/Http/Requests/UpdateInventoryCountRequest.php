<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryCountRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحًا له بإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        // يجب أن تتحقق هنا مما إذا كان المستخدم الحالي يمتلك صلاحية تعديل هذا الجرد
        // return $this->user()->can('update', $this->route('inventory_count'));

        return true; // الآن نسمح للجميع مؤقتاً
    }

    /**
     * الحصول على قواعد التحقق التي تنطبق على الطلب.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // نفس قواعد التحقق المستخدمة في الإنشاء
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
