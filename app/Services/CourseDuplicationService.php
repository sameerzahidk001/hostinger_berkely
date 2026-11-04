<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseBeneficiaries;
use App\Models\CourseEnrollment;
use App\Models\CourseFaq;
use App\Models\CourseFee;
use App\Models\CourseFeePackages;
use App\Models\CourseObjective;
use App\Models\CourseReward;
use App\Models\CourseStructure;
use App\Models\CourseStructureFirst;
use App\Models\CourseStructureSubHeading;
use App\Models\CourseStructureSubHeadingFirst;
use App\Models\CourseStructureSubHeadingUnit;
use App\Models\CourseTestimonial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CourseDuplicationService
{
    public function duplicate(Course $source): Course
    {
        return DB::transaction(function () use ($source) {
            $source->loadMissing([
                'courseObjectivePoints',
                'courseBeneficiaries',
                'courseRewards',
                'courseSyllabus.courseSyllabusHighlights',
                'courseEnrollments',
                'courseFaq',
                'dynamicLabel',
                'seo',
                'categories',
                'schools',
                'subjects',
                'relatedCourses',
                'testimonials',
                'courseStructures.subHeadings.subHeadingsUnits',
                'courseStructuresFirst.subHeadingsFirst',
                'courseFeePackages',
                'feePackages.packageFeatures',
            ]);

            $copy = $source->replicate(['created_by', 'updated_by', 'deleted_at']);
            $copy->title = $this->uniqueTitle($source->title ?: 'Course');
            $copy->slug = $this->uniqueSlug($copy->title);
            if (! empty($source->short_name)) {
                $copy->short_name = 'Copy of ' . $source->short_name;
            }
            // Draft = disabled via soft delete so original stays live on the site.
            $copy->deleted_at = now();
            $copy->save();

            $this->copyBelongsToMany($source, $copy);
            $this->copySimpleHasMany($source->courseObjectivePoints, CourseObjective::class, 'course_id', $copy->id);
            $this->copySimpleHasMany($source->courseBeneficiaries, CourseBeneficiaries::class, 'course_id', $copy->id);
            $this->copySimpleHasMany($source->courseRewards, CourseReward::class, 'course_id', $copy->id);
            $this->copySimpleHasMany($source->courseEnrollments, CourseEnrollment::class, 'course_id', $copy->id);
            $this->copySimpleHasMany($source->courseFaq, CourseFaq::class, 'course_id', $copy->id);
            $this->copySimpleHasMany($source->testimonials, CourseTestimonial::class, 'course_id', $copy->id);
            $this->copySimpleHasMany($source->courseFeePackages, CourseFee::class, 'courses_id', $copy->id);

            $this->copySyllabus($source, $copy);
            $this->copyStructures($source, $copy);
            $this->copyStructuresFirst($source, $copy);
            $this->copyLegacyFeePackages($source, $copy);
            $this->copyDynamicLabel($source, $copy);
            $this->copySeo($source, $copy);

            record_panel_activity(
                'Course Duplicated',
                ($source->title ?: 'Course #' . $source->id) . ' → ' . $copy->title,
                route('course.edit', $copy->id)
            );

            return $copy->fresh();
        });
    }

    private function copyBelongsToMany(Course $source, Course $copy): void
    {
        if ($source->categories->isNotEmpty()) {
            $copy->categories()->sync($source->categories->pluck('id')->all());
        }
        if ($source->schools->isNotEmpty()) {
            $copy->schools()->sync($source->schools->pluck('id')->all());
        }
        if ($source->subjects->isNotEmpty()) {
            $copy->subjects()->sync($source->subjects->pluck('id')->all());
        }
        if ($source->relatedCourses->isNotEmpty()) {
            $copy->relatedCourses()->sync($source->relatedCourses->pluck('id')->all());
        }
    }

    private function copySimpleHasMany($rows, string $modelClass, string $fk, int $newId): void
    {
        foreach ($rows as $row) {
            $new = $row->replicate(['created_by', 'updated_by', $fk]);
            $new->{$fk} = $newId;
            $new->save();
        }
    }

    private function copySyllabus(Course $source, Course $copy): void
    {
        foreach ($source->courseSyllabus as $syllabus) {
            $newSyllabus = $syllabus->replicate(['created_by', 'updated_by', 'course_id']);
            $newSyllabus->course_id = $copy->id;
            $newSyllabus->save();

            foreach ($syllabus->courseSyllabusHighlights as $highlight) {
                $newHighlight = $highlight->replicate(['course_syllabus_id']);
                $newHighlight->course_syllabus_id = $newSyllabus->id;
                $newHighlight->save();
            }
        }
    }

    private function copyStructures(Course $source, Course $copy): void
    {
        foreach ($source->courseStructures as $structure) {
            $newStructure = $structure->replicate(['courses_id']);
            $newStructure->courses_id = $copy->id;
            $newStructure->save();

            foreach ($structure->subHeadings as $sub) {
                $newSub = new CourseStructureSubHeading();
                $newSub->forceFill([
                    'course_structures_id' => $newStructure->id,
                    'sub_heading' => $sub->sub_heading,
                ])->save();

                foreach ($sub->subHeadingsUnits as $unit) {
                    CourseStructureSubHeadingUnit::create([
                        'sub_headings_id' => $newSub->id,
                        'unit_title' => $unit->unit_title,
                        'unit_video' => $unit->unit_video,
                        'thumbnail' => $unit->thumbnail,
                    ]);
                }
            }
        }
    }

    private function copyStructuresFirst(Course $source, Course $copy): void
    {
        foreach ($source->courseStructuresFirst as $structure) {
            $newStructure = $structure->replicate(['courses_id']);
            $newStructure->courses_id = $copy->id;
            $newStructure->save();

            foreach ($structure->subHeadingsFirst as $sub) {
                $newSub = new CourseStructureSubHeadingFirst();
                $attrs = [
                    'sub_heading' => $sub->sub_heading,
                ];
                if (Schema::hasColumn($newSub->getTable(), 'course_structures_id')) {
                    $attrs['course_structures_id'] = $newStructure->id;
                } elseif (Schema::hasColumn($newSub->getTable(), 'course_structure_id')) {
                    $attrs['course_structure_id'] = $newStructure->id;
                }
                $newSub->forceFill($attrs)->save();
            }
        }
    }

    private function copyLegacyFeePackages(Course $source, Course $copy): void
    {
        foreach ($source->feePackages as $package) {
            $newPackage = $package->replicate(['courses_id']);
            $newPackage->courses_id = $copy->id;
            $newPackage->save();

            if ($package->packageFeatures) {
                $features = $package->packageFeatures->replicate(['fee_packages_id']);
                $features->fee_packages_id = $newPackage->id;
                $features->save();
            }
        }
    }

    private function copyDynamicLabel(Course $source, Course $copy): void
    {
        if (! $source->dynamicLabel) {
            return;
        }

        $label = $source->dynamicLabel->replicate(['course_id']);
        $label->course_id = $copy->id;
        $label->save();
    }

    private function copySeo(Course $source, Course $copy): void
    {
        if (! $source->seo) {
            return;
        }

        $seo = $source->seo->replicate(['created_by', 'updated_by', 'page_id', 'course_id']);
        $seo->course_id = $copy->id;
        $seo->page_id = null;
        // Media path reused; original files are never deleted or overwritten.
        $seo->save();
    }

    private function uniqueTitle(string $title): string
    {
        $base = 'Copy of ' . trim($title);
        $candidate = $base;
        $i = 2;

        while (Course::withTrashed()->where('title', $candidate)->exists()) {
            $candidate = $base . ' (' . $i . ')';
            $i++;
        }

        return $candidate;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'copy-of-course';
        $candidate = $base;
        $i = 1;

        while (Course::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
