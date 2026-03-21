<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('salary_cycle_id')->constrained()->onDelete('cascade');
            $table->decimal('budget_amount', 10, 2);
            $table->timestamps();

            $table->unique(['category_id', 'salary_cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_budgets');
    }
};
