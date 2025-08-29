<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierReturnController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource with filtering options.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = SupplierReturn::with([
            'batch.drug',
            'supplier',
            'user',
            'purchaseInvoice'
        ]);


        $query->when($request->filled('supplier_id'), function ($q) use ($request) {
            $q->where('supplier_id', $request->supplier_id);
        });

        $query->when($request->filled('drug_id'), function ($q) use ($request) {
            $q->whereHas('batch', function ($batchQuery) use ($request) {
                $batchQuery->where('drug_id', $request->drug_id);
            });
        });

        $query->when($request->filled('user_id'), function ($q) use ($request) {
            $q->where('user_id', $request->user_id);
        });

        $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        });

        // 5. فلترة بكلمة بحث عامة (ملاحظات، اسم المورد، اسم الدواء)
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            $q->where(function ($innerQuery) use ($searchTerm) {
                $innerQuery->where('notes', 'like', $searchTerm)
                    ->orWhereHas('supplier', function ($supplierQuery) use ($searchTerm) {
                        $supplierQuery->where('name', 'like', $searchTerm);
                    })
                    ->orWhereHas('batch.drug', function ($drugQuery) use ($searchTerm) {
                        $drugQuery->where('name', 'like', $searchTerm);
                    });
            });
        });

        $returns = $query->latest()->paginate(15);

        return $this->success($returns, 'تم جلب سجلات المرتجعات بنجاح');
    }

    public function show(SupplierReturn $supplierReturn)
    {
        $supplierReturn->load('batch.drug', 'supplier', 'user', 'purchaseInvoice');

        return $this->success($supplierReturn, 'تم جلب سجل المرتجع بنجاح.');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'returned_quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $batch = Batch::findOrFail($validatedData['batch_id']);

            if ($validatedData['returned_quantity'] > $batch->stock) {
                DB::rollBack();
                return $this->error('الكمية المرتجعة أكبر من المخزون المتاح.', 422);
            }

            $creditAmount = $validatedData['returned_quantity'] * $batch->unit_cost;

            $supplierReturn = SupplierReturn::create([
                'batch_id' => $batch->id,
                'supplier_id' => $batch->purchaseItem->purchaseInvoice->supplier_id,
                'purchase_invoice_id' => $batch->purchaseItem->purchaseInvoice->id,
                'user_id' => Auth::id(),
                'returned_quantity' => $validatedData['returned_quantity'],
                'credit_amount' => $creditAmount,
                'notes' => $validatedData['notes'],
            ]);

            $batch->decrement('stock', $validatedData['returned_quantity']);
            if ($batch->stock===0){
             $batch->update(['status'=>'sold_out']);
                }
            $supplier = Supplier::find($supplierReturn->supplier_id);
            if ($supplier) {
                $supplier->decrement('account_balance', $creditAmount);
            }

            DB::commit();

            return $this->success($supplierReturn->load('batch.drug', 'supplier'), 'تم تسجيل عملية الإرجاع بنجاح.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في تسجيل عملية الإرجاع: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified return record.
     *
     * @param Request $request
     * @param SupplierReturn $supplierReturn
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, SupplierReturn $supplierReturn)
    {
        $validatedData = $request->validate([
            'notes' => 'required|string',
        ]);

        $supplierReturn->update([
            'notes' => $validatedData['notes'],
        ]);

        return $this->success($supplierReturn, 'تم تحديث الملاحظات بنجاح.');
    }

    /**
     * Remove the specified return record from storage.
     *
     * @param SupplierReturn $supplierReturn
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SupplierReturn $supplierReturn)
    {
        DB::beginTransaction();
        try {
            $batch = $supplierReturn->batch;
            $supplier = $supplierReturn->supplier;

            if ($batch) {
                $batch->increment('stock', $supplierReturn->returned_quantity);
            }
            if ($batch->stock>0){
                $batch->update(['status'=>'available']);
            }

            if ($supplier) {
                $supplier->increment('account_balance', $supplierReturn->credit_amount);
            }

            $supplierReturn->delete();

            DB::commit();

            return $this->success(null, 'تم حذف سجل المرتجع وإعادة الكمية إلى المخزون بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في حذف سجل المرتجع: ' . $e->getMessage(), 500);
        }
    }

    public function getReturnsSummary(Request $request)
    {
        $query = SupplierReturn::query();
        $query->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id));
        $query->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id));
        $query->when($request->filled('drug_id'), fn($q) => $q->whereHas('batch', fn($bq) => $bq->where('drug_id', $request->drug_id)));
        $query->when($request->filled('start_date') && $request->filled('end_date'), fn($q) => $q->whereBetween('created_at', [Carbon::parse($request->input('start_date'))->startOfDay(), Carbon::parse($request->input('end_date'))->endOfDay()]));
        $query->when($request->filled('search'), fn($q) => $q->where(fn($iq) => $iq->where('notes', 'like', '%' . $request->search . '%')));

        $query->with(['supplier', 'batch.drug']);
        $returns = $query->get();

        $summary = [
            'total_returned_value' => number_format($returns->sum('credit_amount'), 2),
            'total_returned_quantity' => $returns->sum('returned_quantity'),
        ];

        $response = [
            'summary' => $summary,
        ];

        if (!$request->filled('supplier_id')) {
            $summaryBySupplier = $returns->groupBy('supplier_id')->map(function ($returnsBySupplier) {
                $supplier = $returnsBySupplier->first()->supplier;
                return [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'total_returned_value' => number_format($returnsBySupplier->sum('credit_amount'), 2),
                    'total_returned_quantity' => $returnsBySupplier->sum('returned_quantity'),
                ];
            });
            $response['summary_by_supplier'] = $summaryBySupplier->values();
        }

        if (!$request->filled('drug_id')) {
            $summaryByDrug = $returns->groupBy('batch.drug.id')->map(function ($returnsByDrug) {
                $drug = $returnsByDrug->first()->batch->drug;
                return [
                    'drug_id' => $drug->id,
                    'drug_name' => $drug->name,
                    'total_returned_value' => number_format($returnsByDrug->sum('credit_amount'), 2),
                    'total_returned_quantity' => $returnsByDrug->sum('returned_quantity'),
                ];
            });
            $response['summary_by_drug'] = $summaryByDrug->values();
        }

        return $this->success($response, 'تم حساب ملخص المرتجعات بنجاح.');
    }
}
