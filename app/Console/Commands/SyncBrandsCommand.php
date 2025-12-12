<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BrandSyncService;
use Exception;
use Illuminate\Console\Command;

final class SyncBrandsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brands:sync {--stats : Show statistics only without syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize brands from external MySQL database';

    /**
     * Execute the console command.
     */
    public function handle(BrandSyncService $syncService): int
    {
        if ($this->option('stats')) {
            return $this->showStats($syncService);
        }

        $this->info('Starting brand synchronization...');

        try {
            $stats = $syncService->sync();

            $this->info('Brand synchronization completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Skipped', $stats['skipped']],
                    ['Total External', $stats['total_external']],
                ]
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Synchronization failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Show statistics about external brands.
     */
    private function showStats(BrandSyncService $syncService): int
    {
        $this->info('Fetching statistics from external database...');

        try {
            $stats = $syncService->getExternalStats();

            if (! empty($stats['error'])) {
                $errorMessage = is_string($stats['error']) ? $stats['error'] : json_encode($stats['error']);
                $this->error('Failed to get statistics: '.$errorMessage);

                return Command::FAILURE;
            }

            $this->info('External Database Statistics:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Products', $stats['total_products']],
                    ['Unique Brands', $stats['brands_count']],
                    ['Products Without Brand', $stats['products_without_brand']],
                ]
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to get statistics: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
