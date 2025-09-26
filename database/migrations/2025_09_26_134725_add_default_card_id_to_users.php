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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_card_id')->nullable()
                ->after('phone')->constrained('cards')->nullOnDelete();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->string('api_token', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_card_id');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->string('api_token', 100)->nullable(false)->change();
        });
    }
};
