<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource with filtering.
     */
    public function index(Request $request)
    {
        $query = Batch::with('drug', 'purchaseItem.purchaseInvoice');

        // Filter by drug ID
        if ($request->filled('drug_id')) {
            $query->where('drug_id', $request->drug_id);
        }

        // Filter by status (active, expired, pending, etc.)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Find batches expiring before a certain date
        if ($request->filled('expiry_before')) {
            $query->whereDate('expiry_date', '<=', $request->expiry_before);
        }

        // Find batches expiring after a certain date
        if ($request->filled('expiry_after')) {
            $query->whereDate('expiry_date', '>=', $request->expiry_after);
        }

        // Search by drug name
        if ($request->filled('search')) {
            $query->whereHas('drug', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $batches = $query->latest()->paginate(20);

        return $this->success(
            $batches,
            'تم جلب الدفعات بنجاح'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $batch = Batch::with(['drug', 'purchaseItem.purchaseInvoice.supplier'])->find($id);

        if (!$batch) {
            return $this->error('الدفعة غير موجودة', 404);
        }

        return $this->success($batch, 'تم جلب الدفعة بنجاح');
    }

    /**
     * Store a newly created resource in storage.
     * This method is intentionally left empty because batches should only be created
     * through the PurchaseInvoiceController to ensure data integrity.
     */
    public function store(Request $request)
    {
        return $this->error('لا يمكن إنشاء دفعة بشكل مباشر. يرجى استخدام نقطة نهاية فواتير المشتريات.', 405); // 405 Method Not Allowed
    }

    /**
     * Update the specified resource in storage.
     * This method is intentionally left empty because batches should only be updated
     * through the PurchaseInvoiceController. A direct update might be allowed for specific fields like 'status' in the future.
     */
    public function update(Request $request, $id)
    {
        return $this->error('لا يمكن تعديل دفعة بشكل مباشر. يرجى استخدام نقطة نهاية فواتير المشتريات.', 405);
    }

    /**
     * Remove the specified resource from storage.
     * This method is intentionally left empty because batches should only be deleted
     * when their parent purchase invoice is deleted.
     */
    public function destroy($id)
    {
        return $this->error('لا يمكن حذف دفعة بشكل مباشر.', 405);
    }
}
