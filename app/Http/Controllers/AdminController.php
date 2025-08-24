<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotificationJob;
use App\Models\Batch;
use App\Models\Invoice;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; // استيراد للتعامل مع الملفات
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct(NotificationService $notificationService, FirebaseService $firebaseService)
    {
        $this->notificationService = $notificationService;
        $this->firebaseService = $firebaseService;
    }
    public function listPharmacists()
    {
        $pharmacists = User::where('role', 'pharmacist')->get();
        return $this->success($pharmacists);
    }

    public function getPharmacistById($id)
    {
        $pharmacist = User::where('id', $id)
            ->where('role', 'pharmacist')
            ->select(['id', 'name', 'email', 'phone_number', 'address', 'created_at', 'image'])
            ->first();

        if (!$pharmacist) {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        if ($pharmacist->image) {
            $pharmacist->image = asset('storage/' . $pharmacist->image);
        }

        return $this->success($pharmacist);
    }

    public function createPharmacist(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('pharmacist_images', 'public');
            $validatedData['image'] = $imagePath;
        }

        $validatedData['password'] = Hash::make($request->input('password'));
        $validatedData['role'] = 'pharmacist';

        $user = User::create($validatedData);

        $usersToNotify = User::whereIn('role', ['admin','pharmacist'])->get();
        $deviceTokens = $usersToNotify->flatMap(function ($user) {
            return $user->deviceTokens->pluck('token');
        })->filter()->toArray();
        $userIds = $usersToNotify->pluck('id')->toArray();

        $message = "تمت إضافة صيدلي جديد: {$user->name}.";
        $type = 'general';
        $title = 'صيدلي جديد!';

        SendNotificationJob::dispatch($message, $type, $title, $userIds, $deviceTokens);


        return $this->success([
            'user' => $user,
            'token' => $user->createToken($user->email)->plainTextToken
        ], 201);
    }

    public function updatePharmacist(Request $request, $id)
    {
        $pharmacist = User::findOrFail($id);

        if ($pharmacist->role !== 'pharmacist') {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($pharmacist->id)
            ],
            'phone_number' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'password' => 'sometimes|string|min:3',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($pharmacist->image) {
                Storage::disk('public')->delete($pharmacist->image);
            }
            $imagePath = $request->file('image')->store('pharmacist_images', 'public');
            $validatedData['image'] = $imagePath;
        }


        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($request->input('password'));
        }

        $pharmacist->update($validatedData);

        return $this->success($pharmacist);
    }

    public function deletePharmacist($id)
    {
        $pharmacist = User::findOrFail($id);
        if ($pharmacist->role !== 'pharmacist') {
            return $this->error('The specified user is not a pharmacist', 400);
        }

        if ($pharmacist->image) {
            Storage::disk('public')->delete($pharmacist->image);
        }

        $pharmacist->delete();
        return $this->success([], 'Pharmacist deleted successfully');
    }
    public function statistics(Request $request)
    {
        // 1. التحقق من المدخلات والصلاحيات
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'supplier_id' => 'nullable|exists:suppliers,id', // يجب إضافة هذا للتصفية على الموردين
        ]);

        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'غير مصرح لك بعرض الإحصائيات.'
            ], 403);
        }

        // 2. تحديد نطاق التاريخ
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        // 3. استدعاء الدوال المساعدة للحصول على القيم
        $financialData = $this->getFinancialStatistics($startDate, $endDate);
        $disposedData = $this->getDisposedLossesData($startDate, $endDate);

        // استدعاء دالة المرتجعات
        $returnedData = $this->getReturnedValueData($startDate, $endDate, $request->input('supplier_id'));

        // 4. حساب الربح الصافي
        $netProfit = $financialData['total_profit'] - $disposedData['total_loss'];

        // 5. تجميع و إرجاع النتائج
        return response()->json([
            'statistics' => [
                'total_sales'      => number_format($financialData['total_sales'], 2),
                'total_cost'       => number_format($financialData['total_cost'], 2),
                'total_profit'     => number_format($financialData['total_profit'], 2),
                'total_loss'       => number_format($disposedData['total_loss'], 2),
                'net_profit'       => number_format($netProfit, 2),
                'total_disposed_batches' => $disposedData['total_disposed_batches'],
                'total_returned_value' => number_format($returnedData['total_returned_value'], 2),
                'total_returned_batches' => $returnedData['total_returned_batches'],
            ],
            'message' => 'تم جلب الإحصائيات بنجاح.',
        ]);
    }

    protected function getFinancialStatistics(?Carbon $startDate, ?Carbon $endDate)
    {
        $invoiceQuery = Invoice::query()->where('status', 'active');

        if ($startDate && $endDate) {
            $invoiceQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalProfit = $invoiceQuery->sum('total_profit');
        $totalSales  = $invoiceQuery->sum('total_price');
        $totalCost   = $invoiceQuery->sum('total_cost');

        $capital = \App\Models\Batch::where('status', 'available')
            ->sum(DB::raw('stock * unit_cost'));

        return [
            'total_sales'  => $totalSales,
            'total_cost'   => $totalCost,
            'total_profit' => $totalProfit,
            'capital'      => $capital,
        ];
    }

    protected function getDisposedLossesData(?Carbon $startDate, ?Carbon $endDate)
    {
        $disposedBatches = Batch::where('status', 'disposed');

        if ($startDate && $endDate) {
            $disposedBatches->whereBetween('disposed_at', [$startDate, $endDate]);
        }

        $disposedBatches = $disposedBatches->get();

        $totalLoss = $disposedBatches->sum(function ($batch) {
            return $batch->unit_cost * $batch->stock;
        });

        return [
            'total_disposed_batches' => $disposedBatches->count(),
            'total_loss' => number_format($totalLoss, 2, '.', ''),
        ];
    }

    protected function getReturnedValueData(?Carbon $startDate, ?Carbon $endDate, ?int $supplierId)
    {
        $query = Batch::where('status', 'returned');

        if ($startDate && $endDate) {
            $query->whereBetween('returned_at', [$startDate, $endDate]);
        }

        if ($supplierId) {
            $query->whereHas('purchaseItem.purchaseInvoice', function ($invoiceQuery) use ($supplierId) {
                $invoiceQuery->where('supplier_id', $supplierId);
            });
        }

        $returnedBatches = $query->get();

        $totalReturnedValue = $returnedBatches->sum(function ($batch) {
            return $batch->unit_cost * $batch->stock;
        });

        return [
            'total_returned_batches' => $returnedBatches->count(),
            'total_returned_value' => number_format($totalReturnedValue, 2, '.', ''),
        ];
    }


}
