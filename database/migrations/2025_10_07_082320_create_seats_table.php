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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditorium_id')->constrained('auditoriums')->cascadeOnDelete();
            $table->char('seat_row', 1);
            $table->unsignedInteger('seat_number');
            $table->timestamps();
            $table->unique(['auditorium_id', 'seat_row', 'seat_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
