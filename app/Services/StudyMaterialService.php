<?php

namespace App\Services;

use App\Mail\UserMail;
use App\Models\Email;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\StudyMaterialFolder;
use App\Models\StudyMaterialInstructorAccess;
use App\Models\StudyMaterialStudentAccess;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\StudyMaterialEmailTemplateSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StudyMaterialService
{
    public function isAdminActor(): bool
    {
        return Auth::guard('admin')->check();
    }

    public function isInstructorActor(): bool
    {
        if (Auth::guard('admin')->check()) {
            return false;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->roles()->where('name', 'instructor')->exists();
    }

    public function actorUserId(): ?int
    {
        return Auth::id();
    }

    public function foldersQueryForActor()
    {
        $query = StudyMaterialFolder::query()
            ->with(['course', 'feePackage', 'feePackages', 'instructorAccess.instructor', 'studentAccess']);

        if ($this->isAdminActor()) {
            return $query->latest();
        }

        $userId = $this->actorUserId();

        return $query->where(function ($q) use ($userId) {
            $q->where(function ($owned) use ($userId) {
                $owned->where('owner_type', 'instructor')->where('owner_id', $userId);
            })->orWhereHas('instructorAccess', function ($access) use ($userId) {
                $access->where('instructor_id', $userId)->where('status', 'active');
            });
        })->latest();
    }

    public function canManageFolder(StudyMaterialFolder $folder): bool
    {
        if ($this->isAdminActor()) {
            return true;
        }

        if (!$this->isInstructorActor()) {
            return false;
        }

        $userId = $this->actorUserId();

        if ($folder->owner_type === 'instructor' && (int) $folder->owner_id === (int) $userId) {
            return true;
        }

        return $folder->instructorAccess()
            ->where('instructor_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function canEnableFolder(): bool
    {
        return $this->isAdminActor();
    }

    public function canDisableFolder(StudyMaterialFolder $folder): bool
    {
        return $this->canManageFolder($folder);
    }

    public function computeAccessTill(?Carbon $issuedAt, ?int $validityMonths): ?Carbon
    {
        if ($validityMonths === null || $validityMonths <= 0) {
            return null;
        }

        $from = $issuedAt ? $issuedAt->copy() : now();

        return $from->copy()->addMonths(max(1, min(24, $validityMonths)));
    }

    public function resolveEmailTemplate(array $names): ?Email
    {
        $this->ensureEmailTemplates();

        foreach ($names as $name) {
            $template = Email::query()->where('name', $name)->first();
            if ($template) {
                return $template;
            }
        }

        return null;
    }

    public function ensureEmailTemplates(): void
    {
        (new StudyMaterialEmailTemplateSeeder())->run();
    }

    public function sendFolderStudentEmails(StudyMaterialFolder $folder): array
    {
        $accesses = $folder->studentAccess()->with(['student', 'folder.course'])->get();

        return $this->sendAccessCollection($accesses, fn ($access) => $this->sendStudentAccessEmail($access));
    }

    public function sendFolderInstructorEmails(StudyMaterialFolder $folder): array
    {
        $accesses = $folder->instructorAccess()->with(['instructor', 'folder.course'])->get();

        return $this->sendAccessCollection($accesses, fn ($access) => $this->sendInstructorAccessEmail($access));
    }

    protected function sendAccessCollection($accesses, callable $sender): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($accesses as $access) {
            try {
                if ($sender($access)) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                Log::error('Study material folder email failed', ['message' => $e->getMessage()]);
                $failed++;
            }
        }

        return [
            'total' => $accesses->count(),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    public function sendInstructorAccessEmail(StudyMaterialInstructorAccess $access): bool
    {
        $access->loadMissing(['folder.course', 'instructor']);
        $user = $access->instructor;
        $folder = $access->folder;

        if (!$user?->email || !$folder) {
            return false;
        }

        $template = $this->resolveEmailTemplate([
            'study-material-instructor-access',
            'Study Material Instructor Access',
            'folder-instructor-access',
        ]);

        if (!$template) {
            return false;
        }

        $values = [
            '{name}' => $user->name,
            '{email}' => $user->email,
            '{folder_name}' => $folder->name,
            '{course_name}' => $folder->course->title ?? $folder->course->name ?? '',
            '{validity}' => $folder->validityLabel(),
            '{access_till}' => optional($access->access_till)->format('d M Y') ?? 'None',
            '{issued_at}' => optional($access->issued_at)->format('d M Y') ?? '',
            '{portal_url}' => url('/admin/study-materials/folders'),
            '{login_url}' => url('/admin/login'),
        ];

        try {
            $this->dispatchMail(
                $user,
                $template,
                $this->replacePlaceholders($template->subject, $values),
                $this->replacePlaceholders($template->body, $values)
            );
        } catch (Throwable $e) {
            Log::error('Instructor folder access email failed', ['message' => $e->getMessage()]);
            return false;
        }

        $access->status = 'active';
        $access->sent_at = now();
        $access->save();

        return true;
    }

    public function sendStudentAccessEmail(StudyMaterialStudentAccess $access): bool
    {
        $access->loadMissing(['folder.course', 'student', 'folder.instructorAccess.instructor']);
        $user = $access->student;
        $folder = $access->folder;

        if (!$user?->email || !$folder) {
            return false;
        }

        $template = $this->resolveEmailTemplate([
            'study-material-student-access',
            'Study Material Student Access',
            'folder-student-access',
        ]);

        if (!$template) {
            return false;
        }

        $values = $this->studentPlaceholderValues($access);

        try {
            $this->dispatchMail(
                $user,
                $template,
                $this->replacePlaceholders($template->subject, $values),
                $this->replacePlaceholders($template->body, $values)
            );
        } catch (Throwable $e) {
            Log::error('Student folder access email failed', ['message' => $e->getMessage()]);
            return false;
        }

        $access->status = 'active';
        $access->sent_at = now();
        $access->save();

        return true;
    }

    public function sendStudentDisabledEmail(StudyMaterialStudentAccess $access, string $reason = 'disabled'): bool
    {
        $access->loadMissing(['folder.course', 'student', 'folder.instructorAccess.instructor']);
        $user = $access->student;
        $folder = $access->folder;

        if (!$user?->email || !$folder) {
            return false;
        }

        $template = $this->resolveEmailTemplate([
            'study-material-student-disabled',
            'Study Material Student Disabled',
            'folder-student-disabled',
        ]);

        if (!$template) {
            return false;
        }

        $reasonText = match ($reason) {
            'deleted' => 'removed because the folder was deleted',
            'folder_disabled' => 'disabled because the folder is no longer available',
            default => 'disabled',
        };

        $values = $this->studentPlaceholderValues($access, [
            '{reason}' => $reasonText,
        ]);

        try {
            $this->dispatchMail(
                $user,
                $template,
                $this->replacePlaceholders($template->subject, $values),
                $this->replacePlaceholders($template->body, $values)
            );
        } catch (Throwable $e) {
            Log::error('Student folder disabled email failed', ['message' => $e->getMessage()]);
            return false;
        }

        return true;
    }

    public function notifyAssignedStudentsDisabled(StudyMaterialFolder $folder, string $reason = 'disabled'): int
    {
        $accesses = $folder->studentAccess()
            ->where('status', 'active')
            ->with(['student', 'folder.course', 'folder.instructorAccess.instructor'])
            ->get();

        $sent = 0;

        foreach ($accesses as $access) {
            try {
                if ($this->sendStudentDisabledEmail($access, $reason)) {
                    $sent++;
                }
            } catch (Throwable $e) {
                Log::error('Study material disabled email failed', ['message' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    public function tryGrantAccessForPaidPayment(Payment $payment): int
    {
        try {
            return $this->grantAccessForPaidPayment($payment);
        } catch (Throwable $e) {
            Log::error('Grant study folder access after payment failed', [
                'payment_id' => $payment->id ?? null,
                'message' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    public function grantAccessForPaidPayment(Payment $payment): int
    {
        $payment->loadMissing(['installments', 'courseFee', 'user']);

        $hasPaidInstallment = $payment->installments->contains(
            fn ($row) => $row->status === 'paid' || (float) $row->paid_amount > 0
        );

        if (payment_invoice_status($payment) !== 'Paid' && ! $hasPaidInstallment) {
            return 0;
        }

        $studentId = (int) $payment->user_id;
        $packageId = (int) ($payment->package_id ?? 0);

        if ($studentId <= 0 || $packageId <= 0) {
            return 0;
        }

        $folders = StudyMaterialFolder::query()
            ->where('status', 'active')
            ->where(function ($q) use ($packageId) {
                $q->where('fee_package_id', $packageId)
                    ->orWhereHas('feePackages', function ($packages) use ($packageId) {
                        $packages->where('course_fees.id', $packageId);
                    });
            })
            ->get();

        $granted = 0;

        foreach ($folders as $folder) {
            $access = StudyMaterialStudentAccess::query()
                ->where('folder_id', $folder->id)
                ->where('student_id', $studentId)
                ->first();

            if ($access && $access->status === 'active' && $access->sent_at) {
                continue;
            }

            if (!$access) {
                $issued = now();
                $till = $this->computeAccessTill($issued, $folder->validity_months);
                $access = StudyMaterialStudentAccess::create([
                    'folder_id' => $folder->id,
                    'student_id' => $studentId,
                    'status' => 'disabled',
                    'issued_at' => $issued->toDateString(),
                    'access_till' => $till?->toDateString(),
                    'sent_at' => null,
                ]);
            }

            if ($this->sendStudentAccessEmail($access)) {
                $granted++;
            }
        }

        return $granted;
    }

    public function tryGrantAccessFromInstallment(Installment $installment): int
    {
        $installment->loadMissing('payment');

        if (!$installment->payment) {
            return 0;
        }

        return $this->tryGrantAccessForPaidPayment($installment->payment);
    }

    protected function studentPlaceholderValues(StudyMaterialStudentAccess $access, array $extra = []): array
    {
        $user = $access->student;
        $folder = $access->folder;
        $instructorName = '';

        if ($folder) {
            $instructorName = $folder->instructorAccess
                ->where('status', 'active')
                ->pluck('instructor.name')
                ->filter()
                ->first() ?? $folder->ownerName();
        }

        return array_merge([
            '{name}' => $user->name ?? '',
            '{email}' => $user->email ?? '',
            '{folder_name}' => $folder->name ?? '',
            '{course_name}' => $folder?->course?->title ?? $folder?->course?->name ?? '',
            '{instructor_name}' => $instructorName ?? '',
            '{validity}' => $folder ? $folder->validityLabel() : '',
            '{access_till}' => optional($access->access_till)->format('d M Y') ?? 'None',
            '{issued_at}' => optional($access->issued_at)->format('d M Y') ?? '',
            '{portal_url}' => url('/user/study-materials'),
            '{login_url}' => url('/login'),
            '{reason}' => 'disabled',
        ], $extra);
    }

    protected function replacePlaceholders(?string $text, array $values): string
    {
        return str_replace(array_keys($values), array_values($values), (string) $text);
    }

    protected function dispatchMail(User $user, Email $template, string $subject, string $body): void
    {
        $ccEmails = !empty($template->cc) ? array_filter(array_map('trim', explode(',', $template->cc))) : [];
        $bccEmails = !empty($template->bcc) ? array_filter(array_map('trim', explode(',', $template->bcc))) : [];

        Mail::to($user->email)
            ->cc($ccEmails)
            ->bcc($bccEmails)
            ->send(new UserMail($user, $subject, $body));
    }

    public function studentHasActiveAccess(int $studentId, int $folderId): bool
    {
        return StudyMaterialStudentAccess::query()
            ->where('student_id', $studentId)
            ->where('folder_id', $folderId)
            ->where('status', 'active')
            ->whereHas('folder', function ($folder) {
                $folder->where('status', 'active');
            })
            ->where(function ($q) {
                $q->whereNull('access_till')->orWhereDate('access_till', '>=', now()->toDateString());
            })
            ->exists();
    }
}
