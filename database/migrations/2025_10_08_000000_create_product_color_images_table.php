<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_color_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_color_id')
                ->constrained('product_colors')
                ->cascadeOnDelete();
            $table->string('path');                 // e.g. storage/products/123/red-1.jpg
            $table->string('alt')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('meta')->nullable();       // optional: { "width":1200, "height":1200 }
            $table->timestamps();

            $table->index(['product_color_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_color_images');
    }
};

