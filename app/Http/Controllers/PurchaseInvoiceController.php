<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // <-- قم بإضافة هذا السطر
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceController extends Controller
{
    // Use the custom trait for API responses
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // ابدأ ببناء الاستعلام دون تنفيذه
        $query = PurchaseInvoice::query();

        // قم بتضمين العلاقات لتحسين الأداء (Eager Loading)
        $query->with('supplier', 'user');

        // 1. الفلترة حسب تاريخ معين (يوم واحد)
        if ($request->filled('invoice_date')) {
            // استخدام whereDate للتعامل مع التاريخ فقط وتجاهل الوقت
            $query->whereDate('invoice_date', $request->invoice_date);
        }

        // 2. الفلترة حسب نطاق تاريخ (من تاريخ ... إلى تاريخ)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        // 3. الفلترة حسب السعر (الإجمالي)
        // البحث عن سعر محدد بالضبط
        if ($request->filled('total')) {
            $query->where('total', $request->total);
        }

        // البحث عن نطاق سعر (أكبر من أو يساوي)
        if ($request->filled('min_total')) {
            $query->where('total', '>=', $request->min_total);
        }

        // البحث عن نطاق سعر (أصغر من أو يساوي)
        if ($request->filled('max_total')) {
            $query->where('total', '<=', $request->max_total);
        }

        // 4. فلاتر إضافية مفيدة
        // البحث برقم الفاتورة
        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        // البحث بالمورد
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // البحث بحالة الفاتورة (إذا كان لديك عمود status)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // في النهاية، قم بترتيب النتائج وتنفيذ الاستعلام مع الترقيم
        $invoices = $query->latest()->paginate(15);

        return $this->success($invoices, 'تم جلب الفواتير بنجاح');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validation Rules (Revised)
        $validatedData = $request->validate([
            'invoice_number' => 'nullable|string|unique:purchase_invoices,invoice_number',
            'invoice_date' => 'nullable|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'total' => 'required|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batches' => 'required|array|min:1',
            'items.*.batches.*.batch_number' => 'nullable|string',
            'items.*.batches.*.quantity' => 'required|integer|min:1',
            'items.*.batches.*.expiry_date' => 'required|date|after:today',
            'items.*.batches.*.unit_price' => 'required|numeric|min:0', // Price is defined at batch level
        ]);

        DB::beginTransaction();
        try {
            $invoiceNumber = $validatedData['invoice_number']  ?? 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

            $invoice = PurchaseInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validatedData['invoice_date'] ?? now(), // Apply default date logic
                'supplier_id' => $validatedData['supplier_id'],
                'subtotal' =>$validatedData['subtotal']?? $validatedData['total'],
                'total' => $validatedData['total'],
                'status' => 'paid',
                'notes' => $validatedData['notes'],
                'user_id' => Auth::id(),
            ]);

            foreach ($validatedData['items'] as $itemData) {
                $totalBatchQuantity = array_sum(array_column($itemData['batches'], 'quantity'));
                if ($totalBatchQuantity != $itemData['quantity']) {
                    DB::rollBack();
                    return $this->error('مجموع كميات الدفعات (' . $totalBatchQuantity . ') لا يساوي كمية الصنف (' . $itemData['quantity'] . ') للدواء ' . $itemData['drug_id'], 422);
                }

                $purchaseItem = $invoice->purchaseItems()->create([
                    'drug_id' => $itemData['drug_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'], // The single source of truth for cost
                    'total' => $itemData['unit_cost'] * $itemData['quantity'],
                ]);

                foreach ($itemData['batches'] as $batchData) {
                    $batchNumber = $batchData['batch_number'] ?? 'BCH-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));

                    // إنشاء الدفعة الجديدة
                    $newBatch = $purchaseItem->batches()->create([
                        'drug_id' => $purchaseItem->drug_id,
                        'batch_number' => $batchNumber,
                        'quantity' => $batchData['quantity'],
                        'stock' => $batchData['quantity'],
                        'expiry_date' => $batchData['expiry_date'],
                        'unit_cost' => $purchaseItem->unit_cost, // <-- Copy cost from parent item
                        'unit_price' => $batchData['unit_price'], // Use price from the batch data
                        'total'=> $purchaseItem['unit_cost'] * $batchData['quantity'],
                        'status' => 'active',
                    ]);

                    // ✅ تحديث سعر جميع الدفعات السابقة لنفس الدواء
                    \App\Models\Batch::where('drug_id', $purchaseItem->drug_id)
                        ->where('id', '!=', $newBatch->id) // استثناء الدفعة الجديدة
                        ->update(['unit_price' => $batchData['unit_price']]);
                }
            }

            DB::commit();
            $invoice->load('supplier', 'user', 'purchaseItems.drug', 'purchaseItems.batches');
            return $this->success($invoice, 'تم إنشاء الفاتورة بنجاح', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في إنشاء الفاتورة: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(PurchaseInvoice $invoice)
    {
        $invoice->load('supplier', 'user', 'purchaseItems.drug', 'purchaseItems.batches');
        return $this->success($invoice, 'تم جلب الفاتورة بنجاح');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseInvoice $invoice)
    {
        // 1. Validation Rules
        // The unique rule for 'invoice_number' must ignore the current invoice ID
        $validatedData = $request->validate([
            'invoice_number' => 'nullable|string|unique:purchase_invoices,invoice_number,' . $invoice->id,
            'invoice_date' => 'nullable|date',
            'status'=>'nullable|in:paid,partially paid',
            'supplier_id' => 'required|exists:suppliers,id',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batches' => 'required|array|min:1',
            'items.*.batches.*.batch_number' => 'nullable|string',
            'items.*.batches.*.quantity' => 'required|integer|min:1',
            'items.*.batches.*.expiry_date' => 'required|date', // 'after:today' might be too strict for updates
            'items.*.batches.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 2. Determine Payment Status
            $total = $validatedData['total'];
            $amountPaid = $validatedData['amount_paid'] ?? $invoice->amount_paid; // Use new amount if sent, else old amount
            $amountDue = $total - $amountPaid;

            $status = $validatedData['status'] ?? $invoice->status;


            // 3. Update the main invoice record
            $invoice->update([
                'invoice_number' => $validatedData['invoice_number'] ?? $invoice->invoice_number,
                'invoice_date' => $validatedData['invoice_date'] ?? $invoice->invoice_date,
                'supplier_id' => $validatedData['supplier_id'],
                'total' => $total,
                'status' => $status,
                'notes' => $validatedData['notes'],
                'user_id' => Auth::id(), // The user who made the update
            ]);

            // 4. The "Delete and Recreate" strategy for items and batches
            // First, delete all existing items. Cascading delete will handle the batches.
            $invoice->purchaseItems()->delete();

            // Now, recreate items and batches using the same logic as the 'store' method
            foreach ($validatedData['items'] as $itemData) {
                // (Optional but recommended) Data Integrity Check for quantities
                $totalBatchQuantity = array_sum(array_column($itemData['batches'], 'quantity'));
                if ($totalBatchQuantity != $itemData['quantity']) {
                    DB::rollBack();
                    return $this->error('مجموع كميات الدفعات لا يساوي كمية الصنف للدواء ' . $itemData['drug_id'], 422);
                }

                $purchaseItem = $invoice->purchaseItems()->create([
                    'drug_id' => $itemData['drug_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total' => $itemData['unit_cost'] * $itemData['quantity'],
                ]);

                foreach ($itemData['batches'] as $batchData) {
                    $batchNumber = $batchData['batch_number'] ?? 'BCH-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
                    $purchaseItem->batches()->create([
                        'drug_id' => $purchaseItem->drug_id,
                        'batch_number' => $batchNumber,
                        'quantity' => $batchData['quantity'],
                        'stock' => $batchData['quantity'],
                        'expiry_date' => $batchData['expiry_date'],
                        'unit_cost' => $purchaseItem->unit_cost,
                        'unit_price' => $batchData['unit_price'],
                        'total'=> $purchaseItem['unit_cost'] * $batchData['quantity'],
                        'status' => 'active',
                    ]);
                }
            }

            // 5. If everything succeeds, commit the changes
            DB::commit();

            // Reload all relations to return the updated object
            $invoice->load('supplier', 'user', 'purchaseItems.drug', 'purchaseItems.batches');
            return $this->success($invoice, 'تم تحديث الفاتورة بنجاح');

        } catch (\Exception $e) {
            // If any error occurs, roll back all changes
            DB::rollBack();
            return $this->error('فشل في تحديث الفاتورة: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseInvoice $invoice)
    {
        // تم استخدام Route Model Binding هنا، Laravel تتعامل مع إيجاد الفاتورة أو إرجاع خطأ 404 تلقائيًا.

        DB::beginTransaction();
        try {
            $isAnyItemSold = $invoice->purchaseItems()
                ->whereHas('batches', function ($query) {
                    $query->whereColumn('stock', '<', 'quantity');
                })
                ->exists();

            if ($isAnyItemSold) {
                DB::rollBack();
                return $this->error('لا يمكن حذف هذه الفاتورة لأنه تم بيع أصناف مرتبطة بها.', 403);
            }
            $invoice->delete();

            DB::commit();

            return $this->success(null, 'تم حذف الفاتورة وكل بياناتها المرتبطة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في حذف الفاتورة: ' . $e->getMessage(), 500);
        }
    }
}
