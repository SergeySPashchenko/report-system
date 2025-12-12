<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ProductItemSyncService;
use Exception;
use Illuminate\Console\Command;

final class SyncProductItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-items:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize product items from external MySQL database';

    /**
     * Execute the console command.
     */
    public function handle(ProductItemSyncService $syncService): int
    {
        $this->info('Starting product items synchronization...');

        try {
            $stats = $syncService->sync();

            $this->info('Product items synchronization completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Products Created', $stats['products_created']],
                    ['Products Skipped', $stats['products_skipped']],
                    ['Product Items Created', $stats['product_items_created']],
                    ['Product Items Updated', $stats['product_items_updated']],
                    ['Product Items Skipped', $stats['product_items_skipped']],
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
