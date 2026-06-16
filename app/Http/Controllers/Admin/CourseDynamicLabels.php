<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\DynamicLabels;
use App\Http\Controllers\Controller;

class CourseDynamicLabels extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //return 'hello';
        $data['labels'] = DynamicLabels::with(['course:id,title'])->get();
        return view('admin.course.label.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['courses'] = Course::get(['id','title']);
        return view('admin.course.label.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //return $request;
        // $request->validate([
        //     'course_id' => 'required|exists:courses,id',
        //     // other validation rules
        // ]);
        $data = $request->all();
        
        if ($request->hasFile('who_can_do_img')) {

            $file = $request->file('who_can_do_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['who_can_do_img'] = '/admin/courses/' . $fileName;
        }
        if ($request->hasFile('lectures_img')) {

            $file = $request->file('lectures_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['lectures_img'] = '/admin/courses/' . $fileName;
            $data['lectures_img_file_type'] = $file->getClientOriginalExtension();
        }
        if ($request->hasFile('practice_session_img')) {

            $file = $request->file('practice_session_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['practice_session_img'] = '/admin/courses/' . $fileName;
            $data['practice_session_img_file_type'] = $file->getClientOriginalExtension();
        }
        if ($request->hasFile('mock_examination_img')) {

            $file = $request->file('mock_examination_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['mock_examination_img'] = '/admin/courses/' . $fileName;
            $data['mock_examination_img_file_type'] = $file->getClientOriginalExtension();
        }
        if ($request->hasFile('exam_information_section_img')) {

            $file = $request->file('exam_information_section_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['exam_information_section_img'] = '/admin/courses/' . $fileName;
            
        }
        
        if ($request->hasFile('career_path_section_img')) {

            $file = $request->file('career_path_section_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['career_path_section_img'] = '/admin/courses/' . $fileName;
        }
        if ($request->hasFile('what_you_earn_img')) {

            $file = $request->file('what_you_earn_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['what_you_earn_img'] = '/admin/courses/' . $fileName;
        }

        $course = Course::findOrFail($request->course_id);
        $assignLabels = $course->dynamicLabel()->create($data);

        if ($assignLabels) {
            session()->flash('sucess', 'Labels to course added successfullly!');
        } else {
            session()->flash('failed', 'Failed to add Labels');
        }
        
        return redirect()->route('course-labels.index');
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
        $data['courses'] = Course::get(['id','title']); 
        $data['courseLabel'] = DynamicLabels::with(['course:id,title'])->findOrFail($id);
        return view('admin.course.label.edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //return $request;
        $labels = DynamicLabels::findOrFail($request->id);
        $data = $request->all();
        if ($request->hasFile('who_can_do_img')) {

            $file = $request->file('who_can_do_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['who_can_do_img'] = '/admin/courses/' . $fileName;
        }
        
        if ($request->hasFile('exam_information_section_img')) {

            $file = $request->file('exam_information_section_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['exam_information_section_img'] = '/admin/courses/' . $fileName;
        }
        if ($request->hasFile('lectures_img')) {

            $file = $request->file('lectures_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['lectures_img'] = '/admin/courses/' . $fileName;
            $data['lectures_img_file_type'] = $file->getClientOriginalExtension();
        }
        if ($request->hasFile('practice_session_img')) {

            $file = $request->file('practice_session_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['practice_session_img'] = '/admin/courses/' . $fileName;
            $data['practice_session_img_file_type'] = $file->getClientOriginalExtension();
        }
        if ($request->hasFile('mock_examination_img')) {

            $file = $request->file('mock_examination_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['mock_examination_img'] = '/admin/courses/' . $fileName;
            $data['mock_examination_img_file_type'] = $file->getClientOriginalExtension();
        }
        if ($request->hasFile('career_path_section_img')) {

            $file = $request->file('career_path_section_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['career_path_section_img'] = '/admin/courses/' . $fileName;
        }
        if ($request->hasFile('what_you_earn_img')) {

            $file = $request->file('what_you_earn_img');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $data['what_you_earn_img'] = '/admin/courses/' . $fileName;
        }
        $labelsUpdated = $labels->update($data);
        if ($labelsUpdated) {
            session()->flash('sucess', 'Record Updated successfullly!');
        } else {
            session()->flash('failed', 'Failed to update Record!');
        }
        return redirect()->route('course-labels.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $CourseLabel = DynamicLabels::findOrFail($id);
        $result = $CourseLabel->delete();
        if ($result) {
            session()->flash('sucess', 'Record deleted successfullly!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }
        
        return redirect()->route('course-labels.index');
    }
}
