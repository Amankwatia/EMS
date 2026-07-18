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
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('student_id');
            $table->string('full_name');
            $table->string('class_name')->nullable();
            $table->string('programme')->nullable();
            $table->string('house')->nullable();
            $table->string('gender')->nullable();
            $table->string('pin_hash');
            $table->boolean('is_eligible')->default(true);
            $table->boolean('has_voted')->default(false);
            $table->dateTime('voted_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['election_id', 'student_id']);
            $table->index(['election_id', 'has_voted', 'is_eligible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
