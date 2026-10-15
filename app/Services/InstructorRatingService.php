<?php

namespace App\Services;

use App\Models\ClassBatch;
use App\Models\InstructorStudentReview;
use App\Models\StudyMaterialFolder;
use App\Models\StudyMaterialInstructorAccess;
use App\Models\StudyMaterialStudentAccess;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class InstructorRatingService
{
    public function __construct(protected StudyMaterialService $lms)
    {
    }

    public function ensureSchema(): bool
    {
        $ok = InstructorStudentReview::ensureTable();
        ClassBatch::ensureRateAndReviewColumns();
        StudyMaterialFolder::ensureRateAndReviewColumns();

        return $ok;
    }

    /**
     * Rateable instructor targets for a student (batches + folders with active assignment).
     *
     * @return Collection<int, array{
     *   key: string,
     *   instructor: User,
     *   batch: ?ClassBatch,
     *   folder: ?StudyMaterialFolder,
     *   review_text_required: bool,
     *   existing: ?InstructorStudentReview
     * }>
     */
    public function eligibleTargetsForStudent(int $studentId): Collection
    {
        if (! $this->ensureSchema()) {
            return collect();
        }

        $targets = collect();

        if (Schema::hasTable('class_batches') && Schema::hasTable('class_batch_student')) {
            $batches = ClassBatch::query()
                ->with(['instructors', 'headOfFaculty', 'course:id,title,short_name'])
                ->where('status', 'active')
                ->whereHas('students', fn ($q) => $q->where('users.id', $studentId))
                ->get();

            foreach ($batches as $batch) {
                if (! $this->batchReviewsEnabled($batch)) {
                    continue;
                }
                foreach ($this->batchRateableInstructors($batch) as $instructor) {
                    $key = 'batch:' . $batch->id . ':ins:' . $instructor->id;
                    $targets->put($key, [
                        'key' => $key,
                        'instructor' => $instructor,
                        'batch' => $batch,
                        'folder' => null,
                        'review_text_required' => $this->batchReviewTextRequired($batch),
                        'existing' => null,
                    ]);
                }
            }
        }

        if (Schema::hasTable('study_material_folders') && Schema::hasTable('study_material_student_access')) {
            $accessRows = StudyMaterialStudentAccess::query()
                ->with(['folder.instructorAccess.instructor', 'folder.headOfFaculty', 'folder.course:id,title,short_name'])
                ->where('student_id', $studentId)
                ->where('status', 'active')
                ->get();

            foreach ($accessRows as $access) {
                $folder = $access->folder;
                if (! $folder || ! $this->lms->studentHasActiveAccess($studentId, (int) $folder->id)) {
                    continue;
                }
                if (! $this->folderReviewsEnabled($folder)) {
                    continue;
                }
                foreach ($this->folderRateableInstructors($folder) as $instructor) {
                    $key = 'folder:' . $folder->id . ':ins:' . $instructor->id;
                    // Prefer batch-scoped entry if same instructor already listed from a batch.
                    if ($targets->contains(fn ($row) => (int) $row['instructor']->id === (int) $instructor->id && $row['batch'])) {
                        continue;
                    }
                    $targets->put($key, [
                        'key' => $key,
                        'instructor' => $instructor,
                        'batch' => null,
                        'folder' => $folder,
                        'review_text_required' => $this->folderReviewTextRequired($folder),
                        'existing' => null,
                    ]);
                }
            }
        }

        $existing = InstructorStudentReview::query()
            ->where('student_id', $studentId)
            ->get();

        return $targets->values()->map(function (array $row) use ($existing) {
            $row['existing'] = $existing->first(function (InstructorStudentReview $review) use ($row) {
                if ((int) $review->instructor_id !== (int) $row['instructor']->id) {
                    return false;
                }
                if ($row['batch']) {
                    return (int) $review->class_batch_id === (int) $row['batch']->id;
                }
                if ($row['folder']) {
                    return (int) $review->study_material_folder_id === (int) $row['folder']->id;
                }

                return false;
            });

            return $row;
        });
    }

    public function findTarget(int $studentId, string $key): ?array
    {
        return $this->eligibleTargetsForStudent($studentId)
            ->first(fn ($row) => ($row['key'] ?? '') === $key);
    }

    public function studentCanRate(int $studentId, int $instructorId, ?int $batchId, ?int $folderId): bool
    {
        return $this->eligibleTargetsForStudent($studentId)->contains(function (array $row) use ($instructorId, $batchId, $folderId) {
            if ((int) $row['instructor']->id !== $instructorId) {
                return false;
            }
            if ($batchId) {
                return $row['batch'] && (int) $row['batch']->id === $batchId;
            }
            if ($folderId) {
                return $row['folder'] && (int) $row['folder']->id === $folderId;
            }

            return false;
        });
    }

    /**
     * @return array{average: float|null, count: int}
     */
    public function scoreForInstructor(int $instructorId, bool $publishedOnly = false): array
    {
        if (! $this->ensureSchema()) {
            return ['average' => null, 'count' => 0];
        }

        $query = InstructorStudentReview::query()->forInstructor($instructorId);
        if ($publishedOnly) {
            $query->published();
        }

        $count = (int) $query->count();
        if ($count === 0) {
            return ['average' => null, 'count' => 0];
        }

        $avg = (float) InstructorStudentReview::query()
            ->forInstructor($instructorId)
            ->when($publishedOnly, fn ($q) => $q->published())
            ->avg('stars');

        return [
            'average' => round($avg, 1),
            'count' => $count,
        ];
    }

    /**
     * @return Collection<int, array{instructor_id:int, average:float, count:int, instructor:?User}>
     */
    public function reportByInstructor(?int $instructorId = null): Collection
    {
        if (! $this->ensureSchema()) {
            return collect();
        }

        $query = InstructorStudentReview::query()
            ->selectRaw('instructor_id, AVG(stars) as average, COUNT(*) as review_count')
            ->groupBy('instructor_id')
            ->orderByDesc('average');

        if ($instructorId) {
            $query->where('instructor_id', $instructorId);
        }

        $rows = $query->get();
        $instructors = User::query()
            ->whereIn('id', $rows->pluck('instructor_id'))
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($instructors) {
            return [
                'instructor_id' => (int) $row->instructor_id,
                'average' => round((float) $row->average, 1),
                'count' => (int) $row->review_count,
                'instructor' => $instructors->get((int) $row->instructor_id),
            ];
        });
    }

    protected function batchRateableInstructors(ClassBatch $batch): Collection
    {
        $people = collect();
        foreach ($batch->trainerInstructors() as $user) {
            $people->put((int) $user->id, $user);
        }
        if ($batch->headOfFaculty) {
            $people->put((int) $batch->headOfFaculty->id, $batch->headOfFaculty);
        }

        return $people->values();
    }

    protected function folderRateableInstructors(StudyMaterialFolder $folder): Collection
    {
        $people = collect();
        foreach ($folder->displayTrainerInstructors() as $user) {
            $people->put((int) $user->id, $user);
        }
        $hof = $folder->displayHeadOfFaculty();
        if ($hof) {
            $people->put((int) $hof->id, $hof);
        }

        // Fallback: any active instructor access on the folder.
        if ($people->isEmpty() && Schema::hasTable('study_material_instructor_access')) {
            $access = StudyMaterialInstructorAccess::query()
                ->with('instructor')
                ->where('folder_id', $folder->id)
                ->where('status', 'active')
                ->get();
            foreach ($access as $row) {
                if ($row->instructor) {
                    $people->put((int) $row->instructor->id, $row->instructor);
                }
            }
        }

        return $people->values();
    }

    protected function batchReviewsEnabled(ClassBatch $batch): bool
    {
        if (! Schema::hasColumn('class_batches', 'rate_and_review_enabled')) {
            return true;
        }

        return (bool) ($batch->rate_and_review_enabled ?? true);
    }

    protected function folderReviewsEnabled(StudyMaterialFolder $folder): bool
    {
        if (! Schema::hasColumn('study_material_folders', 'rate_and_review_enabled')) {
            return true;
        }

        return (bool) ($folder->rate_and_review_enabled ?? true);
    }

    protected function batchReviewTextRequired(ClassBatch $batch): bool
    {
        if (! Schema::hasColumn('class_batches', 'rate_review_text_required')) {
            return false;
        }

        return (bool) ($batch->rate_review_text_required ?? false);
    }

    protected function folderReviewTextRequired(StudyMaterialFolder $folder): bool
    {
        if (! Schema::hasColumn('study_material_folders', 'rate_review_text_required')) {
            return false;
        }

        return (bool) ($folder->rate_review_text_required ?? false);
    }
}
