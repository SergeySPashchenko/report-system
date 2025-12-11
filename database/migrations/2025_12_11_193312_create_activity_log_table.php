<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (! is_string($connection)) {
            $connection = null;
        }

        if (! is_string($tableName)) {
            $tableName = 'activity_log';
        }
        Schema::connection($connection)->create($tableName, function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableUlidMorphs('subject', 'subject');
            $table->nullableUlidMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (! is_string($connection)) {
            $connection = null;
        }

        if (! is_string($tableName)) {
            $tableName = 'activity_log';
        }
        Schema::connection($connection)->dropIfExists($tableName);
    }
};
