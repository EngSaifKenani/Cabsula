<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotificationJob;
use App\Models\Batch;
use App\Models\Disposal;
use App\Models\Invoice;
use App\Models\SupplierReturn;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        if (Auth::user()->role !== 'admin') {
            return $this->error('غير مصرح لك بعرض الإحصائيات.', 403);
        }

        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        $financialData = $this->getFinancialStatistics($startDate, $endDate);
        $disposedData = $this->getDisposedLossesData($startDate, $endDate);
        $returnedData = $this->getReturnedValueData($startDate, $endDate, $request->input('supplier_id'));
        $capitalData = $this->getCapitalData();

        $netProfit = $financialData['total_profit'] - $disposedData['total_loss'];

        return $this->success([
            'statistics' => [
                'total_sales'      => number_format($financialData['total_sales'], 2),
                'total_cost'       => number_format($financialData['total_cost'], 2),
                'total_profit'     => number_format($financialData['total_profit'], 2),
                'total_loss'       => number_format($disposedData['total_loss'], 2),
                'net_profit'       => number_format($netProfit, 2),
                'total_disposal_records' => $disposedData['total_disposal_records'],
                'total_disposed_batches' => $disposedData['total_disposed_batches'],
                'total_returned_value' => number_format($returnedData['total_returned_value'], 2),
                'total_returned_records' => $returnedData['total_returned_records'],
                'total_returned_batches' => $returnedData['total_returned_batches'],
                'current_capital' => number_format($capitalData['capital'], 2),
            ],
        ], 'تم جلب الإحصائيات بنجاح.');
    }

    protected function getFinancialStatistics(?Carbon $startDate, ?Carbon $endDate): array
    {
        $invoiceQuery = Invoice::query();
        $invoiceQuery->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]));

        $financials = $invoiceQuery->selectRaw('
            SUM(total_price) as total_sales,
            SUM(total_cost) as total_cost,
            SUM(total_profit) as total_profit
        ')->first();

        return [
            'total_sales'  => (float) ($financials->total_sales ?? 0),
            'total_cost'   => (float) ($financials->total_cost ?? 0),
            'total_profit' => (float) ($financials->total_profit ?? 0),
        ];
    }

    protected function getDisposedLossesData(?Carbon $startDate, ?Carbon $endDate): array
    {
        $disposalQuery = Disposal::query();
        $disposalQuery->when($startDate && $endDate, fn($q) => $q->whereBetween('disposed_at', [$startDate, $endDate]));

        $disposalData = $disposalQuery->selectRaw('
            COUNT(id) as total_disposal_records,
            COUNT(DISTINCT batch_id) as total_disposed_batches,
            SUM(loss_amount) as total_loss
        ')->first();

        return [
            'total_disposal_records' => (int) ($disposalData->total_disposal_records ?? 0),
            'total_disposed_batches' => (int) ($disposalData->total_disposed_batches ?? 0),
            'total_loss' => (float) ($disposalData->total_loss ?? 0),
        ];
    }

    protected function getReturnedValueData(?Carbon $startDate, ?Carbon $endDate, ?int $supplierId): array
    {
        $returnsQuery = SupplierReturn::query();
        $returnsQuery->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]));
        $returnsQuery->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId));

        $returnedData = $returnsQuery->selectRaw('
            COUNT(id) as total_returned_records,
            COUNT(DISTINCT batch_id) as total_returned_batches,
            SUM(credit_amount) as total_returned_value
        ')->first();

        return [
            'total_returned_records' => (int) ($returnedData->total_returned_records ?? 0),
            'total_returned_batches' => (int) ($returnedData->total_returned_batches ?? 0),
            'total_returned_value' => (float) ($returnedData->total_returned_value ?? 0),
        ];
    }

    protected function getCapitalData(): array
    {
        $capital = Batch::where('stock', '>', 0)
            ->sum(DB::raw('stock * unit_cost'));

        return [
            'capital' => (float) $capital,
        ];
    }
}
