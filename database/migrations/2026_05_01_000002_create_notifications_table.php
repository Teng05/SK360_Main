<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('notification_id');
            $table->integer('user_id');
            $table->integer('actor_id')->nullable();
            $table->string('type', 50);
            $table->string('title', 255);
            $table->text('message');
            $table->string('url', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('read_at')->nullable();

            $table->index(['user_id', 'is_read']);
        });

        DB::statement("
            ALTER TABLE notifications
            ADD CONSTRAINT notifications_user_fk FOREIGN KEY (user_id) REFERENCES users(user_id)
        ");

        DB::statement("
            ALTER TABLE notifications
            ADD CONSTRAINT notifications_actor_fk FOREIGN KEY (actor_id) REFERENCES users(user_id)
        ");

        DB::statement("
            ALTER TABLE events
            MODIFY visibility ENUM('public','officials_only','chairman_only','secretary_only') DEFAULT 'public'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE events
            MODIFY visibility ENUM('public','officials_only') DEFAULT 'public'
        ");

        Schema::dropIfExists('notifications');
    }
};
