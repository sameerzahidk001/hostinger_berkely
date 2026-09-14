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

    /**
     * Courses an actor may pick when creating folders / schedules.
     * Instructors only see courses admin assigned them to (courses.instructor_id).
     */
    public function coursesForActor()
    {
        if ($this->isAdminActor()) {
            return \App\Models\Course::query()
                ->where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title', 'slug', 'instructor_id']);
        }

        if (!$this->isInstructorActor()) {
            return collect();
        }

        return courses_for_instructor((int) $this->actorUserId())
            ->filter(function ($course) {
                return (int) ($course->status ?? 1) === 1;
            })
            ->values();
    }

    public function instructorAssignedToCourse(int $courseId): bool
    {
        if ($this->isAdminActor()) {
            return true;
        }

        if (!$this->isInstructorActor() || $courseId <= 0) {
            return false;
        }

        return $this->coursesForActor()->contains(fn ($course) => (int) $course->id === $courseId);
    }

    /**
     * Giving student access requires manage rights AND (for instructors) course assignment.
     */
    public function canAssignStudentAccess(StudyMaterialFolder $folder): bool
    {
        if (!$this->canManageFolder($folder)) {
            return false;
        }

        if ($this->isAdminActor()) {
            return true;
        }

        return $this->instructorAssignedToCourse((int) $folder->course_id);
    }

    /**
     * Students enrolled on a course via active/paid payments (not the full student directory).
     */
    public function studentsForCourse(?int $courseId, int $limit = 500)
    {
        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
            ->orderBy('name');

        if ($this->isAdminActor() && (!$courseId || $courseId <= 0)) {
            return $query->limit($limit)->get(['id', 'name', 'email']);
        }

        if (!$courseId || $courseId <= 0) {
            return collect();
        }

        if ($this->isInstructorActor() && !$this->instructorAssignedToCourse($courseId)) {
            return collect();
        }

        return $query
            ->whereHas('payments', function ($payments) use ($courseId) {
                $payments->where('course_id', $courseId)
                    ->whereIn('status', ['Active', 'active', 'Paid', 'paid', 'Partial', 'partial']);
            })
            ->limit($limit)
            ->get(['id', 'name', 'email']);
    }

    public function studentBelongsToCourse(int $studentId, int $courseId): bool
    {
        if ($studentId <= 0 || $courseId <= 0) {
            return false;
        }

        if ($this->isAdminActor()) {
            return User::query()
                ->whereKey($studentId)
                ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
                ->exists();
        }

        return User::query()
            ->whereKey($studentId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
            ->whereHas('payments', function ($payments) use ($courseId) {
                $payments->where('course_id', $courseId)
                    ->whereIn('status', ['Active', 'active', 'Paid', 'paid', 'Partial', 'partial']);
            })
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
        $courseId = (int) ($payment->course_id ?? 0);

        if ($studentId <= 0) {
            return 0;
        }

        $folders = collect();

        if ($packageId > 0) {
            $folders = StudyMaterialFolder::query()
                ->where('status', 'active')
                ->where(function ($q) use ($packageId) {
                    $q->where('fee_package_id', $packageId)
                        ->orWhereHas('feePackages', function ($packages) use ($packageId) {
                            $packages->where('course_fees.id', $packageId);
                        });
                })
                ->get();
        }

        // Fallback: active folders for the paid course when package link is missing.
        if ($folders->isEmpty() && $courseId > 0) {
            $folders = StudyMaterialFolder::query()
                ->where('status', 'active')
                ->where('course_id', $courseId)
                ->get();
        }

        $granted = 0;

        foreach ($folders as $folder) {
            $access = StudyMaterialStudentAccess::query()
                ->where('folder_id', $folder->id)
                ->where('student_id', $studentId)
                ->first();

            if ($access && $access->status === 'active' && $access->sent_at) {
                $granted++;
                continue;
            }

            if (!$access) {
                $issued = now();
                $till = $this->computeAccessTill($issued, $folder->validity_months);
                $access = StudyMaterialStudentAccess::create([
                    'folder_id' => $folder->id,
                    'student_id' => $studentId,
                    'status' => 'active',
                    'issued_at' => $issued->toDateString(),
                    'access_till' => $till?->toDateString(),
                    'sent_at' => null,
                ]);
            } else {
                if (! $access->issued_at) {
                    $access->issued_at = now()->toDateString();
                }
                if (! $access->access_till) {
                    $access->access_till = $this->computeAccessTill(
                        Carbon::parse($access->issued_at),
                        $folder->validity_months
                    )?->toDateString();
                }
                $access->status = 'active';
                $access->save();
            }

            // Access is granted even if the welcome email fails (SMTP/template issues).
            if (! $access->sent_at) {
                try {
                    $this->sendStudentAccessEmail($access->fresh(['folder.course', 'student', 'folder.instructorAccess.instructor']));
                } catch (Throwable $e) {
                    Log::warning('Folder access granted but email failed', [
                        'access_id' => $access->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $granted++;
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

    /**
     * Folders shown on student Study Materials + Dashboard.
     * Includes disabled access (grey card) so it is never silently removed.
     */
    public function studentPortalAccesses(int $studentId)
    {
        return StudyMaterialStudentAccess::with([
            'folder.course',
            'folder.instructorAccess.instructor',
        ])
            ->where('student_id', $studentId)
            ->whereHas('folder')
            ->where(function ($q) {
                $q->where('status', 'disabled')
                    ->orWhere(function ($open) {
                        $open->where('status', 'active')
                            ->where(function ($till) {
                                $till->whereNull('access_till')->orWhereDate('access_till', '>=', now()->toDateString());
                            });
                    });
            })
            ->latest()
            ->get();
    }

    /**
     * Folders shown on instructor Dashboard + Study Materials.
     * Includes disabled access (grey card) so it is never silently removed.
     */
    public function instructorPortalAccesses(int $instructorId)
    {
        return StudyMaterialInstructorAccess::with([
            'folder.course',
            'folder.instructorAccess.instructor',
        ])
            ->where('instructor_id', $instructorId)
            ->whereHas('folder')
            ->where(function ($q) {
                $q->where('status', 'disabled')
                    ->orWhere(function ($open) {
                        $open->where('status', 'active')
                            ->where(function ($till) {
                                $till->whereNull('access_till')->orWhereDate('access_till', '>=', now()->toDateString());
                            });
                    });
            })
            ->latest()
            ->get();
    }

    public function portalAccessesForUser($user)
    {
        if (! $user) {
            return collect();
        }

        if (method_exists($user, 'roles') && $user->roles()->where('name', 'instructor')->exists()) {
            return $this->instructorPortalAccesses((int) $user->id);
        }

        return $this->studentPortalAccesses((int) $user->id);
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

    public function instructorHasActiveAccess(int $instructorId, int $folderId): bool
    {
        return StudyMaterialInstructorAccess::query()
            ->where('instructor_id', $instructorId)
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

    public function userCanOpenFolder(int $userId, int $folderId): bool
    {
        return $this->studentHasActiveAccess($userId, $folderId)
            || $this->instructorHasActiveAccess($userId, $folderId);
    }
}
