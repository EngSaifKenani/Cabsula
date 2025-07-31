<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $suppliers = $query->latest()->paginate(15);
        return $this->success($suppliers, 'تم جلب الموردين بنجاح');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255|unique:suppliers,email',
                'phone' => 'nullable|string|max:20',
                'contact_person' => 'nullable|string|max:255',
                'note' => 'nullable|string',
                'address' => 'nullable|string',
                'is_active' => 'boolean',
                'tax_number' => 'nullable|string|max:255',
                'commercial_register' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->errors(), 422);
        }

        $supplier = Supplier::create($validatedData);
        return $this->success($supplier, 'تم إنشاء المورد بنجاح', 201);
    }

    /**
     * Display the specified resource.
     */
    public function
    show($id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return $this->error('المورد غير موجود', 404);
        }
        return $this->success($supplier, 'تم جلب المورد بنجاح');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return $this->error('المورد غير موجود', 404);
        }

        try {
                $validatedData = $request->validate([
                    'name' => 'sometimes|required|string|max:255',
                    'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
                    'phone' => 'nullable|string|max:20',
                    'contact_person' => 'nullable|string|max:255',
                    'note' => 'nullable|string',
                    'address' => 'nullable|string',
                    'is_active' => 'boolean',
                    'tax_number' => 'nullable|string|max:255',
                    'commercial_register' => 'nullable|string|max:255',
                ]);
        } catch (ValidationException $e) {
            return $this->error($e->errors(), 422);
        }

        $supplier->update($validatedData);
        return $this->success($supplier, 'تم تحديث المورد بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $supplier = Supplier::withCount('purchaseInvoices')->find($id);
        if (!$supplier) {
            return $this->error('المورد غير موجود', 404);
        }

        // Prevent deletion if the supplier has associated invoices
        if ($supplier->purchase_invoices_count > 0) {
            return $this->error('لا يمكن حذف المورد لوجود فواتير مرتبطة به', 409); // 409 Conflict
        }

        $supplier->delete();
        return $this->success(null, 'تم حذف المورد بنجاح');
    }
}
