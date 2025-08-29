<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryCountRequest;
use App\Http\Requests\UpdateInventoryCountRequest;
use App\Http\Resources\InventoryCountResource;
use App\Models\InventoryCount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryCountController extends Controller
{

    public function index(Request $request)
    {
        $query = InventoryCount::with(['admin']);

        $query->when($request->filled('admin_id'), function ($q) use ($request) {
            $q->where('admin_id', $request->admin_id);
        });

        $query->when($request->filled('start_date'), function ($q) use ($request) {
            $q->whereDate('count_date', '>=', $request->start_date);
        });

        $query->when($request->filled('end_date'), function ($q) use ($request) {
            $q->whereDate('count_date', '<=', $request->end_date);
        });


        $query->when($request->filled('drug_id'), function ($q) use ($request) {
            $q->whereHas('details', function ($detailsQuery) use ($request) {
                $detailsQuery->where('drug_id', $request->drug_id);
            });
        });
       $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            $q->where(function ($innerQuery) use ($searchTerm) {
                $innerQuery->where('notes', 'like', $searchTerm)
                    ->orWhereHas('details', function ($detailsQuery) use ($searchTerm) {
                        $detailsQuery->where('reason', 'like', $searchTerm)
                            ->orWhereHas('drug', function ($drugQuery) use ($searchTerm) {
                                $drugQuery->where('name', 'like', $searchTerm); // افترض أن اسم الدواء في حقل name
                            });
                    });
            });
        });


        $inventoryCounts = $query->latest('count_date')->paginate(20)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجلات الجرد بنجاح',
            'data' => $inventoryCounts
        ]);
    }

    public function store(StoreInventoryCountRequest $request)
    {
        $validated = $request->validated();

        $inventoryCount = DB::transaction(function () use ($validated) {
            $count = InventoryCount::create([
                'count_date' => $validated['count_date'],
                'admin_id' => auth()->id(),
                'notes' => $validated['notes'],
            ]);

            $count->details()->createMany($validated['details']);

            return $count;
        });

        $inventoryCount->load(['admin', 'details.drug', 'details.batch']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء سجل الجرد بنجاح',
            'data' => new InventoryCountResource($inventoryCount)
        ], 201);
    }

    public function show(InventoryCount $inventoryCount)
    {
        $inventoryCount->load(['admin', 'details.drug', 'details.batch']);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => new InventoryCountResource($inventoryCount)
        ]);
    }

    public function update(UpdateInventoryCountRequest $request, InventoryCount $inventoryCount)
    {
        $validated = $request->validated();

        $updatedInventoryCount = DB::transaction(function () use ($inventoryCount, $validated) {
            $inventoryCount->update([
                'count_date' => $validated['count_date'],
                'notes' => $validated['notes'],
            ]);

            $inventoryCount->details()->delete();
            $inventoryCount->details()->createMany($validated['details']);

            return $inventoryCount;
        });

        $updatedInventoryCount->load(['admin', 'details.drug', 'details.batch']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سجل الجرد بنجاح',
            'data' => new InventoryCountResource($updatedInventoryCount)
        ]);
    }

    public function destroy(InventoryCount $inventoryCount)
    {
        $inventoryCount->details()->delete();

        $inventoryCount->delete();

        return response()->json(null, 204);
    }
}
