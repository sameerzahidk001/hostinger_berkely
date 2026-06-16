<?php

namespace App\Http\Controllers\Admin;

use App\Models\Subject;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SubjectController extends Controller
{
    function index(Request $request){

        
        $query = Subject::query();
        if ($request->has('featured')) {
            // Apply filtering to only get featured subjects
            $query->where('is_featured', true);
        }

        $data['subjects'] = $query->paginate(20);
        return view('admin.subject.index')->with($data);
        
    }

    function create(){
        return view('admin.subject.create');
    }

    function store(Request $request){
        //return $request;
        $request->validate([
            'name' => 'required|string|max:255',
           // 'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
           // 'short_description' => 'required',
           // 'description' => 'required',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/subject');
            $file->move($destinationPath, $fileName);
            $data['image'] = '/admin/subject/' . $fileName;
            //$data['image'] = $path;
        }
        //Category::create($data);
        $latestRecordPriority = Subject::latest('id')->select('priority')->first();
        $data['priority'] = $latestRecordPriority->priority += 1;
        $subject = Subject::create($data);
        if ($subject) {
            session()->flash('sucess', 'Record added successfullly!');
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }

        return redirect('admin/subjects')->with($data);

    }

    function edit($id){

        $data['subject'] = Subject::findOrFail($id);

        return view('admin.subject.edit')->with($data);
    }

    function update(Request $request){
        //return $request;
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $subject = Subject::findOrFail($request->subject_id);
        $data = $request->all();
        $data['slug'] = Str::slug($request->name);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/subject');
            $file->move($destinationPath, $fileName);
            $data['image'] = '/admin/subject/' . $fileName;
        }
        $updateResult = $subject->update($data);
        if ($updateResult) {
            session()->flash('sucess', 'Record Updated successfullly!');
        } else {
            session()->flash('failed', 'Failed to update Record!');
        }

        return redirect('admin/subjects');
    }

    function delete(Request $request){

        $id = $request->input('id');
        $Subject = Subject::findOrFail($id);
        $Subject->delete();
        return response()->json(['message' => 'Record deleted successfully']);

    }
}
