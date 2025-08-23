<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotificationJob;
use App\Models\Batch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoice::query();

        // Load relationships for each invoice
        $query->with('supplier', 'user');

        // Filter by specific status if requested
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply other existing filters
        if ($request->filled('invoice_date')) {
            $query->whereDate('invoice_date', $request->invoice_date);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('total')) {
            $query->where('total', $request->total);
        }

        if ($request->filled('min_total')) {
            $query->where('total', '>=', $request->min_total);
        }

        if ($request->filled('max_total')) {
            $query->where('total', '<=', $request->max_total);
        }

        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Paginate and get the results
        $invoices = $query->latest()->paginate(15);

        return $this->success($invoices, 'تم جلب الفواتير بنجاح');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'invoice_number' => 'nullable|string|unique:purchase_invoices,invoice_number',
            'invoice_date' => 'nullable|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0|max:' . ($request->input('total') ?? PHP_FLOAT_MAX),
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batches' => 'required|array|min:1',
            'items.*.batches.*.batch_number' => 'nullable|string',
            'items.*.batches.*.quantity' => 'required|integer|min:1',
            'items.*.batches.*.expiry_date' => 'required|date|after:today',
            'items.*.batches.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $invoiceNumber = $validatedData['invoice_number']  ?? 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            $status = $this->determineInvoiceStatus($validatedData['total'], $validatedData['paid_amount']);

            $invoice = PurchaseInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validatedData['invoice_date'] ?? now(),
                'supplier_id' => $validatedData['supplier_id'],
                'subtotal' => $validatedData['subtotal'] ?? $validatedData['total'],
                'discount' => $validatedData['discount'] ?? 0,
                'total' => $validatedData['total'],
                'paid_amount' => $validatedData['paid_amount'],
                'status' => $status,
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
                    'unit_cost' => $itemData['unit_cost'],
                    'total' => $itemData['unit_cost'] * $itemData['quantity'],
                ]);

                $newUnitPrice = $itemData['batches'][0]['unit_price'];

                foreach ($itemData['batches'] as $batchData) {
                    $batchNumber = $batchData['batch_number'] ?? 'BCH-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
                    $purchaseItem->batches()->create([
                        'drug_id' => $purchaseItem->drug_id,
                        'batch_number' => $batchNumber,
                        'quantity' => $batchData['quantity'],
                        'stock' => $batchData['quantity'],
                        'expiry_date' => $batchData['expiry_date'],
                        'unit_cost' => $purchaseItem->unit_cost,
                        'unit_price' => $newUnitPrice,
                        'total'=> $purchaseItem->unit_cost * $batchData['quantity'],
                    ]);
                }

                \App\Models\Batch::where('drug_id', $purchaseItem->drug_id)
                    ->where('purchase_item_id', '!=', $purchaseItem->id)
                    ->update(['unit_price' => $newUnitPrice]);
            }

            DB::commit();

            $usersToNotify = User::whereIn('role', ['admin','pharmacist'])->get();
            $deviceTokens = $usersToNotify->flatMap(function ($user) {
                return $user->deviceTokens->pluck('token');
            })->toArray();
            $userIds = $usersToNotify->pluck('id')->toArray();
            $message = "تم إنشاء فاتورة إدخال جديدة برقم {$invoiceNumber} تحتوي على " . count($validatedData['items']) . " صنف.";
            $type = 'general';
            $title = 'فاتورة إدخال جديدة!';

            SendNotificationJob::dispatch($message, $type, $title, $userIds, $deviceTokens);


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
        $validatedData = $request->validate([
            'invoice_number' => 'nullable|string|unique:purchase_invoices,invoice_number,' . $invoice->id,
            'invoice_date' => 'nullable|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'subtotal' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0|max:' . ($request->input('total') ?? PHP_FLOAT_MAX),
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'nullable|exists:purchase_items,id',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batches' => 'required|array|min:1',
            'items.*.batches.*.batch_id' => 'nullable|exists:batches,id',
            'items.*.batches.*.batch_number' => 'nullable|string',
            'items.*.batches.*.quantity' => 'required|integer|min:1',
            'items.*.batches.*.expiry_date' => 'required|date',
            'items.*.batches.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $status = $this->determineInvoiceStatus($validatedData['total'], $validatedData['paid_amount']);

            $invoice->update([
                'invoice_number' => $validatedData['invoice_number'] ?? $invoice->invoice_number,
                'invoice_date' => $validatedData['invoice_date'] ?? $invoice->invoice_date,
                'supplier_id' => $validatedData['supplier_id'],
                'subtotal' => $validatedData['subtotal'] ?? $invoice->subtotal,
                'discount' => $validatedData['discount'] ?? $invoice->discount,
                'total' => $validatedData['total'],
                'paid_amount' => $validatedData['paid_amount'],
                'status' => $status,
                'notes' => $validatedData['notes'] ?? $invoice->notes,
                'user_id' => Auth::id(),
            ]);

            $existingItemIds = $invoice->purchaseItems()->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($validatedData['items'] as $itemData) {
                $purchaseItem = PurchaseItem::updateOrCreate(
                    [
                        'id' => $itemData['purchase_item_id'] ?? null,
                        'purchase_invoice_id' => $invoice->id
                    ],
                    [
                        'drug_id' => $itemData['drug_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $itemData['unit_cost'],
                        'total' => $itemData['unit_cost'] * $itemData['quantity'],
                    ]
                );

                $updatedItemIds[] = $purchaseItem->id;

                $existingBatchIds = $purchaseItem->batches()->pluck('id')->toArray();
                $updatedBatchIds = [];

                $newUnitPrice = $itemData['batches'][0]['unit_price'];

                foreach ($itemData['batches'] as $batchData) {
                    $batchNumber = $batchData['batch_number'] ?? 'BCH-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));

                    $batch = Batch::updateOrCreate(
                        ['id' => $batchData['batch_id'] ?? null, 'purchase_item_id' => $purchaseItem->id],
                        [
                            'drug_id' => $purchaseItem->drug_id,
                            'batch_number' => $batchNumber,
                            'quantity' => $batchData['quantity'],
                            'stock' => $batchData['quantity'],
                            'expiry_date' => $batchData['expiry_date'],
                            'unit_cost' => $purchaseItem->unit_cost,
                            'unit_price' => $newUnitPrice,
                            'total' => $purchaseItem->unit_cost * $batchData['quantity'],
                        ]
                    );

                    $updatedBatchIds[] = $batch->id;
                }

                \App\Models\Batch::where('drug_id', $purchaseItem->drug_id)
                    ->whereNotIn('id', $updatedBatchIds)
                    ->update(['unit_price' => $newUnitPrice]);

                $batchesToDelete = array_diff($existingBatchIds, $updatedBatchIds);
                Batch::whereIn('id', $batchesToDelete)->delete();
            }

            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            PurchaseItem::whereIn('id', $itemsToDelete)->delete();

            DB::commit();

            $invoice->load('supplier', 'user', 'purchaseItems.drug', 'purchaseItems.batches');
            return $this->success($invoice, 'تم تحديث الفاتورة بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في تحديث الفاتورة: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseInvoice $invoice)
    {
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

    /**
     * Helper function to determine invoice status based on total and paid amounts.
     *
     * @param float $total
     * @param float $paidAmount
     * @return string
     */
    private function determineInvoiceStatus(float $total, float $paidAmount): string
    {
        if ($paidAmount >= $total) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partially paid';
        } else {
            return 'unpaid';
        }
    }
}
