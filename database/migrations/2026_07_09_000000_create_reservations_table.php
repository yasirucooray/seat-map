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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // platinum, gold, standing
            $table->string('side')->nullable(); // left, right, center
            $table->integer('table_number')->nullable();
            $table->integer('seat_number')->nullable(); // 1-8, null for full table or standing
            $table->boolean('is_full_table')->default(false);
            $table->string('name');
            $table->string('email');
            $table->string('ticket_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
