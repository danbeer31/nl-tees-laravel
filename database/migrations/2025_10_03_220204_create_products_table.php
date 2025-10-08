<?php

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();     // for clean URLs
            $table->text('description')->nullable();
            $table->integer('base_price_cents')->default(0);
            $table->enum('supplier', ['sanmar', 's&s_products']);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active']);           // simple filter index
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
