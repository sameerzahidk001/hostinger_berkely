<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\CourseTestimonial;
use App\Http\Controllers\Controller;
use App\Models\{LearnerStory, Subject};

class LearnerStoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //return $data['stories'] = LearnerStory::with('category')->get();
        $data['all_testimonial'] = CourseTestimonial::with('course:id,title,slug', 'course.categories')->paginate(6);
        return view('admin.story.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['categories'] = Subject::all();
        return view('admin.story.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $data = $request->except('token');
        $data['date'] = $request->date;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/images/stories');
            $file->move($destinationPath, $fileName);
            $data['image'] = '/admin/images/stories/' . $fileName;
        }
        $LearnerStories = LearnerStory::create($data);
        return redirect()->route('learner-stories.index');
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $LearnerStories = LearnerStory::findOrFail($id);
        $LearnerStories->delete();
        return redirect()->route('learner-stories.index');
    }
}
