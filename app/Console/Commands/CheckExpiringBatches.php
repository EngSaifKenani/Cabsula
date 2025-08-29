<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\User;
use App\Jobs\SendNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckExpiringBatches extends Command
{
    protected $signature = 'batches:check-expiring';
    protected $description = 'Checks for batches expiring in the next two months and sends notifications.';

    public function handle()
    {
        $usersToNotify = User::whereIn('role', ['admin','pharmacist'])->get();
        $deviceTokens = $usersToNotify->flatMap(function ($user) {
            return $user->deviceTokens->pluck('token');
        })->toArray(); $userIds = $usersToNotify->pluck('id')->toArray();

        // 1. فحص الدفعات التي انتهت صلاحيتها بالفعل


        // 2. فحص الدفعات التي ستقترب من الانتهاء
        $twoMonthsFromNow = Carbon::now()->addMonths(2);

        $expiringSoonBatches = Batch::where('expiry_date', '>', Carbon::now())
            ->where('expiry_date', '<=', $twoMonthsFromNow)
            ->where('is_expiry_notified', false)
            ->whereIn('status', ['available'])
            ->with('drug')
            ->get();

        foreach ($expiringSoonBatches as $batch) {
            $message = "ستنتهي صلاحية دواء {$batch->drug->name} (دفعة رقم {$batch->id}) بتاريخ " . $batch->expiry_date->format('Y-m-d') . ". يرجى اتخاذ الإجراء اللازم.";
            $type = 'approaching_expiry';
            $title = 'تحذير انتهاء صلاحية!';

            SendNotificationJob::dispatch($message, $type, $title, $userIds, $deviceTokens);

            $batch->is_expiry_notified = true;
            $batch->save();
        }


        $expiringBatches = Batch::where('expiry_date', '<', Carbon::now())
            ->where('is_expiry_notified', true)
            ->whereIn('status', ['available'])
            ->with('drug')
            ->get();

        foreach ($expiringBatches as $batch) {
            $message = "انتهت صلاحية دواء {$batch->drug->name} (دفعة رقم {$batch->id}) بتاريخ " . $batch->expiry_date->format('Y-m-d') . ". يرجى اتخاذ الإجراء اللازم لإزالته من المخزون.";
            $type = 'expired';
            $title = 'انتهاء صلاحية!';

            SendNotificationJob::dispatch($message, $type, $title, $userIds, $deviceTokens);
echo "SSSSSSSSSSS";
            $batch->status = 'expired';
            $batch->save();
        }

        $this->info('Expiring and expired batches checked and notifications sent successfully.');
        return Command::SUCCESS;
    }
}
