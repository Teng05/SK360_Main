<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mobile_contact_changes')) {
            Schema::create('mobile_contact_changes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('change_type', 20);
                $table->string('new_value');
                $table->string('token');
                $table->timestamp('expires_at');
                $table->timestamps();
                $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_contact_changes');
    }
};
