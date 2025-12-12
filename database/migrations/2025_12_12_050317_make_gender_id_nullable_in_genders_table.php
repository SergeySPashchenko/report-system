<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genders', function (Blueprint $table): void {
            $table->unsignedBigInteger('gender_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genders', function (Blueprint $table): void {
            $table->unsignedBigInteger('gender_id')->nullable(false)->change();
        });
    }
};
