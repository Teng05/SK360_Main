<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('accomplishment_reports', 'slot_id')) {
            Schema::table('accomplishment_reports', function (Blueprint $table) {
                $table->integer('slot_id')->nullable()->after('barangay_id');
                $table->index('slot_id');
            });
        }

        if (!Schema::hasColumn('budget_reports', 'slot_id')) {
            Schema::table('budget_reports', function (Blueprint $table) {
                $table->integer('slot_id')->nullable()->after('barangay_id');
                $table->index('slot_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('accomplishment_reports', 'slot_id')) {
            Schema::table('accomplishment_reports', function (Blueprint $table) {
                $table->dropIndex(['slot_id']);
                $table->dropColumn('slot_id');
            });
        }

        if (Schema::hasColumn('budget_reports', 'slot_id')) {
            Schema::table('budget_reports', function (Blueprint $table) {
                $table->dropIndex(['slot_id']);
                $table->dropColumn('slot_id');
            });
        }
    }
};
