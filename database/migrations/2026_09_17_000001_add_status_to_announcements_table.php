<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('announcements') && ! Schema::hasColumn('announcements', 'status')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->string('status', 20)->default('published')->after('visibility');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'status')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
