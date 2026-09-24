<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('neighbour_stock_borrows', function (Blueprint $table) {
            $table->id(); $table->uuid('public_id')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 18, 4); $table->decimal('conversion_factor', 18, 4);
            $table->string('neighbour_name'); $table->string('neighbour_phone', 50)->nullable();
            $table->date('return_due_date')->nullable(); $table->string('notes', 1000)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('neighbour_stock_borrows'); }
};
