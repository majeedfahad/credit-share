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
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->after('user_id');
            $table->string('bound_fingerprint')->nullable()->after('personal_access_token_id');
            $table->timestamp('last_used_at')->nullable()->after('bound_fingerprint');
            $table->timestamp('expires_at')->nullable()->after('last_used_at'); // optional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            //
        });
    }
};
