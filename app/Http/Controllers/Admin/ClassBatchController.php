<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\User;
use App\Services\StudyMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ClassBatchController extends Controller
{
    public function __construct(protected StudyMaterialService $lms)
    {
    }

    public function index()
    {
        if (! Schema::hasTable('class_batches')) {
            return redirect()
                ->route('admin.class-schedules.index')
                ->with('fail', 'Batches table is missing. Run the meeting/batches migration first.');
        }

        $query = ClassBatch::with(['course', 'headOfFaculty', 'instructors', 'students'])
            ->withCount(['schedules', 'students', 'instructors'])
            ->orderByDesc('id');

        if ($this->lms->isInstructorActor()) {
            $uid = Auth::id();
            $query->where(function ($q) use ($uid) {
                $q->whereHas('instructors', fn ($q2) => $q2->where('users.id', $uid))
                    ->orWhere('head_of_faculty_id', $uid);
            });
        }

        $batches = $query->paginate(20);

        return view('admin.study-materials.batches.index', [
            'batches' => $batches,
            'isAdmin' => $this->lms->isAdminActor(),
        ]);
    }

    public function create()
    {
        abort_unless($this->lms->isAdminActor(), 403);

        return view('admin.study-materials.batches.form', $this->formData(new ClassBatch([
            'status' => 'active',
            'code' => ClassBatch::generateCode(),
        ])));
    }

    public function store(Request $request)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $batch = new ClassBatch();
        $batch->name = trim((string) $request->input('name'));
        $batch->course_id = (int) $request->input('course_id');
        $batch->head_of_faculty_id = $request->input('head_of_faculty_id') ?: null;
        $batch->status = $request->input('status', 'active') === 'active' ? 'active' : 'disabled';
        $batch->created_by_admin_id = Auth::guard('admin')->id();
        $batch->code = ClassBatch::generateCode();
        $batch->save();
        ClassBatch::ensureCode($batch);

        $this->syncRelations($batch, $request);

        return redirect()
            ->route('admin.class-batches.index')
            ->with('success', 'Batch created: ' . $batch->code);
    }

    public function edit($id)
    {
        $batch = ClassBatch::with(['instructors', 'students'])->findOrFail($id);
        $this->authorizeBatchAccess($batch, false);

        return view('admin.study-materials.batches.form', $this->formData($batch));
    }

    public function update(Request $request, $id)
    {
        $batch = ClassBatch::findOrFail($id);
        $this->authorizeBatchAccess($batch, true);

        $validator = Validator::make($request->all(), $this->rules($batch->id));
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($this->lms->isAdminActor()) {
            $batch->name = trim((string) $request->input('name'));
            $batch->course_id = (int) $request->input('course_id');
            $batch->head_of_faculty_id = $request->input('head_of_faculty_id') ?: null;
            $batch->status = $request->input('status', 'active') === 'active' ? 'active' : 'disabled';
            $batch->save();
            $this->syncRelations($batch, $request);
        }

        return redirect()
            ->route('admin.class-batches.index')
            ->with('success', 'Batch updated: ' . $batch->code);
    }

    public function destroy($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);
        $batch = ClassBatch::findOrFail($id);
        $batch->delete();

        return redirect()
            ->route('admin.class-batches.index')
            ->with('success', 'Batch deleted.');
    }

    protected function formData(ClassBatch $batch): array
    {
        $courses = $this->lms->coursesForActor();
        if ($batch->course_id && ! $courses->contains('id', $batch->course_id) && $batch->course) {
            $courses = $courses->prepend($batch->course)->unique('id')->values();
        }

        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $students = $this->lms->isAdminActor()
            ? $this->lms->studentsForCourse((int) ($batch->course_id ?: 0))
            : $batch->students;

        return [
            'batch' => $batch,
            'courses' => $courses,
            'instructors' => $instructors,
            'students' => $students,
            'selectedInstructorIds' => old('instructor_ids', $batch->instructors->pluck('id')->all()),
            'selectedStudentIds' => old('student_ids', $batch->students->pluck('id')->all()),
            'isAdmin' => $this->lms->isAdminActor(),
            'readOnly' => ! $this->lms->isAdminActor(),
        ];
    }

    protected function rules(?int $batchId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'head_of_faculty_id' => 'nullable|exists:users,id',
            'instructor_ids' => 'nullable|array',
            'instructor_ids.*' => 'exists:users,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'status' => 'nullable|in:active,disabled',
        ];
    }

    protected function syncRelations(ClassBatch $batch, Request $request): void
    {
        $instructorIds = collect($request->input('instructor_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $batch->instructors()->sync($instructorIds);

        $studentIds = collect($request->input('student_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $this->lms->studentBelongsToCourse($id, (int) $batch->course_id))
            ->unique()
            ->values()
            ->all();
        $batch->students()->sync($studentIds);
    }

    protected function authorizeBatchAccess(ClassBatch $batch, bool $mutating): void
    {
        if ($this->lms->isAdminActor()) {
            return;
        }

        if ($mutating) {
            abort(403);
        }

        $userId = (int) Auth::id();
        $allowed = (int) $batch->head_of_faculty_id === $userId
            || $batch->instructors()->where('users.id', $userId)->exists();

        abort_unless($allowed, 403);
    }
}
