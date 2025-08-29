<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotificationJob;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseInvoiceController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoice::query();

        $query->with('supplier', 'user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

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

        $invoices = $query->latest()->paginate(15);

        return $this->success($invoices, 'تم جلب الفواتير بنجاح');
    }

    public function show(PurchaseInvoice $invoice)
    {
        $invoice->load([
            'supplier',
            'user',
            'purchaseItems.drug',
            'purchaseItems.batches',
            'payments',
        ]);

        return $this->success($invoice, 'تم جلب الفاتورة بنجاح');
    }

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
            $calculatedSubtotal = collect($validatedData['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_cost'];
            });
            $sentDiscount = $validatedData['discount'] ?? 0;
            $calculatedTotal = $calculatedSubtotal - $sentDiscount;
            if (abs($calculatedTotal - $validatedData['total']) > 0.001) {
                DB::rollBack();
                return $this->error('إجمالي الفاتورة غير صحيح. يرجى مراجعة قيم الأصناف.', 422);
            }

            $supplier = Supplier::find($validatedData['supplier_id']);
            $initialPaidAmount = $validatedData['paid_amount'];
            $creditApplied = 0;

            if ($supplier->account_balance < 0) {
                $creditToApply = abs($supplier->account_balance);
                $remainingTotal = $calculatedTotal - $initialPaidAmount;
                $creditApplied = min($creditToApply, $remainingTotal);
            }

            $finalPaidAmount = $initialPaidAmount + $creditApplied;
            $unpaidAmount = $calculatedTotal - $finalPaidAmount;

            $status = $this->determineInvoiceStatus($calculatedTotal, $finalPaidAmount);
            $paidAt = ($status === 'paid') ? now() : null;

            $invoiceNumber = $validatedData['invoice_number']  ?? 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

            $invoice = PurchaseInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validatedData['invoice_date'] ?? now(),
                'supplier_id' => $validatedData['supplier_id'],
                'subtotal' => $validatedData['subtotal'] ?? $calculatedSubtotal,
                'discount' => $validatedData['discount'] ?? 0,
                'total' => $calculatedTotal,
                'paid_amount' => $finalPaidAmount,
                'status' => $status,
                'paid_at' => $paidAt,
                'notes' => $validatedData['notes'],
                'user_id' => Auth::id(),
            ]);

            if ($finalPaidAmount > 0) {
                Payment::create([
                    'purchase_invoice_id' => $invoice->id,
                    'user_id' => Auth::id(),
                    'amount' => $finalPaidAmount,
                    'notes' => 'دفعة أولية عند إنشاء الفاتورة.',
                ]);
            }

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

                foreach($itemData['batches'] as $batchData) {
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

                Batch::where('drug_id', $purchaseItem->drug_id)
                    ->where('purchase_item_id', '!=', $purchaseItem->id)
                    ->update(['unit_price' => $newUnitPrice]);
            }

            $supplier->account_balance += $unpaidAmount;
            $supplier->save();

            DB::commit();

            $usersToNotify = User::whereIn('role', ['admin','pharmacist'])->get();
            $deviceTokens = $usersToNotify->flatMap(fn($user) => $user->deviceTokens->pluck('token'))->filter()->toArray();
            $userIds = $usersToNotify->pluck('id')->toArray();
            $message = "تم إنشاء فاتورة إدخال جديدة برقم {$invoiceNumber} تحتوي على " . count($validatedData['items']) . " صنف.";
            $type = 'general';
            $title = 'فاتورة إدخال جديدة!';
            SendNotificationJob::dispatch($message, $type, $title, $userIds, $deviceTokens);

            $invoice->load('supplier', 'user', 'purchaseItems.drug', 'purchaseItems.batches', 'payments');

            return $this->success($invoice, 'تم إنشاء الفاتورة بنجاح', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في إنشاء الفاتورة: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, PurchaseInvoice $invoice)
    {
        $oldInvoiceTotal = $invoice->total;
        $oldPaidAmount = $invoice->paid_amount;
        $oldSupplierId = $invoice->supplier_id;

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
            $calculatedSubtotal = collect($validatedData['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_cost'];
            });
            $sentDiscount = $validatedData['discount'] ?? 0;
            $calculatedTotal = $calculatedSubtotal - $sentDiscount;
            if (abs($calculatedTotal - $validatedData['total']) > 0.001) {
                DB::rollBack();
                return $this->error('إجمالي الفاتورة غير صحيح. يرجى مراجعة قيم الأصناف.', 422);
            }

            $supplier = Supplier::find($validatedData['supplier_id']);
            $initialPaidAmount = $validatedData['paid_amount'];
            $creditApplied = 0;

            if ($supplier->account_balance < 0) {
                $creditToApply = abs($supplier->account_balance);
                $remainingTotal = $calculatedTotal - $initialPaidAmount;
                $creditApplied = min($creditToApply, $remainingTotal);
            }

            $finalPaidAmount = $initialPaidAmount + $creditApplied;
            $unpaidAmount = $calculatedTotal - $finalPaidAmount;

            $status = $this->determineInvoiceStatus($calculatedTotal, $finalPaidAmount);
            $paidAt = ($status === 'paid' && $invoice->status !== 'paid') ? now() : $invoice->paid_at;
            if ($status !== 'paid') {
                $paidAt = null;
            }

            $invoice->update([
                'invoice_number' => $validatedData['invoice_number'] ?? $invoice->invoice_number,
                'invoice_date' => $validatedData['invoice_date'] ?? $invoice->invoice_date,
                'supplier_id' => $validatedData['supplier_id'],
                'subtotal' => $validatedData['subtotal'] ?? $calculatedSubtotal,
                'discount' => $validatedData['discount'] ?? $invoice->discount,
                'total' => $calculatedTotal,
                'paid_amount' => $finalPaidAmount,
                'status' => $status,
                'paid_at' => $paidAt,
                'notes' => $validatedData['notes'] ?? $invoice->notes,
                'user_id' => Auth::id(),
            ]);

            // ** تحديث رصيد المورد **
            $newUnpaidAmount = $invoice->total - $invoice->paid_amount;
            $oldUnpaidAmount = $oldInvoiceTotal - $oldPaidAmount;
            $difference = $newUnpaidAmount - $oldUnpaidAmount;

            if ($invoice->supplier_id !== $oldSupplierId) {
                $oldSupplier = Supplier::find($oldSupplierId);
                $oldSupplier->decrement('account_balance', $oldUnpaidAmount);
                $newSupplier = Supplier::find($invoice->supplier_id);
                $newSupplier->increment('account_balance', $newUnpaidAmount);
            } else {
                $supplier = $invoice->supplier;
                $supplier->increment('account_balance', $difference);
            }

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

                Batch::where('drug_id', $purchaseItem->drug_id)
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

            $supplier = $invoice->supplier;
            if ($supplier) {
                $supplier->decrement('account_balance', $invoice->total);
            }
            foreach ($invoice->purchaseItems as $purchaseItem) {
                $purchaseItem->batches()->delete();
                $purchaseItem->delete();
            }
            $invoice->payments()->delete();
            $invoice->delete();

            DB::commit();

            return $this->success(null, 'تم حذف الفاتورة وكل بياناتها المرتبطة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في حذف الفاتورة: ' . $e->getMessage(), 500);
        }
    }

    private function determineInvoiceStatus(float $total, float $paidAmount): string
    {
        if (abs($paidAmount - $total) < 0.001) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partially paid';
        } else {
            return 'unpaid';
        }
    }

}
