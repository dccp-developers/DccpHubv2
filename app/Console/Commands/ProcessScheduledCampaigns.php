<?php

namespace App\Console\Commands;

use App\Services\NotificationCampaignService;
use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled notification campaigns and send them';

    /**
     * Execute the console command.
     */
    public function handle(NotificationCampaignService $service)
    {
        $this->info('Processing scheduled notification campaigns...');

        $processed = $service->processScheduledCampaigns();

        if ($processed > 0) {
            $this->info("Successfully processed {$processed} scheduled campaigns.");
        } else {
            $this->info('No scheduled campaigns found to process.');
        }

        return Command::SUCCESS;
    }
}
