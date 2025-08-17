<?php

namespace App\Http\Controllers;

use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\Drug;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource with filtering.
     */

    public function index(Request $request, Drug $drug)
    {
        $batches = $drug->batches()->with(['purchaseItem.purchaseInvoice'])->get();

        $groupedBatches = $batches->groupBy('status');
        $drug['unit_price']=$batches[0]->unit_price;
        $response = [
            'drug'=>$drug,
            'active' => BatchResource::collection($groupedBatches->get('active', collect())),
            'expired' => BatchResource::collection($groupedBatches->get('expired', collect())),
            'sold_out' => BatchResource::collection($groupedBatches->get('sold_out', collect())),
        ];

        return $this->success(
            $response,
            'تم جلب دفعات الدواء وتصنيفها بنجاح'
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
