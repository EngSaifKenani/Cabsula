<?php

namespace App\Console\Commands;

use App\Models\MonthlyManufacturerReport;
use App\Models\WeeklyManufacturerReport;
use App\Services\ReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateManufacturerReports extends Command
{
    //php artisan reports:update-manufacturer
    // php artisan schedule:run
    protected $signature = 'reports:update-manufacturer';
    protected $description = 'Update weekly and monthly manufacturer sales reports from the view';
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }
    public function handle()
    {
        $this->info('⏳ Updating manufacturer reports...');
        $this->reportService->updateReports();
        $this->info('✅ Manufacturer reports updated.');
    }
}
