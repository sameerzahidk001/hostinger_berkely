<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseFee;
use App\Models\StudyMaterialFolder;
use App\Models\StudyMaterialItem;
use App\Models\User;
use App\Services\StudyMaterialService;
use App\Services\ZohoLmsService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StudyMaterialFolderController extends Controller
{
    public function __construct(
        protected StudyMaterialService $lms,
        protected ZohoLmsService $zoho
    ) {
    }

    public function index(Request $request)
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('study_material_folders')) {
            return redirect()
                ->route('admin.lms.install')
                ->with('fail', 'LMS tables are missing. Create them here (do not use Ignition Run Migrations).');
        }

        $search = trim((string) $request->get('search', ''));
        $query = $this->lms->foldersQueryForActor();

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhereHas('course', function ($course) use ($like) {
                        $course->where('title', 'like', $like);
                    })
                    ->orWhereHas('instructorAccess.instructor', function ($instructor) use ($like) {
                        $instructor->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    });
            });
        }

        $folders = $query->paginate(20)->withQueryString();

        return view('admin.study-materials.folders.index', [
            'folders' => $folders,
            'isAdmin' => $this->lms->isAdminActor(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        $courses = Course::query()->where('status', 1)->orderBy('title')->get(['id', 'title']);
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.study-materials.folders.create', [
            'courses' => $courses,
            'instructors' => $instructors,
            'isAdmin' => $this->lms->isAdminActor(),
            'packages' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $request->request->remove('code');
        $validator = Validator::make($request->all(), $this->folderRules());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $status = 'disabled';
        if ($this->lms->canEnableFolder() && $request->input('status') === 'active') {
            $status = 'active';
        }

        $packageIds = $this->packageIdsFromRequest($request);
        $validityMonths = $this->validityMonthsFromRequest($request);

        $folder = new StudyMaterialFolder();
        $folder->name = trim($request->name);
        $folder->code = null;
        $folder->course_id = $request->course_id;
        $folder->fee_package_id = $packageIds[0] ?? null;
        $folder->validity_months = $validityMonths;
        $folder->status = $status;

        if ($this->lms->isInstructorActor()) {
            $folder->owner_type = 'instructor';
            $folder->owner_id = Auth::id();
            $folder->created_by_user_id = Auth::id();
        } else {
            $folder->owner_type = 'admin';
            $folder->owner_id = Auth::guard('admin')->id();
            $folder->created_by_admin_id = Auth::guard('admin')->id();
        }

        $folder->save();
        $folder->ensureCode();
        $folder->feePackages()->sync($packageIds);

        $accessTill = $this->lms->computeAccessTill(now(), $folder->validity_months);

        if ($this->lms->isInstructorActor()) {
            $folder->instructorAccess()->create([
                'instructor_id' => Auth::id(),
                'status' => 'active',
                'issued_at' => now()->toDateString(),
                'access_till' => $accessTill?->toDateString(),
                'sent_at' => now(),
            ]);
        } elseif ($request->filled('instructor_ids')) {
            foreach ((array) $request->instructor_ids as $instructorId) {
                $folder->instructorAccess()->firstOrCreate(
                    ['instructor_id' => $instructorId],
                    [
                        'status' => 'disabled',
                        'issued_at' => now()->toDateString(),
                        'access_till' => $accessTill?->toDateString(),
                    ]
                );
            }
        }

        $this->storeStructure((array) $request->input('structure', []), $folder, null, 0);

        return redirect()
            ->route('admin.study-materials.folders.edit', $folder->id)
            ->with('success', 'Folder structure saved. Now add files to the main folder or a subfolder.');
    }

    public function sendStudents($id)
    {
        $folder = StudyMaterialFolder::findOrFail($id);
        abort_unless($this->lms->canManageFolder($folder), 403);

        $result = $this->lms->sendFolderStudentEmails($folder);

        return $this->folderSendRedirect($result, 'student');
    }

    public function sendInstructors($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $folder = StudyMaterialFolder::findOrFail($id);
        $result = $this->lms->sendFolderInstructorEmails($folder);

        return $this->folderSendRedirect($result, 'instructor');
    }

    protected function folderSendRedirect(array $result, string $audience)
    {
        if ($result['total'] === 0) {
            return redirect()->back()->with(
                'fail',
                'No ' . $audience . 's are assigned to this folder yet. Assign access first, then send.'
            );
        }

        if ($result['sent'] === 0) {
            return redirect()->back()->with(
                'fail',
                'Could not send ' . $audience . ' emails. Check Admin → Email Templates and SMTP settings.'
            );
        }

        $message = $result['sent'] . ' ' . $audience . ' email(s) sent.';
        if ($result['failed'] > 0) {
            $message .= ' ' . $result['failed'] . ' failed.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function edit($id)
    {
        $folder = StudyMaterialFolder::with(['rootItems.childrenRecursive', 'instructorAccess', 'studentAccess', 'items', 'feePackages'])->findOrFail($id);
        abort_unless($this->lms->canManageFolder($folder), 403);

        $courses = Course::query()->where('status', 1)->orderBy('title')->get(['id', 'title']);
        $packages = $this->packagesForCourse($folder->course_id)->get();
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.study-materials.folders.edit', [
            'folder' => $folder,
            'courses' => $courses,
            'packages' => $packages,
            'instructors' => $instructors,
            'isAdmin' => $this->lms->isAdminActor(),
            'lockedInstructor' => $folder->owner_type === 'instructor',
            'selectedPackageIds' => old('fee_package_ids', $folder->selectedPackageIds()),
            'folderOptions' => $folder->folderTreeOptions(),
            'zohoWorkDriveReady' => $this->zoho->isWorkDriveReady(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $folder = StudyMaterialFolder::findOrFail($id);
        abort_unless($this->lms->canManageFolder($folder), 403);

        $request->request->remove('code');
        $validator = Validator::make($request->all(), $this->folderRules($folder->id));
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $packageIds = $this->packageIdsFromRequest($request);
        $wasActive = $folder->status === 'active';

        $folder->name = trim($request->name);
        $folder->course_id = $request->course_id;
        $folder->fee_package_id = $packageIds[0] ?? null;
        $folder->validity_months = $this->validityMonthsFromRequest($request);

        if ($this->lms->isAdminActor()) {
            $folder->status = $request->input('status', $folder->status) === 'active' ? 'active' : 'disabled';
        } elseif ($this->lms->canDisableFolder($folder) && $request->input('status') === 'disabled') {
            $folder->status = 'disabled';
        }

        $folder->save();
        $folder->ensureCode();
        $folder->feePackages()->sync($packageIds);

        if ($wasActive && $folder->status === 'disabled') {
            $this->lms->notifyAssignedStudentsDisabled($folder, 'folder_disabled');
        }

        return redirect()
            ->route('admin.study-materials.folders.edit', $folder->id)
            ->with('success', 'Folder details updated.');
    }

    public function storeSubfolder(Request $request, $id)
    {
        $folder = StudyMaterialFolder::with('items')->findOrFail($id);
        abort_unless($this->lms->canManageFolder($folder), 403);

        $validator = Validator::make($request->all(), [
            'subfolder_name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:study_material_items,id',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $parentId = $this->resolvedParentId($folder, $request->input('parent_id'));

        StudyMaterialItem::create([
            'folder_id' => $folder->id,
            'parent_id' => $parentId,
            'type' => 'folder',
            'name' => trim($request->subfolder_name),
        ]);

        return redirect()
            ->route('admin.study-materials.folders.edit', $folder->id)
            ->with('success', 'Subfolder added.');
    }

    public function storeFile(Request $request, $id)
    {
        $folder = StudyMaterialFolder::with('items')->findOrFail($id);
        abort_unless($this->lms->canManageFolder($folder), 403);

        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:study_material_items,id',
            'source' => 'required|in:upload,workdrive,zoho',
            'files' => 'required_if:source,upload|required_if:source,workdrive|nullable|array',
            'files.*' => 'nullable|file|max:102400',
            'zoho_name' => 'required_if:source,zoho|nullable|string|max:255',
            'zoho_url' => 'required_if:source,zoho|nullable|url|max:2000',
        ], [
            'files.required_if' => 'Choose at least one file to upload.',
            'files.*.uploaded' => 'The file failed to upload. Maximum size is 100 MB.',
            'files.*.max' => 'The file failed to upload. Maximum size is 100 MB.',
            'zoho_name.required_if' => 'Enter a display name for the Zoho WorkDrive file.',
            'zoho_url.required_if' => 'Enter the Zoho WorkDrive link.',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $parentId = $this->resolvedParentId($folder, $request->input('parent_id'));

        if ($request->input('source') === 'zoho') {
            StudyMaterialItem::create([
                'folder_id' => $folder->id,
                'parent_id' => $parentId,
                'type' => 'file',
                'name' => trim($request->zoho_name),
                'external_url' => trim($request->zoho_url),
                'mime' => 'zoho/workdrive',
            ]);

            return redirect()
                ->route('admin.study-materials.folders.edit', $folder->id)
                ->with('success', 'Zoho WorkDrive file added.');
        }

        if ($request->input('source') === 'workdrive') {
            if (!$this->zoho->isWorkDriveReady()) {
                return redirect()->back()->with('fail', 'Connect Zoho WorkDrive OAuth first, or paste a WorkDrive link instead.');
            }

            $uploaded = 0;
            try {
                foreach ((array) $request->file('files', []) as $file) {
                    if (!$file instanceof UploadedFile || !$file->isValid()) {
                        continue;
                    }
                    $remote = $this->zoho->uploadToWorkDrive($file);
                    if (!$remote || empty($remote['url'])) {
                        continue;
                    }
                    StudyMaterialItem::create([
                        'folder_id' => $folder->id,
                        'parent_id' => $parentId,
                        'type' => 'file',
                        'name' => $file->getClientOriginalName(),
                        'external_url' => $remote['url'],
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => 'zoho/workdrive',
                        'size' => $file->getSize(),
                    ]);
                    $uploaded++;
                }
            } catch (\Throwable $e) {
                Log::error('Zoho WorkDrive upload threw', ['message' => $e->getMessage()]);
                return redirect()->back()->with(
                    'fail',
                    'WorkDrive could not be reached from this PC. Use Paste WorkDrive link, or Upload to server.'
                );
            }

            if ($uploaded === 0) {
                return redirect()->back()->with('fail', 'WorkDrive upload failed. Paste a WorkDrive link, or upload to the server.');
            }

            return redirect()
                ->route('admin.study-materials.folders.edit', $folder->id)
                ->with('success', $uploaded . ' file(s) uploaded to Zoho WorkDrive.');
        }

        $this->storeUploadedFiles($request->file('files', []), $folder, $parentId);

        return redirect()
            ->route('admin.study-materials.folders.edit', $folder->id)
            ->with('success', 'File(s) uploaded.');
    }

    public function destroy($id)
    {
        abort_unless($this->lms->isAdminActor(), 403);

        $folder = StudyMaterialFolder::with('items')->findOrFail($id);

        $this->lms->notifyAssignedStudentsDisabled($folder, 'deleted');

        foreach ($folder->items as $item) {
            if ($item->disk_path && File::exists(public_path($item->disk_path))) {
                File::delete(public_path($item->disk_path));
            }
        }

        $uploadDir = public_path('uploads/study-materials/' . $folder->id);
        if (File::isDirectory($uploadDir)) {
            File::deleteDirectory($uploadDir);
        }

        $folder->studentAccess()->delete();
        $folder->instructorAccess()->delete();
        $folder->feePackages()->detach();
        $folder->delete();

        return redirect()
            ->route('admin.study-materials.folders.index')
            ->with('success', 'Folder deleted. Student and instructor access were removed.');
    }

    public function renameItem(Request $request, $id)
    {
        $item = StudyMaterialItem::with('folder')->findOrFail($id);
        abort_unless($this->lms->canManageFolder($item->folder), 403);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $item->name = trim($request->input('name'));
        $item->save();

        return redirect()->back()->with('success', 'Item renamed.');
    }

    public function toggleDownload(Request $request, $id)
    {
        $item = StudyMaterialItem::with('folder')->findOrFail($id);
        abort_unless($this->lms->canManageFolder($item->folder), 403);
        abort_if($item->type !== 'file', 404);

        $item->allow_download = $request->boolean('allow_download');
        $item->save();

        return redirect()->back()->with(
            'success',
            $item->allow_download
                ? 'Students can download this file.'
                : 'Download is hidden for students on this file.'
        );
    }

    public function destroyItem($id)
    {
        $item = StudyMaterialItem::with('folder')->findOrFail($id);
        abort_unless($this->lms->canManageFolder($item->folder), 403);

        $this->deleteItemRecursive($item);

        return redirect()->back()->with('success', 'Item removed.');
    }

    public function packagesByCourse($courseId)
    {
        $packages = $this->packagesForCourse($courseId)
            ->get(['id', 'package_name', 'price', 'currency']);

        return response()->json($packages);
    }

    protected function folderRules(?int $folderId = null): array
    {
        $nameRule = 'required|string|max:255|unique:study_material_folders,name';
        if ($folderId) {
            $nameRule .= ',' . $folderId;
        }

        return [
            'name' => $nameRule,
            'course_id' => 'required|exists:courses,id',
            'fee_package_ids' => 'nullable|array',
            'fee_package_ids.*' => 'exists:course_fees,id',
            'validity_months' => 'nullable|integer|min:1|max:24',
            'status' => 'nullable|in:disabled,active',
            'instructor_ids' => 'nullable|array',
            'instructor_ids.*' => 'exists:users,id',
            'structure' => 'nullable|array',
            'structure.*.name' => 'nullable|string|max:255',
        ];
    }

    protected function normalizedFolderCode($code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : $code;
    }

    protected function validityMonthsFromRequest(Request $request): ?int
    {
        if (!$request->filled('validity_months')) {
            return null;
        }

        $months = (int) $request->input('validity_months');

        return $months > 0 ? min(24, $months) : null;
    }

    protected function packageIdsFromRequest(Request $request): array
    {
        return collect((array) $request->input('fee_package_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function packagesForCourse($courseId)
    {
        return CourseFee::query()
            ->where('courses_id', $courseId)
            ->orderBy('package_name');
    }

    protected function resolvedParentId(StudyMaterialFolder $folder, $parentId): ?int
    {
        if (!$parentId) {
            return null;
        }

        $parent = $folder->items
            ->where('id', (int) $parentId)
            ->where('type', 'folder')
            ->first();

        abort_unless($parent, 422, 'Choose a folder inside this study materials folder.');

        return (int) $parent->id;
    }

    protected function storeStructure(array $nodes, StudyMaterialFolder $folder, $parentId = null, int $depth = 0): void
    {
        if ($depth > 3) {
            return;
        }

        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $name = trim((string) ($node['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $item = StudyMaterialItem::create([
                'folder_id' => $folder->id,
                'parent_id' => $parentId,
                'type' => 'folder',
                'name' => $name,
            ]);

            $this->storeStructure((array) ($node['children'] ?? []), $folder, $item->id, $depth + 1);
        }
    }

    protected function storeUploadedFiles($files, StudyMaterialFolder $folder, $parentId = null): void
    {
        if (empty($files)) {
            return;
        }

        $dir = public_path('uploads/study-materials/' . $folder->id);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $uploadedFiles = is_array($files) ? $files : [$files];

        foreach ($uploadedFiles as $uploaded) {
            if (!$uploaded instanceof UploadedFile || !$uploaded->isValid()) {
                continue;
            }
            $safe = Str::slug(pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME));
            $filename = $safe . '_' . time() . '_' . Str::random(4) . '.' . $uploaded->getClientOriginalExtension();
            $uploaded->move($dir, $filename);
            $relative = 'uploads/study-materials/' . $folder->id . '/' . $filename;

            StudyMaterialItem::create([
                'folder_id' => $folder->id,
                'parent_id' => $parentId,
                'type' => 'file',
                'name' => $uploaded->getClientOriginalName(),
                'disk_path' => $relative,
                'original_name' => $uploaded->getClientOriginalName(),
                'mime' => $uploaded->getClientMimeType(),
                'size' => File::size(public_path($relative)),
            ]);
        }
    }

    protected function deleteItemRecursive(StudyMaterialItem $item): void
    {
        foreach ($item->children as $child) {
            $this->deleteItemRecursive($child);
        }
        if ($item->disk_path && File::exists(public_path($item->disk_path))) {
            File::delete(public_path($item->disk_path));
        }
        $item->delete();
    }
}
