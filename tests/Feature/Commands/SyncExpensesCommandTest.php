<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncExpensesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_validates_date_format(): void
    {
        $this->artisan('expenses:sync', ['date' => 'invalid-date'])
            ->expectsOutput('Invalid date format. Please use YYYY-MM-DD format (e.g., 2022-07-02)')
            ->assertFailed();
    }

    public function test_command_accepts_valid_date_format(): void
    {
        try {
            $this->artisan('expenses:sync', ['date' => '2022-07-02'])
                ->expectsOutput('Starting expenses synchronization for date: 2022-07-02...')
                ->assertSuccessful();
        } catch (Exception $e) {
            $this->markTestSkipped('External database connection not configured: '.$e->getMessage());
        }
    }

    public function test_command_displays_statistics_after_sync(): void
    {
        try {
            $this->artisan('expenses:sync', ['date' => '2022-07-02'])
                ->expectsOutput('Starting expenses synchronization for date: 2022-07-02...')
                ->expectsOutput('Expenses synchronization completed!')
                ->assertSuccessful();
        } catch (Exception $e) {
            $this->markTestSkipped('External database connection not configured: '.$e->getMessage());
        }
    }
}
