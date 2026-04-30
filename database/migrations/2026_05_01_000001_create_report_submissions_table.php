<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_submissions', function (Blueprint $table) {
            $table->bigIncrements('report_submission_id');
            $table->unsignedBigInteger('user_id');
            $table->string('report_title');
            $table->string('submission_method', 20);
            $table->string('report_file_path')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_submissions');
    }
};
