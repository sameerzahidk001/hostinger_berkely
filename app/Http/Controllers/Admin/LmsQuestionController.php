<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LmsQuestion;
use App\Services\LmsAssessmentService;
use App\Services\StudyMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        abort_unless($this->assessments->canManageAssessments(), 403);
        $this->assessments->ensure();

        $type = trim((string) $request->get('type', ''));
        $search = trim((string) $request->get('search', ''));
        $query = LmsQuestion::query()->with(['course' => function ($q) {
            $q->withTrashed()->select('id', 'title');
        }])->orderByDesc('id');
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
        abort_unless($this->assessments->canManageAssessments(), 403);
        $this->assessments->ensure();

        return view('admin.assessments.questions.form', $this->formData(new LmsQuestion([
            'type' => LmsQuestion::TYPE_MCQ,
            'max_marks' => 1,
            'status' => 'active',
        ])));
    }

    public function store(Request $request)
    {
        abort_unless($this->assessments->canManageAssessments(), 403);
        $this->assessments->ensure();

        $rows = $request->input('questions');
        if (is_array($rows) && $rows !== []) {
            $validator = Validator::make($request->all(), $this->bulkRules());
            $validator->after(function ($v) use ($rows) {
                foreach ($rows as $i => $row) {
                    if ($this->isBlankHtml($row['stem'] ?? null)) {
                        $v->errors()->add('questions.' . $i . '.stem', 'Question ' . ($i + 1) . ' stem is required.');
                    }
                }
            });
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $sharedCourse = $request->input('course_id') ?: null;
            $sharedTopic = trim((string) $request->input('topic', '')) ?: null;
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
        abort_unless($this->assessments->canManageAssessments(), 403);
        $this->assessments->ensure();

        return view('admin.assessments.questions.form', $this->formData(LmsQuestion::findOrFail($id)));
    }

    public function update(Request $request, $id)
    {
        abort_unless($this->assessments->canManageAssessments(), 403);
        $this->assessments->ensure();
        $q = LmsQuestion::findOrFail($id);
        $validator = Validator::make($request->all(), $this->rules());
        $validator->after(function ($v) use ($request) {
            if ($this->isBlankHtml($request->input('stem'))) {
                $v->errors()->add('stem', 'Question stem is required.');
            }
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
        abort_unless($this->lms->isAdminActor() || panel_sees_all_dashboards(), 403);
        $this->assessments->ensure();
        LmsQuestion::findOrFail($id)->delete();

        return back()->with('success', 'Question removed.');
    }

    protected function rules(): array
    {
        return [
            'type' => 'required|in:mcq,essay,simulation',
            'stem' => 'nullable|string',
            'topic' => 'nullable|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'max_marks' => 'required|numeric|min:0.1',
            'explanation' => 'nullable|string',
            'status' => 'required|in:active,disabled',
            'options' => 'nullable|array',
            'options.*.text' => 'nullable|string',
            'options.*.is_correct' => 'nullable',
            'correct_option' => 'nullable|integer|min:0',
        ];
    }

    protected function bulkRules(): array
    {
        return [
            'course_id' => 'nullable|exists:courses,id',
            'topic' => 'nullable|string|max:255',
            'status' => 'required|in:active,disabled',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:mcq,essay,simulation',
            'questions.*.stem' => 'nullable|string',
            'questions.*.topic' => 'nullable|string|max:255',
            'questions.*.max_marks' => 'required|numeric|min:0.1',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.text' => 'nullable|string',
            'questions.*.correct_option' => 'nullable|integer|min:0',
        ];
    }

    protected function isBlankHtml(?string $html): bool
    {
        $text = trim(html_entity_decode(strip_tags((string) $html)));

        return $text === '';
    }

    protected function fill(LmsQuestion $q, Request $request, bool $creating): void
    {
        $this->fillFromArray($q, [
            'type' => $request->input('type'),
            'stem' => $request->input('stem'),
            'topic' => $request->input('topic'),
            'course_id' => $request->input('course_id'),
            'max_marks' => $request->input('max_marks', 1),
            'explanation' => $request->input('explanation'),
            'status' => $request->input('status', 'active'),
            'options' => $request->input('options', []),
            'correct_option' => $request->input('correct_option', 0),
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
        $q->max_marks = (float) ($row['max_marks'] ?? 1);
        $explanation = trim((string) ($row['explanation'] ?? ''));
        $q->explanation = $this->isBlankHtml($explanation) ? null : $explanation;
        $status = $defaults['status'] ?? ($row['status'] ?? 'active');
        $q->status = $status === 'active' ? 'active' : 'disabled';

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
        } else {
            $q->options_json = null;
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
            'courses' => $this->lms->coursesForActor(),
        ];
    }
}
