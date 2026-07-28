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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_claim_id')->nullable()->constrained('duty_claims')->onDelete('cascade');
            $table->foreignId('replacement_id')->nullable()->constrained('replacement_duties')->onDelete('cascade');
            $table->string('proof_url');
            $table->timestamp('uploaded_at');
            $table->enum('verify_status', ['pending', 'approved', 'rejected', 'rejected_final'])->default('pending');
            $table->integer('resubmit_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
