<?php

namespace App\Console\Commands;

use App\Models\Visitor;
use Illuminate\Console\Command;

class ClearAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:clear 
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all analytics/visitor data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Visitor::count();

        if ($count === 0) {
            $this->info('Analytics table is already empty.');
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("This will delete all {$count} visitor records. Are you sure?")) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        Visitor::truncate();

        $this->info("Successfully cleared {$count} analytics records.");
        
        return self::SUCCESS;
    }
}
