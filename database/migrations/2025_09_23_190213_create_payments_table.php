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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->nullable()->constrained('cards')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('merchant')->nullable();
            $table->text('raw_text')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
