<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Drug;
use App\Models\Invoice; // تم التعديل هنا
use App\Models\InvoiceItem; // تم التعديل هنا
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index()
    {
        $totalDrugs = Drug::count();
        $salesTodayCount = Invoice::whereDate('created_at', Carbon::today())->count();
        $salesYesterdayCount = Invoice::whereDate('created_at', Carbon::yesterday())->count();

        $percentageChange = 0;
        if ($salesYesterdayCount > 0) {
            $percentageChange = (($salesTodayCount - $salesYesterdayCount) / $salesYesterdayCount) * 100;
        } elseif ($salesTodayCount > 0) {
            $percentageChange = 100;
        }


        $topSellingDrugs = InvoiceItem::query()
        ->select('drug_id', DB::raw('SUM(quantity) as total_quantity_sold'))
        ->groupBy('drug_id')
            ->orderByDesc('total_quantity_sold')
            ->limit(5)
            ->with('drug:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'drug_id' => $item->drug_id,
                    'drug_name' => $item->drug ? $item->drug->name : 'دواء محذوف',
                    'total_sold' => (int) $item->total_quantity_sold,
                ];
            });

        $expiryThresholdInDays = 60;
        $nearExpiryDrugs = Batch::query()
            ->where('stock', '>', 0)
            ->whereBetween('expiry_date', [Carbon::today(), Carbon::today()->addDays($expiryThresholdInDays)])
            ->with('drug:id,name')
            ->orderBy('expiry_date', 'asc')
            ->limit(20)
            ->get()
            ->map(function ($batch) {
                return [
                    'name' => $batch->drug ? $batch->drug->name : 'دواء غير معروف',
                    'expiry' => $batch->expiry_date,
                    'quantity' => $batch->stock,
                ];
            });


        // --- 2. بيانات المبيعات لآخر 7 أيام (للرسم البياني) ---
        $salesDataQuery = Invoice::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_price) as sales')
            )
            ->whereBetween('created_at', [Carbon::today()->subDays(6), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date'); // لجعل البحث أسهل

        $salesData = [];
        // حلقة لضمان وجود كل الأيام السبعة حتى لو كانت مبيعاتها صفراً
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $salesData[] = [
                'name' => $date->format('D'), // Mon, Tue, etc.
                'sales' => $salesDataQuery->has($dateString) ? (float) $salesDataQuery[$dateString]->sales : 0,
            ];
        }


        $reorder_level = 10;

        $lowStockDrugs = Drug::query()
            ->select('id', 'name')
            ->withSum('batches', 'stock')
            ->get()
            ->filter(function ($drug) use ($reorder_level) {
                return $drug->batches_sum_stock <= $reorder_level && $drug->batches_sum_stock > 0;
            })
            ->sortBy('batches_sum_stock')
            ->take(10)
            ->map(function ($drug) {
                return [
                    'id'=>$drug->id,
                    'name' => $drug->name,
                    'quantity' => (int) $drug->batches_sum_stock,
                ];
            })->values();

        $statistics = [
            'total_drugs' => $totalDrugs,
            'sales_today_count' => $salesTodayCount,
            'sales_yesterday_count' => $salesYesterdayCount,
            'sales_percentage_change_from_yesterday' => round($percentageChange, 2),
            'top_selling_drugs' => $topSellingDrugs,
            'near_expiry_drugs' => $nearExpiryDrugs,
            'sales_data_weekly' => $salesData,
            'low_stock_drugs' => $lowStockDrugs,
        ];

        return response()->json([
            'success' => true,
            'message' => 'تم جلب إحصائيات لوحة التحكم بنجاح',
            'data' => $statistics
        ]);
    }
}
