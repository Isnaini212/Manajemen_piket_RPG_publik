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
        Schema::create('replacement_duties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_claim_id')->constrained('duty_claims')->onDelete('cascade');
            $table->timestamp('deadline');
            $table->enum('status', ['offered', 'taken', 'completed', 'expired'])->default('offered');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replacement_duties');
    }
};
