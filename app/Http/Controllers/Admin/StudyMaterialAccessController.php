<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudyMaterialFolder;
use App\Models\StudyMaterialInstructorAccess;
use App\Models\StudyMaterialStudentAccess;
use App\Models\User;
use App\Services\StudyMaterialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StudyMaterialAccessController extends Controller
{
    public function __construct(protected StudyMaterialService $lms)
    {
    }

    public function index(Request $request)
    {
        $path = $request->get('type') === 'instructor'
            ? '/admin/study-materials/access/instructors'
            : '/admin/study-materials/access/students';

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $path .= '?search=' . urlencode($search);
        }

        return new \Illuminate\Http\RedirectResponse($path);
    }

    public function students(Request $request)
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('study_material_folders')) {
            return redirect()
                ->route('admin.lms.install')
                ->with('fail', 'LMS tables are missing. Create them here (do not use Ignition Run Migrations).');
        }

        $search = $this->searchTerm($request);
        $rows = $this->studentAccessQuery($search)->paginate(20)->withQueryString();
        $isAdmin = $this->lms->isAdminActor();

        return view('admin.study-materials.access.students', compact('rows', 'search', 'isAdmin'));
    }

    public function instructors(Request $request)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $search = $this->searchTerm($request);
        $rows = $this->instructorAccessQuery($search)->paginate(20)->withQueryString();

        return view('admin.study-materials.access.instructors', compact('rows', 'search'));
    }

    public function createInstructor(Request $request)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $folders = StudyMaterialFolder::with('course')->orderBy('name')->get();
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get();
        $selectedFolder = $request->get('folder_id');

        return view('admin.study-materials.access.assign-instructor', compact('folders', 'instructors', 'selectedFolder'));
    }

    public function storeInstructor(Request $request)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|exists:study_material_folders,id',
            'instructor_ids' => 'required|array|min:1',
            'instructor_ids.*' => 'exists:users,id',
            'issued_at' => 'nullable|date',
            'access_till' => 'nullable|date|after_or_equal:issued_at',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $folder = StudyMaterialFolder::findOrFail($request->folder_id);
        $issued = $request->filled('issued_at') ? Carbon::parse($request->issued_at) : now();
        $till = $request->filled('access_till')
            ? Carbon::parse($request->access_till)
            : $this->lms->computeAccessTill($issued, $folder->validity_months);

        foreach ($request->instructor_ids as $instructorId) {
            StudyMaterialInstructorAccess::updateOrCreate(
                [
                    'folder_id' => $folder->id,
                    'instructor_id' => $instructorId,
                ],
                [
                    'status' => 'disabled',
                    'issued_at' => $issued->toDateString(),
                    'access_till' => $till?->toDateString(),
                    'sent_at' => null,
                ]
            );
        }

        return redirect()
            ->route('admin.study-materials.access.instructors')
            ->with('success', 'Instructor access created (disabled until Send).');
    }

    public function sendInstructor($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $access = StudyMaterialInstructorAccess::findOrFail($id);
        $sent = $this->lms->sendInstructorAccessEmail($access);

        if (!$sent) {
            return redirect()->back()->with(
                'fail',
                'Could not send email. Add template "study-material-instructor-access" under Admin → Emails and check SMTP.'
            );
        }

        return redirect()->back()->with('success', 'Instructor access emailed and activated.');
    }

    public function editInstructor($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $access = StudyMaterialInstructorAccess::with(['folder.course', 'instructor'])->findOrFail($id);
        $folders = StudyMaterialFolder::with('course')->orderBy('name')->get();
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get();

        return view('admin.study-materials.access.edit-instructor', compact('access', 'folders', 'instructors'));
    }

    public function updateInstructor(Request $request, $id)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $access = StudyMaterialInstructorAccess::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|exists:study_material_folders,id',
            'instructor_id' => [
                'required',
                'exists:users,id',
                Rule::unique('study_material_instructor_access', 'instructor_id')
                    ->where(fn ($q) => $q->where('folder_id', $request->folder_id))
                    ->ignore($access->id),
            ],
            'issued_at' => 'nullable|date',
            'access_till' => 'nullable|date|after_or_equal:issued_at',
            'status' => 'required|in:active,disabled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $folder = StudyMaterialFolder::findOrFail($request->folder_id);
        $identityChanged = (int) $access->folder_id !== (int) $folder->id
            || (int) $access->instructor_id !== (int) $request->instructor_id;

        $access->folder_id = $folder->id;
        $access->instructor_id = $request->instructor_id;
        $access->issued_at = $request->filled('issued_at') ? Carbon::parse($request->issued_at)->toDateString() : $access->issued_at;
        $access->access_till = $request->filled('access_till')
            ? Carbon::parse($request->access_till)->toDateString()
            : null;
        $access->status = $request->status;
        if ($identityChanged) {
            $access->sent_at = null;
        }
        $access->save();

        return redirect()
            ->route('admin.study-materials.access.instructors')
            ->with('success', 'Instructor access updated.');
    }

    public function createStudent(Request $request)
    {
        $folders = $this->lms->foldersQueryForActor()
            ->where('status', 'active')
            ->with('course')
            ->orderBy('name')
            ->get();

        if ($this->lms->isInstructorActor()) {
            $folders = $folders->filter(function ($folder) {
                return $this->lms->instructorAssignedToCourse((int) $folder->course_id);
            })->values();
        }

        $selectedFolder = $request->get('folder_id') ?: old('folder_id');
        $folder = $selectedFolder ? StudyMaterialFolder::find($selectedFolder) : null;
        if ($folder && ! $this->lms->canAssignStudentAccess($folder)) {
            $folder = null;
            $selectedFolder = null;
        }

        $students = $folder
            ? $this->lms->studentsForCourse((int) $folder->course_id)
            : ($this->lms->isAdminActor() ? $this->lms->studentsForCourse(null) : collect());

        $defaultTill = null;
        if ($folder && !$folder->hasUnlimitedValidity()) {
            $defaultTill = $this->lms->computeAccessTill(now(), $folder->validity_months)?->format('Y-m-d');
        }

        return view('admin.study-materials.access.assign-student', [
            'folders' => $folders,
            'students' => $students,
            'selectedFolder' => $selectedFolder,
            'defaultTill' => $defaultTill,
            'isInstructor' => $this->lms->isInstructorActor(),
        ]);
    }

    public function storeStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|exists:study_material_folders,id',
            'student_id' => 'required|exists:users,id',
            'issued_at' => 'nullable|date',
            'access_till' => 'nullable|date|after_or_equal:issued_at',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $folder = StudyMaterialFolder::findOrFail($request->folder_id);
        abort_unless($this->lms->canAssignStudentAccess($folder), 403);
        abort_unless($folder->status === 'active', 422, 'Only active folders can be assigned to students.');

        if (! $this->lms->studentBelongsToCourse((int) $request->student_id, (int) $folder->course_id)) {
            return redirect()->back()
                ->withErrors(['student_id' => 'You can only assign students enrolled in this course.'])
                ->withInput();
        }

        $issued = $request->filled('issued_at') ? Carbon::parse($request->issued_at) : now();
        $till = $request->filled('access_till')
            ? Carbon::parse($request->access_till)
            : $this->lms->computeAccessTill($issued, $folder->validity_months);

        StudyMaterialStudentAccess::updateOrCreate(
            [
                'folder_id' => $folder->id,
                'student_id' => $request->student_id,
            ],
            [
                'status' => 'disabled',
                'issued_at' => $issued->toDateString(),
                'access_till' => $till?->toDateString(),
                'sent_at' => null,
            ]
        );

        return redirect()
            ->route('admin.study-materials.access.students')
            ->with('success', 'Student access created (disabled until Send).');
    }

    public function sendStudent($id)
    {
        $access = StudyMaterialStudentAccess::with('folder')->findOrFail($id);
        abort_unless($this->lms->canAssignStudentAccess($access->folder), 403);

        $sent = $this->lms->sendStudentAccessEmail($access);

        if (!$sent) {
            return redirect()->back()->with(
                'fail',
                'Could not send email. Add template "study-material-student-access" under Admin → Emails and check SMTP.'
            );
        }

        return redirect()->back()->with('success', 'Student access emailed and activated.');
    }

    public function editStudent($id)
    {
        $access = StudyMaterialStudentAccess::with(['folder.course', 'student'])->findOrFail($id);
        abort_unless($this->lms->canAssignStudentAccess($access->folder), 403);

        $folders = $this->lms->foldersQueryForActor()
            ->where(function ($q) use ($access) {
                $q->where('status', 'active')->orWhere('id', $access->folder_id);
            })
            ->with('course')
            ->orderBy('name')
            ->get();

        if ($this->lms->isInstructorActor()) {
            $folders = $folders->filter(function ($folder) {
                return $this->lms->instructorAssignedToCourse((int) $folder->course_id);
            })->values();
        }

        $students = $this->lms->studentsForCourse((int) $access->folder->course_id);
        if ($access->student && ! $students->contains('id', $access->student_id)) {
            $students = $students->prepend($access->student)->unique('id')->values();
        }

        return view('admin.study-materials.access.edit-student', [
            'access' => $access,
            'folders' => $folders,
            'students' => $students,
            'isInstructor' => $this->lms->isInstructorActor(),
        ]);
    }

    public function updateStudent(Request $request, $id)
    {
        $access = StudyMaterialStudentAccess::with('folder')->findOrFail($id);
        abort_unless($this->lms->canAssignStudentAccess($access->folder), 403);

        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|exists:study_material_folders,id',
            'student_id' => [
                'required',
                'exists:users,id',
                Rule::unique('study_material_student_access', 'student_id')
                    ->where(fn ($q) => $q->where('folder_id', $request->folder_id))
                    ->ignore($access->id),
            ],
            'issued_at' => 'nullable|date',
            'access_till' => 'nullable|date|after_or_equal:issued_at',
            'status' => 'required|in:active,disabled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $folder = StudyMaterialFolder::findOrFail($request->folder_id);
        abort_unless($this->lms->canAssignStudentAccess($folder), 403);

        if (! $this->lms->studentBelongsToCourse((int) $request->student_id, (int) $folder->course_id)) {
            return redirect()->back()
                ->withErrors(['student_id' => 'You can only assign students enrolled in this course.'])
                ->withInput();
        }

        $identityChanged = (int) $access->folder_id !== (int) $folder->id
            || (int) $access->student_id !== (int) $request->student_id;
        $wasActive = $access->status === 'active';

        $access->folder_id = $folder->id;
        $access->student_id = $request->student_id;
        $access->issued_at = $request->filled('issued_at') ? Carbon::parse($request->issued_at)->toDateString() : $access->issued_at;
        $access->access_till = $request->filled('access_till')
            ? Carbon::parse($request->access_till)->toDateString()
            : null;
        $access->status = $request->status;
        if ($identityChanged) {
            $access->sent_at = null;
        }
        $access->save();

        if ($wasActive && $access->status === 'disabled') {
            $this->lms->sendStudentDisabledEmail($access->fresh([
                'student',
                'folder.course',
                'folder.instructorAccess.instructor',
            ]), 'disabled');
        }

        return redirect()
            ->route('admin.study-materials.access.students')
            ->with('success', 'Student access updated.');
    }

    public function disableInstructor($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);
        $access = StudyMaterialInstructorAccess::findOrFail($id);
        $access->status = 'disabled';
        $access->save();

        return redirect()->back()->with('success', 'Instructor access disabled.');
    }

    public function disableStudent($id)
    {
        $access = StudyMaterialStudentAccess::with(['folder', 'student', 'folder.course', 'folder.instructorAccess.instructor'])->findOrFail($id);
        abort_unless($this->lms->canAssignStudentAccess($access->folder), 403);

        if ($access->status === 'disabled') {
            return redirect()->back()->with('success', 'Student access is already disabled.');
        }

        $access->status = 'disabled';
        $access->save();
        $this->lms->sendStudentDisabledEmail($access, 'disabled');

        return redirect()->back()->with('success', 'Student access disabled.');
    }

    public function destroyStudent($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $access = StudyMaterialStudentAccess::with(['folder', 'student'])->findOrFail($id);
        $label = trim(($access->student->name ?? 'Student') . ' / ' . ($access->folder->name ?? 'folder'));
        $access->delete();

        return redirect()
            ->route('admin.study-materials.access.students')
            ->with('success', 'Student access deleted: ' . $label);
    }

    public function studentsByFolder($folderId)
    {
        $folder = StudyMaterialFolder::findOrFail($folderId);
        abort_unless($this->lms->canAssignStudentAccess($folder), 403);

        $students = $this->lms->studentsForCourse((int) $folder->course_id);

        return response()->json(
            $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
            ])->values()
        );
    }

    protected function searchTerm(Request $request): string
    {
        return trim((string) $request->get('search', ''));
    }

    protected function likePattern(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    protected function actorFolderIds()
    {
        return $this->lms->foldersQueryForActor()->pluck('id');
    }

    protected function studentAccessQuery(string $search)
    {
        $query = StudyMaterialStudentAccess::with(['folder.course', 'student'])
            ->whereIn('folder_id', $this->actorFolderIds())
            ->latest();

        if ($search === '') {
            return $query;
        }

        $like = $this->likePattern($search);

        return $query->where(function ($q) use ($like) {
            $q->whereHas('student', function ($student) use ($like) {
                $student->where('name', 'like', $like)->orWhere('email', 'like', $like);
            })->orWhereHas('folder', function ($folder) use ($like) {
                $folder->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhereHas('course', function ($course) use ($like) {
                        $course->where('title', 'like', $like);
                    });
            });
        });
    }

    protected function instructorAccessQuery(string $search)
    {
        $query = StudyMaterialInstructorAccess::with(['folder.course', 'instructor'])
            ->whereIn('folder_id', $this->actorFolderIds())
            ->latest();

        if ($search === '') {
            return $query;
        }

        $like = $this->likePattern($search);

        return $query->where(function ($q) use ($like) {
            $q->whereHas('instructor', function ($instructor) use ($like) {
                $instructor->where('name', 'like', $like)->orWhere('email', 'like', $like);
            })->orWhereHas('folder', function ($folder) use ($like) {
                $folder->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhereHas('course', function ($course) use ($like) {
                        $course->where('title', 'like', $like);
                    });
            });
        });
    }
}
