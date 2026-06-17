<?php

namespace App\Http\Controllers\User;

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\CourseTestimonial;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $courseId = $request->input('course_id');
        $categoryId = $request->input('category_id');
        $status = $request->input('status', 'active');

        $query = CourseTestimonial::with('course:id,title,short_name,slug', 'course.categories')
            ->where('user_id', Auth::id())
            ->withTrashed();

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        if ($categoryId) {
            $query->whereHas('course.categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        if ($status === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($status === 'disabled') {
            $query->whereNotNull('deleted_at');
        }

        $data['all_testimonial'] = $query->get();
        $data['categories'] = Category::all();
        $data['courses'] = Course::select('id', 'title', 'short_name')->get();

        return view('user.testimonials.index')->with($data);
    }

    public function create()
    {
        $data['courses'] = Course::select('id', 'title')->get();
        return view('user.testimonials.create')->with($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'city'         => 'required|string|max:255',
            'country'      => 'required|string|max:255',
            'date'         => 'nullable|date',
            'text'         => 'required|string',
            'asset_path'   => 'nullable|url',
            'linkedin_url' => 'required|url',
            'rating'       => 'required|string',
            'course_id'    => 'required|integer|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->except('_token');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('student/courses/testimonial/');
            $file->move($destinationPath, $fileName);
            $data['image'] = '/student/courses/testimonial/' . $fileName;
        }

        $data['user_id'] = Auth::id();

        $testimonial = CourseTestimonial::create($data);
        if ($testimonial) {
            return redirect()->back()->with('success', 'Testimonial submitted successfully.');
        } else {
            return redirect()->back()->with('failed', 'Failed to insert Record!');
        }
    }

    public function show(string $id)
    {
        $data['course_test'] = Course::select('id', 'title', 'slug')
            ->withTrashed()
            ->with('testimonials')
            ->where('id', $id)
            ->first();
        return view('user.testimonials.show')->with($data);
    }

    public function edit(string $id)
    {
        $data['testimonial'] = CourseTestimonial::with('course:id,title,slug')
            ->withTrashed()
            ->findOrFail($id);
        return view('user.testimonials.edit')->with($data);
    }

    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'city'         => 'required|string|max:255',
            'country'      => 'required|string|max:255',
            'date'         => 'nullable|date',
            'text'         => 'required|string',
            'asset_path'   => 'nullable|url',
            'linkedin_url' => 'required|url',
            'rating'       => 'required|string',
            'course_id'    => 'required|integer|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $testimonial = CourseTestimonial::withTrashed()->findOrFail($id);

        $data = $request->except('_token', '_method');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('student/courses/testimonial/');
            $file->move($destinationPath, $fileName);
            $data['image'] = '/student/courses/testimonial/' . $fileName;
        }

        $testimonial_updated = $testimonial->update($data);

        if ($testimonial_updated) {
            return redirect()->back()->with('success', 'Testimonial updated successfully!');
        } else {
            return redirect()->back()->with('failed', 'Failed to update Record!');
        }
    }

    public function destroy(Request $request, string $id)
    {
        $testimonial = CourseTestimonial::withTrashed()->findOrFail($id);
        $result = $testimonial->forceDelete();

        if ($result) {
            session()->flash('success', 'Record deleted successfully!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }

        return redirect()->route('user.testimonial.show', ['testimonial' => $request->course_id]);
    }
}
