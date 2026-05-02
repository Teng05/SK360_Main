<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wall_post_likes')) {
            return;
        }

        Schema::create('wall_post_likes', function (Blueprint $table) {
            $table->id('wall_post_like_id');
            $table->integer('announcement_id');
            $table->integer('user_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['announcement_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wall_post_likes');
    }
};
