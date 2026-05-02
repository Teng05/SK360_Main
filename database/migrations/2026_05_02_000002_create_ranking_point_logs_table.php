<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ranking_point_logs')) {
            return;
        }

        Schema::create('ranking_point_logs', function (Blueprint $table) {
            $table->id('ranking_point_log_id');
            $table->integer('barangay_id');
            $table->integer('user_id')->nullable();
            $table->string('reporting_period', 50);
            $table->string('action', 50);
            $table->integer('points');
            $table->string('source_type', 50);
            $table->string('source_id', 100);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['reporting_period', 'barangay_id', 'action', 'source_type', 'source_id'], 'ranking_point_logs_unique_source');
            $table->index('barangay_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_point_logs');
    }
};
