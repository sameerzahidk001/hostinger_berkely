<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\MeetingAccount;
use App\Models\SiteSettings;
use App\Models\User;
use App\Services\MeetingLinkService;
use App\Services\StudyMaterialService;
use App\Services\ZohoLmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassScheduleController extends Controller
{
    public function __construct(
        protected StudyMaterialService $lms,
        protected ZohoLmsService $zoho,
        protected MeetingLinkService $meetings
    ) {
    }

    public function index()
    {
        if (! Schema::hasTable('class_schedules')) {
            return redirect()
                ->route('admin.lms.install')
                ->with('fail', 'LMS tables are missing. Create them here (do not use Ignition Run Migrations).');
        }

        $batchList = $this->batchesForActor()
            ->load(['course', 'headOfFaculty', 'instructors'])
            ->loadCount(['schedules', 'students']);

        $calendarEvents = $this->calendarQuery()
            ->get()
            ->flatMap(fn (ClassSchedule $row) => $row->toFullCalendarEvent(route('admin.class-schedules.edit', $row->id)))
            ->values();
        $zohoEmbed = $this->zohoCalendarEmbedUrl();

        return view('admin.study-materials.schedules.index', [
            'batchList' => $batchList,
            'calendarEvents' => $calendarEvents,
            'zohoEmbed' => $zohoEmbed,
            'isAdmin' => $this->lms->isAdminActor(),
        ]);
    }

    public function showBatch($batchId)
    {
        if (! Schema::hasTable('class_schedules')) {
            return redirect()->route('admin.lms.install');
        }

        $batchModel = ClassBatch::with(['course', 'headOfFaculty', 'instructors', 'students'])->findOrFail((int) $batchId);
        $this->assertCanUseBatch($batchModel);

        $sessions = ClassSchedule::with(['course', 'instructor', 'headOfFaculty', 'students', 'meetingAccount', 'batch.course', 'batch.headOfFaculty'])
            ->where('batch_id', $batchModel->id)
            ->orderBy('scheduled_at')
            ->get();

        // Safety net: create Join links for scheduled sessions still stuck on "Link soon".
        $missingIds = $sessions
            ->filter(function (ClassSchedule $row) {
                return blank($row->zoho_link)
                    && ! in_array(strtolower((string) ($row->status ?: 'scheduled')), ['cancelled', 'completed'], true);
            })
            ->take(10)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $backfillNote = null;
        $backfillOk = false;
        if ($missingIds !== []) {
            $backfill = $this->attachMeetingsForSeries($missingIds);
            $backfillOk = ($backfill['meeting'] ?? '') === 'created';
            if ($backfillOk) {
                $backfillNote = 'Missing Join link(s) were created automatically.';
            } elseif (! empty($backfill['error'])) {
                $backfillNote = 'Could not auto-create Join link: ' . $backfill['error'];
            }
            $sessions = ClassSchedule::with(['course', 'instructor', 'headOfFaculty', 'students', 'meetingAccount', 'batch.course', 'batch.headOfFaculty'])
                ->where('batch_id', $batchModel->id)
                ->orderBy('scheduled_at')
                ->get();
        }

        $group = $this->batchListGroups($sessions)->first();
        if (! $group) {
            $group = [
                'batch_id' => $batchModel->id,
                'batch_code' => $batchModel->code,
                'batch_name' => $batchModel->name,
                'course' => $batchModel->course,
                'instructor' => $batchModel->instructors->first(),
                'head_of_faculty' => $batchModel->headOfFaculty,
                'sessions' => collect(),
                'students' => $batchModel->students,
                'primary' => null,
                'latest_at' => 0,
            ];
        }

        if ($backfillNote && ! session()->has('success') && ! session()->has('fail')) {
            session()->flash($backfillOk ? 'success' : 'fail', $backfillNote);
        }

        return view('admin.study-materials.schedules.show', [
            'batch' => $group,
            'batchModel' => $batchModel,
            'isAdmin' => $this->lms->isAdminActor(),
        ]);
    }

    public function create(Request $request)
    {
        ClassSchedule::ensureRecurrenceColumns();
        ClassSchedule::ensureTimezoneColumn();

        $batchId = (int) old('batch_id', $request->query('batch_id', 0));
        $batch = $batchId ? ClassBatch::with(['instructors', 'students', 'course'])->find($batchId) : null;
        if ($batch) {
            $this->assertCanUseBatch($batch);
        }

        $courses = $this->lms->coursesForActor();
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $students = $batch
            ? $this->studentsForScheduleForm((int) $batch->course_id, $batch->students)
            : $this->studentsForScheduleForm((int) old('course_id', 0));

        return view('admin.study-materials.schedules.create', [
            'courses' => $courses,
            'instructors' => $instructors,
            'students' => $students,
            'classBatches' => $this->batchesForActor(),
            'selectedBatch' => $batch,
            'meetingAccounts' => MeetingAccount::activeForDropdown(),
            'defaultMeetingAccountId' => MeetingAccount::defaultId(),
            'meetingAccountProviders' => MeetingAccount::activeForDropdown()->mapWithKeys(fn ($a) => [$a->id => $a->provider]),
            'timezoneOptions' => ClassSchedule::timezoneOptions(),
            'zohoMeetingReady' => $this->zoho->isMeetingReady(),
            'zohoHostEmail' => $this->zoho->hostAccountEmail(),
            'isInstructor' => $this->lms->isInstructorActor(),
            'isAdmin' => $this->lms->isAdminActor(),
            'useInstructorPortal' => $this->lms->isInstructorActor(),
        ]);
    }

    public function store(Request $request)
    {
        ClassSchedule::ensureRecurrenceColumns();
        ClassSchedule::ensureTimezoneColumn();
        ClassSchedule::ensureMeetingKeyColumn();

        $validator = Validator::make($request->all(), $this->scheduleRules($request));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $batch = ClassBatch::with(['students', 'instructors'])->findOrFail((int) $request->input('batch_id'));
        $this->assertCanUseBatch($batch);

        if ($this->lms->isInstructorActor() && ! $this->lms->instructorAssignedToCourse((int) $batch->course_id)) {
            return redirect()->back()
                ->withErrors(['batch_id' => 'You can only schedule classes for courses/batches assigned to you.'])
                ->withInput();
        }

        $schedule = new ClassSchedule();
        $schedule->fill($request->only([
            'instructor_id', 'head_of_faculty_id', 'meeting_account_id',
            'scheduled_at', 'timezone', 'duration_minutes', 'zoho_link', 'title', 'notes',
        ]));
        if (! filled($schedule->timezone)) {
            $acct = MeetingAccount::query()->find((int) $request->input('meeting_account_id'));
            $schedule->timezone = $acct?->timezone ?: config('app.timezone', 'Asia/Dubai');
        }
        $schedule->batch_id = $batch->id;
        $schedule->batch_name = $batch->name;
        $schedule->course_id = $batch->course_id;
        if (! $schedule->head_of_faculty_id) {
            $schedule->head_of_faculty_id = $batch->head_of_faculty_id;
        }
        if (! $schedule->instructor_id) {
            $schedule->instructor_id = $batch->primaryInstructorId() ?: Auth::id();
        }
        $schedule->duration_minutes = (int) ($request->input('duration_minutes') ?: 60);
        $schedule->status = 'scheduled';
        $this->applyRecurrenceAndReminders($schedule, $request);

        if ($this->lms->isAdminActor()) {
            $schedule->created_by_admin_id = Auth::guard('admin')->id();
        } else {
            $schedule->created_by_user_id = Auth::id();
            if (! $schedule->instructor_id) {
                $schedule->instructor_id = Auth::id();
            }
        }

        $schedule->save();

        // Split recurrence first so Zoho Calendar does not get an RRULE (and extra sessions).
        $studentIds = $this->resolveScheduleStudentIds($request, $batch);
        $schedule->students()->sync($studentIds);
        $siblingIds = $this->materializeRecurringSessions($schedule, $studentIds);
        $extraDays = count($siblingIds);

        // Create a Join link for every day in the series (primary + split days).
        $seriesIds = array_values(array_unique(array_merge([(int) $schedule->id], $siblingIds)));
        $zohoStatus = $this->attachMeetingsForSeries($seriesIds);

        $message = $this->scheduleSavedMessage('created', $zohoStatus);
        if ($extraDays > 0) {
            $message .= ' Split into ' . ($extraDays + 1) . ' separate session days — edit or delete each day as needed.';
        }

        return $this->redirectToBatchSchedule($batch->id, $message);
    }

    public function edit($id)
    {
        ClassSchedule::ensureRecurrenceColumns();

        $schedule = ClassSchedule::with(['students', 'batch.students', 'batch.instructors'])->findOrFail($id);
        $this->assertCanManageSchedule($schedule);

        // Existing recurring series → split into individual days so link/description can be edited per day.
        if ($schedule->isRecurring()) {
            $studentIds = $schedule->students()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $extra = $this->materializeRecurringSessions($schedule, $studentIds);
            $seriesIds = array_values(array_unique(array_merge([(int) $schedule->id], $extra)));
            $this->attachMeetingsForSeries($seriesIds);

            return $this->redirectToBatchSchedule(
                $schedule->batch_id ?: $schedule->id,
                'Recurring series split into ' . (count($extra) + 1) . ' editable session days. Use Edit to change the Join link or description, or Delete for one day.'
            );
        }

        $courses = $this->lms->coursesForActor();
        if ($schedule->course_id && ! $courses->contains('id', $schedule->course_id) && $schedule->course) {
            $courses = $courses->prepend($schedule->course)->unique('id')->values();
        }
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $batch = $schedule->batch;
        $students = $batch
            ? $this->studentsForScheduleForm(
                (int) old('course_id', $schedule->course_id),
                $batch->students->merge($schedule->students)
            )
            : $this->studentsForScheduleForm(
                (int) old('course_id', $schedule->course_id),
                $schedule->students
            );

        ClassSchedule::ensureRecurrenceColumns();
        ClassSchedule::ensureTimezoneColumn();

        return view('admin.study-materials.schedules.edit', [
            'schedule' => $schedule,
            'courses' => $courses,
            'instructors' => $instructors,
            'students' => $students,
            'classBatches' => $this->batchesForActor(),
            'selectedBatch' => $batch,
            'meetingAccounts' => MeetingAccount::activeForDropdown(),
            'defaultMeetingAccountId' => MeetingAccount::defaultId(),
            'meetingAccountProviders' => MeetingAccount::activeForDropdown()->mapWithKeys(fn ($a) => [$a->id => $a->provider]),
            'timezoneOptions' => ClassSchedule::timezoneOptions(),
            'zohoMeetingReady' => $this->zoho->isMeetingReady(),
            'zohoHostEmail' => $this->zoho->hostAccountEmail(),
            'isInstructor' => $this->lms->isInstructorActor(),
            'isAdmin' => $this->lms->isAdminActor(),
            'useInstructorPortal' => $this->lms->isInstructorActor(),
        ]);
    }

    public function students(Request $request)
    {
        $courseId = (int) $request->query('course_id', 0);
        $keepIds = collect(explode(',', (string) $request->query('keep', '')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $assigned = $keepIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $keepIds)->get(['id', 'name', 'email']);

        $students = $this->studentsForScheduleForm($courseId, $assigned);

        return response()->json([
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'text' => $s->name . ' (' . $s->email . ')',
            ])->values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        ClassSchedule::ensureRecurrenceColumns();
        ClassSchedule::ensureTimezoneColumn();
        ClassSchedule::ensureMeetingKeyColumn();

        $schedule = ClassSchedule::with('batch')->findOrFail($id);
        $this->assertCanManageSchedule($schedule);

        $validator = Validator::make($request->all(), array_merge($this->scheduleRules($request), [
            'status' => 'required|in:scheduled,completed,cancelled',
        ]));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $batch = ClassBatch::with('students')->findOrFail((int) $request->input('batch_id'));
        $this->assertCanUseBatch($batch);

        $beforeAt = optional($schedule->scheduled_at)->format('Y-m-d H:i:s');
        $beforeTz = (string) ($schedule->timezone ?? '');
        $beforeDuration = (int) ($schedule->duration_minutes ?: 60);
        $beforeTitle = (string) ($schedule->title ?? '');
        $beforeNotes = (string) ($schedule->notes ?? '');

        $schedule->fill($request->only([
            'instructor_id', 'head_of_faculty_id', 'meeting_account_id',
            'scheduled_at', 'timezone', 'duration_minutes', 'zoho_link', 'title', 'notes', 'status',
        ]));
        if (! filled($schedule->timezone)) {
            $acct = MeetingAccount::query()->find((int) $request->input('meeting_account_id'));
            $schedule->timezone = $acct?->timezone ?: config('app.timezone', 'Asia/Dubai');
        }
        $schedule->batch_id = $batch->id;
        $schedule->batch_name = $batch->name;
        $schedule->course_id = $batch->course_id;
        $schedule->duration_minutes = (int) ($request->input('duration_minutes') ?: 60);
        // Edit updates THIS day only — never recreate / expand a recurring series.
        $request->merge(['recurrence_type' => ClassSchedule::RECURRENCE_NONE]);
        $this->applyRecurrenceAndReminders($schedule, $request);
        $schedule->save();

        $studentIds = $this->resolveScheduleStudentIds($request, $batch);
        $schedule->students()->sync($studentIds);

        $afterAt = optional($schedule->scheduled_at)->format('Y-m-d H:i:s');
        $timeChanged = $beforeAt !== $afterAt
            || $beforeTz !== (string) ($schedule->timezone ?? '')
            || $beforeDuration !== (int) ($schedule->duration_minutes ?: 60)
            || $beforeTitle !== (string) ($schedule->title ?? '')
            || $beforeNotes !== (string) ($schedule->notes ?? '');

        $zohoStatus = $this->meetings->syncAfterScheduleUpdate($schedule, $timeChanged);

        return $this->redirectToBatchSchedule($batch->id, $this->scheduleSavedMessage('updated', $zohoStatus));
    }

    public function destroy($id)
    {
        $schedule = ClassSchedule::with('batch')->findOrFail($id);
        $this->assertCanManageSchedule($schedule);
        $batchId = $schedule->batch_id;
        $schedule->delete();

        if ($batchId) {
            return $this->redirectToBatchSchedule($batchId, 'Scheduled day deleted.');
        }

        return $this->redirectToScheduleIndex('Scheduled day deleted.');
    }

    public function ics($id)
    {
        $schedule = ClassSchedule::with(['course', 'instructor', 'batch'])->findOrFail($id);
        $this->assertCanManageSchedule($schedule);

        return $this->icsResponse(
            [$schedule],
            'class-' . $schedule->id . '.ics'
        );
    }

    public function feed()
    {
        $schedules = $this->calendarQuery()->get();

        return $this->icsResponse($schedules, 'berkeley-class-schedule.ics');
    }

    public function saveZohoEmbed(Request $request)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $url = $this->extractZohoCalendarUrl($request->input('zoho_calendar_embed_url'));
        if ($request->filled('zoho_calendar_embed_url') && !$url) {
            return redirect()->back()->with('fail', 'Use a Zoho Calendar embed or share URL (calendar.zoho.com).');
        }

        if (! Schema::hasTable('site_settings') || ! Schema::hasColumn('site_settings', 'zoho_calendar_embed_url')) {
            return redirect()->back()->with('fail', 'Run database migrations to enable Zoho Calendar embed.');
        }

        $settings = SiteSettings::query()->first();
        if (!$settings) {
            return redirect()->back()->with('fail', 'Site settings record is missing.');
        }

        $settings->zoho_calendar_embed_url = $url;
        $settings->save();

        return redirect()->route('admin.class-schedules.index')->with('success', 'Zoho Calendar embed saved.');
    }

    public function batchMeta(Request $request)
    {
        $batch = ClassBatch::with(['course', 'headOfFaculty', 'instructors', 'students'])
            ->findOrFail((int) $request->query('batch_id', 0));
        $this->assertCanUseBatch($batch);

        return response()->json([
            'id' => $batch->id,
            'name' => $batch->name,
            'code' => $batch->code,
            'course_id' => $batch->course_id,
            'course_title' => $batch->course->title ?? '',
            'head_of_faculty_id' => $batch->head_of_faculty_id,
            'instructor_ids' => $batch->instructors->pluck('id')->values(),
            'primary_instructor_id' => $batch->primaryInstructorId(),
            'students' => $this->studentsForScheduleForm((int) $batch->course_id, $batch->students)->map(function ($s) use ($batch) {
                return [
                    'id' => $s->id,
                    'text' => $s->name . ' (' . $s->email . ')',
                    'selected' => $batch->students->contains('id', $s->id),
                ];
            })->values(),
        ]);
    }

    protected function scheduleRules(?Request $request = null): array
    {
        $request = $request ?: request();
        $accountId = (int) $request->input('meeting_account_id');
        $account = $accountId
            ? MeetingAccount::query()->whereKey($accountId)->first()
            : null;
        $isZoom = $account && $account->isZoom();
        $zoomAutoReady = $isZoom && $account->hasRequiredCredentials();

        return [
            'batch_id' => [
                'required',
                Rule::exists('class_batches', 'id')->where(fn ($q) => $q->where('status', 'active')),
            ],
            'instructor_id' => 'nullable|exists:users,id',
            'head_of_faculty_id' => 'nullable|exists:users,id',
            'meeting_account_id' => [
                'required',
                Rule::exists('meeting_accounts', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'scheduled_at' => 'required|date',
            'timezone' => 'nullable|string|max:64',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            // Zoom without API credentials: paste Join link. Zoho / Zoom-with-API: optional auto-create.
            'zoho_link' => ($isZoom && ! $zoomAutoReady ? 'required' : 'nullable') . '|url|max:500',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'recurrence_type' => 'nullable|in:none,daily,weekly,weekdays',
            'recurrence_days' => 'nullable|array',
            'recurrence_days.*' => 'in:MO,TU,WE,TH,FR,SA,SU',
            'recurrence_end' => 'nullable|in:never,on,after',
            'recurrence_count' => 'nullable|integer|min:1|max:52',
            'recurrence_until' => 'nullable|date',
            'reminders' => 'nullable|array|max:8',
            'reminders.*.action' => 'nullable|in:email,popup,notification',
            'reminders.*.amount' => 'nullable|integer|min:1|max:60',
            'reminders.*.unit' => 'nullable|in:minutes,hours,days',
        ];
    }

    protected function batchesForActor()
    {
        if (! Schema::hasTable('class_batches')) {
            return collect();
        }

        $query = ClassBatch::with(['course', 'instructors'])
            ->where('status', 'active')
            ->orderBy('name');

        if ($this->lms->isInstructorActor()) {
            $uid = Auth::id();
            $query->where(function ($q) use ($uid) {
                $q->whereHas('instructors', fn ($q2) => $q2->where('users.id', $uid))
                    ->orWhere('head_of_faculty_id', $uid);
            });
        }

        return $query->get();
    }

    protected function assertCanUseBatch(ClassBatch $batch): void
    {
        if ($this->lms->isAdminActor()) {
            return;
        }

        $uid = (int) Auth::id();
        $ok = (int) $batch->head_of_faculty_id === $uid
            || $batch->instructors()->where('users.id', $uid)->exists();
        abort_unless($ok, 403);
    }

    protected function assertCanManageSchedule(ClassSchedule $schedule): void
    {
        if ($this->lms->isAdminActor()) {
            return;
        }

        abort_unless($this->lms->isInstructorActor(), 403);

        if ($schedule->batch) {
            $this->assertCanUseBatch($schedule->batch);

            return;
        }

        abort_unless(
            (int) $schedule->instructor_id === (int) Auth::id()
            || (int) $schedule->head_of_faculty_id === (int) Auth::id(),
            403
        );
    }

    protected function resolveScheduleStudentIds(Request $request, ClassBatch $batch): array
    {
        $requested = collect($request->input('student_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($requested->isEmpty()) {
            return $batch->students->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        // Admin + Instructor can pick students (batch roster or course-enrolled).
        $batchStudentIds = $batch->students->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $requested
            ->filter(function ($id) use ($batch, $batchStudentIds) {
                if (in_array($id, $batchStudentIds, true)) {
                    return true;
                }

                return $this->lms->studentBelongsToCourse($id, (int) $batch->course_id);
            })
            ->unique()
            ->values()
            ->all();
    }

    protected function applyRecurrenceAndReminders(ClassSchedule $schedule, Request $request): void
    {
        if (! ClassSchedule::supportsRecurrenceColumns()) {
            return;
        }

        $type = (string) $request->input('recurrence_type', ClassSchedule::RECURRENCE_NONE);
        $schedule->recurrence_type = $type ?: ClassSchedule::RECURRENCE_NONE;
        $schedule->recurrence_days = in_array($type, [ClassSchedule::RECURRENCE_WEEKLY, ClassSchedule::RECURRENCE_WEEKDAYS], true)
            ? array_values(array_unique($request->input('recurrence_days', [])))
            : null;

        if ($type === ClassSchedule::RECURRENCE_WEEKDAYS) {
            $schedule->recurrence_days = ['MO', 'TU', 'WE', 'TH', 'FR'];
        }

        $endMode = (string) $request->input('recurrence_end', 'after');
        if ($type === ClassSchedule::RECURRENCE_NONE) {
            $schedule->recurrence_days = null;
            $schedule->recurrence_count = null;
            $schedule->recurrence_until = null;
        } elseif ($endMode === 'never') {
            $schedule->recurrence_count = null;
            $schedule->recurrence_until = null;
        } elseif ($endMode === 'on') {
            $schedule->recurrence_until = $request->input('recurrence_until') ?: null;
            $schedule->recurrence_count = null;
        } else {
            $schedule->recurrence_count = max(1, min(52, (int) ($request->input('recurrence_count') ?: 13)));
            $schedule->recurrence_until = null;
        }

        $reminders = [];
        foreach ((array) $request->input('reminders', []) as $row) {
            $action = strtolower((string) ($row['action'] ?? ''));
            $amount = (int) ($row['amount'] ?? 0);
            $unit = strtolower((string) ($row['unit'] ?? 'days'));
            if (! in_array($action, ['email', 'popup', 'notification'], true) || $amount < 1) {
                continue;
            }
            $minutes = match ($unit) {
                'minutes' => $amount,
                'hours' => $amount * 60,
                default => $amount * 1440,
            };
            $reminders[] = [
                'action' => $action,
                'minutes' => -$minutes,
            ];
        }

        $schedule->reminders = $reminders !== [] ? $reminders : ClassSchedule::defaultReminders();
    }

    protected function scheduleSavedMessage(string $action, array $zohoStatus): string
    {
        $base = $action === 'updated' ? 'Class schedule updated.' : 'Class schedule created.';
        $meetingStatus = $zohoStatus['meeting'] ?? 'failed';
        $calendarStatus = $zohoStatus['calendar'] ?? 'skipped';

        $parts = [$base];
        $meetingNote = match ($meetingStatus) {
            'created' => 'Meeting Join link was created automatically.',
            'updated' => 'Zoho/Zoom meeting time was updated to match this schedule.',
            'existing' => 'Meeting Join link was saved.',
            'manual_required' => 'Zoom selected — paste the Zoom Join URL in Meeting link (required), or add Zoom Server-to-Server OAuth credentials on the Meeting Account to auto-create.',
            'not_configured' => 'Selected meeting account is missing or not ready, so the Join link could not be auto-created. Check Meeting Accounts.',
            'failed' => 'Meeting Join link was not created/updated automatically.'
                . (! empty($zohoStatus['error']) ? ' ' . $zohoStatus['error'] : ' If the host email is not the Zoho OAuth user (e.g. sk@ while OAuth is bdm@), open Meeting Accounts and set Presenter ZUID for that host.'),
            default => 'Meeting Join link was not created automatically.'
                . (! empty($zohoStatus['error']) ? ' ' . $zohoStatus['error'] : ''),
        };
        if ($meetingNote) {
            $parts[] = $meetingNote;
        }

        if ($calendarStatus === 'created') {
            $parts[] = 'The meeting was also added to Zoho Calendar with the join link.';
        } elseif ($calendarStatus === 'existing') {
            $parts[] = 'Zoho Calendar event already exists.';
        } elseif ($meetingStatus === 'created' && $calendarStatus === 'failed') {
            $parts[] = 'Zoho Calendar event was not created — import the .ics if needed.';
        } elseif ($calendarStatus === 'not_configured') {
            $parts[] = 'Connect Zoho Calendar on the meeting account to auto-add the class to the calendar.';
        }

        return implode(' ', $parts);
    }

    /**
     * Options for Assign students: admins see all students; instructors see course-enrolled.
     * Always merge already-assigned so edit never drops selected people from the dropdown.
     */
    protected function studentsForScheduleForm(int $courseId, $assignedStudents = null)
    {
        $students = $this->lms->isAdminActor()
            ? $this->lms->studentsForCourse(null)
            : $this->lms->studentsForCourse($courseId > 0 ? $courseId : null);

        if ($assignedStudents) {
            $students = $students->merge(collect($assignedStudents))->unique('id');
        }

        return $students->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    protected function calendarQuery()
    {
        $query = ClassSchedule::with(['course', 'instructor', 'batch'])->orderBy('scheduled_at');
        if ($this->lms->isInstructorActor()) {
            $uid = (int) Auth::id();
            $query->where(function ($q) use ($uid) {
                $q->where('instructor_id', $uid)
                    ->orWhere('head_of_faculty_id', $uid)
                    ->orWhereHas('batch', function ($b) use ($uid) {
                        $b->where('head_of_faculty_id', $uid)
                            ->orWhereHas('instructors', fn ($i) => $i->where('users.id', $uid));
                    });
            });
        }

        return $query;
    }

    protected function zohoCalendarEmbedUrl(): ?string
    {
        if (! Schema::hasTable('site_settings') || ! Schema::hasColumn('site_settings', 'zoho_calendar_embed_url')) {
            return null;
        }

        return SiteSettings::query()->value('zoho_calendar_embed_url');
    }

    protected function extractZohoCalendarUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/src=["\']([^"\']+)["\']/', $value, $match)) {
            $value = html_entity_decode($match[1]);
        }
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        if ($host === '' || ! preg_match('/(^|\.)zoho\.(com|eu|in|com\.au)$/', $host)) {
            return null;
        }

        return $value;
    }

    /**
     * Group schedules into batch cards for the Batch list UI.
     *
     * @param  \Illuminate\Support\Collection<int, ClassSchedule>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    /**
     * Attach Zoho/Zoom Join links for each session id (with short delay + one retry).
     * Recurring create previously only linked the first day — siblings stayed on "Link soon"
     * until each was opened and saved again.
     *
     * @param  array<int, int>  $seriesIds
     * @return array{meeting: string, calendar: string}
     */
    protected function attachMeetingsForSeries(array $seriesIds): array
    {
        $zohoStatus = ['meeting' => 'failed', 'calendar' => 'skipped', 'error' => null];
        $pending = [];

        foreach ($seriesIds as $index => $sid) {
            $row = ClassSchedule::with(['meetingAccount', 'course', 'instructor', 'students'])->find($sid);
            if (! $row) {
                continue;
            }
            if ($index > 0) {
                usleep(350000);
            }
            $status = $this->meetings->attachIntegrations($row);
            if (($status['meeting'] ?? '') === 'failed') {
                $pending[] = (int) $sid;
                $zohoStatus['error'] = $status['error'] ?? $this->meetings->lastError;
            }
            if (in_array($status['meeting'] ?? '', ['created', 'existing', 'manual_required'], true)
                || ($zohoStatus['meeting'] ?? '') === 'failed') {
                $zohoStatus = array_merge($zohoStatus, $status);
            }
        }

        foreach ($pending as $sid) {
            usleep(500000);
            $row = ClassSchedule::with(['meetingAccount', 'course', 'instructor', 'students'])->find($sid);
            if (! $row || filled($row->zoho_link)) {
                continue;
            }
            $status = $this->meetings->attachIntegrations($row);
            if (in_array($status['meeting'] ?? '', ['created', 'existing'], true)) {
                $zohoStatus = array_merge($zohoStatus, $status);
            } else {
                $zohoStatus['error'] = $status['error'] ?? $this->meetings->lastError ?? $zohoStatus['error'];
            }
        }

        return $zohoStatus;
    }

    /**
     * Turn a recurring series into individual ClassSchedule rows (one per class day)
     * so Admin/Instructor can edit the Join link / description or delete a single day.
     *
     * @return array<int, int> IDs of newly created sibling session days (not including the primary).
     */
    protected function materializeRecurringSessions(ClassSchedule $schedule, array $studentIds): array
    {
        if (! $schedule->isRecurring()) {
            return [];
        }

        $starts = collect($schedule->occurrenceStarts())
            ->unique(fn ($start) => $start->format('Y-m-d H:i'))
            ->values()
            ->all();
        if ($starts === []) {
            return [];
        }

        $schedule->scheduled_at = $starts[0];
        $schedule->recurrence_type = ClassSchedule::RECURRENCE_NONE;
        $schedule->recurrence_days = null;
        $schedule->recurrence_count = null;
        $schedule->recurrence_until = null;
        // Clear link before split — each day gets its own meeting after materialize.
        $schedule->zoho_link = null;
        $schedule->zoho_calendar_event_uid = null;
        $schedule->save();

        $createdIds = [];
        foreach (array_slice($starts, 1) as $start) {
            // Never recreate a day that already exists on this batch.
            $exists = ClassSchedule::query()
                ->where('batch_id', $schedule->batch_id)
                ->where('scheduled_at', $start->format('Y-m-d H:i:s'))
                ->exists();
            if ($exists) {
                continue;
            }

            $copy = $schedule->replicate();
            $copy->scheduled_at = $start;
            $copy->timezone = $schedule->timezone;
            $copy->recurrence_type = ClassSchedule::RECURRENCE_NONE;
            $copy->recurrence_days = null;
            $copy->recurrence_count = null;
            $copy->recurrence_until = null;
            $copy->zoho_link = null;
            $copy->zoho_calendar_event_uid = null;
            $copy->save();
            if ($studentIds !== []) {
                $copy->students()->sync($studentIds);
            }
            $createdIds[] = (int) $copy->id;
        }

        return $createdIds;
    }

    public function clearBatch($batchId)
    {
        $batch = ClassBatch::findOrFail((int) $batchId);
        $this->assertCanUseBatch($batch);

        $deleted = ClassSchedule::query()->where('batch_id', $batch->id)->delete();

        return $this->redirectToBatchSchedule(
            $batch->id,
            'Removed ' . $deleted . ' session(s) from this batch. They will not come back unless you create them again.'
        );
    }

    protected function redirectToBatchSchedule($batchId, string $message)
    {
        if ($this->lms->isInstructorActor()) {
            return redirect()
                ->route('user.class-schedules.batch', $batchId)
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.class-schedules.batch', $batchId)
            ->with('success', $message);
    }

    protected function redirectToScheduleIndex(string $message)
    {
        if ($this->lms->isInstructorActor()) {
            return redirect()
                ->route('user.class-schedules.index')
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', $message);
    }

    protected function batchListGroups($rows)
    {
        return collect($rows)
            ->groupBy(function (ClassSchedule $row) {
                if ($row->batch_id) {
                    return 'batch:' . $row->batch_id;
                }

                $name = trim((string) ($row->batch_name ?: $row->title ?: 'Untitled batch'));

                return 'name:' . mb_strtolower($name) . '|' . (int) $row->course_id;
            })
            ->map(function ($sessions) {
                /** @var \Illuminate\Support\Collection<int, ClassSchedule> $sessions */
                $first = $sessions->sortBy('scheduled_at')->first();
                $batchModel = $first->relationLoaded('batch') ? $first->batch : $first->batch()->with('course')->first();
                $students = $sessions
                    ->flatMap(fn (ClassSchedule $row) => $row->students)
                    ->unique('id')
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values();

                return [
                    'batch_id' => $batchModel?->id ?: $first->batch_id,
                    'batch_code' => $batchModel?->code,
                    'batch_name' => $batchModel?->name ?: ($first->batch_name ?: ($first->title ?: 'Untitled batch')),
                    'course' => $batchModel?->course ?: $first->course,
                    'instructor' => $first->instructor,
                    'head_of_faculty' => $batchModel?->headOfFaculty ?: $first->headOfFaculty,
                    'sessions' => $sessions->sortBy('scheduled_at')->values(),
                    'students' => $students,
                    'primary' => $sessions->sortByDesc('scheduled_at')->first(),
                    'latest_at' => $sessions->max(fn (ClassSchedule $row) => $row->scheduled_at?->timestamp ?? 0),
                ];
            })
            ->sortByDesc(fn ($batch) => (int) ($batch['latest_at'] ?? 0))
            ->values();
    }

    protected function icsResponse($schedules, string $filename)
    {
        $events = collect($schedules)
            ->map(fn (ClassSchedule $row) => $row->toIcsEvent())
            ->implode("\r\n");

        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//BerkeleyME//Class Schedule//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n" . $events . "\r\nEND:VCALENDAR\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
