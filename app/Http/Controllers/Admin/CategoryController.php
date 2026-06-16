<?php

namespace App\Http\Controllers\Admin;

use App\Models\School;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['schools'] = School::all();
        $data['categories'] = Category::with('schools', 'courses:id,title', 'createdBy', 'updatedBy')
            ->withCount('courses')
            ->withTrashed()
            ->get();
        return view('admin.category.index')->with($data);
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
        $request->validate([
            'name' => 'required',
            // other validation rules
        ]);

        $data = $request->except('_token');
        $data['slug'] = Str::slug($request->name);
        $category = Category::create($data);

        if ($category) {
            session()->flash('sucess', 'Record Added successfullly!');
            // if ($request->has('schools')) {
            //     $category->schools()->sync($request->input('schools'));
            // }
        } else {
            session()->flash('sucess', 'Failed to insert Record!');
        }
        return redirect()->route('category.index');
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
        $data['category'] = Category::findOrFail($id);
        $data['schools'] = School::all();
        return view('admin.category.edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //return $request;
        $request->validate([
            'name' => 'required',
        ]);
        $category = Category::findOrFail($id);
        $data = $request->except('_token');
        $data['slug'] = Str::slug($request->name);
        $category_updated = $category->update($data);
        $categories_schools_updated = $category->schools()->sync($request->schools);

        if ($category_updated) {
            session()->flash('sucess', 'Record Added successfullly!');
            return redirect()->route('category.index');
        } else {
            session()->flash('sucess', 'Failed to insert Record!');
            return redirect()->route('category.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $del_category = $category->forceDelete();
        if ($del_category) {
            session()->flash('sucess', 'Record deleted successfullly!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }
        return redirect()->route('category.index');
    }

    function updateStatusCategory(Request $request, $id)
    {

        $category = Category::withTrashed()->findOrFail($id);

        try {
            if ($request->status == 'disable') {
                $category->delete(); // Soft delete
            } elseif ($request->status == 'active') {
                $category->restore(); // Restore
            }
            return response()->json(['success' => 'category status updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating category status. Please try again.'], 500);
        }
    }
}