<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Drug;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // التحقق من المدخلات
        $request->validate([
            'user_id'         => 'nullable|integer|exists:users,id',
            'invoice_number'  => 'nullable|string|max:255',
            'min_total_price' => 'nullable|numeric|min:0',
            'max_total_price' => 'nullable|numeric|min:0',
            'on_date'         => 'nullable|date_format:Y-m-d',
            'from_date'       => 'nullable|date_format:Y-m-d',
            'to_date'         => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'page'            => 'nullable|integer|min:1',
            'include_canceled'=> 'nullable|boolean',
            'canceled_only'   => 'nullable|boolean',
        ]);

        $user = auth()->user();

        // استعلام الفواتير الأساسي مع العلاقات
        $query = Invoice::with(['items.drug', 'items.batch', 'user']);

        // دعم الفواتير الملغاة عبر SoftDeletes
        if ($request->boolean('canceled_only')) {
            // جلب الفواتير الملغاة فقط
            $query = $query->onlyTrashed();
        } elseif ($request->boolean('include_canceled')) {
            // جلب كل الفواتير، نشطة وملغاة
            $query = $query->withTrashed();
        } else {
            // جلب الفواتير النشطة فقط
            $query = $query->where('status', 'active');
        }

        // فلترة حسب دور المستخدم
        if ($user->role === 'pharmacist') {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // فلترة حسب رقم الفاتورة
        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        // فلترة حسب السعر الإجمالي
        if ($request->filled('min_total_price')) {
            $query->where('total_price', '>=', $request->min_total_price);
        }
        if ($request->filled('max_total_price')) {
            $query->where('total_price', '<=', $request->max_total_price);
        }

        // فلترة حسب التواريخ
        $onDate = $request->input('on_date');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($onDate) {
            $query->whereDate('created_at', $onDate);
        } elseif ($fromDate && $toDate) {
            $query->whereBetween('created_at', [$fromDate, Carbon::parse($toDate)->endOfDay()]);
        } elseif ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        } elseif ($toDate) {
            $query->whereDate('created_at', '<=', Carbon::parse($toDate)->endOfDay());
        }

        // جلب كل الفواتير المفلترة لحساب الإجماليات
        $allFilteredInvoices = $query->orderBy('created_at', 'desc')->get();

        // حساب الإجماليات الصافية فقط للفواتير النشطة
        $netTotalCost = $netTotalPrice = $netTotalProfit = 0;
        foreach ($allFilteredInvoices as $invoice) {
            if (!$invoice->trashed() && $invoice->status === 'active') {
                $netTotalCost += $invoice->total_cost;
                $netTotalPrice += $invoice->total_price;
                $netTotalProfit += $invoice->total_profit;
            }
        }

        // تقسيم النتائج للصفحات
        $perPage = 50;
        $page = $request->input('page', 1);
        $offset = ($page * $perPage) - $perPage;
        $invoices = $allFilteredInvoices->slice($offset, $perPage)->values();
        $paginatedInvoices = new \Illuminate\Pagination\LengthAwarePaginator(
            $invoices,
            $allFilteredInvoices->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // إخفاء المعلومات الحساسة إذا لم يكن المستخدم أدمن
        if ($user->role !== 'admin') {
            $netTotalCost = $netTotalProfit = null;

            foreach ($paginatedInvoices as $invoice) {
                unset($invoice->total_cost, $invoice->total_profit);

                if ($invoice->relationLoaded('items')) {
                    foreach ($invoice->items as $item) {
                        unset($item->cost, $item->profit_amount);
                        if ($item->relationLoaded('batch')) {
                            unset($item->batch->unit_cost);
                        }
                    }
                }
            }
        }

        return response()->json([
            'invoices' => $paginatedInvoices,
            'net_totals' => [
                'net_total_cost' => $netTotalCost,
                'net_total_price' => $netTotalPrice,
                'net_total_profit' => $netTotalProfit,
            ],
            'message' => 'تم جلب الفواتير بنجاح.',
        ]);
    }






    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $items = $request->items;

        $lastId = Invoice::max('id') ?? 0;
        $invoiceNumber = 'INV-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'user_id' => auth()->id(),
            'total_cost' => 0,
            'total_price' => 0,
            'total_profit' => 0,
        ]);

        $totalCost = $totalPrice = $totalProfit = 0;

        foreach ($items as $item) {
            $drug = Drug::findOrFail($item['drug_id']);
            $quantityNeeded = $item['quantity'];

            // جلب أقدم الدفعات التي تتبع الدواء ولديها مخزون
            $batches = Batch::where('drug_id', $drug->id)
                ->where('stock', '>', 0)
                ->orderBy('created_at', 'asc') // أو orderBy('expiry_date', 'asc') إذا تريد حسب تاريخ الانتهاء
                ->get();

            if ($batches->isEmpty()) {
                return response()->json([
                    'error' => "لا يوجد أي دفعات متاحة لهذا الدواء: {$drug->name}"
                ], 400);
            }

            foreach ($batches as $batch) {
                if ($quantityNeeded <= 0) break;

                $deductQuantity = min($batch->stock, $quantityNeeded);

                $cost = $batch->unit_cost * $deductQuantity;
                $price = $batch->unit_price * $deductQuantity;
                $profit = ($batch->unit_price - $batch->unit_cost) * $deductQuantity;

                // إضافة العنصر إلى الفاتورة
                $invoice->items()->create([
                    'drug_id' => $drug->id,
                    'batch_id' => $batch->id,
                    'quantity' => $deductQuantity,
                    'cost' => $cost,
                    'price' => $price,
                    'profit_amount' => $profit,
                ]);

                // خصم من المخزون
                $batch->decrement('stock', $deductQuantity);

                // جمع الإجماليات
                $totalCost += $cost;
                $totalPrice += $price;
                $totalProfit += $profit;

                $quantityNeeded -= $deductQuantity;
            }

            // إذا لم نتمكن من تغطية الكمية المطلوبة
            if ($quantityNeeded > 0) {
                return response()->json([
                    'error' => "الكمية المطلوبة أكبر من المخزون المتاح للدواء: {$drug->name}"
                ], 400);
            }
        }

        $invoice->update([
            'total_cost' => $totalCost,
            'total_price' => $totalPrice,
            'total_profit' => $totalProfit,
        ]);

        $invoice->load('items.drug', 'items.batch');

        if (auth()->user()->role !== 'admin') {
            unset($invoice->total_cost, $invoice->total_profit);
            foreach ($invoice->items as $item) {
                unset($item->cost, $item->profit_amount);
                if ($item->relationLoaded('batch')) {
                    unset($item->batch->unit_cost);
                }
            }
        }

        return response()->json([
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'invoice' => $invoice
        ]);
    }


    /**
     * عرض فاتورة واحدة.
     */
        public function show($id)
        {
            // تحميل الفاتورة مع العناصر المرتبطة (الأدوية والدفعات والمستخدم)
            $invoice = Invoice::with(['items.drug', 'items.batch', 'user'])->findOrFail($id);
            $user = auth()->user();

            // الصيدلي لا يمكنه مشاهدة فواتير غيره
            if ($user->role === 'pharmacist' && $invoice->user_id !== $user->id) {
                return response()->json(['error' => 'غير مصرح لك بمشاهدة هذه الفاتورة'], 403);
            }

            // إذا لم يكن المستخدم مشرفًا، قم بإخفاء الحقول الحساسة
            if ($user->role !== 'admin') {
                // إخفاء تكلفة الفاتورة الإجمالية والربح الإجمالي
                unset($invoice->total_cost, $invoice->total_profit);

                // إخفاء حقول التكلفة والربح من كل عنصر فاتورة
                foreach ($invoice->items as $item) {
                    unset($item->cost, $item->profit_amount);

                    // إذا كانت الدفعة محملة، قم بإخفاء حقل التكلفة منها
                    if ($item->relationLoaded('batch')) {
                        unset($item->batch->unit_cost);
                    }
                }
            }

            return response()->json($invoice);
        }

    public function update(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();
        $invoice = Invoice::with('items.batch')->findOrFail($id);

        // السماح فقط للأدمن أو صاحب الفاتورة
        if ($user->role !== 'admin' && $invoice->user_id !== $user->id) {
            return response()->json(['error' => 'غير مصرح لك بتعديل هذه الفاتورة'], 403);
        }

        DB::beginTransaction();

        try {
            // 1. إعادة الكميات القديمة للمخزون
            foreach ($invoice->items as $item) {
                if ($item->batch) {
                    $item->batch->increment('stock', $item->quantity);
                }
            }

            // 2. حذف العناصر القديمة
            $invoice->items()->delete();

            $items = $request->items;
            $totalCost = $totalPrice = $totalProfit = 0;

            // 3. إضافة العناصر الجديدة بنفس منطق store()
            foreach ($items as $item) {
                $drug = Drug::findOrFail($item['drug_id']);
                $quantityNeeded = $item['quantity'];

                $batches = Batch::where('drug_id', $drug->id)
                    ->where('stock', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($batches->isEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "لا يوجد أي دفعات متاحة لهذا الدواء: {$drug->name}"
                    ], 400);
                }

                foreach ($batches as $batch) {
                    if ($quantityNeeded <= 0) break;

                    $deductQuantity = min($batch->stock, $quantityNeeded);

                    $cost = $batch->unit_cost * $deductQuantity;
                    $price = $batch->unit_price * $deductQuantity;
                    $profit = ($batch->unit_price - $batch->unit_cost) * $deductQuantity;

                    $invoice->items()->create([
                        'drug_id' => $drug->id,
                        'batch_id' => $batch->id,
                        'quantity' => $deductQuantity,
                        'cost' => $cost,
                        'price' => $price,
                        'profit_amount' => $profit,
                    ]);

                    $batch->decrement('stock', $deductQuantity);

                    $totalCost += $cost;
                    $totalPrice += $price;
                    $totalProfit += $profit;

                    $quantityNeeded -= $deductQuantity;
                }

                if ($quantityNeeded > 0) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "الكمية المطلوبة أكبر من المخزون المتاح للدواء: {$drug->name}"
                    ], 400);
                }
            }

            // 4. تحديث الإجماليات
            $invoice->update([
                'total_cost' => $totalCost,
                'total_price' => $totalPrice,
                'total_profit' => $totalProfit,
            ]);

            DB::commit();

            $invoice->load('items.drug', 'items.batch');

            if ($user->role !== 'admin') {
                unset($invoice->total_cost, $invoice->total_profit);
                foreach ($invoice->items as $item) {
                    unset($item->cost, $item->profit_amount);
                    if ($item->relationLoaded('batch')) {
                        unset($item->batch->unit_cost);
                    }
                }
            }

            return response()->json([
                'message' => 'تم تعديل الفاتورة بنجاح',
                'invoice' => $invoice
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'حدث خطأ أثناء تعديل الفاتورة', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        $user = auth()->user();
        $invoiceIds = $id ? [$id] : $request->input('ids');

        if (empty($invoiceIds)) {
            return response()->json(['error' => 'الرجاء تحديد معرّفات الفواتير للإلغاء.'], 400);
        }

        if (!is_array($invoiceIds)) {
            $invoiceIds = [$invoiceIds];
        }

        foreach ($invoiceIds as $invoiceId) {
            if (!is_numeric($invoiceId)) {
                return response()->json(['error' => 'معرّفات الفواتير يجب أن تكون أرقام صحيحة.'], 400);
            }
        }

        DB::beginTransaction();

        try {
            $canceledCount = 0;
            $failedIds = [];

            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::with('items.batch')->find($invoiceId);
                if (!$invoice) {
                    $failedIds[] = $invoiceId;
                    continue;
                }

                // السماح فقط للأدمن أو صاحب الفاتورة
                if ($user->role !== 'admin' && $invoice->user_id !== $user->id) {
                    $failedIds[] = $invoiceId;
                    continue;
                }

                // إعادة الكمية للمخزون
                foreach ($invoice->items as $item) {
                    if ($item->batch) {
                        $item->batch->increment('stock', $item->quantity);
                    }
                }

                // تحديث الحالة إلى "ملغاة"
                $invoice->update(['status' => 'canceled']);
                $invoice->delete(); // soft delete

                $canceledCount++;
            }

            DB::commit();

            $message = "تم إلغاء {$canceledCount} فاتورة بنجاح.";
            if (!empty($failedIds)) {
                $message .= " لم يتم إلغاء الفواتير التالية: " . implode(', ', $failedIds) . ".";
                return response()->json(['message' => $message, 'failed_ids' => $failedIds], 200);
            }

            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'حدث خطأ أثناء إلغاء الفواتير.'], 500);
        }
    }

}
