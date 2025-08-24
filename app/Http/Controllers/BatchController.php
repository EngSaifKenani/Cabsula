<?php

namespace App\Http\Controllers;

use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\Drug;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
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
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['available', 'expired', 'sold_out', 'disposed', 'returned']),
            ],
        ]);

        $batch = Batch::find($id);

        if (!$batch) {
            return $this->error('الدفعة غير موجودة', 404);
        }

        $batch->status = $validated['status'];

        // Automatically record who performed the action and when
        if ($validated['status'] == 'disposed') {
            $batch->disposed_at = now();
            $batch->disposed_by = auth()->id(); // Get the currently logged-in user's ID
            // Clear return fields in case it was returned before
            $batch->returned_at = null;
            $batch->returned_by = null;
        } elseif ($validated['status'] == 'returned') {
            $batch->returned_at = now();
            $batch->returned_by = auth()->id();
            // Clear dispose fields
            $batch->disposed_at = null;
            $batch->disposed_by = null;
        } else {
            // If status is changed to something else (e.g., available), clear all action fields
            $batch->disposed_at = null;
            $batch->disposed_by = null;
            $batch->returned_at = null;
            $batch->returned_by = null;
        }

        $batch->save();

        return $this->success(new BatchResource($batch->fresh(['disposer', 'returner'])), 'تم تحديث حالة الدفعة بنجاح');
    }

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

    public function getDisposedLosses(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        $disposedBatches = Batch::where('status', 'disposed')
            ->whereBetween('disposed_at', [$startDate, $endDate])
            ->get();

        $totalLoss = $disposedBatches->sum(function ($batch) {
            return $batch->unit_cost * $batch->stock;
        });

        return $this->success([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_disposed_batches' => $disposedBatches->count(),
            'total_loss' => number_format($totalLoss, 2),
        ], 'تم حساب الخسائر بنجاح.');
    }

    public function getReturnedValue(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        $query = Batch::where('status', 'returned')
            ->whereBetween('returned_at', [$startDate, $endDate]);

        $query->when($request->filled('supplier_id'), function ($q) use ($request) {
            $q->whereHas('purchaseItem.purchaseInvoice', function ($invoiceQuery) use ($request) {
                $invoiceQuery->where('supplier_id', $request->supplier_id);
            });
        });

        $returnedBatches = $query->get();

        $totalReturnedValue = $returnedBatches->sum(function ($batch) {
            return $batch->unit_cost * $batch->stock;
        });

        return $this->success([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_returned_batches' => $returnedBatches->count(),
            'total_returned_value' => number_format($totalReturnedValue, 2),
        ], 'تم حساب قيمة المرتجعات بنجاح.');
    }

}
