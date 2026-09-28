<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'profile_pic')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('profile_pic')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep existing profile photos intact when rolling back application code.
    }
};
