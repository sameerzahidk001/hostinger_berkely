<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\ClassSchedule;
use App\Models\StudyMaterialFolder;
use App\Models\StudyMaterialItem;
use App\Models\StudyMaterialInstructorAccess;
use App\Models\StudyMaterialStudentAccess;
use App\Services\StudyMaterialService;
use App\Services\ZohoLmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class StudyMaterialController extends Controller
{
    public function __construct(
        protected StudyMaterialService $lms,
        protected ZohoLmsService $zoho
    ) {
    }

    public function index()
    {
        $accesses = $this->lms->portalAccessesForUser(Auth::user());

        return view('user.study-materials.index', compact('accesses'));
    }

    public function show($id)
    {
        abort_unless($this->lms->userCanOpenFolder((int) Auth::id(), (int) $id), 403, 'This folder is disabled or you no longer have access.');

        $folder = StudyMaterialFolder::with([
            'course',
            'rootItems.childrenRecursive',
            'instructorAccess.instructor',
        ])->findOrFail($id);

        $access = StudyMaterialStudentAccess::query()
            ->where('student_id', Auth::id())
            ->where('folder_id', $folder->id)
            ->first();

        if (! $access) {
            $access = StudyMaterialInstructorAccess::query()
                ->where('instructor_id', Auth::id())
                ->where('folder_id', $folder->id)
                ->first();
        }

        record_user_activity(
            'Opened study folder',
            $folder->name,
            route('user.study-materials.show', $folder->id),
            'student',
            Auth::id()
        );

        return view('user.study-materials.show', compact('folder', 'access'));
    }

    public function viewFile(Request $request, $itemId)
    {
        $item = StudyMaterialItem::with('folder')->findOrFail($itemId);
        abort_if($item->type !== 'file', 404);
        abort_unless($this->lms->userCanOpenFolder((int) Auth::id(), (int) $item->folder_id), 403, 'This folder is disabled or you no longer have access.');

        $asDownload = $request->boolean('download');
        if ($asDownload) {
            abort_unless($item->allowsDownload(), 403);
        }
        if ($asDownload || $request->boolean('raw')) {
            return $this->streamItem($request, $item, $asDownload);
        }

        return response()->view('user.study-materials.secure-frame', [
            'item' => $item,
            'mode' => $item->isExternal() ? 'external' : 'file',
            'rawUrl' => $item->portalPreviewUrl(),
            'downloadUrl' => $item->portalDownloadUrl(),
        ]);
    }

    protected function streamItem(Request $request, StudyMaterialItem $item, bool $asDownload): Response
    {
        @set_time_limit(0);

        $filename = $item->original_name ?: $item->name;
        $disposition = ($asDownload ? 'attachment' : 'inline') . '; filename="' . addslashes($filename) . '"';
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=120',
            'Content-Disposition' => $disposition,
        ];
        if ($asDownload) {
            $headers['Content-Type'] = 'application/octet-stream';
        }

        $path = $item->publicPath();
        if ($path && File::exists($path)) {
            if (!$asDownload) {
                $headers['Content-Type'] = $item->mime && $item->mime !== 'zoho/workdrive'
                    ? $item->mime
                    : (File::mimeType($path) ?: 'application/octet-stream');
            }

            return $asDownload
                ? response()->download($path, $filename, $headers)
                : response()->file($path, $headers);
        }

        if ($item->external_url) {
            $info = $this->zoho->workDrivePublicFileInfo($item->external_url);
            if ($info) {
                $this->rememberWorkDriveMeta($item, $info);
                if (!empty($info['name'])) {
                    $filename = $info['name'];
                    $headers['Content-Disposition'] = ($asDownload ? 'attachment' : 'inline') . '; filename="' . addslashes($filename) . '"';
                }
                if (!empty($info['extn']) && !$asDownload) {
                    $headers['Content-Type'] = $this->zoho->mimeFromExtension($info['extn']) ?: 'application/octet-stream';
                }
            }

            if (!empty($info['download_url'])) {
                return $this->zoho->proxyRemoteStream(
                    $info['download_url'],
                    $asDownload ? null : $request->header('Range'),
                    $headers,
                    $asDownload
                );
            }

            $remote = $this->zoho->fetchRemoteFile($item->external_url);
            if (!$remote) {
                return response()->view('user.study-materials.file-error', [
                    'item' => $item,
                    'downloadUrl' => $item->portalDownloadUrl(),
                    'asDownload' => $asDownload,
                ], 502);
            }

            $headers['Content-Type'] = $asDownload ? 'application/octet-stream' : ($remote['mime'] ?: 'application/octet-stream');
            if (!empty($remote['path']) && is_file($remote['path'])) {
                $response = $asDownload
                    ? response()->download($remote['path'], $filename, $headers)
                    : response()->file($remote['path'], $headers);
                if (!empty($remote['delete_after'])) {
                    $response->deleteFileAfterSend(true);
                }

                return $response;
            }

            return response($remote['body'] ?? '', 200, $headers);
        }

        abort(404);
    }

    protected function rememberWorkDriveMeta(StudyMaterialItem $item, array $info): void
    {
        $updates = [];
        if (!empty($info['extn'])) {
            $mime = $this->zoho->mimeFromExtension($info['extn']);
            if ($mime && ($item->mime === 'zoho/workdrive' || blank($item->mime))) {
                $updates['mime'] = $mime;
            }
        }
        if (!empty($info['name']) && blank($item->original_name)) {
            $updates['original_name'] = $info['name'];
        }
        if (!empty($info['size']) && empty($item->size)) {
            $updates['size'] = $info['size'];
        }
        if ($updates) {
            $item->forceFill($updates)->save();
        }
    }

    public function schedules()
    {
        $isInstructor = Auth::user()?->roles()->where('name', 'instructor')->exists() ?? false;
        $batches = $this->studentBatchSummaries();

        return view('user.study-materials.schedules', [
            'batches' => $batches,
            'calendarEvents' => collect(),
            'isInstructor' => $isInstructor,
        ]);
    }

    public function batchSummariesForActor(bool $withSessions = false)
    {
        return $this->studentBatchSummaries($withSessions);
    }

    public function scheduleBatch($batchKey)
    {
        $batches = $this->studentBatchSummaries(true);
        $batch = $batches->first(function ($row) use ($batchKey) {
            if (is_numeric($batchKey) && ! empty($row['batch_id'])) {
                return (int) $row['batch_id'] === (int) $batchKey;
            }

            return ($row['key'] ?? '') === (string) $batchKey;
        });
        abort_unless($batch, 404);

        $calendarEvents = collect($batch['session_models'] ?? [])
            ->flatMap(fn (ClassSchedule $row) => $row->toFullCalendarEvent(
                $row->zoho_link ?: route('user.class-schedules.index')
            ))
            ->values();

        $canManage = $this->instructorCanManageBatch($batch);

        return view('user.study-materials.schedule-batch', [
            'batch' => $batch,
            'calendarEvents' => $calendarEvents,
            'canManageSessions' => $canManage,
        ]);
    }

    public function schedulesIcs()
    {
        return $this->icsDownload($this->studentSchedules(), 'my-class-schedule.ics');
    }

    public function scheduleIcs($id)
    {
        $schedule = $this->studentSchedules()->firstWhere('id', (int) $id);
        abort_unless($schedule, 403);

        return $this->icsDownload(collect([$schedule]), 'class-' . $schedule->id . '.ics');
    }

    /**
     * @param  bool  $withSessions  Include expanded session rows + models for detail page.
     */
    protected function studentBatchSummaries(bool $withSessions = false)
    {
        $schedules = $this->studentSchedules();

        return $schedules
            ->groupBy(function (ClassSchedule $row) {
                if ($row->batch_id) {
                    return 'batch:' . $row->batch_id;
                }

                $name = trim((string) ($row->batch_name ?: $row->title ?: 'My batch'));

                return 'name:' . mb_strtolower($name) . '|' . (int) $row->course_id;
            })
            ->map(function ($group, $key) use ($withSessions) {
                $first = $group->sortBy('scheduled_at')->first();
                $batchModel = $first->batch;

                $sessions = $group
                    ->flatMap(function (ClassSchedule $schedule) {
                        return collect($schedule->occurrenceStarts())->map(function ($start) use ($schedule) {
                            return (object) [
                                'id' => $schedule->id,
                                'scheduled_at' => $start,
                                'zoho_link' => $schedule->zoho_link,
                                'duration_minutes' => $schedule->durationMinutes(),
                                'title' => $schedule->title,
                                'notes' => $schedule->notes,
                                'status' => $schedule->status ?: 'scheduled',
                                'timezone_label' => method_exists($schedule, 'timezoneLabel')
                                    ? $schedule->timezoneLabel()
                                    : (string) ($schedule->timezone ?: config('app.timezone', 'Asia/Dubai')),
                            ];
                        });
                    })
                    ->sortBy(fn ($row) => $row->scheduled_at?->timestamp ?? 0)
                    ->values();

                $row = [
                    'key' => $key,
                    'batch_id' => $batchModel?->id ?: $first->batch_id,
                    'batch_code' => $batchModel?->code,
                    'batch_name' => $batchModel?->name
                        ?: ($first->batch_name ?: ($first->title ?: 'My batch')),
                    'course' => $batchModel?->course ?: $first->course,
                    'instructor' => $first->instructor
                        ?: $batchModel?->instructors?->first(),
                    'head_of_faculty' => $batchModel?->headOfFaculty ?: $first->headOfFaculty,
                    'session_count' => $sessions->count(),
                    'latest_at' => $sessions->max(fn ($s) => $s->scheduled_at?->timestamp ?? 0),
                    // Next upcoming session only — do not fall back to the batch start date.
                    'next_at' => optional(
                        $sessions->first(fn ($s) => $s->scheduled_at
                            && $s->scheduled_at->isFuture()
                            && ! in_array(strtolower((string) ($s->status ?? '')), ['cancelled', 'completed'], true))
                    )->scheduled_at,
                ];

                if ($withSessions) {
                    $row['sessions'] = $sessions;
                    $row['session_models'] = $group->values();
                }

                return $row;
            })
            ->sortByDesc(fn ($batch) => (int) ($batch['latest_at'] ?? 0))
            ->values();
    }

    protected function studentSchedules()
    {
        $query = ClassSchedule::with([
            'course',
            'instructor',
            'headOfFaculty',
            'meetingAccount',
            'batch.course',
            'batch.headOfFaculty',
            'batch.instructors',
        ])->whereIn('status', ['scheduled', 'completed', 'cancelled']);

        if (Auth::user()?->roles()->where('name', 'instructor')->exists()) {
            $uid = (int) Auth::id();
            $query->where(function ($q) use ($uid) {
                $q->where('instructor_id', $uid)
                    ->orWhere('head_of_faculty_id', $uid);

                if (Schema::hasTable('class_batches')
                    && Schema::hasColumn('class_schedules', 'batch_id')) {
                    $q->orWhereHas('batch', function ($b) use ($uid) {
                        $b->where('head_of_faculty_id', $uid)
                            ->orWhereHas('instructors', fn ($i) => $i->where('users.id', $uid));
                    });
                }
            });
        } else {
            $userId = (int) Auth::id();
            // Show schedules assigned to the student OR any session in a batch they belong to.
            $query->where(function ($q) use ($userId) {
                $q->whereHas('students', fn ($s) => $s->where('users.id', $userId));

                if (Schema::hasTable('class_batches')
                    && Schema::hasTable('class_batch_student')
                    && Schema::hasColumn('class_schedules', 'batch_id')) {
                    $q->orWhereHas('batch.students', fn ($s) => $s->where('users.id', $userId));
                }
            });
        }

        return $query->orderBy('scheduled_at')->get();
    }

    /**
     * Instructors assigned to the batch (or listed on sessions) can edit/add from the portal view.
     */
    protected function instructorCanManageBatch(array $batch): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->roles()->where('name', 'instructor')->exists()) {
            return false;
        }

        $uid = (int) $user->id;
        $hofId = (int) ($batch['head_of_faculty']->id ?? 0);
        $insId = (int) ($batch['instructor']->id ?? 0);
        if ($uid === $hofId || $uid === $insId) {
            return true;
        }

        $batchId = (int) ($batch['batch_id'] ?? 0);
        if ($batchId > 0 && Schema::hasTable('class_batches')) {
            $model = ClassBatch::with('instructors')->find($batchId);
            if ($model) {
                if ((int) $model->head_of_faculty_id === $uid) {
                    return true;
                }
                if ($model->instructors->contains('id', $uid)) {
                    return true;
                }
            }
        }

        return collect($batch['session_models'] ?? [])->contains(function ($row) use ($uid) {
            return (int) ($row->instructor_id ?? 0) === $uid
                || (int) ($row->head_of_faculty_id ?? 0) === $uid;
        });
    }

    protected function icsDownload($schedules, string $filename)
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
