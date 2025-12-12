<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ExpenseSyncService;
use Exception;
use Illuminate\Console\Command;

final class SyncExpensesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:sync {date : The date to sync expenses for (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize expenses from external MySQL database for a specific date';

    /**
     * Execute the console command.
     */
    public function handle(ExpenseSyncService $syncService): int
    {
        $date = $this->argument('date');

        // Validate date format
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('Invalid date format. Please use YYYY-MM-DD format (e.g., 2022-07-02)');

            return Command::FAILURE;
        }

        $this->info("Starting expenses synchronization for date: {$date}...");

        try {
            $stats = $syncService->syncForDate($date);

            $this->info('Expenses synchronization completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['ExpenseTypes Created', $stats['expensetypes_created']],
                    ['ExpenseTypes Skipped', $stats['expensetypes_skipped']],
                    ['Categories Created', $stats['categories_created']],
                    ['Categories Skipped', $stats['categories_skipped']],
                    ['Genders Created', $stats['genders_created']],
                    ['Genders Skipped', $stats['genders_skipped']],
                    ['Products Created', $stats['products_created']],
                    ['Products Updated', $stats['products_updated']],
                    ['Expenses Created', $stats['expenses_created']],
                    ['Expenses Skipped', $stats['expenses_skipped']],
                ]
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Synchronization failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
