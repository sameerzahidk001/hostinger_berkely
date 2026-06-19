<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_activity_logs')) {
            return;
        }

        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('audience', 20)->index();
            $table->string('action');
            $table->text('item')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('session_id', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
