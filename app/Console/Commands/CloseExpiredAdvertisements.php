<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advertisement;

class CloseExpiredAdvertisements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:close-expired-advertisements';

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
    Advertisement::where('is_active', true)
        ->where('expired_at', '<=', now())
        ->update(['is_active' => false]);

    $this->info('Expired advertisements closed successfully.');
}
}
