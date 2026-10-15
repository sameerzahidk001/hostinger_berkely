<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\Currency;
use App\Models\InstructorBatchPayment;
use App\Models\User;
use App\Services\StudyMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InstructorBatchPaymentController extends Controller
{
    public function __construct(protected StudyMaterialService $lms)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeManage();
        InstructorBatchPayment::ensureTable();

        $batchId = (int) $request->get('batch_id', 0);
        $instructorId = (int) $request->get('instructor_id', 0);
        $status = trim((string) $request->get('status', ''));
        $search = trim((string) $request->get('search', ''));

        $query = InstructorBatchPayment::query()
            ->with(['batch.course:id,title', 'instructor:id,name,email'])
            ->orderByDesc('updated_at');

        if ($batchId > 0) {
            $query->where('class_batch_id', $batchId);
        }
        if ($instructorId > 0) {
            $query->where('instructor_id', $instructorId);
        }
        if ($status !== '' && array_key_exists($status, InstructorBatchPayment::statuses())) {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('notes', 'like', $like)
                    ->orWhere('currency', 'like', $like)
                    ->orWhereHas('instructor', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orWhereHas('batch', fn ($b) => $b->where('name', 'like', $like)->orWhere('code', 'like', $like));
            });
        }

        return view('admin.instructor-payments.index', [
            'rows' => $query->paginate(20)->withQueryString(),
            'batches' => $this->batchesForSelect(),
            'instructors' => $this->instructorsForSelect(),
            'statuses' => InstructorBatchPayment::statuses(),
            'filters' => [
                'batch_id' => $batchId,
                'instructor_id' => $instructorId,
                'status' => $status,
                'search' => $search,
            ],
            'isAdmin' => $this->lms->isAdminActor(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManage();
        InstructorBatchPayment::ensureTable();

        $payment = new InstructorBatchPayment([
            'status' => InstructorBatchPayment::STATUS_PENDING,
            'currency' => 'AED',
            'class_batch_id' => $request->get('batch_id'),
            'instructor_id' => $request->get('instructor_id'),
        ]);

        return view('admin.instructor-payments.form', $this->formData($payment));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        InstructorBatchPayment::ensureTable();

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $batchId = (int) $request->input('class_batch_id');
        $instructorId = (int) $request->input('instructor_id');

        if (! $this->instructorOnBatch($batchId, $instructorId)) {
            return redirect()->back()
                ->withErrors(['instructor_id' => 'Selected instructor is not assigned to this batch.'])
                ->withInput();
        }

        if (InstructorBatchPayment::query()
            ->where('class_batch_id', $batchId)
            ->where('instructor_id', $instructorId)
            ->exists()) {
            return redirect()->back()
                ->withErrors(['instructor_id' => 'A payment record already exists for this instructor on this batch. Edit it instead.'])
                ->withInput();
        }

        $payment = new InstructorBatchPayment();
        $this->fillPayment($payment, $request, true);
        $this->storeSlip($payment, $request);
        $payment->save();

        return redirect()
            ->route('admin.instructor-payments.index')
            ->with('success', 'Instructor payment saved.');
    }

    public function edit($id)
    {
        $this->authorizeManage();
        InstructorBatchPayment::ensureTable();
        $payment = InstructorBatchPayment::with(['batch', 'instructor'])->findOrFail($id);

        return view('admin.instructor-payments.form', $this->formData($payment));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManage();
        InstructorBatchPayment::ensureTable();
        $payment = InstructorBatchPayment::findOrFail($id);

        $validator = Validator::make($request->all(), $this->rules($payment->id));
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $batchId = (int) $request->input('class_batch_id');
        $instructorId = (int) $request->input('instructor_id');

        if (! $this->instructorOnBatch($batchId, $instructorId)) {
            return redirect()->back()
                ->withErrors(['instructor_id' => 'Selected instructor is not assigned to this batch.'])
                ->withInput();
        }

        $duplicate = InstructorBatchPayment::query()
            ->where('class_batch_id', $batchId)
            ->where('instructor_id', $instructorId)
            ->where('id', '!=', $payment->id)
            ->exists();
        if ($duplicate) {
            return redirect()->back()
                ->withErrors(['instructor_id' => 'A payment record already exists for this instructor on this batch.'])
                ->withInput();
        }

        $this->fillPayment($payment, $request, false);
        $this->storeSlip($payment, $request);
        $payment->save();

        return redirect()
            ->route('admin.instructor-payments.index')
            ->with('success', 'Instructor payment updated.');
    }

    public function destroy($id)
    {
        abort_unless($this->lms->isAdminActor() || panel_sees_all_dashboards(), 403);
        InstructorBatchPayment::ensureTable();
        $payment = InstructorBatchPayment::findOrFail($id);
        $payment->delete();

        return redirect()
            ->route('admin.instructor-payments.index')
            ->with('success', 'Instructor payment removed.');
    }

    public function slip($id)
    {
        $this->authorizeManage();
        InstructorBatchPayment::ensureTable();
        $payment = InstructorBatchPayment::findOrFail($id);
        abort_unless($payment->hasSlip(), 404);

        $absolute = public_path(ltrim((string) $payment->payment_slip_path, '/\\'));
        abort_unless(is_file($absolute), 404);

        return response()->file($absolute);
    }

    public function batchInstructors(Request $request)
    {
        $this->authorizeManage();
        $batchId = (int) $request->get('batch_id', 0);
        abort_unless($batchId > 0 && Schema::hasTable('class_batches'), 404);

        $batch = ClassBatch::with(['instructors', 'headOfFaculty'])->findOrFail($batchId);
        $people = collect();
        foreach ($batch->trainerInstructors() as $user) {
            $people->put((int) $user->id, [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }
        if ($batch->headOfFaculty) {
            $people->put((int) $batch->headOfFaculty->id, [
                'id' => (int) $batch->headOfFaculty->id,
                'name' => $batch->headOfFaculty->name,
                'email' => $batch->headOfFaculty->email,
            ]);
        }

        return response()->json(['instructors' => $people->values()]);
    }

    protected function authorizeManage(): void
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return;
        }

        $role = normalize_panel_role(panel_role_name());
        abort_unless($role === 'accountant', 403);
    }

    protected function rules(?int $id = null): array
    {
        return [
            'class_batch_id' => 'required|exists:class_batches,id',
            'instructor_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'status' => 'required|in:pending,approved,paid',
            'due_date' => 'nullable|date',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'payment_slip' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ];
    }

    protected function fillPayment(InstructorBatchPayment $payment, Request $request, bool $creating): void
    {
        $payment->class_batch_id = (int) $request->input('class_batch_id');
        $payment->instructor_id = (int) $request->input('instructor_id');
        // Rate removed from UI; keep existing DB value untouched on edit, null on create.
        if ($creating) {
            $payment->rate = null;
        }
        $payment->amount = (float) $request->input('amount');
        $payment->currency = package_currency_code($request->input('currency'));
        $payment->status = (string) $request->input('status');
        if (\Illuminate\Support\Facades\Schema::hasColumn('instructor_batch_payments', 'due_date')) {
            $payment->due_date = $request->filled('due_date') ? $request->input('due_date') : null;
        }
        $payment->paid_date = $request->filled('paid_date') ? $request->input('paid_date') : null;
        $payment->notes = trim((string) $request->input('notes', '')) ?: null;

        if ($payment->status === InstructorBatchPayment::STATUS_PAID && ! $payment->paid_date) {
            $payment->paid_date = now()->toDateString();
        }
        if ($payment->status !== InstructorBatchPayment::STATUS_PAID && ! $request->filled('paid_date')) {
            // keep existing paid_date on update if status leaves paid with cleared date from form
        }

        if ($creating) {
            if ($this->lms->isAdminActor()) {
                $payment->created_by_admin_id = Auth::guard('admin')->id();
            } else {
                $payment->created_by_user_id = Auth::id();
            }
        }

        if ($this->lms->isAdminActor()) {
            $payment->updated_by_admin_id = Auth::guard('admin')->id();
        } else {
            $payment->updated_by_user_id = Auth::id();
        }
    }

    protected function storeSlip(InstructorBatchPayment $payment, Request $request): void
    {
        if (! $request->hasFile('payment_slip')) {
            return;
        }

        $file = $request->file('payment_slip');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $name = 'slip_' . ($payment->id ?: 'new') . '_' . Str::random(8) . '.' . $ext;
        $relativeDir = 'uploads/instructor-payment-slips';
        $dest = public_path($relativeDir);
        public_upload_move($file, $dest, $name);
        $payment->payment_slip_path = $relativeDir . '/' . $name;
    }

    protected function instructorOnBatch(int $batchId, int $instructorId): bool
    {
        if (! Schema::hasTable('class_batches')) {
            return false;
        }

        $batch = ClassBatch::with(['instructors', 'headOfFaculty'])->find($batchId);
        if (! $batch) {
            return false;
        }

        if ((int) $batch->head_of_faculty_id === $instructorId) {
            return true;
        }

        return $batch->instructors->contains(fn ($u) => (int) $u->id === $instructorId);
    }

    protected function formData(InstructorBatchPayment $payment): array
    {
        $currencies = Schema::hasTable('currencies')
            ? Currency::query()->orderBy('code')->get(['id', 'code', 'name'])
            : collect();

        return [
            'payment' => $payment,
            'batches' => $this->batchesForSelect(),
            'instructors' => $this->instructorsForSelect(),
            'statuses' => InstructorBatchPayment::statuses(),
            'currencies' => $currencies,
            'isAdmin' => $this->lms->isAdminActor(),
        ];
    }

    protected function batchesForSelect()
    {
        if (! Schema::hasTable('class_batches')) {
            return collect();
        }

        return ClassBatch::query()
            ->with('course:id,title')
            ->orderByDesc('id')
            ->get(['id', 'code', 'name', 'course_id']);
    }

    protected function instructorsForSelect()
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
