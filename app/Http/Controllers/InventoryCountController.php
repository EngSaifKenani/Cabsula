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
    /**
     * دالة لعرض جميع عمليات الجرد مع فلترة متقدمة
     * GET /inventory-counts
     */
    public function index(Request $request)
    {
        // نبدأ بالاستعلام الأساسي مع تحميل العلاقات الرئيسية لتجنب مشكلة N+1
        $query = InventoryCount::with(['admin']);

        // --- الفلترة الأساسية ---

        // 1. فلترة حسب الموظف (الأدمن) الذي قام بالجرد
        $query->when($request->filled('admin_id'), function ($q) use ($request) {
            $q->where('admin_id', $request->admin_id);
        });

        // 2. فلترة حسب نطاق زمني
        $query->when($request->filled('start_date'), function ($q) use ($request) {
            $q->whereDate('count_date', '>=', $request->start_date);
        });

        $query->when($request->filled('end_date'), function ($q) use ($request) {
            $q->whereDate('count_date', '<=', $request->end_date);
        });


        // --- الفلترة المتقدمة (Advanced Filtering) ---

        // 3. فلترة لإظهار عمليات الجرد التي تحتوي على دواء معين
        $query->when($request->filled('drug_id'), function ($q) use ($request) {
            $q->whereHas('details', function ($detailsQuery) use ($request) {
                $detailsQuery->where('drug_id', $request->drug_id);
            });
        });

        // 5. فلترة حسب حالة الفروقات (الأكثر أهمية في الجرد)
        // القيم الممكنة: any_discrepancy | shortage | surplus | matched
//        $query->when($request->filled('discrepancy_status'), function ($q) use ($request) {
//            $status = $request->discrepancy_status;
//
//            $q->whereHas('details', function ($detailsQuery) use ($status) {
//                if ($status === 'any_discrepancy') {
//                    // أي جرد يحتوي على فرق (زيادة أو نقصان)
//                    $detailsQuery->whereColumn('counted_quantity', '!=', 'system_quantity');
//                } elseif ($status === 'shortage') {
//                    // جرد يحتوي على نقص فقط
//                    $detailsQuery->whereColumn('counted_quantity', '<', 'system_quantity');
//                } elseif ($status === 'surplus') {
//                    // جرد يحتوي على زيادة فقط
//                    $detailsQuery->whereColumn('counted_quantity', '>', 'system_quantity');
//                } elseif ($status === 'matched') {
//                    // جرد كانت كل كمياته مطابقة
//                    $detailsQuery->whereColumn('counted_quantity', '=', 'system_quantity');
//                }
//            });
//        });

        // 6. فلترة بكلمة بحث عامة (اسم الدواء، سبب الفرق، الملاحظات)
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


        // الترتيب والتقسيم إلى صفحات
        $inventoryCounts = $query->latest('count_date')->paginate(20)->withQueryString();

        // إرسال البيانات إلى ملف الـ view أو كاستجابة API
        // return view('inventory.index', compact('inventoryCounts'));
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

        // تحميل العلاقات لإرجاعها في الـ Resource
        $inventoryCount->load(['admin', 'details.drug', 'details.batch']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء سجل الجرد بنجاح',
            'data' => new InventoryCountResource($inventoryCount)
        ], 201); // 201 Created Status
    }

    /**
     * عرض تفاصيل عملية جرد واحدة.
     */
    public function show(InventoryCount $inventoryCount)
    {
        // تحميل كل العلاقات الضرورية للعرض
        $inventoryCount->load(['admin', 'details.drug', 'details.batch']);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => new InventoryCountResource($inventoryCount)
        ]);
    }

    /**
     * تحديث بيانات عملية جرد.
     */
    public function update(UpdateInventoryCountRequest $request, InventoryCount $inventoryCount)
    {
        $validated = $request->validated();

        // التحقق من صلاحية المستخدم لتعديل هذا السجل (اختياري)
        // $this->authorize('update', $inventoryCount);

        $updatedInventoryCount = DB::transaction(function () use ($inventoryCount, $validated) {
            // 1. تحديث البيانات الرئيسية
            $inventoryCount->update([
                'count_date' => $validated['count_date'],
                'notes' => $validated['notes'],
            ]);

            // 2. حذف التفاصيل القديمة وإضافة الجديدة (أبسط طريقة للتحديث)
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

    /**
     * حذف عملية جرد.
     */
    public function destroy(InventoryCount $inventoryCount)
    {
        // التحقق من صلاحية المستخدم لحذف هذا السجل (اختياري)
        // $this->authorize('delete', $inventoryCount);

        // سيتم حذف التفاصيل تلقائياً بسبب onDelete('cascade') في الـ Migration
        $inventoryCount->delete();

        // 204 No Content هو الرد الأنسب لعمليات الحذف الناجحة في الـ API
        return response()->json(null, 204);
    }}
