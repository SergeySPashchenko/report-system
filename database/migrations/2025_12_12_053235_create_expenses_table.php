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
        Schema::create('expenses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->bigInteger('external_id')->nullable()->unique();
            $table->date('ExpenseDate');
            $table->decimal('Expense', 10, 2);
            $table->bigInteger('ProductID')->nullable();
            $table->bigInteger('ExpenseID')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys через ProductID та ExpenseTypeID (не через ULID)
            // ProductID має unique constraint в products таблиці
            $table->foreign('ProductID')->references('ProductID')->on('products')->onDelete('set null')->onUpdate('cascade');
            // ExpenseTypeID має unique constraint в expensetypes таблиці
            $table->foreign('ExpenseID')->references('ExpenseTypeID')->on('expensetypes')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
