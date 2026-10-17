<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LmsQuestion;
use App\Services\LmsAssessmentService;
use App\Services\StudyMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class LmsQuestionController extends Controller
{
    public function __construct(
        protected LmsAssessmentService $assessments,
        protected StudyMaterialService $lms
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();

        $type = trim((string) $request->get('type', ''));
        $search = trim((string) $request->get('search', ''));
        $query = LmsQuestion::query()->with(['course' => function ($q) {
            $q->withTrashed()->select('id', 'title');
        }])->orderByDesc('id');

        // Instructors only manage QBANK for courses Admin assigned them.
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $query->where(function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds ?: [0]);
            });
        }
        if ($type !== '' && array_key_exists($type, LmsQuestion::types())) {
            $query->where('type', $type);
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('stem', 'like', $like)
                    ->orWhere('topic', 'like', $like)
                    ->orWhereHas('course', function ($cq) use ($like) {
                        $cq->withTrashed()->where('title', 'like', $like);
                    });
            });
        }

        return view('admin.assessments.questions.index', [
            'rows' => $query->paginate(20)->withQueryString(),
            'types' => LmsQuestion::types(),
            'type' => $type,
            'search' => $search,
        ]);
    }

    public function create()
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();

        return view('admin.assessments.questions.form', $this->formData(new LmsQuestion([
            'type' => LmsQuestion::TYPE_MCQ,
            'max_marks' => 1,
            'status' => 'active',
        ])));
    }

    public function store(Request $request)
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();

        $rows = $request->input('questions');
        if (is_array($rows) && $rows !== []) {
            $validator = Validator::make($request->all(), $this->bulkRules());
            $validator->after(function ($v) use ($request, $rows) {
                foreach ($rows as $i => $row) {
                    if ($this->isBlankHtml($row['stem'] ?? null)) {
                        $v->errors()->add('questions.' . $i . '.stem', 'Question ' . ($i + 1) . ' stem is required.');
                    }
                }
                $this->assertRichTextLimits($v, $request, $rows);
            });
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $sharedCourse = $request->input('course_id') ?: null;
            $sharedTopic = trim((string) $request->input('topic', '')) ?: null;
            $sharedTargetRole = trim((string) $request->input('target_role', '')) ?: null;
            $sharedStatus = $request->input('status', 'active') === 'active' ? 'active' : 'disabled';
            $saved = 0;
            $totalMarks = 0.0;

            foreach ($rows as $row) {
                if ($this->isBlankHtml($row['stem'] ?? null)) {
                    continue;
                }
                $q = new LmsQuestion();
                $this->fillFromArray($q, $row, [
                    'course_id' => $sharedCourse,
                    'topic' => trim((string) ($row['topic'] ?? '')) ?: $sharedTopic,
                    'target_role' => $sharedTargetRole,
                    'status' => $sharedStatus,
                ], true);
                $q->save();
                $saved++;
                $totalMarks += (float) $q->max_marks;
            }

            if ($saved < 1) {
                return back()->withErrors(['questions' => 'Add at least one question with a stem.'])->withInput();
            }

            return redirect()->route('admin.assessments.questions.index')
                ->with('success', $saved . ' question' . ($saved === 1 ? '' : 's') . ' saved (total marks ' . number_format($totalMarks, 1) . ').');
        }

        $validator = Validator::make($request->all(), $this->rules());
        $validator->after(function ($v) use ($request) {
            if ($this->isBlankHtml($request->input('stem'))) {
                $v->errors()->add('stem', 'Question stem is required.');
            }
            $this->assertRichTextLimits($v, $request);
        });
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $q = new LmsQuestion();
        $this->fill($q, $request, true);
        $q->save();

        return redirect()->route('admin.assessments.questions.index')->with('success', 'Question saved.');
    }

    public function edit($id)
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();
        $question = LmsQuestion::findOrFail($id);
        if ($this->instructorMustPickCourse()) {
            abort_unless($question->course_id && $this->lms->instructorAssignedToCourse((int) $question->course_id), 403);
        }

        return view('admin.assessments.questions.form', $this->formData($question));
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();
        $q = LmsQuestion::findOrFail($id);
        $validator = Validator::make($request->all(), $this->rules());
        $validator->after(function ($v) use ($request) {
            if ($this->isBlankHtml($request->input('stem'))) {
                $v->errors()->add('stem', 'Question stem is required.');
            }
            $this->assertRichTextLimits($v, $request);
        });
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $this->fill($q, $request, false);
        $q->save();

        return redirect()->route('admin.assessments.questions.index')->with('success', 'Question updated.');
    }

    public function destroy($id)
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();
        $question = LmsQuestion::findOrFail($id);
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            abort_unless($question->course_id && in_array((int) $question->course_id, $courseIds, true), 403);
        }
        $question->delete();

        return back()->with('success', 'Question removed.');
    }

    public function importForm()
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();

        return view('admin.assessments.questions.import', [
            'courses' => $this->lms->coursesForActor(),
        ]);
    }

    public function importTemplate()
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $csv = "topic,stem,option_a,option_b,option_c,option_d,option_e,correct,max_marks,course_id\n"
            . "Sample,\"What is 2+2?\",2,3,4,5,6,C,1,\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mcq-import-template.csv"',
        ]);
    }

    public function importStore(Request $request)
    {
        abort_unless($this->assessments->canManageQuestionBank(), 403);
        $this->assessments->ensure();

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'course_id' => ($this->instructorMustPickCourse() ? 'required' : 'nullable') . '|exists:courses,id',
        ]);

        $defaultCourseId = $request->filled('course_id') ? (int) $request->input('course_id') : null;
        if ($defaultCourseId && $this->instructorMustPickCourse()) {
            abort_unless($this->lms->instructorAssignedToCourse($defaultCourseId), 403);
        }

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('fail', 'Could not read the uploaded file.');
        }

        $header = null;
        $saved = 0;
        $skipped = 0;
        $line = 0;
        while (($cols = fgetcsv($handle)) !== false) {
            $line++;
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $cols);
                continue;
            }
            if (count(array_filter($cols, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $cols[$i] ?? '';
            }

            $stem = trim((string) ($row['stem'] ?? ''));
            $a = trim((string) ($row['option_a'] ?? ''));
            $b = trim((string) ($row['option_b'] ?? ''));
            $c = trim((string) ($row['option_c'] ?? ''));
            $d = trim((string) ($row['option_d'] ?? ''));
            $e = trim((string) ($row['option_e'] ?? ''));
            if ($stem === '' || $a === '' || $b === '') {
                $skipped++;
                continue;
            }

            $correctRaw = strtoupper(trim((string) ($row['correct'] ?? 'A')));
            $correctMap = [
                'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4,
                '1' => 0, '2' => 1, '3' => 2, '4' => 3, '5' => 4,
            ];
            $correct = $correctMap[$correctRaw] ?? 0;

            $courseId = trim((string) ($row['course_id'] ?? ''));
            $courseId = $courseId !== '' ? (int) $courseId : $defaultCourseId;
            if ($courseId && $this->instructorMustPickCourse()) {
                if (! $this->lms->instructorAssignedToCourse($courseId)) {
                    $skipped++;
                    continue;
                }
            }

            $options = [];
            foreach ([$a, $b, $c, $d, $e] as $text) {
                if ($text === '') {
                    continue;
                }
                $options[] = [
                    'key' => (string) count($options),
                    'text' => $text,
                    'is_correct' => false,
                ];
            }
            if ($options === []) {
                $skipped++;
                continue;
            }
            $correct = min($correct, count($options) - 1);
            foreach ($options as $i => &$opt) {
                $opt['key'] = (string) $i;
                $opt['is_correct'] = $i === $correct;
            }
            unset($opt);

            $q = new LmsQuestion();
            $q->type = LmsQuestion::TYPE_MCQ;
            $q->stem = $stem;
            $q->topic = trim((string) ($row['topic'] ?? '')) ?: null;
            $q->course_id = $courseId ?: null;
            $q->max_marks = max(0.1, (float) ($row['max_marks'] ?? 1));
            $q->status = 'active';
            $q->options_json = $options;
            if ($this->lms->isAdminActor()) {
                $q->created_by_admin_id = Auth::guard('admin')->id();
            } else {
                $q->created_by_user_id = Auth::id();
            }
            $q->save();
            $saved++;
        }
        fclose($handle);

        return redirect()->route('admin.assessments.questions.index')
            ->with('success', "Imported {$saved} MCQ(s)" . ($skipped ? "; skipped {$skipped} row(s)." : '.'));
    }

    protected function instructorMustPickCourse(): bool
    {
        return $this->lms->isInstructorActor()
            && ! $this->lms->isAdminActor()
            && ! panel_sees_all_dashboards();
    }

    protected function rules(): array
    {
        $courseRule = $this->instructorMustPickCourse() ? 'required|exists:courses,id' : 'nullable|exists:courses,id';

        return [
            'type' => 'required|in:mcq,essay,read,translation,simulation,case_study',
            'stem' => 'nullable|string',
            'topic' => 'nullable|string|max:255',
            'course_id' => $courseRule,
            'target_role' => 'nullable|in:' . implode(',', array_keys(LmsQuestion::targetRoles())),
            'max_marks' => 'required|numeric|min:0.1',
            'explanation' => 'nullable|string',
            'status' => 'required|in:active,disabled',
            'options' => 'nullable|array',
            'options.*.text' => 'nullable|string|max:500',
            'options.*.is_correct' => 'nullable',
            'correct_option' => 'nullable|integer|min:0',
            'time_limit_minutes' => 'nullable|integer|min:1|max:600',
            'sub_questions' => 'nullable|array',
            'sub_questions.*.text' => 'nullable|string|max:5000',
            'sub_questions.*.marks' => 'nullable|numeric|min:0',
            'sub_questions.*.percent' => 'nullable|numeric|min:0|max:100',
        ];
    }

    protected function bulkRules(): array
    {
        $courseRule = $this->instructorMustPickCourse() ? 'required|exists:courses,id' : 'nullable|exists:courses,id';

        return [
            'course_id' => $courseRule,
            'target_role' => 'nullable|in:' . implode(',', array_keys(LmsQuestion::targetRoles())),
            'topic' => 'nullable|string|max:255',
            'status' => 'required|in:active,disabled',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:mcq,essay,read,translation,simulation,case_study',
            'questions.*.stem' => 'nullable|string',
            'questions.*.topic' => 'nullable|string|max:255',
            'questions.*.max_marks' => 'required|numeric|min:0.1',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.text' => 'nullable|string|max:500',
            'questions.*.correct_option' => 'nullable|integer|min:0',
            'questions.*.time_limit_minutes' => 'nullable|integer|min:1|max:600',
            'questions.*.sub_questions' => 'nullable|array',
            'questions.*.sub_questions.*.text' => 'nullable|string|max:5000',
            'questions.*.sub_questions.*.marks' => 'nullable|numeric|min:0',
            'questions.*.sub_questions.*.percent' => 'nullable|numeric|min:0|max:100',
        ];
    }

    protected function isBlankHtml(?string $html): bool
    {
        return $this->plainTextLen($html) === 0;
    }

    protected function plainTextLen(?string $html): int
    {
        $text = html_entity_decode(strip_tags((string) $html));
        $text = str_replace("\xc2\xa0", ' ', $text);

        return mb_strlen(trim($text));
    }

    protected function assertRichTextLimits($validator, Request $request, ?array $rows = null): void
    {
        $stemMax = 10000;
        $explanationMax = 10000;

        if (is_array($rows)) {
            foreach ($rows as $i => $row) {
                if ($this->plainTextLen($row['stem'] ?? null) > $stemMax) {
                    $validator->errors()->add('questions.' . $i . '.stem', 'Question ' . ($i + 1) . ' stem exceeds ' . number_format($stemMax) . ' characters.');
                }
                if ($this->plainTextLen($row['explanation'] ?? null) > $explanationMax) {
                    $validator->errors()->add('questions.' . $i . '.explanation', 'Question ' . ($i + 1) . ' explanation exceeds ' . number_format($explanationMax) . ' characters.');
                }
            }

            return;
        }

        if ($this->plainTextLen($request->input('stem')) > $stemMax) {
            $validator->errors()->add('stem', 'Question stem exceeds ' . number_format($stemMax) . ' characters.');
        }
        if ($this->plainTextLen($request->input('explanation')) > $explanationMax) {
            $validator->errors()->add('explanation', 'Explanation exceeds ' . number_format($explanationMax) . ' characters.');
        }
    }

    protected function fill(LmsQuestion $q, Request $request, bool $creating): void
    {
        $this->fillFromArray($q, [
            'type' => $request->input('type'),
            'stem' => $request->input('stem'),
            'topic' => $request->input('topic'),
            'course_id' => $request->input('course_id'),
            'target_role' => $request->input('target_role'),
            'max_marks' => $request->input('max_marks', 1),
            'time_limit_minutes' => $request->input('time_limit_minutes'),
            'explanation' => $request->input('explanation'),
            'status' => $request->input('status', 'active'),
            'options' => $request->input('options', []),
            'correct_option' => $request->input('correct_option', 0),
            'sub_questions' => $request->input('sub_questions', []),
        ], [], $creating);
    }

    protected function fillFromArray(LmsQuestion $q, array $row, array $defaults, bool $creating): void
    {
        $q->type = $row['type'] ?? LmsQuestion::TYPE_MCQ;
        $q->stem = trim((string) ($row['stem'] ?? ''));
        $q->topic = array_key_exists('topic', $defaults)
            ? ($defaults['topic'] ?: null)
            : (trim((string) ($row['topic'] ?? '')) ?: null);
        $q->course_id = array_key_exists('course_id', $defaults)
            ? ($defaults['course_id'] ?: null)
            : (($row['course_id'] ?? null) ?: null);
        if ($q->course_id && $this->instructorMustPickCourse()) {
            abort_unless($this->lms->instructorAssignedToCourse((int) $q->course_id), 403);
        }
        if (Schema::hasTable('lms_questions') && ! Schema::hasColumn('lms_questions', 'target_role')) {
            Schema::table('lms_questions', function ($table) {
                $table->string('target_role', 40)->nullable()->index();
            });
        }
        if (Schema::hasColumn('lms_questions', 'target_role')) {
            $role = array_key_exists('target_role', $defaults)
                ? trim((string) ($defaults['target_role'] ?? ''))
                : trim((string) ($row['target_role'] ?? ''));
            $allowed = array_keys(LmsQuestion::targetRoles());
            $q->target_role = ($role !== '' && in_array($role, $allowed, true)) ? $role : null;
        }
        $explanation = trim((string) ($row['explanation'] ?? ''));
        $q->explanation = $this->isBlankHtml($explanation) ? null : $explanation;
        $status = $defaults['status'] ?? ($row['status'] ?? 'active');
        $q->status = $status === 'active' ? 'active' : 'disabled';

        if (Schema::hasColumn('lms_questions', 'time_limit_minutes')) {
            $tl = $row['time_limit_minutes'] ?? null;
            $q->time_limit_minutes = ($tl !== null && $tl !== '' && (int) $tl > 0) ? (int) $tl : null;
        }

        if ($q->type === LmsQuestion::TYPE_MCQ) {
            $options = [];
            $correct = (int) ($row['correct_option'] ?? 0);
            foreach ((array) ($row['options'] ?? []) as $i => $optRow) {
                $text = trim((string) ($optRow['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $options[] = [
                    'key' => (string) count($options),
                    'text' => $text,
                    'is_correct' => (int) $i === $correct || ! empty($optRow['is_correct']),
                ];
            }
            if ($options !== []) {
                foreach ($options as $i => &$opt) {
                    $opt['key'] = (string) $i;
                    $opt['is_correct'] = $i === $correct;
                }
                unset($opt);
            }
            $q->options_json = $options;
            $q->max_marks = (float) ($row['max_marks'] ?? 1);
        } else {
            // Sub-questions a/b/c with per-part marks (not for MCQ).
            $subs = [];
            $n = 0;
            foreach ((array) ($row['sub_questions'] ?? []) as $subRow) {
                $text = trim((string) ($subRow['text'] ?? ''));
                $percent = $subRow['percent'] ?? $subRow['marks'] ?? 0;
                $percent = (float) $percent;
                if ($text === '' && $percent <= 0) {
                    continue;
                }
                $n++;
                $subs[] = [
                    'key' => (string) $n,
                    'text' => $text,
                    'percent' => max(0, min(100, $percent)),
                ];
            }
            $q->options_json = $subs !== [] ? $subs : null;
            // Total marks stay on the question; sub-questions are % of that total.
            $q->max_marks = (float) ($row['max_marks'] ?? 1);
        }

        if ($creating) {
            if ($this->lms->isAdminActor()) {
                $q->created_by_admin_id = Auth::guard('admin')->id();
            } else {
                $q->created_by_user_id = Auth::id();
            }
        }
    }

    protected function formData(LmsQuestion $question): array
    {
        return [
            'question' => $question,
            'types' => LmsQuestion::types(),
            'targetRoles' => LmsQuestion::targetRoles(),
            'courses' => $this->lms->coursesForActor(),
        ];
    }
}
