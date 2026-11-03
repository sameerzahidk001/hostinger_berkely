<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\PageSection;
use App\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = 15;
        $page = max(1, (int) $request->input('page', 1));

        $files = $this->imageFiles();
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $files = array_values(array_filter($files, function ($file) use ($needle) {
                $hay = mb_strtolower(($file['rel'] ?? '') . ' ' . ($file['name'] ?? ''));

                return str_contains($hay, $needle);
            }));
        }

        usort($files, function ($a, $b) {
            return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
        });

        $total = count($files);
        $lastPage = max(1, (int) ceil(($total ?: 1) / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $slice = array_slice($files, ($page - 1) * $perPage, $perPage);
        $usages = $this->usageIndex($slice);

        $rows = [];
        foreach ($slice as $file) {
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
                'size' => (int) ($file['size'] ?? 0),
                'links' => $links,
            ];
        }

        $pager = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => collect($request->query())->except('page')->all(),
            ]
        );

        return view('admin.media.index', [
            'rows' => $pager,
            'search' => $search,
            'rowsTotal' => $total,
        ]);
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
                        'size' => $file->getSize(),
                    ];
                }
            }
        }

        return $out;
    }

    /** @param array<int, array{rel:string,name:string}> $files */
    private function usageIndex(array $files): array
    {
        $byPath = [];
        $byName = [];
        if ($files === []) {
            return ['by_path' => [], 'by_name' => []];
        }

        $add = function (string $blob, string $label, string $url) use (&$byPath, &$byName, $files) {
            if ($blob === '') {
                return;
            }
            foreach ($files as $file) {
                $rel = str_replace('\\', '/', (string) ($file['rel'] ?? ''));
                if ($rel === '') {
                    continue;
                }
                if (stripos($blob, $rel) === false && stripos($blob, $file['name']) === false) {
                    continue;
                }
                $key = strtolower($rel);
                $byPath[$key][$url] = $label;
                $byName[strtolower($file['name'])][$url] = $label;
            }
        };

        $needles = array_values(array_filter(array_map(function ($file) {
            return str_replace('\\', '/', (string) ($file['rel'] ?? ''));
        }, $files)));

        if (class_exists(PageSection::class) && Schema::hasTable('page_sections')) {
            $q = PageSection::query()->with('page:id,page_name');
            $q->where(function ($outer) use ($needles) {
                foreach ($needles as $rel) {
                    $outer->orWhere('data', 'like', '%' . addcslashes($rel, '%_\\') . '%');
                }
            });
            foreach ($q->limit(200)->get() as $section) {
                $page = $section->page;
                if (! $page) {
                    continue;
                }
                $label = $page->page_name ?: ('Page #' . $page->id);
                $data = $section->data;
                $blob = is_string($data) ? $data : json_encode($data);
                $add((string) $blob, $label, route('pages.edit', $page->id));
            }
        }

        if (class_exists(Course::class) && Schema::hasTable('courses')) {
            $imgCols = [];
            foreach (['thumbnail', 'post_image', 'overview_img', 'banner_section', 'image_text_2col_card'] as $col) {
                if (Schema::hasColumn('courses', $col)) {
                    $imgCols[] = $col;
                }
            }
            if ($imgCols !== []) {
                $q = Course::query()->select(array_merge(['id', 'title'], $imgCols));
                $q->where(function ($outer) use ($needles, $imgCols) {
                    foreach ($needles as $rel) {
                        $like = '%' . addcslashes($rel, '%_\\') . '%';
                        foreach ($imgCols as $col) {
                            $outer->orWhere($col, 'like', $like);
                        }
                    }
                });
                foreach ($q->limit(200)->get() as $course) {
                    $label = 'Course: ' . ($course->title ?: ('#' . $course->id));
                    $add(json_encode($course->getAttributes()), $label, route('course.edit', $course->id));
                }
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
