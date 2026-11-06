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
            if (!Schema::hasColumn('reservations', 'is_checked_in')) {
                $table->boolean('is_checked_in')->default(false)->after('is_temporary');
            }
            if (!Schema::hasColumn('reservations', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('is_checked_in');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'checked_in_at')) {
                $table->dropColumn('checked_in_at');
            }
            if (Schema::hasColumn('reservations', 'is_checked_in')) {
                $table->dropColumn('is_checked_in');
            }
        });
    }
};

