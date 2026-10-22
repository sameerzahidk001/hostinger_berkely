<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ZohoCrmSyncService
{
    public function __construct(private ZohoCrmOAuthService $oauth)
    {
    }

    public function ensureSchema(): bool
    {
        try {
            if (Schema::hasTable('payments')) {
                Schema::table('payments', function (Blueprint $table) {
                    foreach ([
                        'zoho_crm_contact_id' => 'string',
                        'zoho_crm_deal_id' => 'string',
                        'zoho_crm_invoice_id' => 'string',
                        'zoho_crm_sync_status' => 'string',
                        'zoho_crm_last_error' => 'text',
                    ] as $col => $type) {
                        if (! Schema::hasColumn('payments', $col)) {
                            $type === 'text' ? $table->text($col)->nullable() : $table->string($col)->nullable();
                        }
                    }
                    if (! Schema::hasColumn('payments', 'zoho_crm_synced_at')) {
                        $table->timestamp('zoho_crm_synced_at')->nullable();
                    }
                });
            }

            if (Schema::hasTable('installments')) {
                Schema::table('installments', function (Blueprint $table) {
                    if (! Schema::hasColumn('installments', 'zoho_crm_receipt_id')) {
                        $table->string('zoho_crm_receipt_id')->nullable();
                    }
                    if (! Schema::hasColumn('installments', 'zoho_crm_sync_status')) {
                        $table->string('zoho_crm_sync_status')->nullable();
                    }
                    if (! Schema::hasColumn('installments', 'zoho_crm_last_error')) {
                        $table->text('zoho_crm_last_error')->nullable();
                    }
                    if (! Schema::hasColumn('installments', 'zoho_crm_synced_at')) {
                        $table->timestamp('zoho_crm_synced_at')->nullable();
                    }
                });
            }

            if (! Schema::hasTable('zoho_crm_sync_logs')) {
                Schema::create('zoho_crm_sync_logs', function (Blueprint $table) {
                    $table->id();
                    $table->string('entity_type', 40);
                    $table->unsignedBigInteger('entity_id')->nullable();
                    $table->string('action', 80)->nullable();
                    $table->boolean('success')->default(false);
                    $table->text('request_summary')->nullable();
                    $table->text('response_summary')->nullable();
                    $table->timestamps();
                });
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Zoho CRM schema ensure failed: ' . $e->getMessage());

            return false;
        }
    }

    public function syncPayment(Payment $payment, bool $attachPdf = true, bool $syncReceipts = true): bool
    {
        if (! $this->oauth->isEnabled()) {
            return false;
        }

        $this->ensureSchema();
        $payment->loadMissing([
            'user', 'course', 'courseFee', 'page', 'pageFeeService', 'pageFeePackage', 'installments',
        ]);

        try {
            if (! $payment->user) {
                throw new \RuntimeException('Payment has no student user.');
            }

            $contactId = $this->upsertContact($payment->user);
            $dealId = $this->upsertDeal($payment, $contactId);
            $invoiceId = $this->upsertInvoice($payment, $contactId, $dealId);

            if ($attachPdf && $invoiceId) {
                $this->attachInvoicePdf($payment, $invoiceId);
            }

            if ($syncReceipts) {
                foreach ($payment->installments as $installment) {
                    if ((float) ($installment->paid_amount ?? 0) > 0 || strtolower((string) $installment->status) === 'paid') {
                        $this->upsertReceipt($installment, false);
                    }
                }
            }

            $this->markPaymentSynced($payment, $contactId, $dealId, $invoiceId, null);
            $this->log('payment', $payment->id, 'syncPayment', true, 'INV-' . $this->pad($payment->id), $invoiceId);

            return true;
        } catch (\Throwable $e) {
            $this->markPaymentSynced($payment, null, null, null, $e->getMessage());
            $this->log('payment', $payment->id, 'syncPayment', false, null, $e->getMessage());
            Log::warning('Zoho CRM payment sync failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function upsertReceipt(Installment $installment, bool $attachPdf = true): bool
    {
        if (! $this->oauth->isEnabled()) {
            return false;
        }

        $this->ensureSchema();
        $installment->loadMissing([
            'payment.user', 'payment.course', 'payment.courseFee', 'payment.page', 'payment.pageFeePackage', 'user',
        ]);

        try {
            $payment = $installment->payment;
            if (! $payment) {
                throw new \RuntimeException('Installment has no payment.');
            }

            // Ensure parent invoice exists first (do not re-enter receipt upsert).
            $this->syncPayment($payment, false, false);

            $payment->refresh();
            $module = config('zoho_crm.modules.receipt');
            $receiptKey = 'RC-' . $this->pad($installment->id);
            $fieldReceipt = config('zoho_crm.fields.website_receipt_id');
            $payload = $this->buildReceiptPayload($installment, $receiptKey);

            $existingId = $installment->zoho_crm_receipt_id
                ?: $this->searchByField($module, $fieldReceipt, $receiptKey);

            if ($existingId) {
                $this->updateRecord($module, $existingId, $payload);
                $crmId = $existingId;
            } else {
                $crmId = $this->createRecord($module, $payload);
            }

            if ($attachPdf && $crmId) {
                $this->attachReceiptPdf($installment, $crmId);
            }

            $this->markInstallmentSynced($installment, $crmId, null);
            $this->log('installment', $installment->id, 'upsertReceipt', true, $receiptKey, $crmId);

            return true;
        } catch (\Throwable $e) {
            $this->markInstallmentSynced($installment, null, $e->getMessage());
            $this->log('installment', $installment->id, 'upsertReceipt', false, null, $e->getMessage());
            Log::warning('Zoho CRM receipt sync failed', [
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function cancelInvoiceInCrm(Payment $payment): bool
    {
        if (! $this->oauth->isEnabled()) {
            return false;
        }

        $this->ensureSchema();

        try {
            $module = config('zoho_crm.modules.invoice');
            $invoiceKey = 'INV-' . $this->pad($payment->id);
            $field = config('zoho_crm.fields.website_invoice_id');
            $crmId = $payment->zoho_crm_invoice_id ?: $this->searchByField($module, $field, $invoiceKey);

            if (! $crmId) {
                return true;
            }

            $statusField = config('zoho_crm.fields.invoice_status');
            $this->updateRecord($module, $crmId, [
                $statusField => 'Cancelled',
                $field => $invoiceKey,
            ]);

            $this->markPaymentSynced(
                $payment,
                $payment->zoho_crm_contact_id,
                $payment->zoho_crm_deal_id,
                $crmId,
                null,
                'cancelled'
            );
            $this->log('payment', $payment->id, 'cancelInvoice', true, $invoiceKey, $crmId);

            return true;
        } catch (\Throwable $e) {
            $this->markPaymentSynced($payment, null, null, null, $e->getMessage());
            $this->log('payment', $payment->id, 'cancelInvoice', false, null, $e->getMessage());

            return false;
        }
    }

    public function upsertContact(User $user): string
    {
        $module = config('zoho_crm.modules.contact');
        $email = trim((string) $user->email);
        if ($email === '') {
            throw new \RuntimeException('Student email is required for CRM Contact.');
        }

        $existing = $this->searchContactByEmail($email);
        $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $payload = [
            'Email' => $email,
            'Last_Name' => $parts[1] ?? ($parts[0] ?: 'Student'),
            'First_Name' => isset($parts[1]) ? ($parts[0] ?? null) : null,
            'Phone' => $user->mobile_number ?: null,
            'Mailing_Street' => $user->address ?: null,
            'Mailing_City' => $user->city ?: null,
            'Mailing_Zip' => $user->post_code ?: null,
            'Mailing_Country' => $user->country ?: null,
        ];
        $payload = array_filter($payload, fn ($v) => $v !== null && $v !== '');

        if ($existing) {
            $this->updateRecord($module, $existing, $payload);

            return $existing;
        }

        return $this->createRecord($module, $payload);
    }

    public function upsertDeal(Payment $payment, string $contactId): ?string
    {
        $module = config('zoho_crm.modules.deal');
        $invoiceKey = 'INV-' . $this->pad($payment->id);
        $dealName = $payment->programTitle() . ' — ' . $invoiceKey;
        $packageName = $payment->feePackage()?->package_name ?? '';

        $payload = [
            'Deal_Name' => mb_substr($dealName, 0, 120),
            'Stage' => $this->dealStage($payment),
            'Amount' => (float) ($payment->price ?? 0),
            'Currency' => $payment->currency ?: 'AED',
            'Closing_Date' => optional($payment->created_at)->toDateString() ?: now()->toDateString(),
            'Contact_Name' => ['id' => $contactId],
            config('zoho_crm.fields.website_invoice_id') => $invoiceKey,
            config('zoho_crm.fields.package_name') => $packageName,
            config('zoho_crm.fields.balance_due') => $this->balanceDue($payment),
        ];

        $existing = $payment->zoho_crm_deal_id
            ?: $this->searchByField($module, config('zoho_crm.fields.website_invoice_id'), $invoiceKey);

        if ($existing) {
            $this->updateRecord($module, $existing, $payload);

            return $existing;
        }

        return $this->createRecord($module, $payload);
    }

    public function upsertInvoice(Payment $payment, string $contactId, ?string $dealId): string
    {
        $module = config('zoho_crm.modules.invoice');
        $invoiceKey = 'INV-' . $this->pad($payment->id);
        $field = config('zoho_crm.fields.website_invoice_id');
        $payload = $this->buildInvoicePayload($payment, $invoiceKey, $contactId, $dealId);

        $existing = $payment->zoho_crm_invoice_id
            ?: $this->searchByField($module, $field, $invoiceKey);

        if ($existing) {
            $this->updateRecord($module, $existing, $payload);

            return $existing;
        }

        return $this->createRecord($module, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildInvoicePayload(Payment $payment, string $invoiceKey, string $contactId, ?string $dealId): array
    {
        $fee = $payment->feePackage();
        $installments = $payment->installments ?? collect();
        $plan = $installments->map(function (Installment $row) {
            return 'Installment ' . $row->installment_number
                . ' due ' . optional($row->due_date)->format('Y-m-d')
                . ' remaining ' . number_format((float) $row->remaining_amount, 2);
        })->implode('; ');

        $payload = [
            'Subject' => $invoiceKey . ' — ' . $payment->programTitle(),
            'Invoice_Date' => optional($payment->created_at)->toDateString() ?: now()->toDateString(),
            'Contact_Name' => ['id' => $contactId],
            'Grand_Total' => (float) ($payment->price ?? 0),
            'Currency' => $payment->currency ?: 'AED',
            'Description' => $plan,
            config('zoho_crm.fields.website_invoice_id') => $invoiceKey,
            config('zoho_crm.fields.package_name') => $fee?->package_name,
            config('zoho_crm.fields.balance_due') => $this->balanceDue($payment),
            config('zoho_crm.fields.invoice_status') => payment_invoice_status($payment),
            config('zoho_crm.fields.installment_no') => $installments->count() . ' of ' . max(1, (int) ($payment->total_installment ?: $installments->count())),
        ];

        if ($dealId) {
            $payload['Deal_Name__s'] = ['id' => $dealId];
        }

        return array_filter($payload, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReceiptPayload(Installment $installment, string $receiptKey): array
    {
        $payment = $installment->payment;
        $invoiceKey = 'INV-' . $this->pad((int) $payment->id);
        $balanceAfter = max(0, (float) ($payment->price ?? 0) - (float) $payment->installments->sum('paid_amount'));

        return array_filter([
            'Name' => $receiptKey,
            'Description' => 'Receipt for ' . $invoiceKey . ' installment #' . $installment->installment_number,
            config('zoho_crm.fields.website_receipt_id') => $receiptKey,
            config('zoho_crm.fields.linked_invoice') => $invoiceKey,
            config('zoho_crm.fields.installment_no') => (string) $installment->installment_number
                . ' of ' . max(1, (int) ($payment->total_installment ?: $payment->installments->count())),
            'Amount' => (float) ($installment->paid_amount ?? 0),
            'Paid_Date' => optional($installment->paid_date)->toDateString() ?: now()->toDateString(),
            config('zoho_crm.fields.gateway') => $installment->payment_method ?: 'manual',
            config('zoho_crm.fields.balance_due') => $balanceAfter,
            config('zoho_crm.fields.package_name') => $payment->feePackage()?->package_name,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function attachInvoicePdf(Payment $payment, string $crmRecordId): void
    {
        $payment->loadMissing(['user.roles', 'course', 'courseFee', 'page', 'pageFeePackage', 'installments']);
        $payments = $payment;
        $installments = $payment->installments;
        $user = $payment->user;
        $course = $payment->course;
        $coursefee = $payment->isPageFee() ? $payment->pageFeePackage : $payment->courseFee;
        $programTitle = $payment->programTitle();
        $invoiceNo = 'INV-' . $this->pad($payment->id);

        $pdf = PDF::loadView(
            'admin.payments.invoice-pdf',
            compact('payments', 'installments', 'user', 'course', 'coursefee', 'programTitle')
        )->setPaper('a4');

        $this->attachFile(
            config('zoho_crm.modules.invoice'),
            $crmRecordId,
            $invoiceNo . '.pdf',
            $pdf->output()
        );
    }

    public function attachReceiptPdf(Installment $installment, string $crmRecordId): void
    {
        $installment->loadMissing(['payment.course', 'payment.courseFee', 'payment.page', 'payment.pageFeePackage', 'user']);
        try {
            $pdf = PDF::loadView('admin.payments.receipt-pdf', compact('installment'))->setPaper('a4');
            $this->attachFile(
                config('zoho_crm.modules.receipt'),
                $crmRecordId,
                'RC-' . $this->pad($installment->id) . '.pdf',
                $pdf->output()
            );
        } catch (\Throwable $e) {
            Log::warning('Zoho CRM receipt PDF attach skipped: ' . $e->getMessage());
        }
    }

    public function retryFailed(int $limit = 50): int
    {
        if (! $this->oauth->isEnabled()) {
            return 0;
        }

        $this->ensureSchema();
        $done = 0;

        if (Schema::hasColumn('payments', 'zoho_crm_sync_status')) {
            Payment::query()
                ->where(function ($q) {
                    $q->whereNull('zoho_crm_sync_status')
                        ->orWhereIn('zoho_crm_sync_status', ['failed', 'pending']);
                })
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->each(function (Payment $payment) use (&$done) {
                    if ($this->syncPayment($payment)) {
                        $done++;
                    }
                });
        }

        if (Schema::hasColumn('installments', 'zoho_crm_sync_status')) {
            Installment::query()
                ->where(function ($q) {
                    $q->where('status', 'paid')
                        ->orWhere('paid_amount', '>', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('zoho_crm_sync_status')
                        ->orWhereIn('zoho_crm_sync_status', ['failed', 'pending']);
                })
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->each(function (Installment $installment) use (&$done) {
                    if ($this->upsertReceipt($installment)) {
                        $done++;
                    }
                });
        }

        return $done;
    }

    /**
     * Backfill Active / Partial / Paid invoices and their paid receipts.
     */
    public function backfill(?string $since = null, int $limit = 200): int
    {
        if (! $this->oauth->isEnabled()) {
            return 0;
        }

        $this->ensureSchema();
        $query = Payment::query()
            ->with(['installments', 'user', 'course', 'courseFee', 'page', 'pageFeePackage'])
            ->where(function ($q) {
                // Active invoices on LMS, plus any with payment activity (Partial/Paid).
                $q->where('status', 'Active')
                    ->orWhereHas('installments', function ($iq) {
                        $iq->where('paid_amount', '>', 0)->orWhere('status', 'paid');
                    });
            })
            ->orderBy('id');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $done = 0;
        $query->limit($limit)->get()->each(function (Payment $payment) use (&$done) {
            if ($this->syncPayment($payment)) {
                $done++;
            }
        });

        return $done;
    }

    private function searchContactByEmail(string $email): ?string
    {
        $module = config('zoho_crm.modules.contact');
        $response = $this->oauth->http()->get(
            rtrim((string) config('zoho_crm.api_base'), '/') . '/' . $module . '/search',
            ['email' => $email]
        );

        if ($response->status() === 204) {
            return null;
        }

        if (! $response->successful()) {
            // Fallback criteria search
            return $this->searchByField($module, 'Email', $email);
        }

        return data_get($response->json(), 'data.0.id');
    }

    private function searchByField(string $module, string $field, string $value): ?string
    {
        $criteria = '(' . $field . ':equals:' . $this->escapeCriteria($value) . ')';
        $response = $this->oauth->http()->get(
            rtrim((string) config('zoho_crm.api_base'), '/') . '/' . $module . '/search',
            ['criteria' => $criteria]
        );

        if ($response->status() === 204 || ! $response->successful()) {
            return null;
        }

        return data_get($response->json(), 'data.0.id');
    }

    private function createRecord(string $module, array $data): string
    {
        $response = $this->oauth->http()->post(
            rtrim((string) config('zoho_crm.api_base'), '/') . '/' . $module,
            ['data' => [$data]]
        );

        $this->assertZohoWrite($response, 'create ' . $module);

        $id = data_get($response->json(), 'data.0.details.id');
        if (! $id) {
            throw new \RuntimeException('CRM create ' . $module . ' returned no id: ' . $response->body());
        }

        return (string) $id;
    }

    private function updateRecord(string $module, string $id, array $data): void
    {
        $response = $this->oauth->http()->put(
            rtrim((string) config('zoho_crm.api_base'), '/') . '/' . $module,
            ['data' => [array_merge(['id' => $id], $data)]]
        );

        $this->assertZohoWrite($response, 'update ' . $module);
    }

    private function attachFile(string $module, string $recordId, string $filename, string $binary): void
    {
        $response = $this->oauth->http()
            ->asMultipart()
            ->attach('file', $binary, $filename)
            ->post(rtrim((string) config('zoho_crm.api_base'), '/') . '/' . $module . '/' . $recordId . '/Attachments');

        if (! $response->successful()) {
            Log::warning('Zoho CRM attachment failed', [
                'module' => $module,
                'record' => $recordId,
                'body' => $response->body(),
            ]);
        }
    }

    private function assertZohoWrite($response, string $action): void
    {
        $json = $response->json();
        $status = strtolower((string) data_get($json, 'data.0.status', ''));
        $code = (string) data_get($json, 'data.0.code', '');
        $message = (string) (data_get($json, 'data.0.message') ?: data_get($json, 'message') ?: $response->body());

        if (! $response->successful() || $status === 'error' || ($code !== '' && strtoupper($code) !== 'SUCCESS')) {
            throw new \RuntimeException('CRM ' . $action . ' failed: ' . $message);
        }
    }

    private function balanceDue(Payment $payment): float
    {
        $paid = (float) ($payment->installments?->sum('paid_amount') ?? 0);

        return max(0, (float) ($payment->price ?? 0) - $paid);
    }

    private function dealStage(Payment $payment): string
    {
        return match (payment_invoice_status($payment)) {
            'Paid' => 'Closed Won',
            'Partial' => 'Qualification',
            default => 'Qualification',
        };
    }

    private function pad(int $id): string
    {
        return str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    private function escapeCriteria(string $value): string
    {
        return str_replace(['(', ')'], ['\\(', '\\)'], $value);
    }

    private function markPaymentSynced(
        Payment $payment,
        ?string $contactId,
        ?string $dealId,
        ?string $invoiceId,
        ?string $error,
        string $status = 'synced'
    ): void {
        if (! Schema::hasColumn('payments', 'zoho_crm_sync_status')) {
            return;
        }

        $attrs = [
            'zoho_crm_sync_status' => $error ? 'failed' : $status,
            'zoho_crm_last_error' => $error,
            'zoho_crm_synced_at' => $error ? null : now(),
        ];
        if ($contactId) {
            $attrs['zoho_crm_contact_id'] = $contactId;
        }
        if ($dealId) {
            $attrs['zoho_crm_deal_id'] = $dealId;
        }
        if ($invoiceId) {
            $attrs['zoho_crm_invoice_id'] = $invoiceId;
        }

        $payment->forceFill($attrs)->save();
    }

    private function markInstallmentSynced(Installment $installment, ?string $receiptId, ?string $error): void
    {
        if (! Schema::hasColumn('installments', 'zoho_crm_sync_status')) {
            return;
        }

        $attrs = [
            'zoho_crm_sync_status' => $error ? 'failed' : 'synced',
            'zoho_crm_last_error' => $error,
            'zoho_crm_synced_at' => $error ? null : now(),
        ];
        if ($receiptId) {
            $attrs['zoho_crm_receipt_id'] = $receiptId;
        }

        $installment->forceFill($attrs)->save();
    }

    private function log(
        string $entityType,
        ?int $entityId,
        string $action,
        bool $success,
        ?string $request,
        ?string $response
    ): void {
        if (! Schema::hasTable('zoho_crm_sync_logs')) {
            return;
        }

        try {
            DB::table('zoho_crm_sync_logs')->insert([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'success' => $success,
                'request_summary' => $request ? Str::limit($request, 2000) : null,
                'response_summary' => $response ? Str::limit($response, 2000) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore log write failures
        }
    }
}
