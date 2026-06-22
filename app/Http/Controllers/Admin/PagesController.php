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

        $query = Page::with(['parent', 'seo.page.sections', 'seo.course', 'faqs', 'createdBy', 'updatedBy']);

        if ($data['pagesStatusEnabled']) {
            $query->where('status', 1);
        }

        $data['pages'] = $query
            ->orderByDesc('created_at')
            ->get()
            ->each(function (Page $page) {
                if ($page->seo) {
                    $page->seo_analysis = app(SeoAnalyzerService::class)->analyze($page->seo);
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
        $data['pages'] = Page::with(['parent', 'seo.page.sections', 'seo.course', 'faqs', 'createdBy', 'updatedBy'])
            ->where('status', 0)
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (Page $page) {
                if ($page->seo) {
                    $page->seo_analysis = app(SeoAnalyzerService::class)->analyze($page->seo);
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
            $page->page_name ?: $page->url,
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
            'meta_title' => 'nullable|string|max:' . seo_field_limits()['title_max'],
            'meta_description' => 'nullable|string|max:' . seo_field_limits()['meta_description_max'],
            'meta_keywords' => 'nullable|string|max:' . seo_field_limits()['priority_keywords_max_total'],
            'meta_additional_keywords' => 'nullable|string|max:' . seo_field_limits()['additional_keywords_max_total'],
            'meta_thumbnail_path' => 'nullable|string',
            'local_meta_thumbnail_input' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Handle Meta Information (PagesSEO)
        $meta = PagesSEO::firstOrNew(['page_id' => $id]);

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

        $meta->title = $request->input('meta_title');
        $meta->meta_description = $request->input('meta_description');
        $meta->keywords = $request->input('meta_keywords');
        $meta->additional_keywords = $request->input('meta_additional_keywords');
        assign_column_if_exists($meta, 'thumbnail_alt', $request->input('meta_thumbnail_alt'));

        if ($request->hasFile('local_meta_thumbnail_input')) {
            $file = $request->file('local_meta_thumbnail_input');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $slug = Str::slug($originalName) . '-' . time();
            $fileName = $slug . '.' . $extension;
            $destinationPath = public_path('images/library/');
            $file->move($destinationPath, $fileName);
            $meta->thumbnail = '/images/library/' . $fileName;
        } elseif ($request->filled('meta_thumbnail_path')) {
            $meta->thumbnail = str_replace('\\', '/', $request->meta_thumbnail_path);
        }

        $meta->save();

        $page->load('sections');
        $existingSections = $page->sections->keyBy('id');
        $newSectionIds = [];

        if (! $request->has('sections')) {
            return redirect()->back()
                ->with('error', 'Page meta saved, but section data was not received. The form may be too large — try again or ask hosting to increase post_max_size and max_input_vars.')
                ->withInput();
        }

        $sections = $request->input('sections');
        if (! is_array($sections)) {
            return redirect()->back()
                ->with('error', 'Invalid section data received. Existing sections were kept unchanged.')
                ->withInput();
        }

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

            if (empty($section['section_type'])) {
                continue;
            }

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

        // Delete removed sections
        $sectionsToDelete = $existingSections->keys()->diff($newSectionIds);
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