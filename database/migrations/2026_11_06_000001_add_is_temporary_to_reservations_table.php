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
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'is_temporary')) {
                $table->boolean('is_temporary')->default(false)->after('is_full_table');
            }
            $table->string('email')->nullable()->change();
            $table->string('ticket_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'is_temporary')) {
                $table->dropColumn('is_temporary');
            }
            $table->string('email')->nullable(false)->change();
            $table->string('ticket_number')->nullable(false)->change();
        });
    }
};

