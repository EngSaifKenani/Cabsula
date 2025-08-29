<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('phone', 'like', $searchTerm);
            });
        }

        $suppliers = $query->with('manufacturers')->latest()->paginate(15);
        return $this->success($suppliers, 'تم جلب الموردين بنجاح');
    }

    public function store(StoreSupplierRequest $request)
    {
        $validatedData = $request->validated();

        $manufacturerIds = $validatedData['manufacturer_ids'];
        unset($validatedData['manufacturer_ids']);

        $supplier = Supplier::create($validatedData);

        $supplier->manufacturers()->sync($manufacturerIds);

        return $this->success($supplier->load('manufacturers'), 'تم إنشاء المورد بنجاح', 201);
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('manufacturers', 'purchaseInvoices','payments');
        return $this->success($supplier, 'تم جلب المورد بنجاح');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validatedData = $request->validated();

        if (isset($validatedData['manufacturer_ids'])) {
            $manufacturerIds = $validatedData['manufacturer_ids'];
            unset($validatedData['manufacturer_ids']);

            $supplier->manufacturers()->sync($manufacturerIds);
        }

        $supplier->update($validatedData);

        return $this->success($supplier->load('manufacturers'), 'تم تحديث المورد بنجاح');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseInvoices()->exists()) {
            return $this->error('لا يمكن حذف المورد لوجود فواتير مرتبطة به', 409); // 409 Conflict
        }

        if ($supplier->supplierReturns()->exists()) {
            return $this->error('لا يمكن حذف المورد لوجود سجلات مرتجعات مرتبطة به', 409); // 409 Conflict
        }

        $supplier->manufacturers()->detach();

        $supplier->delete();

        return $this->success(null, 'تم حذف المورد بنجاح');
    }
}
