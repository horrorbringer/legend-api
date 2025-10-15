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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('rating', 10)->nullable();
            $table->string('genre', 50)->nullable();
            $table->string('poster_url')->nullable(); // URL to movie poster
            $table->string('type', 50)->nullable();  // e.g., '2D', '3D', 'IMAX'
            $table->date('release_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
