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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->string('candidate_name');
            $table->string('student_id')->nullable();
            $table->string('class_name')->nullable();
            $table->string('programme')->nullable();
            $table->string('house')->nullable();
            $table->string('gender')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('manifesto')->nullable();
            $table->string('ballot_number')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['election_id', 'position_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
