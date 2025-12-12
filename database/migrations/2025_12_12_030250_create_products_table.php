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
        Schema::create('products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->bigInteger('ProductID')->unique();
            $table->string('Product');
            $table->string('slug');
            $table->boolean('newSystem')->default(1);
            $table->boolean('Visible')->default(1);
            $table->boolean('flyer')->default(0);
            $table->foreignUlid('main_category_id')->nullable()->constrained('categories')->onDelete('set null')->onUpdate('cascade');
            $table->foreignUlid('marketing_category_id')->nullable()->constrained('categories')->onDelete('set null')->onUpdate('cascade');
            $table->foreignUlid('gender_id')->nullable()->constrained('genders')->onDelete('set null')->onUpdate('cascade');
            $table->foreignUlid('brand_id')->nullable()->constrained('brands')->onDelete('set null')->onUpdate('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
