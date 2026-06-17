<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use App\Models\PagesSEO;
use App\Services\SeoAnalyzerService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SeoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Initialize the query builder
        $query = PagesSEO::with(['page', 'course', 'createdBy', 'updatedBy']);

        // Filter by title/name if provided
        if ($request->has('name') && !empty($request->name)) {
            $query->where('title', 'LIKE', '%' . $request->name . '%');
        }

        // Filter by type (either page or course)
        if ($request->has('type') && !empty($request->type)) {
            if ($request->type == 'page') {
                $query->whereNotNull('page_id'); // Filter for pages
            } elseif ($request->type == 'course') {
                $query->whereNotNull('course_id'); // Filter for courses
            }
        }

        $analyzer = app(SeoAnalyzerService::class);
        $perPage = (int) ($request->input('per_page', 20));
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $paginator = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $paginator->getCollection()->transform(function (PagesSEO $seo) use ($analyzer) {
            $seo->analysis = $analyzer->analyze($seo);
            return $seo;
        });

        $data['pages_seo'] = $paginator;
        $data['per_page'] = $perPage;

        // Return the view with filtered data
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
        //return $request;
        $request->validate([
            'title' => 'required',
            // other validation rules
        ]);

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
        $data['page_seo'] = PagesSEO::with(['page.sections', 'course'])->findOrFail($id);
        $data['seo_analysis'] = app(SeoAnalyzerService::class)->analyze($data['page_seo']);
        return view('admin.seo.edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //return $request;
        // $request->validate([
        //     'page_url' => 'required',
        //     // other validation rules
        // ]);
        //$page_seo = PagesSEO::findOrFail($id);
        if ($request->has('course_id')) {
            $page_seo = PagesSEO::where('course_id', $request->course_id)->first();
        } elseif ($request->has('page_id')) {
            $page_seo = PagesSEO::where('page_id', $request->page_id)->first();
        }
        $data = $request->except('_token');
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
        
        if($pages_seo_updated && $request->has('course_id') ){
            if($pages_seo_updated){
                session()->flash('sucess', 'Record Added successfullly!');
            }else{
                session()->flash('failed', 'Failed to insert Record!');
            }
            
            return redirect()->route('admin.courses');

        }elseif($pages_seo_updated && $request->has('page_id')){
            if($pages_seo_updated){
                session()->flash('sucess', 'Record Added successfullly!');
            }else{
                session()->flash('failed', 'Failed to insert Record!');
            }
            return redirect()->route('pages-seo.index');
        }
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
