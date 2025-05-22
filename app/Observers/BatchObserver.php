<?php

namespace App\Observers;

use App\Models\Batch;
use Illuminate\Support\Facades\DB;

class BatchObserver
{
    /**
     * Handle the Batch "created" event.
     */
    public function created(Batch $batch): void
    {
        //
    }

    /**
     * Handle the Batch "updated" event.
     */
    public function updated(Batch $batch)
    {
        if ($batch->isDirty('sold')) {
            $this->updateDrugTotalSales($batch);
        }
    }

    protected function updateDrugTotalSales(Batch $batch)
    {
        // استخدم transaction لتجنب المشاكل
        DB::transaction(function () use ($batch) {
            $drug = $batch->drug()->lockForUpdate()->first();
            $drug->total_sold = $drug->batches()->sum('sold');
            $drug->save();
        });
    }


    /**
     * Handle the Batch "deleted" event.
     */
    public function deleted(Batch $batch): void
    {
    }

    /**
     * Handle the Batch "restored" event.
     */
    public function restored(Batch $batch): void
    {
        //
    }

    /**
     * Handle the Batch "force deleted" event.
     */
    public function forceDeleted(Batch $batch): void
    {
        //
    }
}
