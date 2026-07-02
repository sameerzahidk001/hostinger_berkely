<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use App\Models\School;
use App\Models\PagesSEO;
use App\Models\Category;
use App\Models\PageSection;
use Illuminate\Support\Str;
use App\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Services\SeoAnalyzerService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class PagesController extends Controller
{
    public function __construct()
    {
        $this->middleware('long.running')->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['categories'] = Category::orderBy('name', 'asc')->get();
        $data['category_page_id'] = SiteSettings::pluck('categories')->first();
        $data['categories_pages_slug_base'] = SiteSettings::pluck('category_perma')->first();
        $data['pagesStatusEnabled'] = pages_status_enabled();

        $query = Page::with(['parent', 'seo', 'faqs', 'createdBy', 'updatedBy']);

        if ($data['pagesStatusEnabled']) {
            $query->where('status', 1);
        }

        $analyzer = app(SeoAnalyzerService::class);
        $data['pages'] = $query
            ->orderByDesc('created_at')
            ->get()
            ->each(function (Page $page) use ($analyzer) {
                if ($page->seo) {
                    $page->seo_analysis = $analyzer->analyzeMetaOnlyForListing($page->seo);
                }
            });

        return view('admin.pages.index')->with($data);
    }

    public function disabledPages()
    {
        if (! pages_status_enabled()) {
            return redirect()
                ->route('pages.index')
                ->with('error', 'Page disable is not active on the database yet. Run database/sql/add-pages-status-column.sql on the server, then try again.');
        }

        $data['categories'] = Category::orderBy('name', 'asc')->get();
        $data['category_page_id'] = SiteSettings::pluck('categories')->first();
        $data['categories_pages_slug_base'] = SiteSettings::pluck('category_perma')->first();
        $data['pagesStatusEnabled'] = true;
        $analyzer = app(SeoAnalyzerService::class);
        $data['pages'] = Page::with(['parent', 'seo', 'faqs', 'createdBy', 'updatedBy'])
            ->where('status', 0)
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (Page $page) use ($analyzer) {
                if ($page->seo) {
                    $page->seo_analysis = $analyzer->analyzeMetaOnlyForListing($page->seo);
                }
            });

        return view('admin.pages.disabled-pages')->with($data);
    }

    public function updateStatus(Request $request, $id)
    {
        if (! pages_status_enabled()) {
            return response()->json([
                'error' => 'Page disable is not active on the database yet. Run database/sql/add-pages-status-column.sql on the server.',
            ], 500);
        }

        $page = Page::findOrFail($id);
        $status = normalize_page_status($request->input('status'));
        $page->status = $status;
        $page->save();

        touch_content_audit($page);
        record_panel_activity(
            $status ? 'Page Enabled' : 'Page Disabled',
            ($page->page_name ?: $page->url) . ' — Status: ' . ($status ? 'Active' : 'Disable'),
            route('pages.edit', $page->id),
            $request
        );

        return response()->json(['success' => 'Page status updated successfully!']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_name' => 'required|string|max:255',
            'url' => [
                'required',
                'regex:/^[a-z0-9-]+$/', // Only allows lowercase letters, numbers, and hyphens
                'max:255',
                Rule::unique('pages')->where(function ($query) use ($request) {
                    return $query->where('parent_id', $request->parent_id);
                }),
            ],
            'parent_id' => 'nullable|integer|exists:pages,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->except('_token');
        $status = normalize_page_status($request->input('status'));
        unset($data['status']);

        if (empty($data['url']) && ! empty($data['page_name'])) {
            $data['url'] = Page::slugFromName($data['page_name']);
        }

        $url = $data['url'];
        $baseUrl = $url;
        $suffix = 1;
        while (Page::where('url', $url)->where('parent_id', $data['parent_id'] ?? null)->exists()) {
            $url = $baseUrl . '-' . $suffix++;
        }
        $data['url'] = $url;

        $pageCreated = Page::create($data);
        assign_column_if_exists($pageCreated, 'status', $status);
        if ($pageCreated->isDirty()) {
            $pageCreated->save();
        }

        if ($pageCreated && ! pages_status_enabled()) {
            session()->flash('error', 'Page was created, but Active/Disabled status is not saved until database/sql/add-pages-status-column.sql is run on the server.');
        }

        if ($pageCreated) {
            session()->flash('success', 'Page created. Add content sections and save.');
            record_panel_activity(
                'Page Created',
                $pageCreated->page_name ?: $pageCreated->url,
                route('pages.edit', $pageCreated->id),
                $request
            );
        } else {
            session()->flash('error', 'Failed to insert record!');
        }

        return redirect()->route('pages.edit', $pageCreated->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit($id)
    {
        $category_page_id = SiteSettings::pluck('categories')->first();

        $allpages = Page::whereNull('parent_id')->get();
        $page = Page::with('sections')->findOrFail($id);

        $page->sections = $page->sections
            ->sortBy('order')
            ->values()
            ->map(function ($section) {
                $section->data = json_decode($section->data, true) ?? [];

                if (empty($section->data['section_type']) && ! empty($section->section_type)) {
                    $section->data['section_type'] = $section->section_type;
                }

                return $section;
            });

        $meta = PagesSEO::where('page_id', $id)->first();

        $schools = School::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('name', 'asc')->get();

        return view('admin.pages.edit', compact('page', 'meta', 'schools', 'categories', 'allpages', 'category_page_id'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);
        $this->mergeSectionsPayload($request);

        $validator = Validator::make($request->all(), [
            'page_name' => 'required|string|max:255',
            'url' => [
                'required',
                'regex:/^[a-z0-9-]+$/', // Only allows lowercase letters, numbers, and hyphens
                'max:255',
                Rule::unique('pages', 'url')
                    ->ignore($id) // Ignore current page ID while checking uniqueness
                    ->where(function ($query) use ($request) {
                        return $query->where('parent_id', $request->parent_id);
                    }),
            ],
            'parent_id' => 'nullable|integer|exists:pages,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $page->page_name = $request->input('page_name');
        $page->url = $request->input('url');
        $page->category_id = $request->input('category_id');
        $page->parent_id = $request->input('parent_id');

        if (pages_status_enabled()) {
            $page->status = normalize_page_status($request->input('status'));
        }

        if ($page->category_id) {
            $page->parent_id = null;
        }
        $page->save();

        if (! pages_status_enabled()) {
            session()->flash('error', 'Page content saved, but Active/Disabled status is not saved until database/sql/add-pages-status-column.sql is run on the server.');
        }

        $page->load('sections');
        $existingSections = $page->sections->keyBy('id');
        $newSectionIds = [];

        if (! $request->has('sections')) {
            return redirect()->back()
                ->with('fail', 'Section data was not received. Please try saving again. If it keeps failing, contact support to raise post_max_size on the server.')
                ->withInput();
        }

        $sections = $request->input('sections');
        if (! is_array($sections)) {
            return redirect()->back()
                ->with('fail', 'Invalid section data received. Existing sections were kept unchanged.')
                ->withInput();
        }

        // Form keys must map to visual order (0, 1, 2...), not stale array indexes.
        $sections = array_values($sections);

        foreach ($sections as $order => $section) {
            if (! is_array($section)) {
                continue;
            }

            // Get the existing section based on ID (if available)
            $existingSection = isset($section['section_id']) ? $existingSections->get($section['section_id']) : null;
            $oldData = $existingSection ? (json_decode($existingSection->data, true) ?? []) : [];

            if (empty($section['section_type'])) {
                $section['section_type'] = $existingSection->section_type
                    ?? ($oldData['section_type'] ?? null);
            }

            $section['section_type'] = normalize_section_type_key($section['section_type'] ?? '')
                ?? ($section['section_type'] ?? null);

            // 🔹 Preserve old images for cards before updating
            if (isset($section['cards'])) {
                foreach ($section['cards'] as $cardIndex => $card) {
                    if ($request->input("sections.$order.cards.$cardIndex.icon_source") === 'upload') {
                        if ($request->hasFile("sections.$order.cards.$cardIndex.icon")) {
                            $uploadedFile = $request->file("sections.$order.cards.$cardIndex.icon");
                            $filename = generateFileName($uploadedFile);
                            $uploadedFile->move(public_path('images/library'), $filename);

                            $section['cards'][$cardIndex]['icon'] = $filename;
                        } else {
                            // Preserve the old image if no new file is uploaded
                            $section['cards'][$cardIndex]['icon'] = $oldData['cards'][$cardIndex]['icon'] ?? null;
                        }
                    } elseif ($request->input("sections.$order.cards.$cardIndex.icon_source") === 'library') {
                        $section['cards'][$cardIndex]['icon'] = $request->input("sections.$order.cards.$cardIndex.icon");
                    } elseif ($request->input("sections.$order.cards.$cardIndex.icon_source") === 'remove') {
                        $section['cards'][$cardIndex]['icon'] = null;
                    } else {
                        $section['cards'][$cardIndex]['icon'] = $section['cards'][$cardIndex]['icon']
                            ?? ($oldData['cards'][$cardIndex]['icon'] ?? null);
                    }

                    if ($request->input("sections.$order.cards.$cardIndex.image_source") === 'upload') {
                        if ($request->hasFile("sections.$order.cards.$cardIndex.image")) {
                            $uploadedFile = $request->file("sections.$order.cards.$cardIndex.image");
                            $filename = generateFileName($uploadedFile);
                            $uploadedFile->move(public_path('images/library'), $filename);

                            $section['cards'][$cardIndex]['image'] = $filename;
                        } else {
                            // Preserve the old image if no new file is uploaded
                            $section['cards'][$cardIndex]['image'] = $oldData['cards'][$cardIndex]['image'] ?? null;
                        }
                    } elseif ($request->input("sections.$order.cards.$cardIndex.image_source") === 'library') {
                        $section['cards'][$cardIndex]['image'] = $request->input("sections.$order.cards.$cardIndex.image");
                    } elseif ($request->input("sections.$order.cards.$cardIndex.image_source") === 'remove') {
                        $section['cards'][$cardIndex]['image'] = null;
                    } else {
                        $section['cards'][$cardIndex]['image'] = $section['cards'][$cardIndex]['image']
                            ?? ($oldData['cards'][$cardIndex]['image'] ?? null);
                    }

                    if (empty($section['cards'][$cardIndex]['image']) && ! empty($oldData['cards'][$cardIndex]['image'])) {
                        $section['cards'][$cardIndex]['image'] = $oldData['cards'][$cardIndex]['image'];
                    }
                    if (empty($section['cards'][$cardIndex]['icon']) && ! empty($oldData['cards'][$cardIndex]['icon'])) {
                        $section['cards'][$cardIndex]['icon'] = $oldData['cards'][$cardIndex]['icon'];
                    }
                }
            }

            if (isset($section['list_items'])) {
                foreach ($section['list_items'] as $itemIndex => $item) {
                    if ($request->input("sections.$order.list_items.$itemIndex.icon_source") === 'upload') {
                        if ($request->hasFile("sections.$order.list_items.$itemIndex.icon")) {
                            $uploadedFile = $request->file("sections.$order.list_items.$itemIndex.icon");
                            $filename = generateFileName($uploadedFile);
                            $uploadedFile->move(public_path('images/library'), $filename);

                            $section['list_items'][$itemIndex]['icon'] = $filename;
                        } else {
                            // Preserve the old image if no new file is uploaded
                            $section['list_items'][$itemIndex]['icon'] = $oldData['list_items'][$itemIndex]['icon'] ?? null;
                        }
                    } elseif ($request->input("sections.$order.list_items.$itemIndex.icon_source") === 'library') {
                        $section['list_items'][$itemIndex]['icon'] = $request->input("sections.$order.list_items.$itemIndex.icon");
                    } elseif ($request->input("sections.$order.list_items.$itemIndex.icon_source") === 'remove') {
                        $section['list_items'][$itemIndex]['icon'] = null;
                    }
                }
            }

            if ($request->input("sections.$order.image_source") === 'upload') {
                if ($request->hasFile("sections.$order.image")) {

                    $uploadedFile = $request->file("sections.$order.image");
                    $filename = generateFileName($uploadedFile);
                    $uploadedFile->move(public_path('images/library'), $filename);

                    $section['image'] = $filename;
                } else {
                    $section['image'] = $oldData['image'] ?? null;
                }
            } elseif ($request->input("sections.$order.image_source") === 'library') {
                $section['image'] = $request->input("sections.$order.image");
            } elseif ($request->input("sections.$order.image_source") === 'remove') {
                $section['image'] = null;
            }

            if ($request->input("sections.$order.icon_source") === 'upload') {
                if ($request->hasFile("sections.$order.icon")) {
                    $uploadedFile = $request->file("sections.$order.icon");
                    $filename = generateFileName($uploadedFile);
                    $uploadedFile->move(public_path('images/library'), $filename);

                    $section['icon'] = $filename;
                } else {
                    $section['icon'] = $oldData['icon'] ?? null;
                }
            } elseif ($request->input("sections.$order.icon_source") === 'library') {
                $section['icon'] = $request->input("sections.$order.icon");
            } elseif ($request->input("sections.$order.icon_source") === 'remove') {
                $section['icon'] = null;
            }

            if (empty($section['image']) && ! empty($oldData['image'])) {
                $section['image'] = $oldData['image'];
            }
            if (empty($section['icon']) && ! empty($oldData['icon'])) {
                $section['icon'] = $oldData['icon'];
            }

            foreach (['image_alt', 'icon_alt'] as $altField) {
                if (trim((string) ($section[$altField] ?? '')) === '' && ! empty($oldData[$altField])) {
                    $section[$altField] = $oldData[$altField];
                }
            }

            if (isset($section['cards'])) {
                foreach ($section['cards'] as $cardIndex => $card) {
                    foreach (['image_alt', 'icon_alt'] as $altField) {
                        $submitted = trim((string) ($section['cards'][$cardIndex][$altField] ?? ''));
                        $previous = $oldData['cards'][$cardIndex][$altField] ?? null;
                        if ($submitted === '' && ! empty($previous)) {
                            $section['cards'][$cardIndex][$altField] = $previous;
                        }
                    }
                }
            }

            if (empty($section['section_type'])) {
                continue;
            }

            $section = normalize_section_backgrounds($section);

            $sectionId = $section['section_id'] ?? $section['id'] ?? null;
            $sectionPayload = [
                'order' => $order,
                'section_type' => $section['section_type'],
                'data' => json_encode($section, JSON_PRETTY_PRINT),
            ];

            if ($sectionId) {
                $savedSection = PageSection::updateOrCreate(
                    ['id' => $sectionId, 'page_id' => $id],
                    $sectionPayload
                );
            } else {
                $savedSection = PageSection::create(array_merge(['page_id' => $id], $sectionPayload));
            }

            // Track updated sections
            $newSectionIds[] = $savedSection->id;
        }

        // Delete removed sections — block accidental mass wipe when the editor sends incomplete data.
        $sectionsToDelete = $existingSections->keys()->diff($newSectionIds);
        $existingCount = $existingSections->count();
        $incomingCount = count($newSectionIds);

        if ($sectionsToDelete->isNotEmpty() && $existingCount > 0) {
            $wouldDeleteCount = $sectionsToDelete->count();
            $looksLikePartialSave = $incomingCount < $existingCount
                && ($wouldDeleteCount > 1 || $incomingCount < (int) ceil($existingCount * 0.5));

            if ($looksLikePartialSave) {
                return redirect()->back()
                    ->with(
                        'fail',
                        'Save blocked to protect page content. The editor only sent '
                        . $incomingCount . ' of ' . $existingCount
                        . ' sections (missing sections were not deleted). Refresh the page, confirm all sections are visible, then save again.'
                    )
                    ->withInput();
            }
        }

        PageSection::whereIn('id', $sectionsToDelete)->delete();

        $page->refresh();
        touch_content_audit($page);
        record_panel_activity(
            'Page Updated',
            $page->page_name ?: $page->url,
            route('pages.edit', $page->id),
            $request
        );

        $redirectRoute = pages_status_enabled() && normalize_page_status($request->input('status')) === 0
            ? 'admin.pages.disabled'
            : 'pages.index';

        return redirect()->route($redirectRoute)->with('success', 'Page saved successfully!');
    }

    private function mergeSectionsPayload(Request $request): void
    {
        $sections = $this->decodeSectionsPayload((string) $request->input('sections_payload', ''));
        if ($sections === null) {
            return;
        }

        $request->merge(['sections' => $sections]);
    }

    private function decodeSectionsPayload(string $payload): ?array
    {
        if ($payload === '') {
            return null;
        }

        // URL-encoded form posts turn base64 "+" into spaces — restore before decode.
        $normalized = strtr(str_replace(' ', '+', $payload), '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $json = base64_decode($normalized, true);
        if ($json === false) {
            return null;
        }

        $sections = json_decode($json, true);

        return is_array($sections) ? $sections : null;
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $page = Page::findOrFail($id);
        $pageLabel = $page->page_name ?: $page->url;
        $wasDisabled = pages_status_enabled() && (int) ($page->status ?? 1) === 0;

        $page->sections()->delete();
        $page->seo()->delete();
        $result = $page->delete();

        if ($result) {
            record_panel_activity(
                'Page Deleted',
                $pageLabel . ($wasDisabled ? ' (was disabled)' : ''),
                route('pages.index'),
                request()
            );
            session()->flash('sucess', 'Record deleted successfullly!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }

        return redirect()->route('pages.index');
    }
}