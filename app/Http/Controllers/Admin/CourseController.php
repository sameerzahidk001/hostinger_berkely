<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CourseTestimonial;
use App\Models\Instructor;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\{Currency, Subject, Course, School, CourseObjective, CourseReward, CourseFaq, CourseEnrollment, CourseSyllabus, CourseBeneficiaries, CourseFeePackages, FeePackagesFeatures, CourseStructure, CourseFee};

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('long.running')->only(['store', 'update']);
    }

    function index(Request $request)
    {
        $query = Course::with([
            'categories' => function ($query) {
                $query->orderBy('name', 'asc');
            },
            'seo',
            'courseFeePackages',
            'courseStructures',
            'relatedCourses:id,title,slug',
            'testimonials',
            'createdBy',
            'updatedBy',
        ]);

        // Get all categories for the filter dropdown
        $data['categories'] = Category::all();

        // Get the filtered or default list of courses
        $data['courses'] = $query->orderByDesc('created_at')->get();
        $data['instructors'] = DB::table('users')
            ->join('courses', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(users.id, courses.instructor_id)"), '>', DB::raw('0'));
            })
            ->join('user_has_roles', 'users.id', '=', 'user_has_roles.user_id')
            ->where('user_has_roles.role_id', 2)
            ->select('users.*')
            ->distinct()
            ->get();
        $data['course_id'] = DB::table('users')
            ->join('courses', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(users.id, courses.instructor_id)"), '>', DB::raw('0'));
            })
            ->join('user_has_roles', 'users.id', '=', 'user_has_roles.user_id')
            ->where('user_has_roles.role_id', 2)
            ->select('courses.id as course_id')
            ->distinct()
            ->pluck('course_id')
            ->first();
        return view('admin.course.index')->with($data);
    }

    function disabledCourses(Request $request)
    {
        $query = Course::onlyTrashed()->with([
            'categories' => function ($query) {
                $query->orderBy('name', 'asc');
            },
            'schools',
            'seo',
            'courseFeePackages',
            'courseStructures',
            'courseStructuresFirst',
            'courseFaq',
            'relatedCourses:id,title,slug',
            'testimonials',
            'createdBy',
            'updatedBy',
        ]);

        $data['courses'] = $query->orderByDesc('created_at')->get();

        return view('admin.course.disabled-courses')->with($data);
    }

    function instructors($id)
    {
        $course = Course::findOrFail($id);
        $instructors = User::whereHas('roles', function ($q) {
            $q->where('role_id', 2);
        })->get();

        $instructorIds = course_instructor_ids($course);

        // Fetch assigned instructor users
        $assignIntructors = User::whereIn('id', $instructorIds)->get();

        return view('admin.course.instructors', [
            'course' => $course,
            'instructors' => $instructors,
            'assignIntructors' => $assignIntructors,
        ]);
    }
    
    function create()
    {
        $data['subjects'] = Subject::all();
        $data['instructors'] = DB::table('users')
            ->join('user_has_roles', 'users.id', '=', 'user_has_roles.user_id') // adjust pivot table name if different
            ->where('user_has_roles.role_id', 2)
            ->select('users.*')
            ->get();
        return view('admin.course.create')->with($data);
    }

    function edit($id)
    {
        //return $data['course'] = Course::with('subjects')->where('id', $id)->first();
        $data['course'] = Course::withTrashed()->with('categories', 'dynamicLabel')->findOrFail($id);
        $data['categories'] = Category::all();
        $data['schools'] = School::all();
        return view('admin.course.edit')->with($data);
    }

    function show($id)
    {
        $data['course'] = Course::withTrashed()->with('courseObjectivePoints', 'courseRewards', 'courseFaq', 'courseBeneficiaries', 'courseEnrollments', 'courseSyllabus')->where('id', $id)->first();
        return view('admin.course.show')->with($data);
    }

    function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:courses',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->hasFile('thumbnail')) {

            $file = $request->file('thumbnail');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $thumbnail = '/admin/courses/' . $fileName;

        }

        if ($request->hasFile('overview_img')) {
            $file = $request->file('overview_img');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $slug = Str::slug($originalName) . '-' . time();
            $fileName = $slug . '.' . $extension;
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $updatedData['overview_img'] = '/admin/courses/' . $fileName;
        } elseif ($request->filled('overview_img_path')) {
            $updatedData['overview_img'] = $request->input('overview_img_path');
        }

        if ($request->hasFile('video')) {

            $file = $request->file('video');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $video = '/admin/courses/' . $fileName;

        }
        if ($request->hasFile('thumbnail')) {

            $file = $request->file('thumbnail');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $thumbnail = '/admin/courses/' . $fileName;
        }

        $course = new Course;
        $course->title = $request->title;
        $course->slug = Str::slug($request->title);
        $course->short_description = $request->short_description;
        $course->description = $request->detailed_description;
        $course->offered_by = $request->offered_by;
        $course->vision_and_mission = $request->vision_and_mission;
        $course->overview_img = $overview_img ?? NULL;
        $course->benifits = $request->benifits;
        $course->who_can_do = $request->who_can_do;
        $course->thumbnail = $request->thumbnail ?? NULL;

        $course->eligibility = $request->eligibility;
        $course->exam_dates = $request->exam_dates;
        $course->exam_reg_deadline = $request->exam_reg_deadline;
        $course->exam_passing_criteria = $request->exam_passing_criteria;
        $course->exam_location = $request->exam_location ?? NULL;
        $course->exam_location_paragraph = $request->exam_location_paragraph;
        $course->salary = $request->salary;
        $course->career_path = $request->career_path;
        $saveResult = $course->save();
        if (is_array($request->course && $request->course[0]['title'] != NULL)) {
            foreach ($request->course as $courseStructure) {

                $courseStructureInserted = $course->courseStructures()->create([
                    'title' => $courseStructure['title'],
                    'heading' => $courseStructure['heading'],
                    'exam_format' => $courseStructure['exam_format'],
                    'exam_duration' => $courseStructure['exam_duration'],
                ]);

                // Create the related Subheadings
                foreach ($courseStructure['subheading'] as $subheadingData) {
                    $subheading = $courseStructureInserted->subHeadings()->create([
                        'sub_heading' => $subheadingData['subheading'],
                    ]);

                    if (isset($subheadingData['unit']) && is_array($subheadingData['unit'])) {
                        foreach ($subheadingData['unit'] as $unitData) {
                            $unitVideoPath = null;

                            if (isset($unitData['video']) && $unitData['video'] instanceof \Illuminate\Http\UploadedFile) {
                                $file = $unitData['video'];
                                $fileName = $file->getClientOriginalName();
                                $destinationPath = public_path('admin/courses');
                                $file->move($destinationPath, $fileName);
                                $unitVideoPath = '/admin/courses/' . $fileName;
                            }

                            $subheading->subHeadingsUnits()->create([
                                'unit_title' => $unitData['title'],
                                'unit_video' => $unitVideoPath,
                            ]);
                        }
                    }
                }
            }
        }

        if ($saveResult) {
            session()->flash('sucess', 'Record added successfullly!');
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }
        if ($request->has('subject')) {
            $course->subjects()->attach($request->input('subject'));
        }
        return redirect()->route('admin.courses');
    }

    public function addInstructor(Request $request)
    {
        $course_id = $request->input('course_id');
        $newInstructorIds = $request->input('instructor_id');

        // Always work with array
        $newInstructorIds = is_array($newInstructorIds) ? $newInstructorIds : [$newInstructorIds];

        // Fetch the course
        $course = Course::findOrFail($course_id);

        // Convert existing instructor_ids to array
        $existingIds = course_instructor_ids($course);

        // Merge and remove duplicates
        $allInstructorIds = array_values(array_unique(array_merge(
            $existingIds,
            array_map('intval', $newInstructorIds)
        )));

        // Save updated instructor list (stored as JSON array via model cast)
        $course->instructor_id = $allInstructorIds;
        $course->save();

        return back()->with('success', 'Instructors updated successfully!');
    }

    public function deleteInstructor(Request $request, $id)
    {
        $instructorIdToRemove = $request->input('id');
        $course = Course::findOrFail($id);

        $instructorIds = array_values(array_filter(
            course_instructor_ids($course),
            fn ($value) => (int) $value !== (int) $instructorIdToRemove
        ));

        $course->instructor_id = $instructorIds;
        $course->save();

        return back()->with('success', 'Instructors removed successfully!');
    }

    public function update(Request $request, $id)
    {
        $courseModule = $request->course_module;
        
        if ($courseModule == "overview_section") {

            $course = Course::withTrashed()->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:courses,title,' . $id,
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $updatedData = [
                'title' => $request->title,
                'short_name' => $request->short_name,
                'slug' => Str::slug($request->slug),
                'description' => $request->description,
                'offered_by' => $request->offered_by,
                'vision_and_mission' => $request->vision_and_mission,
                'overview_section' => $request->overview_section,
                'overview_video_url' => $request->overview_video_url
            ];

            if ($request->hasFile('overview_img')) {
                $file = $request->file('overview_img');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $slug = Str::slug($originalName) . '-' . time();
                $fileName = $slug . '.' . $extension;
                $destinationPath = public_path('admin/courses');
                $file->move($destinationPath, $fileName);
                $updatedData['overview_img'] = '/admin/courses/' . $fileName;
            } elseif ($request->filled('overview_img_path')) {
                $updatedData['overview_img'] = $request->input('overview_img_path');
            }

            $course_overview_updated = $course->update($updatedData);

            if ($request->has('categories') && !empty($request->categories)) {
                $course->categories()->sync($request->categories);
            } else {
                $course->categories()->detach();
            }

            if ($request->has('schools') && !empty($request->schools)) {
                $course->schools()->sync($request->schools);
            } else {
                $course->schools()->detach();
            }

            $labelData = $request->input('label');
            if (label_request_has_content($labelData)) {
                $createLabels = [
                    'overview' => $labelData['overview'] ?? null,
                    'offered_by' => $labelData['offered_by'] ?? null,
                    'head_office' => $labelData['head_office'] ?? null,
                    'members' => $labelData['members'] ?? null,
                    'founded_in' => $labelData['founded_in'] ?? null,
                    'vission_mission' => $labelData['vission_mission'] ?? null
                ];

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_overview_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "banner_section") {

            $course = Course::withTrashed()->findOrFail($id);
            $updatedData = [
                'short_description' => $request->short_description,
                'banner_section' => $request->banner_section
            ];

            $course_benefits_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [
                    'banner_title' => $labelData['banner_title'] ?? null,
                    'banner_sub_title' => $labelData['banner_sub_title'] ?? null,
                    'banner_sub_title_placement' => $labelData['banner_sub_title_placement'] ?? null,
                    'banner_breadcrumb' => $labelData['banner_breadcrumb'] ?? null,
                    'banner_button_1_text' => $labelData['banner_button_1_text'] ?? null,
                    'banner_button_1_url' => $labelData['banner_button_1_url'] ?? null,
                    'banner_button_2_text' => $labelData['banner_button_2_text'] ?? null,
                    'banner_button_2_url' => $labelData['banner_button_2_url'] ?? null,
                ];

                if ($request->hasFile('label.banner_img_file')) {
                    $file = $request->file('label.banner_img_file');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['banner_image'] = '/admin/courses/' . $fileName;
                } elseif (!empty($request->input('label.banner_img_path'))) {
                    $createLabels['banner_image'] = $labelData['banner_img_path'];
                }

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_benefits_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "benefits_section") {

            $course = Course::withTrashed()->findOrFail($id);
            $updatedData = [
                'benifits' => $request->benifits,
                'benefits_section' => $request->benefits_section
            ];

            $course_benefits_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [
                    'what_you_earn' => $labelData['what_you_earn'] ?? null,
                    'what_you_earn_des' => $labelData['what_you_earn_des'] ?? null,
                ];

                if ($request->hasFile('label.what_you_earn_img')) {
                    $file = $request->file('label.what_you_earn_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['what_you_earn_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['what_you_earn_img_path'])) {
                    $createLabels['what_you_earn_img'] = $labelData['what_you_earn_img_path'];
                }

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_benefits_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "who_can_do_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                'who_can_do' => $request->who_can_do,
                'who_can_do_section' => $request->who_can_do_section
            ];

            $course_who_can_do_section_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [

                    'who_can_do' => $labelData['who_can_do'] ?? null,
                    'who_can_do_subh01' => $labelData['who_can_do_subh01'] ?? null,
                    'who_can_do_subh02' => $labelData['who_can_do_subh02'] ?? null,
                ];

                if ($request->hasFile('label.who_can_do_img')) {
                    $file = $request->file('label.who_can_do_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['who_can_do_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['who_can_do_img_path'])) {
                    $createLabels['who_can_do_img'] = $labelData['who_can_do_img_path'];
                }

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_who_can_do_section_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "eligibility_section") {

            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                'eligibility' => $request->eligibility,
                'eligibility_section' => $request->eligibility_section
            ];

            $course_eligibility_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [

                    'eligibility' => $labelData['eligibility'] ?? null

                ];

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_eligibility_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "learning_methodology_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                //'eligibility' => $request->eligibility,
                'learning_methodology_section' => $request->learning_methodology_section
            ];

            $course_learning_methodology_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [
                    'learning_methodology' => $labelData['learning_methodology'] ?? null,
                    'learning_methodology_overview' => $labelData['learning_methodology_overview'] ?? null,
                    'lectures' => $labelData['lectures'] ?? null,
                    'lectures_des' => $labelData['lectures_des'] ?? null,
                    'practice_session' => $labelData['practice_session'] ?? null,
                    'practice_session_des' => $labelData['practice_session_des'] ?? null,
                    'mock_examination' => $labelData['mock_examination'] ?? null,
                    'mock_examination_description' => $labelData['mock_examination_description'] ?? null,

                ];

                if ($request->hasFile('label.lectures_img')) {
                    $file = $request->file('label.lectures_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['lectures_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['lectures_img_path'])) {
                    $createLabels['lectures_img'] = $labelData['lectures_img_path'];
                }

                if ($request->hasFile('label.practice_session_img')) {
                    $file = $request->file('label.practice_session_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['practice_session_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['practice_session_img_path'])) {
                    $createLabels['practice_session_img'] = $labelData['practice_session_img_path'];
                }

                if ($request->hasFile('label.mock_examination_img')) {
                    $file = $request->file('label.mock_examination_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['mock_examination_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['mock_examination_img_path'])) {
                    $createLabels['mock_examination_img'] = $labelData['mock_examination_img_path'];
                }

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_learning_methodology_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "performance_standard_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                'performance_standard_heading' => $request->performance_standard_heading,
                'performance_standard_description' => $request->performance_standard_description,
                'performance_standard_section' => $request->performance_standard_section
            ];

            $course_performance_standard_section_updated = $course->update($updatedData);

            if ($course_performance_standard_section_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "career_path_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                //'eligibility' => $request->eligibility,
                'career_path_section' => $request->career_path_section,
                'career_path' => $request->career_path,
            ];

            $course_career_path_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [

                    'career_path_heading' => $labelData['career_path_heading'] ?? null

                ];

                if ($request->hasFile('label.career_path_section_img')) {
                    $file = $request->file('label.career_path_section_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['career_path_section_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['career_path_section_img_path'])) {
                    $createLabels['career_path_section_img'] = $labelData['career_path_section_img_path'];
                }

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_career_path_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "exam_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                //'eligibility' => $request->eligibility,
                'exam_section' => $request->exam_section,
                'exam_dates' => $request->exam_dates,
                'exam_reg_deadline' => $request->exam_reg_deadline,
                'exam_passing_criteria' => $request->exam_passing_criteria,
                'exam_location_paragraph' => $request->exam_location_paragraph,
                'exam_location' => $request->exam_location,
                'course_exam_format_duration_overview' => $request->course_exam_format_duration_overview,
                'exam_info_custom_01' => $request->exam_info_custom_01,
            ];

            $course_career_path_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [

                    'exam_information' => $labelData['exam_information'] ?? null,
                    'exam_format_duration' => $labelData['exam_format_duration'] ?? null,
                    'exam_format_duration_01' => $labelData['exam_format_duration_01'] ?? null,
                    'exam_format_duration_02' => $labelData['exam_format_duration_02'] ?? null,
                    'exam_format_duration_03' => $labelData['exam_format_duration_03'] ?? null,
                    'exam_dates' => $labelData['exam_dates'] ?? null,
                    'exam_dates_registration' => $labelData['exam_dates_registration'] ?? null,
                    'passing_criteria' => $labelData['passing_criteria'] ?? null,
                    'exam_location' => $labelData['exam_location'] ?? null,
                    'exam_information_section_video_url' => $labelData['exam_information_section_video_url'] ?? null,

                ];

                if ($request->hasFile('label.exam_information_section_img')) {
                    $file = $request->file('label.exam_information_section_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['exam_information_section_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['exam_information_section_img_path'])) {
                    $createLabels['exam_information_section_img'] = $labelData['exam_information_section_img_path'];
                }

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_career_path_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "success_stories_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                //'eligibility' => $request->eligibility,
                'success_stories' => $request->success_stories_section,
                'alumni_benefits_description' => $request->alumni_benefits_description

            ];

            $course_career_path_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [

                    'success_stories' => $labelData['success_stories'] ?? null,
                    'success_stories_link' => $labelData['success_stories_link'] ?? null,
                    'success_stories_link_text' => $labelData['success_stories_link_text'] ?? null,
                    'alumni_benefits' => $labelData['alumni_benefits'] ?? null,

                ];

                if ($request->hasFile('label.learner_stories_img')) {
                    $file = $request->file('label.learner_stories_img');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $slug = Str::slug($originalName) . '-' . time();
                    $fileName = $slug . '.' . $extension;
                    $destinationPath = public_path('admin/courses');
                    $file->move($destinationPath, $fileName);
                    $createLabels['learner_stories_img'] = '/admin/courses/' . $fileName;
                } elseif (!empty($labelData['learner_stories_img_path'])) {
                    $createLabels['learner_stories_img'] = $labelData['learner_stories_img_path'];
                }

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_career_path_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "contact_us_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                //'eligibility' => $request->eligibility,
                'contact_us_section' => $request->contact_us_section,
                'contact_us_text' => $request->contact_us_text,
                'reg_iframe' => $request->reg_iframe,

            ];

            $course_contact_us_updated = $course->update($updatedData);

            if ($course_contact_us_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "custom_section_01") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $updatedData = [
                //'eligibility' => $request->eligibility,
                'custom_section_01' => $request->custom_section_01,
                'custom_section_01_description' => $request->custom_section_01_description

            ];

            $course_career_path_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [

                    'custom_section_01' => $labelData['custom_section_01'] ?? null,
                ];

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_career_path_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        } elseif ($courseModule == "custom_videos_section") {
            //return $request;
            $course = Course::withTrashed()->findOrFail($id);

            $custom_videos = [];

            if ($request->has('custom_videos')) {
                foreach ($request->custom_videos as $index => $video) {
                    // Validate and sanitize the input URL
                    $videoURL = filter_var($video['path'], FILTER_VALIDATE_URL) ? $video['path'] : null;

                    // If no valid URL is provided, skip adding this entry
                    if ($videoURL) {
                        // Add the video data with the provided URL
                        $custom_videos[] = [
                            'title' => $video['title'],
                            'path' => $videoURL,
                        ];
                    }
                }
            }

            // Prepare the updated data
            $updatedData = [
                'custom_videos_section' => $request->custom_videos_section,
                'custom_videos_desc' => $request->custom_videos_desc,
                'custom_videos' => json_encode($custom_videos),  // Encode the array as JSON
            ];

            // Update the course
            $course_custom_video_updated = $course->update($updatedData);

            $labelData = $request->input('label');
            //return $labelData['what_you_earn_img'];
            if (label_request_has_content($labelData)) {
                //return 'filled';
                $createLabels = [

                    'custom_videos_heading' => $labelData['custom_videos_heading'] ?? null,
                ];

                $dynamicLabel = $course->dynamicLabel()->first();
                $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
                if ($dynamicLabel) {
                    $dynamicLabel->update($createLabels);
                } else {
                    $assignLabels = $course->dynamicLabel()->create($createLabels);
                }
            }

            if ($course_custom_video_updated) {
                session()->flash('sucess', 'Record added successfullly!');
            } else {
                session()->flash('failed', 'Failed to add Record!');
            }

            $this->persistCourseModuleImageAlts($course, $request);

            return redirect()->route('course.edit', ['id' => $id]);
        }
    }

    public function deleteTestimonial(Request $request, string $id)
    {
        //return $id;
        $testimonial = CourseTestimonial::withTrashed()->findOrFail($id);
        $result = $testimonial->forceDelete();

        if ($result) {
            session()->flash('sucess', 'Record deleted successfullly!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }

        return redirect()->route('testimonial.index');
    }

    public function updateTestimonialStatus(Request $request, $id)
    {
        $courseTestimonial = CourseTestimonial::withTrashed()->findOrFail($id);

        try {
            $courseTestimonial->update(['status' => $request->status]);
            return response()->json(['success' => 'Testimonial status updated successfully!'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating Testimonial status. Please try again.'], 500);
        }
    }

    public function updateTestimonialPriority(Request $request, $id)
    {
        //return $request->status;
        $CourseTestimonial = CourseTestimonial::withTrashed()->findOrFail($id);

        try {
            // if ($request->status == 'disable') {
            //     $CourseTestimonial->delete(); // Soft delete
            // } elseif ($request->status == 'active') {
            //     $CourseTestimonial->restore(); // Restore
            // }
            $CourseTestimonial->priority = $request->status;
            $CourseTestimonial->save();
            return response()->json(['success' => 'Testimonial status updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating Testimonial status. Please try again.'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $course = Course::withTrashed()->findOrFail($id);

        try {
            if ($request->status == 'disable') {
                $course->delete(); // Soft delete
                record_panel_activity(
                    'Course Disabled',
                    $course->title ?: 'Course #' . $course->id,
                    route('course.edit', $course->id),
                    $request
                );
            } elseif ($request->status == 'active') {
                $course->restore(); // Restore
                record_panel_activity(
                    'Course Enabled',
                    $course->title ?: 'Course #' . $course->id,
                    route('course.edit', $course->id),
                    $request
                );
            }
            return response()->json(['success' => 'Course status updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating course status. Please try again.'], 500);
        }
    }

    public function updateStatusLecturePlan(Request $request, $id)
    {
        $course = Course::withTrashed()->findOrFail($id);

        try {
            if ($request->status == '1') {

                $course->lecture_plan_section = 1;
            } elseif ($request->status == '0') {

                $course->lecture_plan_section = 0;
            }
            $course->save();
            return response()->json(['success' => 'Lecture Plan status updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating course status. Please try again.'], 500);
        }
    }



    function listCourse()
    {
        $data['courses'] = Course::all(['id', 'title', 'slug']);
        $data['categories'] = Category::get();
        return view('admin.course.list-course')->with($data);
    }

    function listCourseInCategoryNew(Request $request)
    {
        //return $request;
        $course = Course::withTrashed()->findOrFail($request->course_id);
        $categories_courses_updated = $course->categories()->sync($request->categories);
        if ($categories_courses_updated) {
            session()->flash('sucess', 'Record Added successfullly!');
            return redirect()->route('course.list-course');
        } else {
            session()->flash('sucess', 'Failed to insert Record!');
            return redirect()->route('course.list-course');
        }
    }


    function CourseStructure($id)
    {

        $data['course'] = Course::withTrashed()->select('id', 'title', 'slug', 'course_structure_overview', 'lecture_plan_section')
            ->with('courseStructures.subHeadings.subHeadingsUnits', 'dynamicLabel:id,course_id,lecture_plan')
            ->where('id', $id)
            ->first();

        return view('admin.course.course-structure.index')->with($data);
    }

    function storeCourseStructurePart(Request $request, $id)
    {
        // return $request;
        // $validator = Validator::make($request->all(), [
        //     'course.heading' => 'required',
        // ]);

        // if ($validator->fails()) {
        //     return redirect()->back()
        //     ->withErrors($validator)
        //     ->withInput();
        // }
        $course = Course::withTrashed()->findOrFail($id);
        $courseStructureInserted = $course->courseStructures()->create([
            'title' => $request->course['title'] ?? NULL,
            'heading' => $request->course['heading'],
            'exam_format' => $request->course['exam_format'] ?? NULL,
            'exam_duration' => $request->course['exam_duration'] ?? NULL,
        ]);

        // Create the related Subheadings
        // foreach ($request->course['subheading'] as $subheadingData) {
        if (!is_null($request->course['subheading']['0']['subheading'])) {
            foreach ($request->course['subheading'] as $index => $subheadingData) {
                $subheading = $courseStructureInserted->subHeadings()->create([
                    'sub_heading' => $subheadingData['subheading'],
                ]);

                if ($subheading) {
                    foreach ($subheadingData['unit'] as $key => $unitData) {
                        if (!is_null($unitData['title'])) {
                            $thumbnail = null;

                            if (isset($unitData['thumbnail']) && $unitData['thumbnail'] instanceof \Illuminate\Http\UploadedFile) {
                                //return 'test';
                                $file = $unitData['thumbnail'];
                                $fileName = $file->getClientOriginalName();
                                $destinationPath = public_path('admin/courses');
                                $file->move($destinationPath, $fileName);
                                $thumbnail = '/admin/courses/' . $fileName;
                            }
                            // else{
                            //     $thumbnail = '/admin/courses/course-gen.png';
                            // }

                            $subheading->subHeadingsUnits()->create([
                                'unit_title' => $unitData['title'],
                                'unit_video' => $unitData['video'] ?? NULL,
                                'thumbnail' => $thumbnail,
                            ]);
                        }
                    }
                }
            }
        }

        if ($courseStructureInserted) {
            session()->flash('sucess', 'Record added successfullly!');
            return redirect()->route('course.course-structure', ['id' => $id]);
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }
    }

    function EditCourseStructurePart($id, $part_id)
    {

        //return $id;
        $course_id = $id;
        $part_id = $part_id;
        $data['course'] = Course::withTrashed()->select('id', 'title', 'slug')
            ->with([
                'courseStructures' => function ($query) use ($part_id) {
                    $query->where('id', $part_id)->with('subHeadings.subHeadingsUnits');
                }
            ])
            ->where('id', $course_id)
            ->first();

        return view('admin.course.course-structure.edit')->with($data);
    }

    function updateCourseStructurePart(Request $request)
    {
        //return $request;
        $validator = Validator::make($request->all(), [
            'course.heading' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $course = Course::withTrashed()->findOrFail($request->course_id);
        $courseStructure = $course->courseStructures()->find($request->course['course_structures_id']);

        if ($courseStructure) {
            $courseStructureUpdated = $courseStructure->update([
                'title' => $request->course['title'] ?? null,
                'heading' => $request->course['heading'],
                'exam_format' => $request->course['exam_format'] ?? null,
                'exam_duration' => $request->course['exam_duration'] ?? null,
                'exam_format' => $request->course['exam_format'] ?? null,
                'overview' => $request->course['overview'] ?? null,
            ]);

            // Update the related Subheadings
            $existingSubheadingIds = $courseStructure->subHeadings->pluck('id')->toArray();
            $incomingSubheadingIds = array_column($request->course['subheading'], 'id');

            // Delete subheadings that are not in the request
            $subheadingsToDelete = array_diff($existingSubheadingIds, $incomingSubheadingIds);
            if (!empty($subheadingsToDelete)) {
                $courseStructure->subHeadings()->whereIn('id', $subheadingsToDelete)->delete();
            }

            foreach ($request->course['subheading'] as $subheadingData) {
                // Ensure sub_heading is not null
                $subHeadingTitle = $subheadingData['subheading'] ?? 'Default Sub Heading Title';

                $subheading = $courseStructure->subHeadings()->updateOrCreate(
                    ['id' => $subheadingData['id'] ?? null],
                    ['sub_heading' => $subHeadingTitle]
                );

                if (isset($subheadingData['unit']) && is_array($subheadingData['unit'])) {
                    $existingUnitIds = $subheading->subHeadingsUnits->pluck('id')->toArray();
                    $incomingUnitIds = array_column($subheadingData['unit'], 'id');

                    // Delete units that are not in the request
                    $unitsToDelete = array_diff($existingUnitIds, $incomingUnitIds);
                    if (!empty($unitsToDelete)) {
                        $subheading->subHeadingsUnits()->whereIn('id', $unitsToDelete)->delete();
                    }

                    foreach ($subheadingData['unit'] as $unitData) {
                        //return $unitData['thumbnail'];
                        if (!is_null($unitData['title'])) {

                            $thumbnail = null;

                            if (isset($unitData['thumbnail']) && $unitData['thumbnail'] instanceof \Illuminate\Http\UploadedFile) {
                                // return 'thumbnail';
                                $file = $unitData['thumbnail'];
                                $fileName = $file->getClientOriginalName();
                                $destinationPath = public_path('admin/courses');
                                $file->move($destinationPath, $fileName);
                                $thumbnail = '/admin/courses/' . $fileName;
                            } else {
                                $thumbnail = $unitData['existing_thumbnail'] ?? null;
                            }

                            $subheading->subHeadingsUnits()->updateOrCreate(
                                ['id' => $unitData['id'] ?? null],
                                [
                                    'unit_title' => $unitData['title'] ?? null,
                                    'thumbnail' => $thumbnail,
                                    'unit_video' => $unitData['video'] ?? null,
                                ]
                            );

                        }
                    }
                }
            }
            // session()->flash('success', 'Record updated successfully!');
            // return redirect()->route('course.course-structure', ['id' => $request->course_id]);
            if ($courseStructureUpdated) {
                session()->flash('success', 'Record updated successfully!');
                // {{ route('course.edit-course-structure-part', ['id' => $course->id, 'part_id' => $courseStructure->id]) }}
                return redirect()->route('course.course-structure', ['id' => $request->course_id]);
            }
        }
        // else {
        //     // Handle the case where the course structure is not found
        //     session()->flash('failed', 'Failed to update Record!');
        //     return redirect()->route('course.course-structure', ['id' => $request->course_id]);
        // }

    }


    public function ckeditorImageUpload(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'upload' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Check if the file is uploaded
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses/editor');
            $file->move($destinationPath, $fileName);
            $image = '/admin/courses/editor/' . $fileName;

            // Return a JSON response with the URL of the uploaded image
            return response()->json([
                'url' => asset($image)
            ]);
        }

        // Return an error response if the file is not uploaded
        return response()->json(['error' => 'File not uploaded'], 400);
    }







    function courseFee($id)
    {
        $data['course'] = Course::withTrashed()->with('courseFeePackages')->where('id', $id)->first();
        $data['currencies'] = Currency::get();
        return view('admin.course.fee.index')->with($data);
    }

    function courseCreateFee($id)
    {
        $data['course'] = Course::withTrashed()->findOrFail($id);
        $data['currencies'] = Currency::get();
        return view('admin.course.fee.create')->with($data);
    }

    public function courseEditFee($id)
    {
        $data['course_fee'] = CourseFee::findOrFail($id);
        $data['course'] = Course::withTrashed()->findOrFail($data['course_fee']->courses_id);
        $data['currencies'] = Currency::get();
        return view('admin.course.fee.edit')->with($data);
    }

    public function courseUpdateFee(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'package_name' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
            'price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'short_description' => 'nullable|string|max:1000',
            'key_point' => 'nullable|string|max:255',
            'package_includes' => 'nullable|string|max:255',
            'priority' => 'required|integer|min:1',
            'Installments' => 'nullable|in:1',
            'showonwebsite' => 'nullable|in:1',
            'package_feature' => 'nullable|array',
            'package_feature.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        if (!empty($validated['package_feature'])) {
            $validated['package_feature'] = array_filter($validated['package_feature']);

            if (empty($validated['package_feature'])) {
                $validated['package_feature'] = null;
            }
        } else {
            $validated['package_feature'] = null;
        }

        $courseFee = CourseFee::findOrFail($id);
        if ($courseFee->package_feature && (!isset($validated['package_feature']) || empty($validated['package_feature']))) {
            $validated['package_feature'] = null;
        }
        $updateRecord = $courseFee->update($validated);

        if ($updateRecord) {
            session()->flash('success', 'Record updated successfully!');
        } else {
            session()->flash('warning', 'Failed to update record!');
        }

        return redirect()->route('course.fee', ['id' => $courseFee->courses_id]);
    }

    function courseStoreFee(Request $request, $id)
    {
        // return $request;
        $validator = Validator::make($request->all(), [
            'package_name' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
            'price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'short_description' => 'nullable|string|max:1000',
            'key_point' => 'nullable|string|max:255',
            'package_includes' => 'nullable|string|max:255',
            'priority' => 'required|integer|min:1',
            'Installments' => 'nullable|in:1',
            'showonwebsite' => 'nullable|in:1',
            'package_feature' => 'nullable|array',
            'package_feature.*' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        if (!empty($validated['package_feature'])) {
            $validated['package_feature'] = array_filter($validated['package_feature']);

            if (empty($validated['package_feature'])) {
                $validated['package_feature'] = null;
            }
        } else {
            $validated['package_feature'] = null;
        }

        $validated['courses_id'] = $id;
        $saveRecord = CourseFee::create($validated);

        if ($saveRecord) {
            session()->flash('success', 'Record added successfully!');
        } else {
            session()->flash('warning', 'Failed to add record!');
        }
        return redirect()->route('course.fee', ['id' => $id]);

    }

    public function courseDeleteFee($id)
    {
        //return $id;
        $courseFee = CourseFee::findOrFail($id);
        $courseFee->delete();
        return redirect()->back()->with('success', 'Course fee deleted successfully.');
    }

    public function updateFeeStatus(Request $request, $id)
    {

        $course = Course::withTrashed()->findOrFail($id);
        $course->fee_visibility = $request->fee_visibility;
        $courseUpdated = $course->save();

        $labelData = $request->input('label');
        //return $labelData['what_you_earn_img'];
        if (label_request_has_content($labelData)) {
            //return 'filled';
            $createLabels = [
                'fee_strucutre' => $labelData['fee_strucutre'] ?? null,
            ];
            $dynamicLabel = $course->dynamicLabel()->first();
            $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
            if ($dynamicLabel) {
                $dynamicLabel->update($createLabels);
            } else {
                $assignLabels = $course->dynamicLabel()->create($createLabels);
            }
        }
        if ($courseUpdated) {
            session()->flash('success', 'Record updated successfully!');
        } else {
            session()->flash('warning', 'Failed to updated record!');
        }
        return redirect()->route('course.fee', ['id' => $id]);
    }


    function addFeeStructure($id)
    {
        $data['course'] = Course::withTrashed()->findOrFail($id);
        return view('admin.course.add-fee-structure')->with($data);
    }


    function storeFeeStructure(Request $request)
    {
        //return $request;

        // Validate the request data
        $validatedData = $request->validate([
            'packages.*.package_name' => 'required|string',
        ]);

        // Find the course by ID
        $course = Course::withTrashed()->findOrFail($request->course_id);
        // Prepare the array of fee packages
        $feePackages = [];
        foreach ($request->package as $package) {
            $price = $package['price'] ?? 0;
            $discountPercentage = $package['discount'] ?? 0;

            // Calculate discounted price
            $discountedPrice = $price - ($price * ($discountPercentage / 100));

            $feePackages[] = [
                'package_name' => $package['name'],
                'price' => $price,
                'discount_percentage' => $discountPercentage,
                'discounted_price' => $discountedPrice,
                'is_recommended' => $package['is_recommended'] ?? false,
            ];
        }

        // Insert the fee packages into the relationship
        $saveResult = $course->feePackages()->createMany($feePackages);

        if ($saveResult) {
            session()->flash('sucess', 'Record added successfullly!');
            return redirect()->route('course.show-fee-str', ['id' => $request->course_id]);
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }

    }

    function storeFeeFeatures(Request $request)
    {
        //return $request;
        //return $request->fee_features['0']['course_access'];
        // Validate the request data
        // $validatedData = $request->validate([
        //     'fee_features.*' => 'required|string',
        // ]);

        $data['course_package'] = Course::withTrashed()->select('id', 'title', 'slug')
            //->with('feePackages')
            ->where('id', $request->course_id)
            ->first();


        foreach ($data['course_package']->feePackages as $key => $feePackageData) {
            //echo $feePackageData['package_name'] .'<br>';
            $saveResult = FeePackagesFeatures::create([

                'fee_packages_id' => $feePackageData['id'],
                'course_access' => $request->fee_features[$key]['course_access'],
                'payment_option' => $request->fee_features[$key]['payment_option'],
                'pass_guarantee' => $request->fee_features[$key]['pass_guarantee'],
                'exam_day_ready_features' => $request->fee_features[$key]['exam_day_ready_features'],
                'unmatched_resources_and_tools' => $request->fee_features[$key]['unmatched_resources_and_tools'],

            ]);

        }

        if ($saveResult) {
            session()->flash('sucess', 'Record added successfullly!');
            return redirect()->route('admin.courses');
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }

    }

    function showFeeStr($id)
    {
        $data['fee_structure'] = $course = Course::withTrashed()->select('id', 'title', 'slug')
            ->with('feePackages')
            ->where('id', $id)
            ->first();

        return view('admin.course.show-fee-structure')->with($data);

    }

    function addcourseObjectives(Request $request)
    {
        //return $request;
        $course = Course::withTrashed()->findorFail($request->course_id);

        $overviews = collect($request->description)->map(function ($key_concept) {
            return new CourseObjective(['description' => $key_concept]);
        });
        $course->courseObjectivePoints()->saveMany($overviews);

        return redirect()->route('course.show', ['id' => $request->course_id]);
    }

    function addcourseEarning(Request $request)
    {
        //return $request;
        $course = Course::withTrashed()->findorFail($request->course_id);
        if ($request->hasFile('image')) {

            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $path = '/admin/courses/' . $fileName;

            $course->courseRewards()->create([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'conditon' => $request->input('conditon'),
                'image' => $path,
            ]);
        }

        return redirect()->route('course.show', ['id' => $request->course_id]);
    }

    public function addCourseFaq(Request $request)
    {
        //return $request;
        $course = Course::withTrashed()->findorFail($request->course_id);
        $course->courseFaq()->create([
            'title' => $request->input('title'),
            'short_description' => $request->input('description'),

        ]);
        return redirect()->route('course.show', ['id' => $request->course_id]);
    }

    function addcourseBeneficiary(Request $request)
    {
        //return $request;
        $course = Course::withTrashed()->findorFail($request->course_id);
        $course->courseBeneficiaries()->create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ]);
        return redirect()->route('course.show', ['id' => $request->course_id]);

    }

    public function addEnrollmentModule(Request $request)
    {

        $course = Course::withTrashed()->findorFail($request->course_id);
        //foreach ($request->input('enrollment', []) as $index => $item) {
        if ($request->hasFile("brochure")) {

            $file = $request->file("brochure");
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('admin/courses');
            $file->move($destinationPath, $fileName);
            $path = '/admin/courses/' . $fileName;
        } else {
            $path = 'courses/1708672161.jpg';
        }
        $course->courseEnrollments()->create([
            //'course_id' => $request->course_id,
            'starting_date' => $request->input('starting_date'),
            'application_deadline' => $request->input('deadline'),
            'discount' => $request->input('early_bird_discount'),
            'brochure' => $path
        ]);
        //}
        return redirect()->route('course.show', ['id' => $request->course_id]);
    }

    public function addCourseSyllabus(Request $request)
    {
        //return $request;
        $course = Course::findorFail($request->course_id);

        $courseSyllabus = $course->courseSyllabus()->create([
            //'course_id' => $request->course_id,
            'title' => $request->input('title'),
            'subtitle' => $request->input('subtitle'),
            'description' => $request->input('description'),
            'duration' => $request->input('duration'),
            'duration_unit' => $request->input('duration_unit'),
            'higlights' => json_encode($request->featuredPoints),
        ]);
        // $courseSyllabus = new CourseSyllabus;
        // $courseSyllabus->course_id = $request->course_id;
        // $courseSyllabus->title = $request->moduleName;
        // $courseSyllabus->subtitle = $request->subtitle;
        // $courseSyllabus->description = $request->moduleDescription;
        // $courseSyllabus->duration = $request->moduleDuration;
        // $courseSyllabus->duration_unit = $request->moduleDurationUnit;
        // $courseSyllabus->concepts = json_encode($request->concept);
        // $courseSyllabus->featured_exercise = json_encode($request->exerciseName);
        // $courseSyllabus->save();



        foreach ($request->input('highlights', []) as $index => $item) {
            //return 't';
            if ($request->hasFile("highlights.$index.h-image")) {

                // $image = $request->file("highlights.$index.h-image");
                // $filename = time().'.'.$image->getClientOriginalExtension();
                // $path = $image->storeAs('course-module', $filename, 'public');
                $file = $request->file("highlights.$index.h-image");
                $fileName = $file->getClientOriginalName();
                $destinationPath = public_path('admin/courses');
                $file->move($destinationPath, $fileName);
                $path = '/admin/courses/' . $fileName;

            } else {
                //return 'else';
                $path = 'courses/1708672161.jpg';
            }
            //return 'zzz';
            $courseSyllabus->courseSyllabusHighlights()->create([
                'title' => $item['title'],
                'image' => $path,
            ]);
        }

        return redirect()->route('course.show', ['id' => $request->course_id]);

    }

    function deleteCourseModule(Request $request)
    {
        //return $request;
        $module = $request->input('module');
        $id = $request->input('id');
        switch ($module) {
            case 'courseObjectivePoints':
                $CourseObjective = CourseObjective::findOrFail($id);
                $CourseObjective->delete();
                break;
            case 'courseSyllabus':
                $CourseSyllabus = CourseSyllabus::findOrFail($id);
                $CourseSyllabus->delete();
                break;
            case 'courseEnrollments':
                $CourseEnrollment = CourseEnrollment::findOrFail($id);
                $CourseEnrollment->delete();
                break;
            case 'courseBeneficiaries':
                $CourseBeneficiaries = CourseBeneficiaries::findOrFail($id);
                $CourseBeneficiaries->delete();
                break;
            case 'courseRewards':
                $CourseReward = CourseReward::findOrFail($id);
                $CourseReward->delete();
                break;
            case 'courseFaq':

                $courseFaq = courseFaq::findOrFail($id);
                $courseFaq->delete();
                break;
            default:
                return response()->json(['error' => 'Failed to Delete Record']);
        }

        return response()->json(['message' => 'Record deleted successfully']);
    }

    function delete(string $id)
    {
        $course = Course::withTrashed()->where('id', $id)->firstOrFail();
        $courseLabel = $course->title ?: 'Course #' . $course->id;
        $wasDisabled = $course->trashed();

        $course_del = $course->forceDelete();
        if ($course_del) {
            record_panel_activity(
                'Course Deleted',
                $courseLabel . ($wasDisabled ? ' (was disabled)' : ''),
                route('admin.courses'),
                request()
            );
            session()->flash('success', 'Record deleted successfully!');
            return redirect()->route('admin.courses');
        } else {
            session()->flash('success', 'Failed to delete record!');
            return redirect()->route('admin.courses');
        }

    }

    function courseObjectiveUpdate(Request $request)
    {
        //return $request;
        $course = Course::find($request->courseId);
        $objectiveId = $request->id;
        // The ID of the objective you want to update
        if ($course) {
            $course->courseObjectivePoints()->where('id', $objectiveId)->update([
                'description' => $request->description,
            ]);
            return response()->json(['message' => 'Record updated successfully!']);
        } else {
            return response()->json(['message' => 'Failed to Update record']);
        }
    }

    // function courseFAQUpdate(Request $request){
    //     //return $request;
    //     $course = Course::find($request->courseId);
    //     $FAQid = $request->id; 
    //     // The ID of the objective you want to update
    //     if ($course) {
    //         $course->courseFaq()->where('id', $FAQid)->update([
    //             'title' => $request->title, 
    //             'short_description' => $request->shortDescription, 
    //         ]);
    //         return response()->json(['message' => 'Record updated successfully!']);
    //     } else {
    //         return response()->json(['message' => 'Failed to Update record']);
    //     }
    // }

    function showCourseFAQs($id)
    {
        //return $id;
        $data['course'] = Course::withTrashed()->select('id', 'title', 'slug', 'faq_section')
            ->with('courseFaq', 'dynamicLabel:id,course_id,faq_heading')
            ->where('id', $id)
            ->first();

        return view('admin.course.faq.index')->with($data);

    }

    function addCourseFAQS($id)
    {

        $data['course'] = Course::withTrashed()->select('id', 'title')->findOrFail($id);
        return view('admin.course.add-faqs')->with($data);
    }

    function storeCourseFAQS(Request $request)
    {
        // return $request;
        $course = Course::withTrashed()->findorFail($request->course_id);
        $saveResult = $course->courseFaq()->create([
            'title' => $request->input('title'),
            'short_description' => $request->input('description'),

        ]);
        if ($saveResult) {
            session()->flash('sucess', 'Record added successfullly!');
            return redirect()->route('course.show-faqs', ['id' => $request->course_id]);
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }
    }

    function editCourseFAQ($course_id, $faq_id)
    {

        //return $faq_id;
        $data['course'] = Course::withTrashed()->findorFail($course_id);
        $data['course_faq'] = CourseFaq::findOrFail($faq_id);
        return view('admin.course.faq.edit')->with($data);

    }

    function updateCourseFAQ(Request $request, $course_id, $faq_id)
    {
        //return $id;
        $course_faq = CourseFaq::withTrashed()->findOrFail($faq_id);
        $data = $request->except('_token');

        $course_faq_updated = $course_faq->update($data);

        if ($course_faq_updated) {
            session()->flash('sucess', 'Record Added successfullly!');
            return redirect()->route('course.show-faqs', ['id' => $course_id]);
        } else {
            session()->flash('sucess', 'Failed to insert Record!');
            return redirect()->route('course.show-faqs', ['id' => $course_id]);
        }

    }



    function delCourseStructure($course_id, $id)
    {

        $course = CourseStructure::findOrFail($id);
        $recordDeleted = $course->delete();
        if ($recordDeleted) {
            session()->flash('sucess', 'Record deleted successfullly!');
            //return redirect()->back();
        } else {
            session()->flash('failed', 'Failed to delete Record!');
        }
        return redirect()->route('course.course-structure', ['id' => $course_id]);
    }


    function addCourseStructureOverview(Request $request, $id)
    {
        //return $request;
        $course = Course::withTrashed()->findOrFail($id);
        $course->course_structure_overview = $request->course_structure_overview;
        $course->lecture_plan_section = $request->lecture_plan_section;
        $courseUpdated = $course->save();

        $labelData = $request->input('label');
        //return $labelData['what_you_earn_img'];
        if (label_request_has_content($labelData)) {
            //return 'filled';
            $createLabels = [
                'lecture_plan' => $labelData['lecture_plan'] ?? null,
            ];
            $dynamicLabel = $course->dynamicLabel()->first();
            $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
            if ($dynamicLabel) {
                $dynamicLabel->update($createLabels);
            } else {
                $assignLabels = $course->dynamicLabel()->create($createLabels);
            }
        }
        if ($courseUpdated) {
            session()->flash('success', 'Record updated successfully!');
        } else {
            session()->flash('warning', 'Failed to updated record!');
        }
        return redirect()->route('course.course-structure', ['id' => $id]);
    }

    function relatedCourses($id)
    {
        $data['course'] = Course::withTrashed()->select('id', 'title', 'slug', 'related_courses_section')->with('dynamicLabel:id,course_id,related_courses')->findOrFail($id);
        $data['related_courses'] = Course::select('id', 'title', 'slug')->where('id', '!=', $id)->get();
        return view('admin.course.related-course')->with($data);
    }

    function assignRelatedCoursesToCourse(Request $request)
    {
        //return $request;
        $course = Course::withTrashed()->findOrFail($request->course_id);
        $selectedRelatedCourses = $request->input('related_courses', []);
        $courses_assigned = $course->relatedCourses()->sync($selectedRelatedCourses);

        // Check if courses were assigned and set flash messages accordingly
        if ($courses_assigned) {
            session()->flash('success', 'Courses assigned successfully!');
        } else {
            session()->flash('warning', 'Failed to assign courses');
        }
        return redirect()->route('admin.courses');
    }

    function relatedCoursesSectionUpdate(Request $request, $id)
    {
        //return $id;

        $course = Course::withTrashed()->findOrFail($id);

        $updatedData = [
            'related_courses_section' => $request->related_courses_section,
        ];

        $course_related_updated = $course->update($updatedData);

        $labelData = $request->input('label');
        //return $labelData['what_you_earn_img'];
        if (label_request_has_content($labelData)) {
            //return 'filled';
            $createLabels = [
                'related_courses' => $labelData['related_courses'] ?? null,
            ];

            $dynamicLabel = $course->dynamicLabel()->first();
            $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
            if ($dynamicLabel) {
                $dynamicLabel->update($createLabels);
            } else {
                $assignLabels = $course->dynamicLabel()->create($createLabels);
            }
        }

        if ($course_related_updated) {
            session()->flash('sucess', 'Record added successfullly!');
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }

        return redirect()->route('course.related-courses', ['id' => $id]);
    }

    function faqSectionUpdate(Request $request, $id)
    {
        //return $request;

        $course = Course::withTrashed()->findOrFail($id);

        $updatedData = [
            'faq_section' => $request->faq_section,
        ];

        $course_related_updated = $course->update($updatedData);

        $labelData = $request->input('label');
        //return $labelData['what_you_earn_img'];
        if (label_request_has_content($labelData)) {
            //return 'filled';
            $createLabels = [
                'faq_heading' => $labelData['faq_heading'] ?? null,
            ];

            $dynamicLabel = $course->dynamicLabel()->first();
            $createLabels = $this->withLabelImageAlts($createLabels, $request, $dynamicLabel);
            if ($dynamicLabel) {
                $dynamicLabel->update($createLabels);
            } else {
                $assignLabels = $course->dynamicLabel()->create($createLabels);
            }
        }

        if ($course_related_updated) {
            session()->flash('sucess', 'Record added successfullly!');
        } else {
            session()->flash('failed', 'Failed to add Record!');
        }

        return redirect()->route('course.show-faqs', ['id' => $id]);
    }

    private function withLabelImageAlts(array $createLabels, Request $request, $dynamicLabel): array
    {
        if (! ensure_image_alt_columns_exist()) {
            return $createLabels;
        }

        $labelAlts = request_image_alts($request, 'label.image_alts');

        if (is_array($labelAlts)) {
            $createLabels['image_alts'] = merge_image_alts(
                $dynamicLabel?->image_alts,
                $labelAlts
            );
        }

        return $createLabels;
    }

    private function persistCourseModuleImageAlts(Course $course, Request $request): void
    {
        if (! ensure_image_alt_columns_exist()) {
            session()->flash(
                'warning',
                'Image alt text could not be saved. Ask your host to run: php artisan berkely:ensure-image-alt-columns'
            );

            return;
        }

        $labelAlts = request_image_alts($request, 'label.image_alts');

        if (is_array($labelAlts)) {
            $dynamicLabel = $course->dynamicLabel()->firstOrCreate(['course_id' => $course->id]);

            if (! persist_model_image_alts($dynamicLabel, $labelAlts)) {
                session()->flash(
                    'warning',
                    'Image alt text could not be saved. Run database/sql/add-image-alt-columns.sql or deploy migrations on the server.'
                );
            }
        }

        $courseAlts = request_image_alts($request, 'image_alts');

        if (is_array($courseAlts)) {
            if (! persist_model_image_alts($course, $courseAlts)) {
                session()->flash(
                    'warning',
                    'Image alt text could not be saved. Run database/sql/add-image-alt-columns.sql or deploy migrations on the server.'
                );
            }
        }
    }
}