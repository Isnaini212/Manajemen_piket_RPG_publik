<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE duty_claims MODIFY COLUMN status ENUM('pending', 'approved', 'failed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE duty_claims MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'failed') DEFAULT 'pending'");
    }
};
