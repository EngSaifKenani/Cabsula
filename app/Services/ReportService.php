<?php

namespace App\Services;

use App\Models\MonthlyManufacturerReport;
use App\Models\WeeklyManufacturerReport;
use Illuminate\Support\Facades\DB;
class ReportService
{
    public function updateReports()
    {
        $weekly = DB::table('manufacturer_sales_view')
            ->selectRaw("
                manufacturer_id,
                manufacturer_name,
                YEAR(DATE_SUB(sale_date, INTERVAL (WEEKDAY(sale_date) + 1) % 7 DAY)) AS year,
                WEEK(DATE_SUB(sale_date, INTERVAL (WEEKDAY(sale_date) + 1) % 7 DAY), 0) AS week,
                SUM(total_quantity_sold) AS total_quantity_sold,
                SUM(total_profit) AS total_profit
            ")
            ->groupByRaw("
                manufacturer_id,
                manufacturer_name,
                YEAR(DATE_SUB(sale_date, INTERVAL (WEEKDAY(sale_date) + 1) % 7 DAY)),
                WEEK(DATE_SUB(sale_date, INTERVAL (WEEKDAY(sale_date) + 1) % 7 DAY), 0)
            ")
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

        $monthly = DB::table('manufacturer_sales_view')
            ->selectRaw("
                manufacturer_id,
                manufacturer_name,
                YEAR(sale_date) AS year,
                MONTH(sale_date) AS month,
                SUM(total_quantity_sold) AS total_quantity_sold,
                SUM(total_profit) AS total_profit
            ")
            ->groupByRaw("
                manufacturer_id,
                manufacturer_name,
                YEAR(sale_date),
                MONTH(sale_date)
            ")
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
    }
}

