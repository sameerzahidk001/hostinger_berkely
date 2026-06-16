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
        $data['pages'] = Page::with(['parent', 'seo', 'createdBy', 'updatedBy'])->get();

        return view('admin.pages.index')->with($data);
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
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->except('_token');
        $pageCreated = Page::create($data);

        if ($pageCreated) {
            session()->flash('success', 'Record added successfully!');
        } else {
            session()->flash('error', 'Failed to insert record!');
        }

        return redirect()->route('pages.index');
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
                $section->data = json_decode($section->data, true);
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
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'meta_additional_keywords' => 'nullable|string|max:255',
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
        $page->save();

        $meta->title = $request->input('meta_title');
        $meta->meta_description = $request->input('meta_description');
        $meta->keywords = $request->input('meta_keywords');
        $meta->additional_keywords = $request->input('meta_additional_keywords');

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

        // Get existing sections indexed by ID
        $existingSections = $page->sections->keyBy('id');

        // Track new section IDs
        $newSectionIds = [];

        // dd($request->sections);

        foreach ($request->sections as $order => $section) {

            // Get the existing section based on ID (if available)
            $existingSection = isset($section['section_id']) ? $existingSections->get($section['section_id']) : null;
            $oldData = $existingSection ? json_decode($existingSection->data, true) : [];

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

            $savedSection = PageSection::updateOrCreate(
                ['id' => $section['id'] ?? null, 'page_id' => $id],
                [
                    'order' => $order,
                    'section_type' => $section['section_type'],
                    'data' => json_encode($section, JSON_PRETTY_PRINT),
                ]
            );

            // Track updated sections
            $newSectionIds[] = $savedSection->id;
        }

        // Delete removed sections
        $sectionsToDelete = $existingSections->keys()->diff($newSectionIds);
        PageSection::whereIn('id', $sectionsToDelete)->delete();

        return redirect()->route('pages.index')->with('success', 'Page saved successfully!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $page = Page::findOrFail($id);
        $page->sections()->delete();
        $page->seo()->delete();
        $result = $page->delete();
        if ($result) {
            session()->flash('sucess', 'Record deleted successfullly!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }

        return redirect()->route('pages.index');
    }
}