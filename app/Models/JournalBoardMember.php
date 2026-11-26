<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JournalBoardMember extends Model
{
    protected $table = 'journal_editorial_board_members';

    protected $fillable = [
        'name', 'role_title', 'affiliation', 'orcid', 'email',
        'subject_id', 'is_active', 'sort_order', 'bio',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('journal_editorial_board_members')) {
                return true;
            }
            Schema::create('journal_editorial_board_members', function (Blueprint $table) {
                $table->id();
                $table->string('name', 180);
                $table->string('role_title', 120)->nullable();
                $table->string('affiliation', 255)->nullable();
                $table->string('orcid', 40)->nullable();
                $table->string('email', 180)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('bio')->nullable();
                $table->timestamps();
            });

            return Schema::hasTable('journal_editorial_board_members');
        } catch (\Throwable $e) {
            return Schema::hasTable('journal_editorial_board_members');
        }
    }

    public function subject()
    {
        return $this->belongsTo(JournalSubject::class, 'subject_id');
    }
}
