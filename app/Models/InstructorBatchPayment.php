<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstructorBatchPayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'class_batch_id',
        'instructor_id',
        'rate',
        'amount',
        'currency',
        'status',
        'paid_date',
        'notes',
        'payment_slip_path',
        'created_by_admin_id',
        'created_by_user_id',
        'updated_by_admin_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_date' => 'date',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID => 'Paid',
        ];
    }

    public static function ensureTable(): bool
    {
        if (Schema::hasTable('instructor_batch_payments')) {
            return true;
        }

        Schema::create('instructor_batch_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_batch_id')->index();
            $table->unsignedBigInteger('instructor_id')->index();
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('AED');
            $table->string('status', 20)->default('pending')->index();
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_slip_path')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['class_batch_id', 'instructor_id'], 'ibp_batch_instructor_unique');
        });

        return Schema::hasTable('instructor_batch_payments');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClassBatch::class, 'class_batch_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'label-primary',
            self::STATUS_APPROVED => 'label-info',
            default => 'label-warning',
        };
    }

    public function hasSlip(): bool
    {
        return filled($this->payment_slip_path);
    }

    public function slipPublicUrl(): ?string
    {
        if (! $this->hasSlip()) {
            return null;
        }

        return asset(ltrim((string) $this->payment_slip_path, '/'));
    }

    public function formattedAmount(): string
    {
        $code = package_currency_code($this->currency);

        return $code . ' ' . number_format((float) $this->amount, 2);
    }

    public function formattedRate(): string
    {
        if ($this->rate === null || $this->rate === '') {
            return '—';
        }

        $code = package_currency_code($this->currency);

        return $code . ' ' . number_format((float) $this->rate, 2);
    }
}
