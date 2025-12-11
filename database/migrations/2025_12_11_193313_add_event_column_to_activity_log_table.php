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

        Schema::connection($connection)->table($tableName, function (Blueprint $table): void {
            $table->string('event')->nullable()->after('subject_type');
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

        Schema::connection($connection)->table($tableName, function (Blueprint $table): void {
            $table->dropColumn('event');
        });
    }
};
