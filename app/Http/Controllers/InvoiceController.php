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
            $user = auth()->user();

            // تحميل الفواتير مع عناصرها، الأدوية، الدفعات، والمستخدم
            // 'items.drug' و 'items.batch' ضروريان لإخفاء البيانات الحساسة لاحقاً
            $query = Invoice::with(['items.drug', 'items.batch', 'user']);

            // 1. فلترة بناءً على دور المستخدم (صيدلي يرى فواتيره فقط)
            if ($user->role === 'pharmacist') {
                $query->where('user_id', $user->id);
            } elseif ($request->filled('user_id')) {
                // المشرف يمكنه الفلترة حسب المستخدم
                $query->where('user_id', $request->user_id);
            }

            // 2. فلترة بناءً على رقم الفاتورة (invoice_number)
            if ($request->filled('invoice_number')) {
                $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
            }

            // 3. فلترة بناءً على السعر الإجمالي (total_price)
            if ($request->filled('min_total_price')) {
                $query->where('total_price', '>=', $request->min_total_price);
            }

            if ($request->filled('max_total_price')) {
                $query->where('total_price', '<=', $request->max_total_price);
            }

            // 4. فلترة التاريخ الموحدة
            $onDate = $request->input('on_date');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            if ($onDate) {
                // فلترة لتاريخ محدد (يوم واحد)
                $query->whereDate('created_at', $onDate);
            } elseif ($fromDate && $toDate) {
                // فلترة لنطاق تاريخ (من تاريخ إلى تاريخ)
                // التأكد من أن to_date يشمل اليوم بأكمله
                $endDate = Carbon::parse($toDate)->endOfDay();
                $query->whereBetween('created_at', [$fromDate, $endDate]);
            } elseif ($fromDate) {
                // فلترة من تاريخ معين فصاعداً
                $query->whereDate('created_at', '>=', $fromDate);
            } elseif ($toDate) {
                // فلترة حتى تاريخ معين (بما في ذلك اليوم بأكمله)
                $endDate = Carbon::parse($toDate)->endOfDay();
                $query->whereDate('created_at', '<=', $endDate);
            }

            // تنفيذ الاستعلام للحصول على الفواتير المفلترة (قبل التقسيم لجمع الإجماليات)
            // نستخدم `get()` هنا لجمع كافة الفواتير المفلترة لحساب الإجماليات
            // ثم نستخدم `paginate()` على المجموعة لضمان تقسيمها للصفحات
            $allFilteredInvoices = $query->orderBy('created_at', 'desc')->get();

            // حساب الإجماليات الصافية
            $netTotalCost = 0;
            $netTotalPrice = 0;
            $netTotalProfit = 0;

            // اجمع الإجماليات من جميع الفواتير المفلترة
            foreach ($allFilteredInvoices as $invoice) {
                $netTotalCost += $invoice->total_cost;
                $netTotalPrice += $invoice->total_price;
                $netTotalProfit += $invoice->total_profit;
            }

            // تطبيق التقسيم على المجموعة المفلترة بعد حساب الإجماليات
            // هذا يضمن أن الإجماليات تمثل جميع الفواتير التي تطابق الفلترة،
            // بينما يتم إرجاع الصفحات المطلوبة فقط
            $perPage = 50;
            $page = $request->input('page', 1);
            $offset = ($page * $perPage) - $perPage;
            $invoices = $allFilteredInvoices->slice($offset, $perPage)->values(); // استخدام values() لإعادة ترقيم المفاتيح
            $paginatedInvoices = new \Illuminate\Pagination\LengthAwarePaginator(
                $invoices,
                $allFilteredInvoices->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );


            // إخفاء المعلومات الحساسة إذا لم يكن المستخدم مشرفًا
            if ($user->role !== 'admin') {
                // إخفاء الإجماليات الحساسة من الاستجابة النهائية
                $netTotalCost = null;
                $netTotalProfit = null;

                foreach ($paginatedInvoices as $invoice) { // استخدم $paginatedInvoices بدلاً من $invoices
                    // إخفاء تكلفة الفاتورة الإجمالية والربح الإجمالي من الفاتورة نفسها
                    unset($invoice->total_cost, $invoice->total_profit);

                    // التأكد من تحميل items قبل المرور عليها
                    if ($invoice->relationLoaded('items')) {
                        foreach ($invoice->items as $item) {
                            // إخفاء حقول التكلفة والربح من كل عنصر فاتورة
                            unset($item->cost, $item->profit_amount);

                            // إخفاء تكلفة الدفعة إذا كانت محملة
                            if ($item->relationLoaded('batch')) {
                                unset($item->batch->unit_cost);
                            }
                        }
                    }
                }
            }

            // إرجاع الفواتير المصفحة والإجماليات
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
            'items.*.batch_id' => 'required|exists:batches,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $items = $request->items;

        foreach ($items as $item) {
            $drug = Drug::find($item['drug_id']);
            $batch = Batch::find($item['batch_id']);

            if (!$drug || !$batch) {
                return response()->json(['error' => "الدواء أو الدفعة غير موجودة"], 404);
            }

            if ($batch->drug_id !== $drug->id) {
                return response()->json(['error' => "الدفعة لا تتبع الدواء المحدد: {$drug->name}"], 400);
            }

            if ($batch->stock < $item['quantity']) {
                return response()->json([
                    'error' => "الكمية غير كافية في الدفعة للدواء: {$drug->name}",
                    'available_stock' => $batch->stock
                ], 400);
            }
        }

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
            $batch = \App\Models\Batch::findOrFail($item['batch_id']);
            $quantity = $item['quantity'];

            $cost = $batch->unit_cost * $quantity;
            $price = $batch->unit_price * $quantity;
            $profit = ( $batch->unit_price - $batch->unit_cost) * $quantity;

            $invoice->items()->create([
                'drug_id' => $item['drug_id'],
                'batch_id' => $batch->id,
                'quantity' => $item['quantity'],
                'cost' => $batch->unit_cost * $item['quantity'], // السعر من الدفعة
                'price' => $batch->unit_price * $item['quantity'], // السعر من الدفعة
                'profit_amount' => ($batch->unit_price - $batch->unit_cost) * $item['quantity'],
            ]);

            // خصم من المخزون وزيادة في الكميات المباعة
            $batch->decrement('stock', $quantity);
            //observer بزيد لحالو
            $totalCost += $cost;
            $totalPrice += $price;
            $totalProfit += $profit;
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


    public function destroy(Request $request, $id = null) // جعل $id اختياريًا للسماح بالمرور عبر الـ Request body
    {
        $user = auth()->user();

        // 1. التحقق من الصلاحيات: فقط الأدمن يمكنه الحذف
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'غير مصرح لك بحذف هذه الفواتير.'], 403);
        }

        // تحديد معرّفات الفواتير المراد حذفها
        // إذا كان $id ممرراً في المسار، استخدمه.
        // وإلا، ابحث عن 'ids' في جسم الطلب (Request body).
        $invoiceIds = $id ? [$id] : $request->input('ids');

        // التحقق مما إذا تم تمرير معرّفات
        if (empty($invoiceIds)) {
            return response()->json(['error' => 'الرجاء تحديد معرّف فاتورة واحد أو عدة معرّفات لحذفها.'], 400);
        }

        // التأكد من أن جميع المعرّفات هي أرقام صحيحة
        if (!is_array($invoiceIds)) {
            $invoiceIds = [$invoiceIds]; // اجعلها مصفوفة إذا كانت قيمة واحدة
        }
        foreach ($invoiceIds as $invoiceId) {
            if (!is_numeric($invoiceId)) {
                return response()->json(['error' => 'معرّفات الفواتير يجب أن تكون أرقامًا صحيحة.'], 400);
            }
        }

        // 2. استخدام Transaction لضمان سلامة البيانات لجميع عمليات الحذف
        DB::beginTransaction();
        try {
            $deletedCount = 0;
            $failedIds = [];

            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::with('items.batch')->find($invoiceId); // استخدم find بدلاً من findOrFail للتعامل مع الفواتير غير الموجودة
                if (!$invoice) {
                    $failedIds[] = $invoiceId; // سجل المعرّف غير الموجود
                    continue; // انتقل إلى الفاتورة التالية
                }

                // 3. إعادة الكميات إلى الدفعات وإنقاص الكميات المباعة
                foreach ($invoice->items as $item) {
                    $batch = $item->batch;

                    if ($batch) {
                        $batch->increment('stock', $item->quantity);
                    }
                    // Observer الخاص بـ InvoiceItem سيتعامل مع Drug::total_sold
                }

                // حذف الفاتورة
                $invoice->delete();
                $deletedCount++;
            }

            // 5. تأكيد التغييرات في قاعدة البيانات
            DB::commit();

            $message = "تم حذف {$deletedCount} فاتورة بنجاح وإعادة الكميات إلى المخزون.";
            if (!empty($failedIds)) {
                $message .= " لم يتم العثور على الفواتير التالية: " . implode(', ', $failedIds) . ".";
                return response()->json(['message' => $message, 'failed_ids' => $failedIds], 200); // 200 OK حتى لو فشل البعض
            }

            return response()->json(['message' => $message]);

        } catch (\Exception $e) {
            // 6. التراجع عن التغييرات في حالة حدوث خطأ
            DB::rollBack();

            return response()->json(['error' => 'حدث خطأ أثناء حذف الفواتير وإعادة الكميات.'], 500);
        }
    }
}
