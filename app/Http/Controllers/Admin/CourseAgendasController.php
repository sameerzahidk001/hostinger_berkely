<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseAgenda;
use App\Models\Course;
use App\Models\Country;

class CourseAgendasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $course_agendas = CourseAgenda::with(['course', 'country', 'createdBy', 'updatedBy'])->get();
        return view('admin.course-agendas.index', compact('course_agendas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $courses = Course::all();
        $countries = Country::all();
        return view('admin.course-agendas.create', compact('courses', 'countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course' => 'required|exists:courses,id',
            'subject' => 'required|string|max:255',
            'delivery_type' => 'required|string|max:255',
            'city' => 'nullable|string|max:150',
            'inquiry' => 'nullable|string',
            'description' => 'nullable|string',
            'country' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value != 0 && !\DB::table('countries')->where('id', $value)->exists()) {
                        $fail('The selected country is invalid.');
                    }
                },
            ],
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        CourseAgenda::create([
            'course_id'    => $validated['course'],
            'subject'      => $validated['subject'],
            'delivery_type'=> $validated['delivery_type'],
            'country_id'   => $validated['country'],
            'city'         => $validated['city'],
            'from'         => $validated['from'],
            'to'           => $validated['to'],
            'inquiry'      => $validated['inquiry'],
            'description'  => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.course-agendas.index')->with('success', 'Course Agenda created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseAgenda $course_agenda)
    {
        return view('admin.course-agendas.show', compact('course_agenda'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CourseAgenda $course_agenda)
    {
        $courses = Course::all();
        $countries = Country::all();
        return view('admin.course-agendas.edit', compact('course_agenda', 'courses', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CourseAgenda $course_agenda)
    {
        $validator = Validator::make($request->all(), [
            'course' => 'required|exists:courses,id',
            'subject' => 'required|string|max:255',
            'delivery_type' => 'required|string|max:255',
            'city' => 'nullable|string|max:150',
            'inquiry' => 'nullable|string',
            'description' => 'nullable|string',
            'country' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value != 0 && !\DB::table('countries')->where('id', $value)->exists()) {
                        $fail('The selected country is invalid.');
                    }
                },
            ],
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $course_agenda->update([
            'course_id'    => $validated['course'],
            'subject'      => $validated['subject'],
            'delivery_type'=> $validated['delivery_type'],
            'country_id'   => $validated['country'],
            'city'         => $validated['city'],
            'from'         => $validated['from'],
            'to'           => $validated['to'],
            'inquiry'      => $validated['inquiry'],
            'description'  => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.course-agendas.index')->with('success', 'Course Agenda updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseAgenda $course_agenda)
    {
        $course_agenda->delete();
        return redirect()->route('admin.course-agendas.index')->with('success', 'Course Agenda deleted successfully.');
    }
}