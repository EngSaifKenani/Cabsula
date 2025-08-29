<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Disposal;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DisposalController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
    $query = Disposal::with([
    'batch.drug',
    'user',
    ]);

    $query->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id));
    $query->when($request->filled('drug_id'), fn($q) => $q->whereHas('batch', fn($bq) => $bq->where('drug_id', $request->drug_id)));
    $query->when($request->filled('start_date') && $request->filled('end_date'), fn($q) => $q->whereBetween('disposed_at', [Carbon::parse($request->input('start_date'))->startOfDay(), Carbon::parse($request->input('end_date'))->endOfDay()]));
    $query->when($request->filled('search'), fn($q) => $q->where(fn($iq) => $iq->where('reason', 'like', '%' . $request->search . '%')->orWhereHas('batch.drug', fn($dq) => $dq->where('name', 'like', '%' . $request->search . '%'))));

    $disposals = $query->latest('disposed_at')->paginate(15);

    return $this->success($disposals, 'تم جلب سجلات الإتلاف بنجاح.');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'disposed_quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $batch = Batch::findOrFail($validatedData['batch_id']);
            if ($validatedData['disposed_quantity'] > $batch->stock) {
                DB::rollBack();
                return $this->error('الكمية المتلفة أكبر من المخزون المتاح.', 422);
            }
            $lossAmount = $validatedData['disposed_quantity'] * $batch->unit_cost;
            $disposal = Disposal::create([
                'batch_id' => $batch->id,
                'user_id' => Auth::id(),
                'disposed_quantity' => $validatedData['disposed_quantity'],
                'loss_amount' => $lossAmount,
                'reason' => $validatedData['reason'],
                'disposed_at' => now(),
            ]);
            $batch->decrement('stock', $validatedData['disposed_quantity']);
            if ($batch->stock===0){
                $batch->update(['status'=>'sold_out']);
            }
            DB::commit();
            return $this->success($disposal->load('batch.drug', 'user'), 'تم تسجيل عملية الإتلاف بنجاح.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في تسجيل عملية الإتلاف: ' . $e->getMessage(), 500);
        }
    }

    public function show(Disposal $disposal)
    {
        $disposal->load('batch.drug', 'user');
        return $this->success($disposal, 'تم جلب سجل الإتلاف بنجاح.');
    }

    public function update(Request $request, Disposal $disposal)
    {
        $validatedData = $request->validate([
            'disposed_quantity' => 'sometimes|integer|min:1',
            'reason' => 'sometimes|string',
        ]);

        DB::beginTransaction();
        try {
            $batch = $disposal->batch;

            if ($request->has('disposed_quantity') && $validatedData['disposed_quantity'] !== $disposal->disposed_quantity) {
                $oldQuantity = $disposal->disposed_quantity;
                $newQuantity = $validatedData['disposed_quantity'];
                $quantityDifference = $newQuantity - $oldQuantity;

                if ($quantityDifference > $batch->stock) {
                    DB::rollBack();
                    return $this->error('الكمية الجديدة تتجاوز المخزون المتاح.', 422);
                }
                $batch->decrement('stock', $quantityDifference);
                if ($batch->stock===0){
                    $batch->update(['status'=>'sold_out']);
                }
                $disposal->loss_amount = $newQuantity * $batch->unit_cost;
            }

            $disposal->update($validatedData);

            DB::commit();
            return $this->success($disposal->load('batch.drug', 'user'), 'تم تحديث سجل الإتلاف بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في تحديث سجل الإتلاف: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Disposal $disposal)
    {
        DB::beginTransaction();
        try {
            $batch = $disposal->batch;
            if ($batch) {
                $batch->increment('stock', $disposal->disposed_quantity);
            }
            if ($batch->stock>0){
                $batch->update(['status'=>'available']);
            }
            $disposal->delete();
            DB::commit();
            return $this->success(null, 'تم حذف سجل الإتلاف وإعادة الكمية إلى المخزون بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في حذف سجل الإتلاف: ' . $e->getMessage(), 500);
        }
    }

    public function getDisposalSummary(Request $request)
    {
        $query = Disposal::query();

        $query->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id));
        $query->when($request->filled('drug_id'), fn($q) => $q->whereHas('batch', fn($bq) => $bq->where('drug_id', $request->drug_id)));
        $query->when($request->filled('start_date') && $request->filled('end_date'), fn($q) => $q->whereBetween('disposed_at', [Carbon::parse($request->input('start_date'))->startOfDay(), Carbon::parse($request->input('end_date'))->endOfDay()]));
        $query->when($request->filled('search'), fn($q) => $q->where(fn($iq) => $iq->where('reason', 'like', '%' . $request->search . '%')->orWhereHas('batch.drug', fn($dq) => $dq->where('name', 'like', '%' . $request->search . '%'))));

        if (!$request->filled('drug_id')) {
            $query->with('batch.drug');
        }

        $disposals = $query->get();

        if ($request->filled('drug_id')) {
            $totalLoss = $disposals->sum('loss_amount');
            $totalQuantity = $disposals->sum('disposed_quantity');
            return $this->success([
                'total_loss_amount' => number_format($totalLoss, 2),
                'total_disposed_quantity' => $totalQuantity,
            ], 'تم حساب ملخص الإتلاف بنجاح.');
        } else {
            $summaryByDrug = $disposals->groupBy('batch.drug.id')->map(function ($disposalsByDrug) {
                $drug = $disposalsByDrug->first()->batch->drug;
                return [
                    'drug_id' => $drug->id,
                    'drug_name' => $drug->name,
                    'total_disposed_quantity' => $disposalsByDrug->sum('disposed_quantity'),
                    'total_loss_amount' => $disposalsByDrug->sum('loss_amount'),
                ];
            });
            return $this->success([
                'summary_by_drug' => $summaryByDrug->values(),
            ], 'تم حساب ملخص الإتلاف مجمعًا بنجاح.');
        }
    }
}
