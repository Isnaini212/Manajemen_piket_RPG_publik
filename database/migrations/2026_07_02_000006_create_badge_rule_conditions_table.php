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
        Schema::create('badge_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_group_id')->constrained('badge_rule_groups')->onDelete('cascade');
            $table->string('field');
            $table->string('operator');
            $table->string('value');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_rule_conditions');
    }
};
