<?php

namespace App\Http\Controllers\Admin;

use App\Models\School;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schools = \App\Models\School::withTrashed()->get();

        foreach ($schools as $school) {
            $school->category_count = \App\Models\Category::whereHas('courses.schools', function ($q) use ($school) {
                $q->where('schools.id', $school->id);
            })->count();
        }

        return view('admin.school.index', compact('schools'));
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
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255|unique:schools,name',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'short_description' => 'nullable|string|max:500',
        ]);

        // Prepare data except files
        $data = $request->except('_token', 'icon', 'image');
        $data['slug'] = Str::slug($request->name);

        $logoFields = ['icon', 'image'];
        foreach ($logoFields as $field) {
            if ($request->hasFile($field)) {
                $uploadedFile = $request->file($field);
                $filename = "{$field}_" . time() . '.' . $uploadedFile->getClientOriginalExtension();
                $uploadedFile->move(public_path('images/library'), $filename);

                $data[$field] = $filename;
            }
        }

        // Store the school data
        $school = School::create($data);

        if ($school) {
            return redirect()->route('school.index')->with('success', 'Record added successfully!');
        } else {
            return redirect()->route('school.index')->with('error', 'Failed to insert record.');
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
        $data['school'] = School::findOrFail($id);
        $data['categories'] = Category::all();
        return view('admin.school.edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'short_description' => 'nullable|string',
        ]);

        $school = School::findOrFail($id);
        $data = $request->except('_token', 'icon', 'image', 'schools');

        // Generate Slug
        $data['slug'] = Str::slug($request->name);

        $logoFields = ['icon', 'image'];
        foreach ($logoFields as $field) {
            if ($request->hasFile($field)) {
                $uploadedFile = $request->file($field);
                $filename = "{$field}_" . time() . '.' . $uploadedFile->getClientOriginalExtension();
                $uploadedFile->move(public_path('images/library'), $filename);

                if (!empty($settings->$field) && file_exists(public_path('images/library/' . $school->$field))) {
                    unlink(public_path('images/library/' . $school->$field));
                }

                $data[$field] = $filename;
            }
        }

        // Update the School Record
        $schoolUpdated = $school->update($data);

        // Update Categories (Many-to-Many Relationship)
        $school->categories()->sync($request->schools ?? []);

        if ($schoolUpdated) {
            return redirect()->route('school.index')->with('success', 'Record Updated Successfully!');
        } else {
            return redirect()->route('school.index')->with('error', 'Failed to Update Record!');
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //return $id;
        $school = School::findOrFail($id);
        $del_school = $school->forceDelete();
        if ($del_school) {
            session()->flash('sucess', 'Record deleted successfullly!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }
        return redirect()->route('school.index');
    }

    function updateStatusSchool(Request $request, $id)
    {

        $school = School::withTrashed()->findOrFail($id);

        try {
            if ($request->status == 'disable') {
                $school->delete(); // Soft delete
            } elseif ($request->status == 'active') {
                $school->restore(); // Restore
            }
            return response()->json(['success' => 'school status updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating school status. Please try again.'], 500);
        }
    }
}