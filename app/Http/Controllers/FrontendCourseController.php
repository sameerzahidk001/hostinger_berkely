<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Category;
use Illuminate\Http\Request;

class FrontendCourseController extends Controller
{
    public function filterCourses()
    {
        $data['schools'] = School::with('categories.courses:id,title')->get();
        return view('filter-courses')->with($data);
    }

    public function schoolCategoriesAJAX(Request $request)
    {
        $schoolId  = $request->school_id;
        $urlTarget = $request->urlTarget ?? '';

        $categories = Category::withCoursesBySchool($schoolId)->get();

        if (!$categories || $categories->isEmpty()) {
            return response()->json([
                'html' => '<p class="text-gray-500 text-center">No categories found for this school.</p>'
            ], 200);
        }

        $html = view('components.categories-courses', [
            'categories' => $categories,
            'urlTarget'  => $urlTarget
        ])->render();

        return response()->json(['html' => $html]);
    }
}