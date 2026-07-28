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
            $table->foreignId('from_student_id')->nullable()->constrained('student_profiles')->onDelete('cascade')->after('id');
        });

        // Isi kolom from_student_id untuk data lama berdasarkan from_claim_id
        \Illuminate\Support\Facades\DB::table('swap_requests')
            ->whereNull('from_student_id')
            ->join('duty_claims', 'swap_requests.from_claim_id', '=', 'duty_claims.id')
            ->update(['swap_requests.from_student_id' => \Illuminate\Support\Facades\DB::raw('duty_claims.student_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('swap_requests', function (Blueprint $table) {
            $table->dropForeign(['from_student_id']);
            $table->dropColumn('from_student_id');
        });
    }
};
