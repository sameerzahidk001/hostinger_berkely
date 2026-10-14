<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageDuplicationService
{
    public function duplicate(Page $source): Page
    {
        return DB::transaction(function () use ($source) {
            $source->loadMissing(['sections', 'faqs', 'seo']);

            $copy = $source->replicate(['created_by', 'updated_by']);
            $copy->page_name = $this->uniqueName($source->page_name ?: 'Page');
            $copy->url = $this->uniqueUrl(
                Page::slugFromName($copy->page_name),
                $source->parent_id
            );

            if (pages_status_enabled()) {
                $copy->status = 0; // draft / disabled — original stays live
            }

            $copy->save();

            foreach ($source->sections as $section) {
                PageSection::create([
                    'page_id' => $copy->id,
                    'section_type' => $section->section_type,
                    'order' => $section->order,
                    // Keep media paths as-is — do not delete or rewrite original files.
                    'data' => $section->data,
                ]);
            }

            foreach ($source->faqs as $faq) {
                $newFaq = $faq->replicate(['created_by', 'updated_by', 'page_id']);
                $newFaq->page_id = $copy->id;
                $newFaq->save();
            }

            if ($source->seo) {
                $newSeo = $source->seo->replicate(['created_by', 'updated_by', 'page_id', 'course_id']);
                $newSeo->page_id = $copy->id;
                $newSeo->course_id = null;
                // Thumbnail path reused; original image file is not touched.
                $newSeo->save();
            }

            record_panel_activity(
                'Page Duplicated',
                ($source->page_name ?: 'Page #' . $source->id) . ' → ' . $copy->page_name,
                route('pages.edit', $copy->id)
            );

            return $copy->fresh(['sections', 'faqs', 'seo']);
        });
    }

    private function uniqueName(string $name): string
    {
        $base = 'Copy of ' . trim($name);
        $candidate = $base;
        $i = 2;

        while (Page::where('page_name', $candidate)->exists()) {
            $candidate = $base . ' (' . $i . ')';
            $i++;
        }

        return $candidate;
    }

    private function uniqueUrl(string $baseSlug, $parentId): string
    {
        $base = Str::slug($baseSlug) ?: 'copy';
        $candidate = $base;
        $i = 1;

        while (
            Page::where('url', $candidate)
                ->where('parent_id', $parentId)
                ->exists()
        ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
