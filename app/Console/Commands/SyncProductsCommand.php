<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ProductSyncService;
use Exception;
use Illuminate\Console\Command;

final class SyncProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize products, categories, and genders from external MySQL database';

    /**
     * Execute the console command.
     */
    public function handle(ProductSyncService $syncService): int
    {
        $this->info('Starting products synchronization...');

        try {
            $stats = $syncService->sync();

            $this->info('Products synchronization completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Categories Created', $stats['categories_created']],
                    ['Categories Skipped', $stats['categories_skipped']],
                    ['Genders Created', $stats['genders_created']],
                    ['Genders Skipped', $stats['genders_skipped']],
                    ['Products Created', $stats['products_created']],
                    ['Products Updated', $stats['products_updated']],
                    ['Products Skipped', $stats['products_skipped']],
                ]
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Synchronization failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
