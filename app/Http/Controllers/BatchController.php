<?php

namespace App\Http\Controllers;

use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\Disposal;
use App\Models\Drug;
use App\Models\ReturnedItem;
use App\Models\SupplierReturn;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    use ApiResponse;



    public function index(Request $request, Drug $drug = null)
    {
        $query = $drug ? $drug->batches() : Batch::query();

        // تحميل العلاقات اللازمة
        $query->with('purchaseItem.purchaseInvoice');

        // تطبيق فلاتر تاريخ انتهاء الصلاحية، تاريخ الشراء، والحالة
        $query->when($request->filled('expiry_start_date') && $request->filled('expiry_end_date'), function ($q) use ($request) {
            $startDate = Carbon::parse($request->input('expiry_start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('expiry_end_date'))->endOfDay();
            $q->whereBetween('expiry_date', [$startDate, $endDate]);
        });

        $query->when($request->filled('purchase_start_date') && $request->filled('purchase_end_date'), function ($q) use ($request) {
            $startDate = Carbon::parse($request->input('purchase_start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('purchase_end_date'))->endOfDay();
            $q->whereHas('purchaseItem.purchaseInvoice', function ($innerQuery) use ($startDate, $endDate) {
                $innerQuery->whereBetween('invoice_date', [$startDate, $endDate]);
            });
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->input('status'));
        });

        // جلب الدفعات مع الترقيم
        $batches = $query->paginate(15);

        // جلب سعر التكلفة من أحدث دفعة إذا كان الدواء موجوداً
        $costPrice = null;
        if ($drug) {
            // البحث عن أحدث دفعة
            $latestBatch = $drug->batches()->latest()->first();
            if ($latestBatch) {
                $unit_price = $latestBatch->unit_price;
            }
        }

        $response = [
            'drug' => $drug ? [
                'id' => $drug->id,
                'name' => $drug->name,
                'unit_price' => $unit_price,
            ] : null,
            'batches' => BatchResource::collection($batches),
        ];

        return $this->success($response, 'تم جلب الدفعات بنجاح');
    }


    /**
     * Display a listing of the resource with advanced filtering.
     */
//    public function index(Request $request, Drug $drug)
//    {
//        $query = $drug->batches()->with(['purchaseItem.purchaseInvoice']);
//
//        if ($request->filled('expiry_start_date') && $request->filled('expiry_end_date')) {
//            $startDate = Carbon::parse($request->input('expiry_start_date'))->startOfDay();
//            $endDate = Carbon::parse($request->input('expiry_end_date'))->endOfDay();
//            $query->whereBetween('expiry_date', [$startDate, $endDate]);
//        }
//
//      /*  if ($request->filled('purchase_start_date') && $request->filled('purchase_end_date')) {
//            $startDate = Carbon::parse($request->input('purchase_start_date'))->startOfDay();
//            $endDate = Carbon::parse($request->input('purchase_end_date'))->endOfDay();
//            $query->whereHas('purchaseItem.purchaseInvoice', function ($q) use ($startDate, $endDate) {
//                $q->whereBetween('invoice_date', [$startDate, $endDate]);
//            });
//        }*/
//
//        if ($request->filled('status')) {
//            $query->where('status', $request->input('status'));
//        }
//
//        $batches = $query->get();
//
//        $firstBatch = $batches->first();
//        $drug['unit_price'] = $firstBatch ? $firstBatch->unit_price : null;
//
//        $groupedBatches = $batches->groupBy('status');
//
//        $response = [
//            'drug' => $drug,
//            'available' => BatchResource::collection($groupedBatches->get('available', collect())),
//            'expired' => BatchResource::collection($groupedBatches->get('expired', collect())),
//            'sold_out' => BatchResource::collection($groupedBatches->get('sold_out', collect())),
//            'disposed' => BatchResource::collection($groupedBatches->get('disposed', collect())),
//            'returned' => BatchResource::collection($groupedBatches->get('returned', collect())),
//        ];
//
//        return $this->success($response, 'تم جلب دفعات الدواء وتصنيفها بنجاح');
//    }



    /**
     * Display the specified resource, including disposer and returner info.
     */
    public function show($id)
    {
        // Eager load the new relationships 'disposer' and 'returner'
        $batch = Batch::with([
            'drug',
            'purchaseItem.purchaseInvoice.supplier',
            'disposer',
            'returner'
        ])->find($id);

        if (!$batch) {
            return $this->error('الدفعة غير موجودة', 404);
        }

        return $this->success(new BatchResource($batch), 'تم جلب الدفعة بنجاح');
    }

    /**
     * Update only the status of a specific batch and record the action.
     */

    // --- Methods below are intentionally blocked ---

    public function store(Request $request)
    {
        return $this->error('لا يمكن إنشاء دفعة بشكل مباشر. يرجى استخدام نقطة نهاية فواتير المشتريات.', 405);
    }

    public function update(Request $request, $id)
    {
        return $this->error('لا يمكن تعديل دفعة بشكل مباشر. استخدم نقطة النهاية المخصصة لتحديث الحالة.', 405);
    }

    public function destroy($id)
    {
        return $this->error('لا يمكن حذف دفعة بشكل مباشر.', 405);
    }

//    public function getDisposedLosses(Request $request)
//    {
//        $request->validate([
//            'start_date' => 'required|date',
//            'end_date' => 'required|date|after_or_equal:start_date',
//        ]);
//
//        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
//        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
//
//        $disposedBatches = Batch::where('status', 'disposed')
//            ->whereBetween('disposed_at', [$startDate, $endDate])
//            ->get();
//
//        $totalLoss = $disposedBatches->sum(function ($batch) {
//            return $batch->unit_cost * $batch->stock;
//        });
//
//        return $this->success([
//            'start_date' => $startDate->toDateString(),
//            'end_date' => $endDate->toDateString(),
//            'total_disposed_batches' => $disposedBatches->count(),
//            'total_loss' => number_format($totalLoss, 2),
//        ], 'تم حساب الخسائر بنجاح.');
//    }
//
//    public function getReturnedValue(Request $request)
//    {
//        $request->validate([
//            'start_date' => 'required|date',
//            'end_date' => 'required|date|after_or_equal:start_date',
//            'supplier_id' => 'nullable|exists:suppliers,id',
//        ]);
//
//        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
//        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
//
//        $query = Batch::where('status', 'returned')
//            ->whereBetween('returned_at', [$startDate, $endDate]);
//
//        $query->when($request->filled('supplier_id'), function ($q) use ($request) {
//            $q->whereHas('purchaseItem.purchaseInvoice', function ($invoiceQuery) use ($request) {
//                $invoiceQuery->where('supplier_id', $request->supplier_id);
//            });
//        });
//
//        $returnedBatches = $query->get();
//
//        $totalReturnedValue = $returnedBatches->sum(function ($batch) {
//            return $batch->unit_cost * $batch->stock;
//        });
//
//        return $this->success([
//            'start_date' => $startDate->toDateString(),
//            'end_date' => $endDate->toDateString(),
//            'total_returned_batches' => $returnedBatches->count(),
//            'total_returned_value' => number_format($totalReturnedValue, 2),
//        ], 'تم حساب قيمة المرتجعات بنجاح.');
//    }


    public function disposeFullBatch(Batch $batch, Request $request)
    {
        $validatedData = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $disposedQuantity = $batch->stock;

        if ($disposedQuantity <= 0) {
            return $this->error('لا يوجد مخزون متاح للإتلاف.', 422);
        }

        DB::beginTransaction();

        try {
            $lossAmount = $disposedQuantity * $batch->unit_cost;

            Disposal::create([
                'batch_id' => $batch->id,
                'user_id' => Auth::id(),
                'disposed_quantity' => $disposedQuantity,
                'loss_amount' => $lossAmount,
                'reason' => $validatedData['reason'],
                'disposed_at' => now(),
            ]);

            $batch->update([
                'stock' => 0,
                'status' => 'sold_out',
                ]);

            DB::commit();

            return $this->success($batch->fresh(), 'تم إتلاف الدفعة بالكامل بنجاح.', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في إتلاف الدفعة: ' . $e->getMessage(), 500);
        }
    }

    public function returnFullBatch(Batch $batch, Request $request)
    {
        $validatedData = $request->validate([
            'notes' => 'required|string|max:500', // جعل حقل الملاحظات إجباريًا
        ]);

        $returnedQuantity = $batch->stock;

        if ($returnedQuantity <= 0) {
            return $this->error('لا يوجد مخزون متاح لإرجاعه.', 422);
        }

        DB::beginTransaction();

        try {
            $creditAmount = $returnedQuantity * $batch->unit_cost;
            SupplierReturn ::create([
                'batch_id' => $batch->id,
                'supplier_id' => $batch->purchaseItem->purchaseInvoice->supplier_id,
                'purchase_invoice_id' => $batch->purchaseItem->purchaseInvoice->id,
                'user_id' => Auth::id(),
                'returned_quantity' => $returnedQuantity,
                'credit_amount' => $creditAmount,
                'notes' => $validatedData['notes'], // استخدام الملاحظات من البيانات المحققة
            ]);

            // 2. تحديث حالة الدفعة وتصفير المخزون
            $batch->update([
                'stock' => 0,
                'status' => 'sold_out',
            ]);

            DB::commit();

            return $this->success($batch->fresh(), 'تم إرجاع الدفعة بالكامل بنجاح.', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في إرجاع الدفعة: ' . $e->getMessage(), 500);
        }
    }

}
