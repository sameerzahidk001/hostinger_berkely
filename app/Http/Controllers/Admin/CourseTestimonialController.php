<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use App\Models\Country;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CourseTestimonial;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CourseTestimonialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courseId = $request->input('course_id');
        $categoryId = $request->input('category_id');
        $status = $request->input('status', 'all');

        $query = CourseTestimonial::with('course:id,title,short_name,slug', 'course.categories')->withTrashed();

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        if ($categoryId) {
            $query->whereHas('course.categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        if ($status === 'active') {
            $query->whereNull('deleted_at')->where(function ($q) {
                $q->where('status', 'show')->orWhereNull('status');
            });
        } elseif ($status === 'disabled') {
            $query->where(function ($q) {
                $q->where('status', 'hide')
                    ->orWhereNotNull('deleted_at');
            });
        } else {
            $query->whereNull('deleted_at');
        }

        $data['all_testimonial'] = $query->latest()->get();
        $data['categories'] = Category::all();
        $data['courses'] = Course::select('id', 'title', 'short_name')->get();

        return view('admin.story.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['courses'] = Course::select('id', 'title')->get();
        $data['countries'] = Country::orderBy('name', 'asc')->get();
        return view('admin.story.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|exists:countries,name',
            'date' => 'nullable|date',
            'course_id' => 'nullable|exists:courses,id',
            'text' => 'nullable|string',
            'asset_path' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'rating' => 'nullable|integer|min:1|max:5',
            'image_path' => 'nullable|string',
            'local_file_input' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $testimonial = new CourseTestimonial();
        $testimonial->course_id = $request->course_id;
        $testimonial->user_id = auth('admin')->user()->id;
        $testimonial->name = $request->name;
        $testimonial->text = $request->text;
        $testimonial->city = $request->city;
        $testimonial->country = $request->country ?? null;
        $testimonial->asset_path = $request->asset_path;
        $testimonial->linkedin_url = $request->linkedin_url; // ✅ added
        $testimonial->rating = $request->rating;             // ✅ added
        $testimonial->asset_type = $request->asset_path ? 'youtube_video' : 'image';
        $testimonial->date = $request->date;

        // ✅ Image handling
        if ($request->hasFile('local_file_input')) {
            $file = $request->file('local_file_input');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $slug = Str::slug($originalName) . '-' . time();
            $fileName = $slug . '.' . $extension;
            $destinationPath = public_path('admin/courses/testimonial/');
            $file->move($destinationPath, $fileName);
            $testimonial->image = '/admin/courses/testimonial/' . $fileName;
        } elseif ($request->filled('image_path')) {
            $testimonial->image = str_replace('\\', '/', $request->image_path);
        }

        $testimonial->save();

        return redirect()->back()->with('success', 'Testimonial submitted successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data['course_test'] = Course::select('id', 'title', 'slug')->withTrashed()->with('testimonials')->where('id', $id)->first();
        return view('admin.course.testimonial.show')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data['courses'] = Course::select('id', 'title')->get();
        $data['countries'] = Country::orderBy('name', 'asc')->get();
        $data['testimonial'] = CourseTestimonial::with('course')->withTrashed()->findOrFail($id);

        return view('admin.story.edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseTestimonial $testimonial)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|exists:countries,name',
            'date' => 'nullable|date',
            'course_id' => 'nullable|exists:courses,id',
            'text' => 'nullable|string',
            'asset_path' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'rating' => 'nullable|integer|min:1|max:5',
            'image_path' => 'nullable|string',
            'local_file_input' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $testimonial->course_id = $request->course_id;
        $testimonial->name = $request->name;
        $testimonial->text = $request->text;
        $testimonial->city = $request->city;
        $testimonial->country = $request->country ?? null;
        $testimonial->asset_path = $request->asset_path;
        $testimonial->linkedin_url = $request->linkedin_url; // ✅ added
        $testimonial->rating = $request->rating;             // ✅ added
        $testimonial->asset_type = $request->asset_path ? 'youtube_video' : 'image';
        $testimonial->date = $request->date;

        // ✅ Image update handling
        if ($request->hasFile('local_file_input')) {
            if (!empty($testimonial->image) && Str::startsWith($testimonial->image, '/admin/courses/testimonial/')) {
                $oldFullPath = public_path(ltrim($testimonial->image, '/'));
                if (is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
            }

            $file = $request->file('local_file_input');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $slug = Str::slug($originalName) . '-' . time();
            $fileName = $slug . '.' . $extension;
            $destinationPath = public_path('admin/courses/testimonial/');
            $file->move($destinationPath, $fileName);
            $testimonial->image = '/admin/courses/testimonial/' . $fileName;
        } elseif ($request->filled('image_path')) {
            $testimonial->image = str_replace('\\', '/', $request->image_path);
        }

        $testimonial->save();

        return redirect()->back()->with('success', 'Testimonial updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $testimonial = CourseTestimonial::withTrashed()->findOrFail($id);

        if (!empty($testimonial->image) && Str::startsWith($testimonial->image, '/admin/courses/testimonial/')) {
            $oldFullPath = public_path(ltrim($testimonial->image, '/'));
            if (is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }

        $result = $testimonial->forceDelete();
        return redirect()->route('testimonial.index')->with('Record deleted successfullly!');
    }
}