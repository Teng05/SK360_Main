<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('budget_reports', 'budget_period_type')) {
            Schema::table('budget_reports', function (Blueprint $table) {
                $table->enum('budget_period_type', ['monthly', 'quarterly', 'annual'])->default('annual')->after('document_type');
                $table->tinyInteger('fiscal_month')->nullable()->after('fiscal_year');
                $table->enum('fiscal_quarter', ['Q1', 'Q2', 'Q3', 'Q4'])->nullable()->after('fiscal_month');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('budget_reports', 'budget_period_type')) {
            Schema::table('budget_reports', function (Blueprint $table) {
                $table->dropColumn(['budget_period_type', 'fiscal_month', 'fiscal_quarter']);
            });
        }
    }
};
