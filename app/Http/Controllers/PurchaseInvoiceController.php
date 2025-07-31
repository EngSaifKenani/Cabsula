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
    public function index()
    {
        // Eager load relationships for better performance
        $invoices = PurchaseInvoice::with('supplier', 'user')
            ->latest()
            ->paginate(15);

        return $this->success($invoices, 'تم جلب الفواتير بنجاح');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                // 'invoice_number' has been removed from validation
                'invoice_number' => 'nullable|string|unique:purchase_invoices,invoice_number',
                'invoice_date' => 'required|date',
                'supplier_id' => 'required|exists:suppliers,id',
                'subtotal' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.drug_id' => 'required|exists:drugs,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_cost' => 'required|numeric|min:0',
                'items.*.total' => 'required|numeric|min:0',
                'items.*.batches' => 'required|array|min:1',
                'items.*.batches.*.batch_number' => 'nullable|string', // <-- تم التعديل هنا
                'items.*.batches.*.quantity' => 'required|integer|min:1',
                'items.*.batches.*.expiry_date' => 'required|date|after:today',
                'items.*.batches.*.selling_price' => 'required|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
            // Return validation errors
            return $this->error($e->errors(), 422);
        }

        // Use a database transaction to ensure data integrity
        DB::beginTransaction();
        try {
            // Generate a unique invoice number automatically
            $invoiceNumber = $validatedData['invoice_number']  ??'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

            // Create the main purchase invoice record
            $invoice = PurchaseInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $validatedData['invoice_date'],
                'supplier_id' => $validatedData['supplier_id'],
                'subtotal' => $validatedData['subtotal'],
                'discount' => $validatedData['discount'] ?? 0,
                'total' => $validatedData['total'],
                'status' => 'completed',
                'notes' => $validatedData['notes'],
                'user_id' => Auth::id(),
            ]);

            // Loop through each item in the invoice
            foreach ($validatedData['items'] as $itemData) {
                $purchaseItem = $invoice->purchaseItems()->create([
                    'drug_id' => $itemData['drug_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total' => $itemData['total'],
                ]);

                // Loop through each batch for the current item
                foreach ($itemData['batches'] as $batchData) {
                    // Generate a batch number automatically if not provided
                    $batchNumber = $batchData['batch_number'] ?? 'BCH-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));

                    $purchaseItem->batches()->create([
                        'drug_id' => $purchaseItem->drug_id,
                        'batch_number' => $batchNumber,
                        'quantity' => $batchData['quantity'],
                        'stock' => $batchData['quantity'], // Initially, stock equals quantity
                        'expiry_date' => $batchData['expiry_date'],
                        'selling_price' => $batchData['selling_price'],
                        'status' => 'pending',
                    ]);
                }
            }

            // If all operations succeed, commit the transaction
            DB::commit();

            // Load all related data to return the complete object
            $invoice->load('supplier', 'user', 'purchaseItems.drug', 'purchaseItems.batches');

            return $this->success($invoice, 'تم إنشاء الفاتورة بنجاح', 201);

        } catch (\Exception $e) {
            // If any error occurs, roll back the transaction
            DB::rollBack();
            return $this->error('فشل في إنشاء الفاتورة: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Find the invoice and load all its nested relationships
        $invoice = PurchaseInvoice::with('supplier', 'user', 'purchaseItems.drug', 'purchaseItems.batches')->find($id);

        if (!$invoice) {
            return $this->error('الفاتورة غير موجودة', 404);
        }

        return $this->success($invoice, 'تم جلب الفاتورة بنجاح');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $invoice = PurchaseInvoice::with('purchaseItems.batches')->find($id);
        if (!$invoice) {
            return $this->error('الفاتورة غير موجودة', 404);
        }

        try {
            $validatedData = $request->validate([
                'status' => 'required|in:draft,completed,cancelled',
                'notes' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Update the invoice itself
            $invoice->update($validatedData);

            // If the invoice is cancelled, delete all associated 'pending' batches
            if ($validatedData['status'] === 'cancelled') {
                foreach ($invoice->purchaseItems as $item) {
                    // Delete only batches that have not been received yet
                    $item->batches()->where('status', 'pending')->delete();
                }
            }

            DB::commit();
            return $this->success($invoice, 'تم تحديث الفاتورة بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في تحديث الفاتورة: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $invoice = PurchaseInvoice::find($id);
        if (!$invoice) {
            return $this->error('الفاتورة غير موجودة', 404);
        }

        // Note: You might need to add logic here to reverse stock changes
        $invoice->delete();

        return $this->success(null, 'تم حذف الفاتورة بنجاح');
    }
}
