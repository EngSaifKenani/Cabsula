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

        // ترتيب النتائج
        $invoices = $query->orderBy('created_at', 'desc')->paginate(50);

        // إخفاء المعلومات الحساسة إذا لم يكن المستخدم مشرفًا
        if ($user->role !== 'admin') {
            foreach ($invoices as $invoice) {
                // إخفاء تكلفة الفاتورة الإجمالية والربح الإجمالي من الفاتورة نفسها
                unset($invoice->total_cost, $invoice->total_profit);

                // التأكد من تحميل items قبل المرور عليها
                if ($invoice->relationLoaded('items')) {
                    foreach ($invoice->items as $item) {
                        // إخفاء حقول التكلفة والربح من كل عنصر فاتورة
                        unset($item->cost, $item->profit_amount);

                        // إخفاء تكلفة الدفعة إذا كانت محملة
                        if ($item->relationLoaded('batch')) {
                            unset($item->batch->cost);
                        }
                    }
                }
            }
        }

        return response()->json($invoices);
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

            $cost = $batch->cost * $quantity;
            $price = $batch->price * $quantity;
            $profit = ( $batch->price - $batch->cost) * $quantity;

            $invoice->items()->create([
                'drug_id' => $item['drug_id'],
                'batch_id' => $batch->id,
                'quantity' => $item['quantity'],
                'cost' => $batch->cost, // السعر من الدفعة
                'price' => $batch->price, // السعر من الدفعة
                'profit_amount' => ($batch->price - $batch->cost) * $item['quantity'],
            ]);

            // خصم من المخزون وزيادة في الكميات المباعة
            $batch->decrement('stock', $quantity);
            $batch->increment('sold', $quantity);
            //$drug->increment('total_sold', $quantity);
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
                    unset($item->batch->cost);
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
                        unset($item->batch->cost);
                    }
                }
            }

            return response()->json($invoice);
        }


    public function destroy($id)
    {
        // حمل الفاتورة مع عناصرها والدفعات المرتبطة
        // هذا ضروري للوصول إلى الكميات والدفعات لإعادة المخزون
        $invoice = Invoice::with('items.batch')->findOrFail($id);
        $user = auth()->user();

        // 1. التحقق من الصلاحيات: فقط الأدمن يمكنه الحذف
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'غير مصرح لك بحذف هذه الفاتورة.'], 403);
        }

        // 2. استخدام Transaction لضمان سلامة البيانات
        // هذا يضمن أن جميع التغييرات ستتم بنجاح أو لا شيء منها سيتم
        DB::beginTransaction();
        try {
            // 3. إعادة الكميات إلى الدفعات وإنقاص الكميات المباعة من الدفعات
            // (الـ Observer سيتعامل مع total_sold للدواء)
            foreach ($invoice->items as $item) {
                $batch = $item->batch; // الوصول إلى الدفعة المحملة

                if ($batch) {
                    // زيادة الكمية في المخزون (stock) الخاص بالدفعة
                    $batch->increment('stock', $item->quantity);
                    // إنقاص الكمية المباعة (sold) من الدفعة
                    $batch->decrement('sold', $item->quantity);
                }
                // *** لا حاجة لتعديل Drug::total_sold هنا مباشرة ***
                // *** سيتعامل Observer الخاص بـ InvoiceItem مع ذلك تلقائيًا ***
            }


            $invoice->delete();

            // 5. تأكيد التغييرات في قاعدة البيانات
            DB::commit();

            return response()->json(['message' => 'تم حذف الفاتورة بنجاح وإعادة الكميات إلى المخزون.']);

        } catch (\Exception $e) {
            // 6. التراجع عن التغييرات في حالة حدوث خطأ
            DB::rollBack();

            return response()->json(['error' => 'حدث خطأ أثناء حذف الفاتورة وإعادة الكميات.'], 500);
        }
    }
}
