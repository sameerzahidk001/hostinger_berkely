<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Page;
use App\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MediaController extends Controller
{
    public function index()
    {
        $files = $this->imageFiles();
        $usages = $this->usageIndex();

        $rows = [];
        foreach ($files as $file) {
            $rel = str_replace('\\', '/', $file['rel']);
            $key = strtolower($rel);
            $base = strtolower($file['name']);
            $links = array_values(array_unique(array_merge(
                $usages['by_path'][$key] ?? [],
                $usages['by_name'][$base] ?? []
            )));
            sort($links);
            $rows[] = [
                'rel' => $rel,
                'url' => asset($rel),
                'name' => $file['name'],
                'mtime' => $file['mtime'],
                'links' => $links,
            ];
        }

        usort($rows, function ($a, $b) {
            return $b['mtime'] <=> $a['mtime'];
        });

        return view('admin.media.index', ['rows' => $rows]);
    }

    public function destroy(Request $request)
    {
        $rel = str_replace('\\', '/', ltrim((string) $request->input('path'), '/'));
        if ($rel === '' || str_contains($rel, '..')) {
            return back()->with('fail', 'Invalid image path.');
        }

        $root = strtolower(explode('/', $rel)[0] ?? '');
        if (! in_array($root, ['images', 'uploads'], true)) {
            return back()->with('fail', 'Only uploaded images in images/ or uploads/ can be deleted.');
        }

        $full = public_path($rel);
        if (! File::exists($full) || ! File::isFile($full)) {
            return back()->with('fail', 'File not found.');
        }

        File::delete($full);

        return back()->with('success', 'Image deleted.');
    }

    private function imageFiles(): array
    {
        $out = [];
        foreach (['images', 'uploads'] as $dir) {
            $path = public_path($dir);
            if (! File::isDirectory($path)) {
                continue;
            }
            foreach (File::allFiles($path) as $file) {
                if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
                    $rel = $dir . '/' . str_replace('\\', '/', $file->getRelativePathname());
                    $out[] = [
                        'rel' => $rel,
                        'name' => $file->getFilename(),
                        'mtime' => $file->getMTime(),
                    ];
                }
            }
        }

        return $out;
    }

    /** @return array{by_path: array<string, array<int, string>>, by_name: array<string, array<int, string>>} */
    private function usageIndex(): array
    {
        $byPath = [];
        $byName = [];

        $add = function (string $blob, string $label, string $url) use (&$byPath, &$byName) {
            if ($blob === '') {
                return;
            }
            if (preg_match_all('#(?:images|uploads)/[a-zA-Z0-9_\-./]+\.(?:jpe?g|png|gif|webp|svg)#i', $blob, $m)) {
                foreach ($m[0] as $path) {
                    $key = strtolower(str_replace('\\', '/', $path));
                    $byPath[$key][$url] = $label;
                    $byName[strtolower(basename($path))][$url] = $label;
                }
            }
        };

        if (class_exists(Page::class) && Schema::hasTable('pages') && Schema::hasTable('page_sections')) {
            $pages = Page::query()->with('sections')->orderBy('page_name')->get();
            foreach ($pages as $page) {
                $label = $page->page_name ?: ('Page #' . $page->id);
                $edit = route('pages.edit', $page->id);
                foreach ($page->sections as $section) {
                    $data = $section->data;
                    $blob = is_string($data) ? $data : json_encode($data);
                    $add((string) $blob, $label, $edit);
                }
            }
        }

        if (class_exists(Course::class) && Schema::hasTable('courses')) {
            $courses = Course::query()->orderBy('title')->get();
            foreach ($courses as $course) {
                $label = 'Course: ' . ($course->title ?: ('#' . $course->id));
                $edit = route('course.edit', $course->id);
                $blob = json_encode($course->getAttributes());
                $add((string) $blob, $label, $edit);
            }
        }

        if (class_exists(SiteSettings::class) && Schema::hasTable('site_settings')) {
            $settings = SiteSettings::query()->first();
            if ($settings) {
                $add(json_encode($settings->toArray()), 'Site Settings', route('site-settings.index'));
            }
        }

        $flatten = function (array $map) {
            $out = [];
            foreach ($map as $key => $pairs) {
                $out[$key] = [];
                foreach ($pairs as $url => $label) {
                    $out[$key][] = $label . '|' . $url;
                }
            }

            return $out;
        };

        return [
            'by_path' => $flatten($byPath),
            'by_name' => $flatten($byName),
        ];
    }
}
