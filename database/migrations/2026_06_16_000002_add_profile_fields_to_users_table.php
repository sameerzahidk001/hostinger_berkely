<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'mobile_number' => fn () => $table->string('mobile_number', 30)->nullable()->after('email'),
                'short_description' => fn () => $table->text('short_description')->nullable(),
                'image' => fn () => $table->string('image')->nullable(),
                'gender' => fn () => $table->string('gender', 20)->nullable(),
                'date_of_birth' => fn () => $table->date('date_of_birth')->nullable(),
                'address' => fn () => $table->string('address')->nullable(),
                'post_code' => fn () => $table->string('post_code', 20)->nullable(),
                'nationality' => fn () => $table->string('nationality', 100)->nullable(),
                'city' => fn () => $table->string('city', 100)->nullable(),
                'country' => fn () => $table->string('country', 100)->nullable(),
                'experience' => fn () => $table->string('experience')->nullable(),
                'education' => fn () => $table->json('education')->nullable(),
                'linkedin' => fn () => $table->string('linkedin')->nullable(),
                'ip_address' => fn () => $table->string('ip_address', 45)->nullable(),
                'approved' => fn () => $table->boolean('approved')->default(1),
                'is_on_web' => fn () => $table->boolean('is_on_web')->default(0),
            ];

            foreach ($columns as $name => $callback) {
                if (!Schema::hasColumn('users', $name)) {
                    $callback();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_number', 'short_description', 'image', 'gender', 'date_of_birth',
                'address', 'post_code', 'nationality', 'city', 'country', 'experience',
                'education', 'linkedin', 'ip_address', 'approved', 'is_on_web',
            ]);
        });
    }
};
