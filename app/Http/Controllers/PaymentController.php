<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Payment::with([
            'purchaseInvoice.supplier',
            'user'
        ]);


        $query->when($request->filled('purchase_invoice_id'), function ($q) use ($request) {
            $q->where('purchase_invoice_id', $request->purchase_invoice_id);
        });

        $query->when($request->filled('supplier_id'), function ($q) use ($request) {
            $q->whereHas('purchaseInvoice', function ($invoiceQuery) use ($request) {
                $invoiceQuery->where('supplier_id', $request->supplier_id);
            });
        });

        $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        });

        $payments = $query->latest()->paginate(15);

        return $this->success($payments, 'تم جلب سجلات المدفوعات بنجاح.');
    }

    public function show(Payment $payment)
    {
        $payment->load('purchaseInvoice.supplier', 'user');

        return $this->success($payment, 'تم جلب سجل الدفعة بنجاح.');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $invoice = PurchaseInvoice::findOrFail($validatedData['purchase_invoice_id']);

            $remainingBalance = $invoice->total - $invoice->paid_amount;
            if (abs($validatedData['amount'] - $remainingBalance) > 0.001 && $validatedData['amount'] > $remainingBalance) {
                return $this->error('المبلغ المدفوع يتجاوز الرصيد المتبقي على الفاتورة.', 422);
            }

            $payment = Payment::create([
                'purchase_invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'amount' => $validatedData['amount'],
                'notes' => $validatedData['notes'],
            ]);

            $newPaidAmount = $invoice->paid_amount + $validatedData['amount'];
            $newStatus = $this->determineInvoiceStatus($invoice->total, $newPaidAmount);

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'paid_at' => ($newStatus === 'paid' && $invoice->status !== 'paid') ? now() : $invoice->paid_at,
            ]);

            $supplier = Supplier::find($invoice->supplier_id);
            $supplier->decrement('account_balance', $validatedData['amount']);

            DB::commit();

            return $this->success($payment, 'تم تسجيل الدفعة بنجاح.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في تسجيل الدفعة: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, Payment $payment)
    {
        $validatedData = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $payment->update($validatedData);

            DB::commit();

            return $this->success($payment, 'تم تحديث ملاحظات الدفعة بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في تحديث ملاحظات الدفعة: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Payment $payment)
    {
        DB::beginTransaction();

        try {
            $invoice = $payment->purchaseInvoice;
            $supplier = $invoice->supplier;

            // 1. إعادة المبلغ إلى الفاتورة
            $newPaidAmount = $invoice->paid_amount - $payment->amount;
            $newStatus = $this->determineInvoiceStatus($invoice->total, $newPaidAmount);

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'paid_at' => null, // إعادة تعيين تاريخ الدفع
            ]);

            // 2. إعادة المبلغ إلى رصيد المورد
            $supplier->increment('account_balance', $payment->amount);

            // 3. حذف سجل الدفعة
            $payment->delete();

            DB::commit();

            return $this->success(null, 'تم حذف سجل الدفعة بنجاح وإعادة المبالغ المستحقة.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل في حذف سجل الدفعة: ' . $e->getMessage(), 500);
        }
    }

    // تأكد من أن هذه الدالة المساعدة موجودة في المتحكم
    private function determineInvoiceStatus(float $total, float $paidAmount): string
    {
        if (abs($paidAmount - $total) < 0.001) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partially paid';
        } else {
            return 'unpaid';
        }
    }

    public function getPaymentsSummary(Request $request)
    {
        $query = Payment::query();

        $query->when($request->filled('purchase_invoice_id'), fn($q) => $q->where('purchase_invoice_id', $request->purchase_invoice_id));
        $query->when($request->filled('supplier_id'), fn($q) => $q->whereHas('purchaseInvoice', fn($invoiceQuery) => $invoiceQuery->where('supplier_id', $request->supplier_id)));
        $query->when($request->filled('start_date') && $request->filled('end_date'), fn($q) => $q->whereBetween('created_at', [Carbon::parse($request->input('start_date'))->startOfDay(), Carbon::parse($request->input('end_date'))->endOfDay()]));

        if (!$request->filled('supplier_id')) {
            $query->with('purchaseInvoice.supplier');
        }

        $payments = $query->get();

        if ($request->filled('supplier_id')) {
            $totalAmount = $payments->sum('amount');
            return $this->success([
                'total_payments_amount' => number_format($totalAmount, 2),
                'total_payments_count' => $payments->count(),
            ], 'تم حساب ملخص المدفوعات بنجاح.');
        } else {
            $summaryBySupplier = $payments
                ->filter(function ($payment) {
                    // نتحقق من وجود فاتورة ومورد مرتبط بها قبل المتابعة
                    return $payment->purchaseInvoice && $payment->purchaseInvoice->supplier;
                })
                ->groupBy('purchaseInvoice.supplier.id')
                ->map(function ($paymentsBySupplier) {
                    $supplier = $paymentsBySupplier->first()->purchaseInvoice->supplier;
                    return [
                        'supplier_id' => $supplier->id,
                        'supplier_name' => $supplier->name,
                        'total_payments_amount' => number_format($paymentsBySupplier->sum('amount'), 2),
                        'total_payments_count' => $paymentsBySupplier->count(),
                    ];
                });
            return $this->success([
                'summary_by_supplier' => $summaryBySupplier->values(),
            ], 'تم حساب ملخص المدفوعات مجمعًا بنجاح.');
        }
    }
}
