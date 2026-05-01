<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('budget_reports', 'template_data')) {
            Schema::table('budget_reports', function (Blueprint $table) {
                $table->longText('template_data')->nullable()->after('generated_pdf_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('budget_reports', 'template_data')) {
            Schema::table('budget_reports', function (Blueprint $table) {
                $table->dropColumn('template_data');
            });
        }
    }
};
