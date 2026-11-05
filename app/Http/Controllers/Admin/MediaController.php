<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\DynamicLabels;
use App\Models\MediaAsset;
use App\Models\PageSection;
use App\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        MediaAsset::ensureTable();

        $search = trim((string) $request->input('search', ''));
        $unusedOnly = $request->boolean('unused');
        $perPage = 15;
        $page = max(1, (int) $request->input('page', 1));

        $files = $this->cachedImageFiles();
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

        // Full-library unused scans time out (2000+ files × content LIKE). Only run
        // them for a filename search that already narrowed the set.
        $unusedSkipped = null;
        if ($unusedOnly) {
            if ($search === '' || count($files) > 40) {
                $unusedOnly = false;
                $unusedSkipped = 'Unused only was skipped so this page can load. Search a filename (40 matches or fewer), then tick Unused only again.';
            } elseif ($this->capDbStatement(8)) {
                try {
                    $files = $this->filterUnused($files);
                } catch (\Throwable $e) {
                    $unusedOnly = false;
                    $unusedSkipped = 'Unused check timed out. Narrow the filename search and try again.';
                } finally {
                    $this->clearDbStatementCap();
                }
            } else {
                $unusedOnly = false;
                $unusedSkipped = 'Unused only was skipped so this page can load. Search a filename, then tick Unused only again.';
            }
        }

        $total = count($files);
        $lastPage = max(1, (int) ceil(($total ?: 1) / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $slice = array_slice($files, ($page - 1) * $perPage, $perPage);
        $metaByPath = $this->metaIndex(array_column($slice, 'rel'));

        // Link lookup scans large page JSON. Cap it so nginx does not 504.
        // Delete still runs a full link check before removing a file.
        $usages = ['by_path' => [], 'by_name' => []];
        $linksKnown = false;
        if ($slice !== [] && $this->capDbStatement(8)) {
            try {
                $usages = $this->usageIndex($slice, false);
                $linksKnown = true;
            } catch (\Throwable $e) {
                $usages = ['by_path' => [], 'by_name' => []];
                $linksKnown = false;
            } finally {
                $this->clearDbStatementCap();
            }
        }

        $rows = [];
        foreach ($slice as $file) {
            $rel = str_replace('\\', '/', $file['rel']);
            $key = strtolower($rel);
            $base = strtolower($file['name']);
            $links = [];
            if ($linksKnown) {
                $links = array_values(array_unique(array_merge(
                    $usages['by_path'][$key] ?? [],
                    $usages['by_name'][$base] ?? []
                )));
                sort($links);
            }
            $meta = MediaAsset::mergeDisplayMeta($metaByPath[$key] ?? [], []);
            $bytes = (int) ($file['size'] ?? 0);
            $rows[] = [
                'rel' => $rel,
                'url' => asset($rel),
                'name' => $file['name'],
                'mtime' => $file['mtime'],
                'size' => $bytes,
                'valid' => $bytes >= 24,
                'width' => 0,
                'height' => 0,
                'links' => $links,
                'links_known' => $linksKnown,
                'alt' => $meta['alt'],
                'title' => $meta['title'],
                'caption' => $meta['caption'],
                'description' => $meta['description'],
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
            'unusedOnly' => $unusedOnly,
            'unusedSkipped' => $unusedSkipped,
        ]);
    }

    public function upload(Request $request)
    {
        MediaAsset::ensureTable();

        $request->validate([
            'image' => lms_image_upload_rule('required'),
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:5000',
        ], [
            'image.required' => 'Choose an image to upload.',
            'image.mimes' => 'Allowed types: JPG, PNG, GIF, WebP, SVG.',
            'image.max' => 'Image must be ' . lms_image_upload_max_kb() . ' KB or smaller.',
        ]);

        $file = $request->file('image');
        $original = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $original) ?: 'image';
        $safe = trim($safe, '-') ?: 'image';
        $fileName = strtolower($safe) . '-' . substr(uniqid('', true), -8) . '.' . $ext;

        $destDir = public_path('images/library');
        if (! File::isDirectory($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        if (function_exists('public_upload_move')) {
            public_upload_move($file, $destDir, $fileName);
        } else {
            $file->move($destDir, $fileName);
            if (function_exists('persist_moved_upload')) {
                persist_moved_upload($destDir, $fileName);
            }
        }

        $rel = 'images/library/' . $fileName;
        $this->forgetImageCache();
        MediaAsset::upsertForPath($rel, [
            'alt' => $request->input('alt'),
            'title' => $request->input('title'),
            'caption' => $request->input('caption'),
            'description' => $request->input('description'),
        ]);

        $url = asset($rel);

        return redirect()
            ->route('admin.media.index', ['search' => $fileName])
            ->with('success', 'Image uploaded. Full URL ready to copy.')
            ->with('uploaded_url', $url);
    }

    public function updateMeta(Request $request)
    {
        MediaAsset::ensureTable();

        $request->validate([
            'path' => 'required|string|max:500',
            'filename' => 'nullable|string|max:255',
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:5000',
            'sync_linked' => 'nullable|boolean',
        ]);

        $rel = MediaAsset::normalizePath($request->input('path'));
        if ($rel === '' || str_contains($rel, '..')) {
            return back()->with('fail', 'Invalid image path.');
        }

        $root = strtolower(explode('/', $rel)[0] ?? '');
        if (! in_array($root, ['images', 'uploads'], true)) {
            return back()->with('fail', 'Only images under images/ or uploads/ can be updated.');
        }

        $full = public_path($rel);
        if (! File::exists($full) || ! File::isFile($full)) {
            return back()->with('fail', 'File not found.');
        }

        $renamed = false;
        $oldRel = $rel;
        $filenameInput = trim((string) $request->input('filename', ''));
        if ($filenameInput !== '') {
            $renameResult = $this->renameMediaFile($rel, $filenameInput);
            if (isset($renameResult['error'])) {
                return back()->with('fail', $renameResult['error']);
            }
            if (! empty($renameResult['rel']) && $renameResult['rel'] !== $rel) {
                $rel = $renameResult['rel'];
                $renamed = true;
                $this->forgetImageCache();
            }
        }

        $alt = trim((string) $request->input('alt', ''));
        $title = trim((string) $request->input('title', ''));
        $caption = trim((string) $request->input('caption', ''));
        $description = trim((string) $request->input('description', ''));

        if ($renamed && MediaAsset::ensureTable()) {
            MediaAsset::query()->where('path', MediaAsset::normalizePath($oldRel))->delete();
        }

        MediaAsset::upsertForPath($rel, [
            'alt' => $alt !== '' ? $alt : null,
            'title' => $title !== '' ? $title : null,
            'caption' => $caption !== '' ? $caption : null,
            'description' => $description !== '' ? $description : null,
        ]);

        $synced = 0;
        // Filename changes must always rewrite linked references or pages break.
        if ($renamed) {
            $synced += $this->rewritePathInLinked($oldRel, $rel);
        }
        if ($request->boolean('sync_linked')) {
            $synced += $this->syncMetaToLinked($rel, $alt, $title, $caption, $description);
        }

        $msg = 'Image metadata saved.';
        if ($renamed) {
            $msg = 'Filename updated and metadata saved.';
        }
        if ($synced > 0) {
            $msg .= ' Updated ' . $synced . ' linked page/course reference' . ($synced === 1 ? '' : 's') . '.';
        }

        return back()->with('success', $msg);
    }

    public function downloadImagesZip()
    {
        $root = public_path('images');
        if (! File::isDirectory($root)) {
            return back()->with('fail', 'Images folder not found.');
        }

        $tmp = storage_path('app/tmp');
        if (! File::isDirectory($tmp)) {
            File::makeDirectory($tmp, 0755, true);
        }

        $zipPath = $tmp . DIRECTORY_SEPARATOR . 'eduberkeley-images-' . date('Ymd-His') . '.zip';
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('fail', 'Could not create zip file.');
        }

        $rootNorm = rtrim(str_replace('\\', '/', $root), '/');
        foreach (File::allFiles($root) as $file) {
            $full = str_replace('\\', '/', $file->getPathname());
            $local = ltrim(substr($full, strlen($rootNorm)), '/');
            if ($local === '') {
                continue;
            }
            $zip->addFile($file->getPathname(), 'images/' . $local);
        }
        $zip->close();

        if (! File::exists($zipPath) || File::size($zipPath) < 32) {
            return back()->with('fail', 'Zip is empty or failed to build.');
        }

        return response()->download($zipPath, 'eduberkeley-images.zip')->deleteFileAfterSend(true);
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

        // Block delete when still linked
        $usages = $this->usageIndex([['rel' => $rel, 'name' => basename($rel)]]);
        $key = strtolower($rel);
        $links = array_values(array_unique(array_merge(
            $usages['by_path'][$key] ?? [],
            $usages['by_name'][strtolower(basename($rel))] ?? []
        )));
        if ($links !== []) {
            return back()->with('fail', 'This image is still linked. Remove it from pages/courses first, or filter Unused to find safe deletes.');
        }

        File::delete($full);
        $this->forgetImageCache();

        if (MediaAsset::ensureTable()) {
            MediaAsset::query()->where('path', MediaAsset::normalizePath($rel))->delete();
        }

        return back()->with('success', 'Unused image deleted.');
    }

    /**
     * @param  array<int, string|null>  $rels
     * @return array<string, array{alt:?string,title:?string,caption:?string,description:?string}>
     */
    private function metaIndex(array $rels): array
    {
        if (! MediaAsset::ensureTable()) {
            return [];
        }

        $normalized = [];
        foreach ($rels as $rel) {
            $n = MediaAsset::normalizePath((string) $rel);
            if ($n !== '') {
                $normalized[] = $n;
            }
        }
        if ($normalized === []) {
            return [];
        }

        $out = [];
        foreach (MediaAsset::query()->whereIn('path', $normalized)->get() as $row) {
            $out[strtolower($row->path)] = [
                'alt' => $row->alt,
                'title' => $row->title,
                'caption' => $row->caption,
                'description' => $row->description ?? null,
            ];
        }

        return $out;
    }

    /**
     * Pull Alternative Text / Title / Caption / Description already stored on linked pages & courses.
     *
     * @param  array<int, array{rel:string,name:string}>  $files
     * @return array<string, array{alt:?string,title:?string,caption:?string,description:?string}>
     */
    private function linkedMetaIndex(array $files): array
    {
        $out = [];
        if ($files === []) {
            return $out;
        }

        $setIfEmpty = function (string $key, string $field, ?string $value) use (&$out) {
            $value = trim((string) $value);
            if ($value === '') {
                return;
            }
            if (! isset($out[$key])) {
                $out[$key] = ['alt' => null, 'title' => null, 'caption' => null, 'description' => null];
            }
            if (trim((string) ($out[$key][$field] ?? '')) === '') {
                $out[$key][$field] = $value;
            }
        };

        $needles = array_values(array_filter(array_map(function ($file) {
            return str_replace('\\', '/', (string) ($file['rel'] ?? ''));
        }, $files)));

        if (class_exists(PageSection::class) && Schema::hasTable('page_sections')) {
            $q = PageSection::query();
            $q->where(function ($outer) use ($needles) {
                foreach ($needles as $rel) {
                    $outer->orWhere('data', 'like', '%' . addcslashes($rel, '%_\\') . '%');
                    $outer->orWhere('data', 'like', '%' . addcslashes(basename($rel), '%_\\') . '%');
                }
            });
            foreach ($q->limit(300)->get() as $section) {
                $data = $section->data;
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    $data = is_array($decoded) ? $decoded : null;
                }
                if (! is_array($data)) {
                    continue;
                }
                foreach ($files as $file) {
                    $rel = str_replace('\\', '/', $file['rel']);
                    $key = strtolower($rel);
                    $base = basename($rel);
                    if ($this->pathMatches($data['image'] ?? null, $rel, $base)) {
                        $setIfEmpty($key, 'alt', $data['image_alt'] ?? null);
                        $setIfEmpty($key, 'title', $data['image_title'] ?? null);
                        $setIfEmpty($key, 'caption', $data['image_caption'] ?? null);
                        $setIfEmpty($key, 'description', $data['image_description'] ?? null);
                    }
                    if ($this->pathMatches($data['icon'] ?? null, $rel, $base)) {
                        $setIfEmpty($key, 'alt', $data['icon_alt'] ?? null);
                    }
                    if (! empty($data['cards']) && is_array($data['cards'])) {
                        foreach ($data['cards'] as $card) {
                            if (! is_array($card)) {
                                continue;
                            }
                            if ($this->pathMatches($card['image'] ?? null, $rel, $base)) {
                                $setIfEmpty($key, 'alt', $card['image_alt'] ?? null);
                                $setIfEmpty($key, 'title', $card['image_title'] ?? null);
                                $setIfEmpty($key, 'caption', $card['image_caption'] ?? null);
                                $setIfEmpty($key, 'description', $card['image_description'] ?? null);
                            }
                            if ($this->pathMatches($card['icon'] ?? null, $rel, $base)) {
                                $setIfEmpty($key, 'alt', $card['icon_alt'] ?? null);
                            }
                        }
                    }
                }
            }
        }

        if (class_exists(Course::class) && Schema::hasTable('courses') && Schema::hasColumn('courses', 'image_alts')) {
            $imgCols = [];
            foreach (['thumbnail', 'post_image', 'overview_img', 'image_text_2col_card'] as $col) {
                if (Schema::hasColumn('courses', $col)) {
                    $imgCols[] = $col;
                }
            }
            if ($imgCols !== []) {
                $q = Course::query()->select(array_merge(['id', 'image_alts'], $imgCols));
                $q->where(function ($outer) use ($needles, $imgCols) {
                    foreach ($needles as $rel) {
                        $like = '%' . addcslashes($rel, '%_\\') . '%';
                        $likeBase = '%' . addcslashes(basename($rel), '%_\\') . '%';
                        foreach ($imgCols as $col) {
                            $outer->orWhere($col, 'like', $like)->orWhere($col, 'like', $likeBase);
                        }
                    }
                });
                foreach ($q->limit(200)->get() as $course) {
                    $map = is_array($course->image_alts) ? $course->image_alts : [];
                    foreach ($files as $file) {
                        $rel = str_replace('\\', '/', $file['rel']);
                        $key = strtolower($rel);
                        $base = basename($rel);
                        $pairs = [
                            'overview_img' => $course->overview_img ?? null,
                            'thumbnail' => $course->thumbnail ?? null,
                            'post_image' => $course->post_image ?? null,
                        ];
                        foreach ($pairs as $altKey => $path) {
                            if ($this->pathMatches($path, $rel, $base) && ! empty($map[$altKey])) {
                                $setIfEmpty($key, 'alt', $map[$altKey]);
                            }
                        }
                        $twoCol = $course->image_text_2col_card ?? null;
                        if (is_string($twoCol)) {
                            $decoded = json_decode($twoCol, true);
                            $twoCol = is_array($decoded) ? $decoded : null;
                        }
                        if (is_array($twoCol) && $this->pathMatches($twoCol['image'] ?? null, $rel, $base) && ! empty($map['image_text_2col'])) {
                            $setIfEmpty($key, 'alt', $map['image_text_2col']);
                        }
                    }
                }
            }
        }

        if (class_exists(DynamicLabels::class) && Schema::hasTable('course_dynamic_labels') && Schema::hasColumn('course_dynamic_labels', 'image_alts')) {
            $labelCols = [
                'banner_image', 'who_can_do_img', 'lectures_img', 'practice_session_img',
                'mock_examination_img', 'career_path_section_img', 'exam_information_section_img',
                'learner_stories_img', 'what_you_earn_img',
            ];
            $existing = [];
            foreach ($labelCols as $col) {
                if (Schema::hasColumn('course_dynamic_labels', $col)) {
                    $existing[] = $col;
                }
            }
            if ($existing !== []) {
                $q = DynamicLabels::query()->select(array_merge(['id', 'image_alts'], $existing));
                $q->where(function ($outer) use ($needles, $existing) {
                    foreach ($needles as $rel) {
                        $like = '%' . addcslashes($rel, '%_\\') . '%';
                        $likeBase = '%' . addcslashes(basename($rel), '%_\\') . '%';
                        foreach ($existing as $col) {
                            $outer->orWhere($col, 'like', $like)->orWhere($col, 'like', $likeBase);
                        }
                    }
                });
                foreach ($q->limit(200)->get() as $label) {
                    $map = is_array($label->image_alts) ? $label->image_alts : [];
                    foreach ($files as $file) {
                        $rel = str_replace('\\', '/', $file['rel']);
                        $key = strtolower($rel);
                        $base = basename($rel);
                        foreach ($existing as $col) {
                            if ($this->pathMatches($label->{$col} ?? null, $rel, $base) && ! empty($map[$col])) {
                                $setIfEmpty($key, 'alt', $map[$col]);
                            }
                        }
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @return array{rel?:string,error?:string}
     */
    private function renameMediaFile(string $rel, string $filenameInput): array
    {
        $rel = MediaAsset::normalizePath($rel);
        $dir = trim(str_replace('\\', '/', dirname($rel)), '.');
        if ($dir === '' || $dir === '/') {
            return ['error' => 'Invalid image directory.'];
        }

        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        $name = pathinfo($filenameInput, PATHINFO_FILENAME);
        $inputExt = strtolower((string) pathinfo($filenameInput, PATHINFO_EXTENSION));
        if ($inputExt !== '' && in_array($inputExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            $ext = $inputExt;
        }
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $name) ?: '';
        $safe = strtolower(trim($safe, '-'));
        if ($safe === '' || $ext === '') {
            return ['error' => 'Invalid filename. Use letters, numbers, dashes, or underscores.'];
        }

        $newRel = $dir . '/' . $safe . '.' . $ext;
        if (strcasecmp($newRel, $rel) === 0) {
            return ['rel' => $rel];
        }

        $oldFull = public_path($rel);
        $newFull = public_path($newRel);
        if (! File::exists($oldFull)) {
            return ['error' => 'File not found.'];
        }
        if (File::exists($newFull)) {
            return ['error' => 'A file with that name already exists.'];
        }

        if (! File::move($oldFull, $newFull)) {
            return ['error' => 'Could not rename the file.'];
        }

        return ['rel' => $newRel];
    }

    private function rewritePathInLinked(string $oldRel, string $newRel): int
    {
        $synced = 0;
        $oldRel = MediaAsset::normalizePath($oldRel);
        $newRel = MediaAsset::normalizePath($newRel);
        $oldBase = basename($oldRel);
        $newBase = basename($newRel);

        $replaceIn = function ($value) use ($oldRel, $newRel, $oldBase, $newBase) {
            if (! is_string($value) || $value === '') {
                return [$value, false];
            }
            $changed = false;
            $next = $value;
            if (stripos($next, $oldRel) !== false) {
                $next = str_ireplace($oldRel, $newRel, $next);
                $changed = true;
            }
            // Replace basename only when it appears as a path segment / filename token.
            if (preg_match('/(^|[\\/])' . preg_quote($oldBase, '/') . '($|[?"\'\\s])/i', $next)) {
                $next = preg_replace('/' . preg_quote($oldBase, '/') . '/i', $newBase, $next);
                $changed = true;
            }

            return [$next, $changed];
        };

        if (class_exists(PageSection::class) && Schema::hasTable('page_sections')) {
            $q = PageSection::query();
            $q->where(function ($outer) use ($oldRel, $oldBase) {
                $outer->where('data', 'like', '%' . addcslashes($oldRel, '%_\\') . '%')
                    ->orWhere('data', 'like', '%' . addcslashes($oldBase, '%_\\') . '%');
            });
            foreach ($q->limit(300)->get() as $section) {
                $raw = $section->data;
                $isString = is_string($raw);
                $blob = $isString ? $raw : json_encode($raw);
                [$next, $changed] = $replaceIn((string) $blob);
                if ($changed) {
                    $decoded = json_decode($next, true);
                    $section->data = is_array($decoded) ? $decoded : $next;
                    $section->save();
                    $synced++;
                }
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
                $q = Course::query()->select(array_merge(['id'], $imgCols));
                $q->where(function ($outer) use ($oldRel, $oldBase, $imgCols) {
                    foreach ($imgCols as $col) {
                        $outer->orWhere($col, 'like', '%' . addcslashes($oldRel, '%_\\') . '%')
                            ->orWhere($col, 'like', '%' . addcslashes($oldBase, '%_\\') . '%');
                    }
                });
                foreach ($q->limit(200)->get() as $course) {
                    $dirty = false;
                    foreach ($imgCols as $col) {
                        [$next, $changed] = $replaceIn((string) ($course->{$col} ?? ''));
                        if ($changed) {
                            $course->{$col} = $next;
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $course->save();
                        $synced++;
                    }
                }
            }
        }

        if (class_exists(DynamicLabels::class) && Schema::hasTable('course_dynamic_labels')) {
            $labelCols = [
                'banner_image', 'who_can_do_img', 'lectures_img', 'practice_session_img',
                'mock_examination_img', 'career_path_section_img', 'exam_information_section_img',
                'learner_stories_img', 'what_you_earn_img',
            ];
            $existing = [];
            foreach ($labelCols as $col) {
                if (Schema::hasColumn('course_dynamic_labels', $col)) {
                    $existing[] = $col;
                }
            }
            if ($existing !== []) {
                $q = DynamicLabels::query()->select(array_merge(['id'], $existing));
                $q->where(function ($outer) use ($oldRel, $oldBase, $existing) {
                    foreach ($existing as $col) {
                        $outer->orWhere($col, 'like', '%' . addcslashes($oldRel, '%_\\') . '%')
                            ->orWhere($col, 'like', '%' . addcslashes($oldBase, '%_\\') . '%');
                    }
                });
                foreach ($q->limit(200)->get() as $label) {
                    $dirty = false;
                    foreach ($existing as $col) {
                        [$next, $changed] = $replaceIn((string) ($label->{$col} ?? ''));
                        if ($changed) {
                            $label->{$col} = $next;
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $label->save();
                        $synced++;
                    }
                }
            }
        }

        return $synced;
    }

    /**
     * @param  array<int, array{rel:string,name:string}>  $files
     * @return array<int, array{rel:string,name:string,mtime?:int,size?:int}>
     */
    private function filterUnused(array $files): array
    {
        if ($files === []) {
            return [];
        }

        $chunkSize = 40;
        $unused = [];
        foreach (array_chunk($files, $chunkSize) as $chunk) {
            $usages = $this->usageIndex($chunk);
            foreach ($chunk as $file) {
                $rel = str_replace('\\', '/', $file['rel']);
                $key = strtolower($rel);
                $base = strtolower($file['name']);
                $links = array_merge($usages['by_path'][$key] ?? [], $usages['by_name'][$base] ?? []);
                if ($links === []) {
                    $unused[] = $file;
                }
            }
        }

        return $unused;
    }

    private function syncMetaToLinked(string $rel, string $alt, string $title, string $caption, string $description = ''): int
    {
        $synced = 0;
        $norm = MediaAsset::normalizePath($rel);
        $base = basename($norm);

        if (class_exists(PageSection::class) && Schema::hasTable('page_sections')) {
            $q = PageSection::query()->with('page:id,page_name');
            $q->where(function ($outer) use ($norm, $base) {
                $outer->where('data', 'like', '%' . addcslashes($norm, '%_\\') . '%')
                    ->orWhere('data', 'like', '%' . addcslashes($base, '%_\\') . '%');
            });
            foreach ($q->limit(300)->get() as $section) {
                $data = $section->data;
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    $data = is_array($decoded) ? $decoded : null;
                }
                if (! is_array($data)) {
                    continue;
                }
                $changed = $this->patchSectionMeta($data, $norm, $base, $alt, $title, $caption, $description);
                if ($changed) {
                    $section->data = $data;
                    $section->save();
                    $synced++;
                }
            }
        }

        if (class_exists(Course::class) && Schema::hasTable('courses')) {
            $synced += $this->syncCourseMeta($norm, $base, $alt);
        }

        return $synced;
    }

    private function pathMatches(?string $stored, string $norm, string $base): bool
    {
        $storedNorm = MediaAsset::normalizePath((string) $stored);
        if ($storedNorm === '') {
            return false;
        }
        if (strcasecmp($storedNorm, $norm) === 0) {
            return true;
        }
        if (strcasecmp(basename($storedNorm), $base) === 0 && (
            str_ends_with(strtolower($norm), '/' . strtolower($base))
            || str_ends_with(strtolower($storedNorm), '/' . strtolower($base))
        )) {
            // Filename match when one side is library filename-only
            return str_contains(strtolower($norm), 'images/library/')
                || str_contains(strtolower($storedNorm), 'images/library/')
                || ! str_contains($storedNorm, '/');
        }

        return false;
    }

    private function patchSectionMeta(array &$data, string $norm, string $base, string $alt, string $title, string $caption, string $description = ''): bool
    {
        $changed = false;

        if ($this->pathMatches($data['image'] ?? null, $norm, $base)) {
            if ($alt !== '') {
                $data['image_alt'] = Str::limit($alt, 125, '');
                $changed = true;
            }
            if ($title !== '') {
                $data['image_title'] = Str::limit($title, 255, '');
                $changed = true;
            }
            if ($caption !== '') {
                $data['image_caption'] = $caption;
                $changed = true;
            }
            if ($description !== '') {
                $data['image_description'] = $description;
                $changed = true;
            }
        }

        if ($this->pathMatches($data['icon'] ?? null, $norm, $base)) {
            if ($alt !== '') {
                $data['icon_alt'] = Str::limit($alt, 125, '');
                $changed = true;
            }
        }

        if (! empty($data['cards']) && is_array($data['cards'])) {
            foreach ($data['cards'] as $key => $card) {
                if (! is_array($card)) {
                    continue;
                }
                if ($this->pathMatches($card['image'] ?? null, $norm, $base)) {
                    if ($alt !== '') {
                        $data['cards'][$key]['image_alt'] = Str::limit($alt, 125, '');
                        $changed = true;
                    }
                    if ($title !== '') {
                        $data['cards'][$key]['image_title'] = Str::limit($title, 255, '');
                        $changed = true;
                    }
                    if ($caption !== '') {
                        $data['cards'][$key]['image_caption'] = $caption;
                        $changed = true;
                    }
                    if ($description !== '') {
                        $data['cards'][$key]['image_description'] = $description;
                        $changed = true;
                    }
                }
                if ($this->pathMatches($card['icon'] ?? null, $norm, $base) && $alt !== '') {
                    $data['cards'][$key]['icon_alt'] = Str::limit($alt, 125, '');
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    private function syncCourseMeta(string $norm, string $base, string $alt): int
    {
        if ($alt === '') {
            return 0;
        }
        $synced = 0;
        $altLimited = Str::limit($alt, 125, '');

        $imgCols = [];
        foreach (['thumbnail', 'post_image', 'overview_img', 'image_text_2col_card'] as $col) {
            if (Schema::hasColumn('courses', $col)) {
                $imgCols[] = $col;
            }
        }

        if ($imgCols !== []) {
            $q = Course::query()->select(array_merge(['id', 'title', 'image_alts'], $imgCols));
            $q->where(function ($outer) use ($norm, $base, $imgCols) {
                $likePath = '%' . addcslashes($norm, '%_\\') . '%';
                $likeBase = '%' . addcslashes($base, '%_\\') . '%';
                foreach ($imgCols as $col) {
                    $outer->orWhere($col, 'like', $likePath)->orWhere($col, 'like', $likeBase);
                }
            });
            foreach ($q->limit(200)->get() as $course) {
                $map = is_array($course->image_alts) ? $course->image_alts : [];
                $dirty = false;
                if ($this->pathMatches($course->overview_img ?? null, $norm, $base)) {
                    $map['overview_img'] = $altLimited;
                    $dirty = true;
                }
                if ($this->pathMatches($course->thumbnail ?? null, $norm, $base)) {
                    $map['thumbnail'] = $altLimited;
                    $dirty = true;
                }
                if ($this->pathMatches($course->post_image ?? null, $norm, $base)) {
                    $map['post_image'] = $altLimited;
                    $dirty = true;
                }
                $twoCol = $course->image_text_2col_card ?? null;
                if (is_string($twoCol)) {
                    $decoded = json_decode($twoCol, true);
                    $twoCol = is_array($decoded) ? $decoded : null;
                }
                if (is_array($twoCol) && $this->pathMatches($twoCol['image'] ?? null, $norm, $base)) {
                    $map['image_text_2col'] = $altLimited;
                    $dirty = true;
                }
                if ($dirty && Schema::hasColumn('courses', 'image_alts')) {
                    $course->image_alts = $map;
                    $course->save();
                    $synced++;
                }
            }
        }

        if (class_exists(DynamicLabels::class) && Schema::hasTable('course_dynamic_labels')) {
            $labelCols = [
                'banner_image', 'who_can_do_img', 'lectures_img', 'practice_session_img',
                'mock_examination_img', 'career_path_section_img', 'exam_information_section_img',
                'learner_stories_img', 'what_you_earn_img',
            ];
            $existing = [];
            foreach ($labelCols as $col) {
                if (Schema::hasColumn('course_dynamic_labels', $col)) {
                    $existing[] = $col;
                }
            }
            if ($existing !== []) {
                $q = DynamicLabels::query()->select(array_merge(['id', 'course_id', 'image_alts'], $existing));
                $q->where(function ($outer) use ($norm, $base, $existing) {
                    $likePath = '%' . addcslashes($norm, '%_\\') . '%';
                    $likeBase = '%' . addcslashes($base, '%_\\') . '%';
                    foreach ($existing as $col) {
                        $outer->orWhere($col, 'like', $likePath)->orWhere($col, 'like', $likeBase);
                    }
                });
                foreach ($q->limit(200)->get() as $label) {
                    $map = is_array($label->image_alts) ? $label->image_alts : [];
                    $dirty = false;
                    foreach ($existing as $col) {
                        if ($this->pathMatches($label->{$col} ?? null, $norm, $base)) {
                            $map[$col] = $altLimited;
                            $dirty = true;
                        }
                    }
                    if ($dirty) {
                        $label->image_alts = $map;
                        $label->save();
                        $synced++;
                    }
                }
            }
        }

        return $synced;
    }

    private function imageCachePath(): string
    {
        return storage_path('app/media-library-index.json');
    }

    /** @return array<int, array{rel:string,name:string,mtime:int,size:int}> */
    private function cachedImageFiles(): array
    {
        $path = $this->imageCachePath();
        if (is_file($path)) {
            $age = time() - (int) @filemtime($path);
            if ($age >= 0 && $age < 900) {
                $decoded = json_decode((string) @file_get_contents($path), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $files = $this->imageFiles();
        $this->writeImageCache($files);

        return $files;
    }

    /** @param array<int, array{rel:string,name:string,mtime:int,size:int}> $files */
    private function writeImageCache(array $files): void
    {
        $path = $this->imageCachePath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, json_encode($files)) === false) {
            return;
        }
        @rename($tmp, $path);
    }

    private function forgetImageCache(): void
    {
        $path = $this->imageCachePath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Abort long content searches before nginx returns 504.
     * Returns false when the server does not allow a statement time limit.
     */
    private function capDbStatement(int $seconds): bool
    {
        $seconds = max(1, $seconds);
        try {
            DB::statement('SET SESSION max_statement_time=' . $seconds);

            return true;
        } catch (\Throwable $e) {
            try {
                DB::statement('SET SESSION MAX_EXECUTION_TIME=' . ($seconds * 1000));

                return true;
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }

    private function clearDbStatementCap(): void
    {
        try {
            DB::statement('SET SESSION max_statement_time=0');
        } catch (\Throwable $e) {
            try {
                DB::statement('SET SESSION MAX_EXECUTION_TIME=0');
            } catch (\Throwable $e2) {
                // Ignore — the request is ending or the variable is unsupported.
            }
        }
    }

    private function imageFiles(): array
    {
        $out = [];
        $skipDirs = [
            'uploads/study-materials' => true,
            'uploads/dbs' => true,
            'uploads/instructor-payment-slips' => true,
        ];
        foreach (['images', 'uploads'] as $dir) {
            $path = public_path($dir);
            if (! is_dir($path)) {
                continue;
            }
            $this->collectImageFiles($path, $dir, $out, $skipDirs);
        }

        return $out;
    }

    /**
     * List image files without walking document/PDF trees (those made File::allFiles exceed the gateway timeout).
     *
     * @param  array<int, array{rel:string,name:string,mtime:int,size:int}>  $out
     * @param  array<string, bool>  $skipDirs
     */
    private function collectImageFiles(string $dir, string $relPrefix, array &$out, array $skipDirs): void
    {
        if (isset($skipDirs[strtolower($relPrefix)])) {
            return;
        }

        $items = @scandir($dir);
        if ($items === false) {
            return;
        }

        $exts = ['jpg' => true, 'jpeg' => true, 'png' => true, 'gif' => true, 'webp' => true, 'svg' => true];
        foreach ($items as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $name;
            $rel = $relPrefix . '/' . $name;
            if (is_dir($full)) {
                $this->collectImageFiles($full, $rel, $out, $skipDirs);
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! isset($exts[$ext])) {
                continue;
            }
            $out[] = [
                'rel' => str_replace('\\', '/', $rel),
                'name' => $name,
                'mtime' => (int) (@filemtime($full) ?: 0),
                'size' => (int) (@filesize($full) ?: 0),
            ];
        }
    }

    /**
     * @return array{valid:bool,width:int,height:int}
     */
    private function probeImageFile(string $path): array
    {
        $empty = ['valid' => false, 'width' => 0, 'height' => 0];
        try {
            if (! is_file($path)) {
                return $empty;
            }
            $size = @filesize($path);
            if ($size === false || $size < 24) {
                return $empty;
            }

            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $head = @file_get_contents($path, false, null, 0, 512);
                if (! is_string($head) || stripos($head, '<svg') === false) {
                    return $empty;
                }

                return ['valid' => true, 'width' => 0, 'height' => 0];
            }

            $info = @getimagesize($path);
            if (! is_array($info) || empty($info[0]) || empty($info[1])) {
                return $empty;
            }

            return [
                'valid' => true,
                'width' => (int) $info[0],
                'height' => (int) $info[1],
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * @param  array<int, array{rel:string,name:string}>  $files
     * @param  bool  $matchBasename  Filename-only LIKE is much slower on large JSON. List view skips it; delete still uses it.
     */
    private function usageIndex(array $files, bool $matchBasename = true): array
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
            $q->where(function ($outer) use ($needles, $matchBasename) {
                foreach ($needles as $rel) {
                    $outer->orWhere('data', 'like', '%' . addcslashes($rel, '%_\\') . '%');
                    if ($matchBasename) {
                        $outer->orWhere('data', 'like', '%' . addcslashes(basename($rel), '%_\\') . '%');
                    }
                }
            });
            foreach ($q->limit(300)->get() as $section) {
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
                $q->where(function ($outer) use ($needles, $imgCols, $matchBasename) {
                    foreach ($needles as $rel) {
                        $like = '%' . addcslashes($rel, '%_\\') . '%';
                        $likeBase = '%' . addcslashes(basename($rel), '%_\\') . '%';
                        foreach ($imgCols as $col) {
                            $outer->orWhere($col, 'like', $like);
                            if ($matchBasename) {
                                $outer->orWhere($col, 'like', $likeBase);
                            }
                        }
                    }
                });
                foreach ($q->limit(200)->get() as $course) {
                    $label = 'Course: ' . ($course->title ?: ('#' . $course->id));
                    $add(json_encode($course->getAttributes()), $label, route('course.edit', $course->id));
                }
            }
        }

        if (class_exists(DynamicLabels::class) && Schema::hasTable('course_dynamic_labels')) {
            $labelCols = [
                'banner_image', 'who_can_do_img', 'lectures_img', 'practice_session_img',
                'mock_examination_img', 'career_path_section_img', 'exam_information_section_img',
                'learner_stories_img', 'what_you_earn_img',
            ];
            $existing = [];
            foreach ($labelCols as $col) {
                if (Schema::hasColumn('course_dynamic_labels', $col)) {
                    $existing[] = $col;
                }
            }
            if ($existing !== []) {
                $q = DynamicLabels::query()->select(array_merge(['id', 'course_id'], $existing));
                $q->where(function ($outer) use ($needles, $existing, $matchBasename) {
                    foreach ($needles as $rel) {
                        $like = '%' . addcslashes($rel, '%_\\') . '%';
                        $likeBase = '%' . addcslashes(basename($rel), '%_\\') . '%';
                        foreach ($existing as $col) {
                            $outer->orWhere($col, 'like', $like);
                            if ($matchBasename) {
                                $outer->orWhere($col, 'like', $likeBase);
                            }
                        }
                    }
                });
                foreach ($q->limit(200)->get() as $labelRow) {
                    $courseId = (int) $labelRow->course_id;
                    $label = 'Course labels #' . $courseId;
                    $url = $courseId > 0 ? route('course.edit', $courseId) : '#';
                    $add(json_encode($labelRow->getAttributes()), $label, $url);
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
