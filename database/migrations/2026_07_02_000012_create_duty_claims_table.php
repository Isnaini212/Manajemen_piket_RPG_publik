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
        Schema::create('duty_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_slot_id')->constrained('duty_slots')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student_profiles')->onDelete('cascade');
            $table->enum('claim_type', ['regular', 'replacement', 'punishment'])->default('regular');
            $table->enum('status', ['pending', 'approved', 'rejected', 'failed'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            // Satu siswa hanya boleh punya satu klaim per slot piket.
            $table->unique(['duty_slot_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_claims');
    }
};
