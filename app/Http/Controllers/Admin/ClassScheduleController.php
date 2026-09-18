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

        $query = ClassSchedule::with(['course', 'instructor', 'headOfFaculty', 'students'])->orderByDesc('scheduled_at');

        if ($this->lms->isInstructorActor()) {
            $query->where('instructor_id', Auth::id());
        }

        $schedules = $query->paginate(20);
        $calendarEvents = $this->calendarQuery()
            ->get()
            ->flatMap(fn (ClassSchedule $row) => $row->toFullCalendarEvent(route('admin.class-schedules.edit', $row->id)))
            ->values();
        $zohoEmbed = $this->zohoCalendarEmbedUrl();

        $batchQuery = ClassSchedule::with(['course', 'instructor', 'headOfFaculty', 'students', 'batch.course', 'batch.headOfFaculty'])
            ->orderBy('batch_name')
            ->orderBy('scheduled_at');
        if ($this->lms->isInstructorActor()) {
            $batchQuery->where('instructor_id', Auth::id());
        }
        $batches = $this->batchListGroups($batchQuery->get());

        return view('admin.study-materials.schedules.index', [
            'schedules' => $schedules,
            'calendarEvents' => $calendarEvents,
            'zohoEmbed' => $zohoEmbed,
            'batches' => $batches,
            'isAdmin' => $this->lms->isAdminActor(),
        ]);
    }

    public function create(Request $request)
    {
        ClassSchedule::ensureRecurrenceColumns();

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
            ? $batch->students
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
            'zohoMeetingReady' => $this->zoho->isMeetingReady(),
            'zohoHostEmail' => $this->zoho->hostAccountEmail(),
            'isInstructor' => $this->lms->isInstructorActor(),
            'isAdmin' => $this->lms->isAdminActor(),
        ]);
    }

    public function store(Request $request)
    {
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
            'scheduled_at', 'duration_minutes', 'zoho_link', 'title', 'notes',
        ]));
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

        $studentIds = $this->resolveScheduleStudentIds($request, $batch);
        $schedule->students()->sync($studentIds);

        $zohoStatus = $this->meetings->attachIntegrations($schedule);
        $extraDays = $this->materializeRecurringSessions($schedule, $studentIds);

        $message = $this->scheduleSavedMessage('created', $zohoStatus);
        if ($extraDays > 0) {
            $message .= ' Split into ' . ($extraDays + 1) . ' separate session days — edit or delete each day as needed.';
        }

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', $message);
    }

    public function edit($id)
    {
        ClassSchedule::ensureRecurrenceColumns();

        $schedule = ClassSchedule::with(['students', 'batch.students', 'batch.instructors'])->findOrFail($id);
        if ($this->lms->isInstructorActor() && (int) $schedule->instructor_id !== (int) Auth::id()) {
            abort(403);
        }

        // Existing recurring series → split into individual days so link/description can be edited per day.
        if ($schedule->isRecurring()) {
            $studentIds = $schedule->students()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $extra = $this->materializeRecurringSessions($schedule, $studentIds);

            return redirect()
                ->route('admin.class-schedules.index')
                ->with(
                    'success',
                    'Recurring series split into ' . ($extra + 1) . ' editable session days. Use Edit to change the Join link or description, or Delete for one day.'
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
            ? $batch->students->merge($schedule->students)->unique('id')->values()
            : $this->studentsForScheduleForm(
                (int) old('course_id', $schedule->course_id),
                $schedule->students
            );

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
            'zohoMeetingReady' => $this->zoho->isMeetingReady(),
            'zohoHostEmail' => $this->zoho->hostAccountEmail(),
            'isInstructor' => $this->lms->isInstructorActor(),
            'isAdmin' => $this->lms->isAdminActor(),
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
        $schedule = ClassSchedule::findOrFail($id);
        if ($this->lms->isInstructorActor() && (int) $schedule->instructor_id !== (int) Auth::id()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), array_merge($this->scheduleRules($request), [
            'status' => 'required|in:scheduled,completed,cancelled',
        ]));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($this->lms->isInstructorActor() && ! $this->lms->instructorAssignedToCourse((int) $request->course_id ?: (int) optional(ClassBatch::find($request->batch_id))->course_id)) {
            return redirect()->back()
                ->withErrors(['batch_id' => 'You can only schedule classes for courses/batches assigned to you.'])
                ->withInput();
        }

        $batch = ClassBatch::with('students')->findOrFail((int) $request->input('batch_id'));
        $this->assertCanUseBatch($batch);

        $schedule->fill($request->only([
            'instructor_id', 'head_of_faculty_id', 'meeting_account_id',
            'scheduled_at', 'duration_minutes', 'zoho_link', 'title', 'notes', 'status',
        ]));
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

        $zohoStatus = $this->meetings->attachIntegrations($schedule);

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', $this->scheduleSavedMessage('updated', $zohoStatus));
    }

    public function destroy($id)
    {
        $schedule = ClassSchedule::findOrFail($id);
        if ($this->lms->isInstructorActor()) {
            abort_unless((int) $schedule->instructor_id === (int) Auth::id(), 403);
        } else {
            abort_unless($this->lms->isAdminActor(), 403);
        }

        $schedule->delete();

        return redirect()->route('admin.class-schedules.index')->with('success', 'Scheduled day deleted.');
    }

    public function ics($id)
    {
        $schedule = ClassSchedule::with(['course', 'instructor'])->findOrFail($id);
        if ($this->lms->isInstructorActor() && (int) $schedule->instructor_id !== (int) Auth::id()) {
            abort(403);
        }

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
            'students' => $batch->students->map(fn ($s) => [
                'id' => $s->id,
                'text' => $s->name . ' (' . $s->email . ')',
            ])->values(),
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
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            // Zoom: paste Join link manually. Zoho: optional (auto-created on save).
            'zoho_link' => ($isZoom ? 'required' : 'nullable') . '|url|max:500',
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

    protected function resolveScheduleStudentIds(Request $request, ClassBatch $batch): array
    {
        if ($this->lms->isInstructorActor()) {
            return $batch->students->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        $requested = collect($request->input('student_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($requested->isEmpty()) {
            return $batch->students->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return $requested
            ->filter(fn ($id) => $this->lms->studentBelongsToCourse($id, (int) $batch->course_id))
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
            'created' => 'Zoho Meeting Join link was created automatically.',
            'existing' => 'Meeting Join link was saved.',
            'manual_required' => 'Zoom selected — paste the Zoom Join link in Meeting link (required).',
            'not_configured' => 'Selected meeting account is missing or not ready, so the Join link could not be auto-created.',
            default => 'Meeting Join link was not created automatically. For Zoho check credentials; for Zoom paste the link manually.',
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
        $query = ClassSchedule::with(['course', 'instructor'])->orderBy('scheduled_at');
        if ($this->lms->isInstructorActor()) {
            $query->where('instructor_id', Auth::id());
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
     * Turn a recurring series into individual ClassSchedule rows (one per class day)
     * so Admin/Instructor can edit the Join link / description or delete a single day.
     */
    protected function materializeRecurringSessions(ClassSchedule $schedule, array $studentIds): int
    {
        if (! $schedule->isRecurring()) {
            return 0;
        }

        $starts = $schedule->occurrenceStarts();
        if ($starts === []) {
            return 0;
        }

        $schedule->scheduled_at = $starts[0];
        $schedule->recurrence_type = ClassSchedule::RECURRENCE_NONE;
        $schedule->recurrence_days = null;
        $schedule->recurrence_count = null;
        $schedule->recurrence_until = null;
        $schedule->save();

        $created = 0;
        foreach (array_slice($starts, 1) as $start) {
            $copy = $schedule->replicate();
            $copy->scheduled_at = $start;
            $copy->save();
            if ($studentIds !== []) {
                $copy->students()->sync($studentIds);
            }
            $created++;
        }

        return $created;
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
