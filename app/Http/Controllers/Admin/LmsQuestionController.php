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
        $query = LmsQuestion::query()->with('course:id,title')->orderByDesc('id');
        if ($type !== '' && array_key_exists($type, LmsQuestion::types())) {
            $query->where('type', $type);
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('stem', 'like', $like)->orWhere('topic', 'like', $like);
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
        $validator = Validator::make($request->all(), $this->rules());
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
            'stem' => 'required|string',
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

    protected function fill(LmsQuestion $q, Request $request, bool $creating): void
    {
        $q->type = $request->input('type');
        $q->stem = trim((string) $request->input('stem'));
        $q->topic = trim((string) $request->input('topic', '')) ?: null;
        $q->course_id = $request->input('course_id') ?: null;
        $q->max_marks = (float) $request->input('max_marks', 1);
        $q->explanation = trim((string) $request->input('explanation', '')) ?: null;
        $q->status = $request->input('status', 'active') === 'active' ? 'active' : 'disabled';

        if ($q->type === LmsQuestion::TYPE_MCQ) {
            $options = [];
            $correct = (int) $request->input('correct_option', 0);
            foreach ((array) $request->input('options', []) as $i => $row) {
                $text = trim((string) ($row['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $options[] = [
                    'key' => (string) count($options),
                    'text' => $text,
                    'is_correct' => (int) $i === $correct || ! empty($row['is_correct']),
                ];
            }
            // Re-key correctness by correct_option index among non-empty options
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
