<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\SiteSettings;
use App\Models\User;
use App\Services\StudyMaterialService;
use App\Services\ZohoLmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ClassScheduleController extends Controller
{
    public function __construct(
        protected StudyMaterialService $lms,
        protected ZohoLmsService $zoho
    ) {
    }

    public function index()
    {
        if (! Schema::hasTable('class_schedules')) {
            return redirect()
                ->route('admin.lms.install')
                ->with('fail', 'LMS tables are missing. Create them here (do not use Ignition Run Migrations).');
        }

        $query = ClassSchedule::with(['course', 'instructor', 'students'])->orderByDesc('scheduled_at');

        if ($this->lms->isInstructorActor()) {
            $query->where('instructor_id', Auth::id());
        }

        $schedules = $query->paginate(20);
        $calendarEvents = $this->calendarQuery()
            ->get()
            ->flatMap(fn (ClassSchedule $row) => $row->toFullCalendarEvent(route('admin.class-schedules.edit', $row->id)))
            ->values();
        $zohoEmbed = $this->zohoCalendarEmbedUrl();

        return view('admin.study-materials.schedules.index', compact('schedules', 'calendarEvents', 'zohoEmbed'));
    }

    public function create()
    {
        $courses = $this->lms->coursesForActor();
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $students = $this->lms->isAdminActor()
            ? $this->lms->studentsForCourse(null)
            : collect();

        return view('admin.study-materials.schedules.create', [
            'courses' => $courses,
            'instructors' => $instructors,
            'students' => $students,
            'zohoMeetingReady' => $this->zoho->isMeetingReady(),
            'isInstructor' => $this->lms->isInstructorActor(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->scheduleRules());

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($this->lms->isInstructorActor() && ! $this->lms->instructorAssignedToCourse((int) $request->course_id)) {
            return redirect()->back()
                ->withErrors(['course_id' => 'You can only schedule classes for courses assigned to you.'])
                ->withInput();
        }

        $schedule = new ClassSchedule();
        $schedule->fill($request->only([
            'batch_name', 'course_id', 'instructor_id', 'scheduled_at', 'duration_minutes', 'zoho_link', 'title', 'notes',
        ]));
        $schedule->duration_minutes = (int) ($request->input('duration_minutes') ?: 60);
        $schedule->status = 'scheduled';
        $this->applyRecurrenceAndReminders($schedule, $request);

        if ($this->lms->isAdminActor()) {
            $schedule->created_by_admin_id = Auth::guard('admin')->id();
        } else {
            $schedule->created_by_user_id = Auth::id();
            if (!$schedule->instructor_id) {
                $schedule->instructor_id = Auth::id();
            }
        }

        $schedule->save();

        $studentIds = collect($request->input('student_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $this->lms->studentBelongsToCourse($id, (int) $schedule->course_id))
            ->values()
            ->all();
        $schedule->students()->sync($studentIds);

        $zohoStatus = $this->zoho->attachIntegrations($schedule);

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', $this->scheduleSavedMessage('created', $zohoStatus));
    }

    public function edit($id)
    {
        $schedule = ClassSchedule::with('students')->findOrFail($id);
        if ($this->lms->isInstructorActor() && (int) $schedule->instructor_id !== (int) Auth::id()) {
            abort(403);
        }

        $courses = $this->lms->coursesForActor();
        if ($schedule->course_id && ! $courses->contains('id', $schedule->course_id) && $schedule->course) {
            $courses = $courses->prepend($schedule->course)->unique('id')->values();
        }
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $students = $this->lms->studentsForCourse((int) $schedule->course_id);

        return view('admin.study-materials.schedules.edit', [
            'schedule' => $schedule,
            'courses' => $courses,
            'instructors' => $instructors,
            'students' => $students,
            'zohoMeetingReady' => $this->zoho->isMeetingReady(),
            'isInstructor' => $this->lms->isInstructorActor(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = ClassSchedule::findOrFail($id);
        if ($this->lms->isInstructorActor() && (int) $schedule->instructor_id !== (int) Auth::id()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), array_merge($this->scheduleRules(), [
            'status' => 'required|in:scheduled,completed,cancelled',
        ]));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($this->lms->isInstructorActor() && ! $this->lms->instructorAssignedToCourse((int) $request->course_id)) {
            return redirect()->back()
                ->withErrors(['course_id' => 'You can only schedule classes for courses assigned to you.'])
                ->withInput();
        }

        $schedule->fill($request->only([
            'batch_name', 'course_id', 'instructor_id', 'scheduled_at', 'duration_minutes', 'zoho_link', 'title', 'notes', 'status',
        ]));
        $schedule->duration_minutes = (int) ($request->input('duration_minutes') ?: 60);
        $this->applyRecurrenceAndReminders($schedule, $request);
        $schedule->save();

        $studentIds = collect($request->input('student_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $this->lms->studentBelongsToCourse($id, (int) $schedule->course_id))
            ->values()
            ->all();
        $schedule->students()->sync($studentIds);

        $zohoStatus = $this->zoho->attachIntegrations($schedule);

        return redirect()
            ->route('admin.class-schedules.index')
            ->with('success', $this->scheduleSavedMessage('updated', $zohoStatus));
    }

    public function destroy($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);
        ClassSchedule::findOrFail($id)->delete();

        return redirect()->route('admin.class-schedules.index')->with('success', 'Schedule deleted.');
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

    protected function scheduleRules(): array
    {
        return [
            'batch_name' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'instructor_id' => 'nullable|exists:users,id',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'zoho_link' => 'nullable|url|max:500',
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
            'created' => 'Zoho Meeting link was created automatically — students can Join Zoho.',
            'existing' => 'Existing Zoho Meeting link was kept.',
            'not_configured' => 'Zoho OAuth is not connected, so the meeting link could not be auto-created.',
            default => 'Zoho Meeting link was not created automatically. Check Zoho Meeting permissions / presenter.',
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
            $parts[] = 'Connect Zoho Calendar to auto-add the class to the calendar.';
        }

        return implode(' ', $parts);
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
