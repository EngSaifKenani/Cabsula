<?php

namespace Database\Seeders;

use App\Models\MonthlyManufacturerReport;
use App\Models\WeeklyManufacturerReport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManufacturerReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Generating weekly manufacturer reports...');
        $weekly = DB::table('manufacturer_sales_view')
            ->selectRaw('manufacturer_id, manufacturer_name, YEAR(sale_date) as year, WEEK(sale_date, 6) as week,
                        SUM(total_quantity_sold) as total_quantity_sold,
                        SUM(total_profit) as total_profit')
            ->groupBy('manufacturer_id', 'manufacturer_name', DB::raw('YEAR(sale_date)'), DB::raw('WEEK(sale_date, 6)'))
            ->get();

        foreach ($weekly as $row) {
            WeeklyManufacturerReport::updateOrCreate(
                [
                    'manufacturer_id' => $row->manufacturer_id,
                    'year' => $row->year,
                    'week' => $row->week,
                ],
                [
                    'manufacturer_name' => $row->manufacturer_name,
                    'total_quantity_sold' => $row->total_quantity_sold,
                    'total_profit' => $row->total_profit,
                ]
            );
        }

        $this->command->info('✅ Weekly reports generated.');

        $this->command->info('🔄 Generating monthly manufacturer reports...');
        $monthly = DB::table('manufacturer_sales_view')
            ->selectRaw('manufacturer_id, manufacturer_name, YEAR(sale_date) as year, MONTH(sale_date) as month,
                        SUM(total_quantity_sold) as total_quantity_sold,
                        SUM(total_profit) as total_profit')
            ->groupBy('manufacturer_id', 'manufacturer_name', DB::raw('YEAR(sale_date)'), DB::raw('MONTH(sale_date)'))
            ->get();

        foreach ($monthly as $row) {
            MonthlyManufacturerReport::updateOrCreate(
                [
                    'manufacturer_id' => $row->manufacturer_id,
                    'year' => $row->year,
                    'month' => $row->month,
                ],
                [
                    'manufacturer_name' => $row->manufacturer_name,
                    'total_quantity_sold' => $row->total_quantity_sold,
                    'total_profit' => $row->total_profit,
                ]
            );
        }

        $this->command->info('✅ Monthly reports generated.');
    }
}
