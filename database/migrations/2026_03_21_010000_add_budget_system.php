<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categories table
        Schema::create("categories", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("name_ar");
            $table->string("icon")->default("📦");
            $table->string("color")->default("#6B7280");
            $table->integer("sort_order")->default(0);
            $table->timestamps();
        });

        // Merchant to category mapping (auto-classification)
        Schema::create("merchant_categories", function (Blueprint $table) {
            $table->id();
            $table->string("merchant_pattern");
            $table->foreignId("category_id")->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique("merchant_pattern");
        });

        // Salary cycles
        Schema::create("salary_cycles", function (Blueprint $table) {
            $table->id();
            $table->date("start_date");
            $table->date("end_date");
            $table->decimal("salary_amount", 14, 2)->nullable();
            $table->decimal("budget", 14, 2)->nullable();
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });

        // Failed payments queue
        Schema::create("failed_payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("device_id")->nullable()->constrained()->nullOnDelete();
            $table->text("raw_text");
            $table->string("error_message")->nullable();
            $table->integer("retry_count")->default(0);
            $table->timestamp("last_retry_at")->nullable();
            $table->boolean("is_processed")->default(false);
            $table->timestamps();
        });

        // Add parent_card_id to cards
        Schema::table("cards", function (Blueprint $table) {
            $table->foreignId("parent_card_id")->nullable()->after("id")->constrained("cards")->nullOnDelete();
        });

        // Add category_id and salary_cycle_id to payments
        Schema::table("payments", function (Blueprint $table) {
            $table->foreignId("category_id")->nullable()->after("note")->constrained()->nullOnDelete();
            $table->foreignId("salary_cycle_id")->nullable()->after("category_id")->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("payments", function (Blueprint $table) {
            $table->dropConstrainedForeignId("category_id");
            $table->dropConstrainedForeignId("salary_cycle_id");
        });
        Schema::table("cards", function (Blueprint $table) {
            $table->dropConstrainedForeignId("parent_card_id");
        });
        Schema::dropIfExists("failed_payments");
        Schema::dropIfExists("salary_cycles");
        Schema::dropIfExists("merchant_categories");
        Schema::dropIfExists("categories");
    }
};
