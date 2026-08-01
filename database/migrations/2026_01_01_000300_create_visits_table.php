<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('visited_at');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('note')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'visited_at']);
            $table->index(['user_id', 'visited_at']);
        });

        Schema::create('visit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty_before')->default(0); // qty_left kunjungan sebelumnya
            $table->unsignedInteger('qty_found')->default(0);  // sisa yang dihitung sales
            $table->unsignedInteger('qty_sold')->default(0);   // qty_before - qty_found
            $table->unsignedInteger('qty_added')->default(0);  // tambahan titipan
            $table->unsignedInteger('qty_left')->default(0);   // qty_found + qty_added
            $table->timestamps();

            $table->unique(['visit_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_items');
        Schema::dropIfExists('visits');
    }
};
