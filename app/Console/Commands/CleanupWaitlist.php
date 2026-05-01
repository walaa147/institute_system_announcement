<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaitingList;
use App\Services\BookingService;
class CleanupWaitlist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-waitlist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @return int
        */
public function handle()
{
    // 1. حذف السجلات التي مر عليها شهر وهي مكتملة
    $oldRecords = WaitingList::whereIn('status', ['converted', 'expired'])
        ->where('updated_at', '<', now()->subDays(30))
        ->delete();

    // 2. معالجة السجلات التي انتهت مهلة تنبيهها (مثلاً بعد 24 ساعة من notified)
    $expiredNotified = WaitingList::where('status', 'notified')
        ->where('updated_at', '<', now()->subHours(24))
        ->get();

    foreach ($expiredNotified as $record) {
        $record->update(['status' => 'expired']);
        // استدعاء دالة التنبيه للشخص التالي
        $service = app(BookingService::class);
        $service->processNextInWaitlist($record->bookable);
    }

    $this->info("تم تنظيف القائمة ومعالجة التنبيهات المنتهية.");
}
}
