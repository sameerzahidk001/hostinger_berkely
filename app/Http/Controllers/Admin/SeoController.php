<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use App\Models\PagesSEO;
use App\Models\SiteSettings;
use App\Services\SeoAnalyzerService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SeoController extends Controller
{
    public function __construct()
    {
        $this->middleware('long.running')->only(['edit', 'analyzePreview']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PagesSEO::query()
            ->select([
                'id',
                'page_id',
                'course_id',
                'title',
                'meta_description',
                'focus_keyword',
                'keywords',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ])
            ->with([
                'page:id,page_name,url,parent_id,category_id',
                'page.parent:id,url',
                'course:id,title,slug',
                'createdBy:id,username,email',
                'updatedBy:id,username,email',
            ]);

        if ($request->filled('name')) {
            $query->where('title', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('type')) {
            if ($request->type === 'page') {
                $query->whereNotNull('page_id');
            } elseif ($request->type === 'course') {
                $query->whereNotNull('course_id');
            }
        }

        $analyzer = app(SeoAnalyzerService::class);
        $data['pages_seo'] = $query
            ->orderByDesc('id')
            ->get()
            ->each(function (PagesSEO $pageSeo) use ($analyzer) {
                $pageSeo->seo_analysis = $analyzer->analyzeForListing($pageSeo);
            });

        $data['category_perma'] = SiteSettings::value('category_perma') ?? 'category';

        return view('admin.seo.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['all_pages'] = Page::whereDoesntHave('seo')->get();
        return view('admin.seo.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(seo_validation_rules());

        $data = $request->except('_token');
        if ($request->has('course_id')) {
            $data['course_id'] = $request->input('course_id');
            $data['page_id'] = NULL;
        } elseif ($request->has('page_id')) {
            $data['page_id'] = $request->input('page_id');
            $data['course_id'] = NULL;
        }
        if ($request->hasFile('thumbnail')) {

            $file = $request->file('thumbnail');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/images/seo/');
            $file->move($destinationPath, $fileName);
            $data['thumbnail'] = '/admin/images/seo/' . $fileName;
        }
        $pages_seo = PagesSEO::create($data);
        
        if($pages_seo){
            session()->flash('sucess', 'Record Added successfullly!');
            return redirect()->route('pages-seo.index');
        }else{
            session()->flash('sucess', 'Failed to insert Record!');
            return redirect()->route('pages-seo.index');
        }
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
    public function edit(string $id)
    {
        $data['page_seo'] = PagesSEO::with(['page.sections', 'course.dynamicLabel', 'course.courseFaq'])->findOrFail($id);
        $data['seo_analysis'] = app(SeoAnalyzerService::class)->analyzeForEdit($data['page_seo']);
        return view('admin.seo.edit')->with($data);
    }

    public function analyzePreview(Request $request, string $id)
    {
        $seo = PagesSEO::with(['page.sections', 'course.dynamicLabel', 'course.courseFaq'])->findOrFail($id);

        $seo->fill($request->only(['title', 'meta_description', 'focus_keyword', 'keywords']));

        return response()->json(app(SeoAnalyzerService::class)->analyze($seo));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate(seo_validation_rules());

        $page_seo = PagesSEO::findOrFail($id);

        $data = $request->except('_token', '_method');
        // dd($request->toArray());
        if ($request->hasFile('thumbnail_img')) {
            $file = $request->file('thumbnail_img');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $slug = Str::slug($originalName) . '-' . time();
            $fileName = $slug . '.' . $extension;
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['thumbnail'] = '/admin/courses/' . $fileName;
        } elseif ($request->filled('thumbnail_img_path')) {
            $data['thumbnail'] = $request->input('thumbnail_img_path');
        }

        $pages_seo_updated = $page_seo->update($data);
        app(SeoAnalyzerService::class)->clearAnalysisCache($page_seo->fresh());

        if ($pages_seo_updated) {
            session()->flash('sucess', 'Record updated successfully!');
        } else {
            session()->flash('failed', 'Failed to update record!');
        }

        if ($page_seo->course_id) {
            return redirect()->route('admin.courses');
        }

        return redirect()->route('pages-seo.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //return $id;
        $page_seo = PagesSEO::findOrFail($id);
        $del_page_seo = $page_seo->delete();
        if($del_page_seo){
            session()->flash('sucess', 'Record deleted successfullly!');
            
        }else{
            session()->flash('failed', 'Failed to delete Record');
        }
        return redirect()->route('pages-seo.index');

    }
}
