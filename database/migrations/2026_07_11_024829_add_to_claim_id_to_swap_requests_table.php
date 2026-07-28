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
        Schema::table('swap_requests', function (Blueprint $table) {
            $table->foreignId('to_claim_id')->nullable()->constrained('duty_claims')->onDelete('cascade')->after('from_claim_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('swap_requests', function (Blueprint $table) {
            $table->dropForeign(['to_claim_id']);
            $table->dropColumn('to_claim_id');
        });
    }
};
