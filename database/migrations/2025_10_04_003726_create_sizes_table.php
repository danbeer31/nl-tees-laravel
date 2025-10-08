<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('label');           // XS, S, M, L, XL, 2XL, 3XL...
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
