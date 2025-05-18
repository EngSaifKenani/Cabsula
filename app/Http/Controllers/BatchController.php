<?php

namespace App\Http\Controllers;

use App\Http\Resources\BatchResource;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Batch::with('drug');

        if ($request->filled('drug_id')) {
            $query->where('drug_id', $request->drug_id);
        }

        if ($request->filled('expiry_before')) {
            $query->whereDate('expiry_date', '<=', $request->expiry_before);
        }

        if ($request->filled('produced_after')) {
            $query->whereDate('production_date', '>=', $request->produced_after);
        }

        if ($request->filled('search')) {
            $query->whereHas('drug', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $batches = $query->latest()->get();

        return $this->success(
            BatchResource::collection($batches),
            'Batches retrieved successfully.'
        );
    }

    public function show($id)
    {
        $batch = Batch::with(['drug'])->find($id);

        if (!$batch) {
            return $this->error('Batch not found.', 404);
        }

        return $this->success(new BatchResource($batch), 'Batch retrieved successfully.');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'drug_id' => 'required|exists:drugs,id',
            'price' => ['nullable',
                'numeric',
                'min:0',
                Rule::when($request->filled('cost'), ['gt:cost'])
            ],
            'cost' => 'required|numeric|min:0',
            'batch_number'=>'nullable|string',
            'profitPercentage' => 'nullable|integer|min:0',
            'quantity' => 'required|integer',
            'production_date' => 'required|date',
            'expiry_date' => 'required|date|after:production_date',
        ]);
        $validated['stock']=$validated['quantity'];
        $cost = $validated['cost'] ;
        $profitPercentage = $request['profitPercentage'] ?? 20;

        if (!isset($request['price'])) {
            $validated['price'] = $cost * (1 + ($profitPercentage / 100));
        } else {
            $validated['price'] = $request['price'];
        }
        $batch = Batch::create($validated);
        $batch->batch_number=$validated['batch_number']??'BATCH-' . now()->format('Ymd') . '-' . Str::random(4);
        $batch->sold=0;
        $batch->status='active';

        $batch->save();
        return $this->success(new BatchResource($batch->load('drug')), 'Batch created successfully.', 201);
    }



    public function update(Request $request, $id)
    {
        $batch = Batch::find($id);

        if (!$batch) {
            return $this->error('Batch not found.', 404);
        }

        $validated = $request->validate([
            'drug_id' => 'sometimes|exists:drugs,id',
            'price' => ['sometimes',
                'numeric',
                'min:0',
                Rule::when($request->filled('cost'), ['gt:cost'])
            ],
            'cost' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'quantity' => 'sometimes|integer',
            'production_date' => 'sometimes|date',
            'expiry_date' => 'sometimes|date|after:production_date',
        ]);
        $batch->update($validated);

        return $this->success(new BatchResource($batch->load('drug')), 'Batch updated successfully.');
    }

    public function destroy($id)
    {
        $batch = Batch::find($id);

        if (!$batch) {
            return $this->error('Batch not found.', 404);
        }

        $batch->delete();
        return $this->success(null, 'Batch deleted successfully.');
    }
}
