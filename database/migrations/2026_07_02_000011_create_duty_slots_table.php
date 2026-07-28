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
        Schema::create('duty_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->date('duty_date');
            $table->integer('quota');
            $table->enum('status', ['open', 'aktif', 'tutup', 'penuh'])->default('open');
            $table->timestamps();
            $table->softDeletes();

            // Cegah slot piket ganda pada tanggal yang sama dalam satu semester.
            $table->unique(['semester_id', 'duty_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_slots');
    }
};
