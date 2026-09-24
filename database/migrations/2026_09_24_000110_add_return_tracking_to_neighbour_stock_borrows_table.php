<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('neighbour_stock_borrows', function (Blueprint $table) { $table->decimal('returned_quantity', 18, 4)->default(0)->after('quantity'); $table->string('status', 30)->default('open')->after('notes'); $table->date('returned_at')->nullable()->after('return_due_date'); $table->text('return_notes')->nullable()->after('notes'); }); }
    public function down(): void { Schema::table('neighbour_stock_borrows', function (Blueprint $table) { $table->dropColumn(['returned_quantity','status','returned_at','return_notes']); }); }
};
