<?php

use App\Models\MeetingAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('meeting_accounts')) {
            Schema::create('meeting_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 16); // zoho | zoom
                $table->string('label');
                $table->string('host_email')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('credentials_json')->nullable();
                $table->string('timezone', 64)->default('Asia/Dubai');
                $table->timestamps();

                $table->index(['provider', 'is_active']);
                $table->index(['provider', 'is_default']);
            });
        }

        if (Schema::hasTable('class_schedules') && ! Schema::hasColumn('class_schedules', 'meeting_account_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('meeting_account_id')->nullable()->after('head_of_faculty_id');
                $table->index('meeting_account_id');
            });
        }

        MeetingAccount::ensureDefaultZohoFromEnv();
    }

    public function down(): void
    {
        if (Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', 'meeting_account_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('meeting_account_id');
            });
        }

        Schema::dropIfExists('meeting_accounts');
    }
};
