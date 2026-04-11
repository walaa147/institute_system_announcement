<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-expired-bookings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    // حذف الحجوزات التي بقيت 'draft' لأكثر من 24 ساعة ولم تُدفع
    $deletedCount = \App\Models\Booking::where('status', 'draft')
        ->where('created_at', '<', now()->subHours(24))
        ->delete();

    $this->info("تم تنظيف {$deletedCount} حجز منتهي الصلاحية.");
}
}
