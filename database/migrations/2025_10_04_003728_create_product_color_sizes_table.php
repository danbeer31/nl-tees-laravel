<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_color_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_color_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_id')->constrained()->cascadeOnDelete();

            // Optional per-color/per-size overrides
            $table->integer('price_diff_cents')->default(0);   // +$2 for 2XL, etc.
            $table->integer('stock_qty')->nullable();          // null = not tracked
            $table->string('sku')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->unique(['product_color_id','size_id']);    // one row per pair
            $table->index(['product_color_id','sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_color_sizes');
    }
};
