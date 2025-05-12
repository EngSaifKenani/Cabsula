<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * عرض جميع الفواتير مع إمكانية الفلترة بالتاريخ.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Invoice::with([ 'user']);

        // الصيدلي يرى فقط فواتيره
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            // المشرف يمكنه فلترة حسب المستخدم
            $query->where('user_id', $request->user_id);
        }

        // فلترة حسب التاريخ
        if ($request->filled('on_date')) {
            $query->whereDate('created_at', $request->on_date);
        }

        if ($request->filled('from_date') && !$request->filled('to_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if (!$request->filled('from_date') && $request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            // تحويل `to_date` إلى آخر لحظة في اليوم (23:59:59)
            $toDate = \Carbon\Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('created_at', [$request->from_date, $toDate]);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(50);

        // إخفاء المعلومات الحساسة إذا لم يكن المستخدم Admin
        if ($user->role !== 'admin') {
            foreach ($invoices as $invoice) {
                unset($invoice->total_cost, $invoice->total_profit);

                foreach ($invoice->items as $item) {
                    unset($item->cost, $item->profit_amount);

                    if ($item->relationLoaded('drug')) {
                        unset($item->drug->cost, $item->drug->profit_amount);
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
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $items = $request->items;

        foreach ($items as $item) {
            $drug = Drug::find($item['drug_id']);

            if (!$drug) {
                return response()->json(['error' => "الدواء غير موجود (ID: {$item['drug_id']})"], 404);
            }

            if ($drug->stock < $item['quantity']) {
                return response()->json([
                    'error' => "الكمية غير كافية للدواء: {$drug->name}",
                    'available_stock' => $drug->stock
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
            $quantity = $item['quantity'];

            $cost = $drug->cost * $quantity;
            $price = $drug->price * $quantity;
            $profit = ($drug->profit_amount ?? ($drug->price - $drug->cost)) * $quantity;

            $invoice->items()->create([
                'drug_id' => $drug->id,
                'quantity' => $quantity,
                'cost' => $drug->cost,
                'price' => $drug->price,
                'profit_amount' => $drug->profit_amount ?? ($drug->price - $drug->cost),
            ]);

            $drug->decrement('stock', $quantity);

            $totalCost += $cost;
            $totalPrice += $price;
            $totalProfit += $profit;
        }

        $invoice->update([
            'total_cost' => $totalCost,
            'total_price' => $totalPrice,
            'total_profit' => $totalProfit,
        ]);

        $invoice->load('items.drug');

        if (auth()->user()->role !== 'admin') {
            // إخفاء من العنصر الأساسي
            unset($invoice->total_cost, $invoice->total_profit);

            // إخفاء من كل عنصر دواء
            foreach ($invoice->items as $item) {
                unset($item->cost, $item->profit_amount);

                if ($item->relationLoaded('drug')) {
                    unset($item->drug->cost, $item->drug->profit_amount);
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
        $invoice = Invoice::with(['items.drug', 'user'])->findOrFail($id);
        $user = auth()->user();

        // الصيدلي لا يمكنه مشاهدة فواتير غيره
        if ($user->role === 'pharmacist' && $invoice->user_id !== $user->id) {
            return response()->json(['error' => 'غير مصرح لك بمشاهدة هذه الفاتورة'], 403);
        }

        if (auth()->user()->role !== 'admin') {
            // إخفاء من العنصر الأساسي
            unset($invoice->total_cost, $invoice->total_profit);

            // إخفاء من كل عنصر دواء
            foreach ($invoice->items as $item) {
                unset($item->cost, $item->profit_amount);

                if ($item->relationLoaded('drug')) {
                    unset($item->drug->cost, $item->drug->profit_amount);
                }
            }
        }

        return response()->json($invoice);
    }


    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $user = auth()->user();

        // فقط الأدمن يمكنه الحذف
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'غير مصرح بالحذف'], 403);
        }

        $invoice->delete();

        return response()->json(['message' => 'تم حذف الفاتورة']);
    }
}
