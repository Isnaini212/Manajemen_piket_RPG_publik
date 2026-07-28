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
        Schema::create('badge_rule_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('badges')->onDelete('cascade');
            $table->enum('logic_operator', ['AND', 'OR'])->default('AND');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_rule_groups');
    }
};
