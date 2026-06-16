<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\CourseTestimonial;
use Illuminate\Support\Facades\Artisan;
use App\Models\{Course, Subject, LearnerStory, Category};
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
//use SEOTools;
class FrontendController extends Controller
{
    function Courses()
    {

        //return $data['subjects'] = Subject::with('courses:id,title,slug,thumbnail')->get();
        // $data['subjects'] = Subject::with(['courses' => function($query) {
        //     $query->select('courses.id','courses.title', 'courses.slug','courses.thumbnail', 'courses.subject_id');
        //         //  ->where('courses.with_subject', true);
        // }])
        // ->orderBy('name', 'ASC')
        // ->get();

        // $data['subjects'] = Subject::with(['courses' => function($query) {
        //     $query->select('courses.id','courses.title', 'courses.slug','courses.thumbnail', 'courses.subject_id');
        //         //  ->where('courses.with_subject', true);
        // }])
        // ->orderBy('priority', 'ASC')
        // ->get();

        //$data['subjects'] = $this->getSubjectsWithCourses();

        $data['categories'] = Category::with('courses:id,title,slug')->get();
        return view('subjects')->with($data);
    }

    function categoryDetails($slug = null)
    {

        $data['subject'] = Category::with([
            'courses' => function ($query) {
                $query->select('courses.id', 'courses.title', 'courses.slug', 'courses.thumbnail', 'courses.subject_id');
                //  ->where('courses.with_subject', true);
            }
        ])
            ->where('slug', $slug)
            //->orderBy('priority')
            ->first();
        $data['title'] = $data['subject']->name;
        return view('category_courses')->with($data);
    }


    // function filterCourses(Request $request){
    //     $subjectIds = $request->subjects;

    //     $query = Course::whereHas('subjects', function ($query) use ($subjectIds) {
    //         $query->whereIn('subject_id', $subjectIds);
    //     });


    //     if ($request->has('Recently_Added')) {
    //         $query->orderBy('id', 'DESC');
    //     }

    //     if ($request->has('keyword')) {

    //         if ($request->has('keyword')) {
    //             $keyword = $request->keyword;
    //             $query->where('title', 'LIKE', '%' . $keyword . '%');
    //         }
    //     }

    //     if ($request->input('additionalFilter') === 'Random') {

    //         $query->inRandomOrder();
    //     } 
    //     // else {
    //     //     if ($request->filled('order')) {
    //     //         [$field, $direction] = explode('_', $request->order);
    //     //         $query->orderBy($field, $direction);
    //     //     } else {
    //     //         // Default ordering if 'order' is not specified
    //     //         $query->orderBy('title', 'asc');
    //     //     }
    //     // }

    //     // Execute the query
    //     $courses = $query->get();

    //     $view = view('_courses_filter', compact('courses'))->render();
    //     return response()->json(['html' => $view]);
    // }

    function courseDetails(Request $request, $slug = null)
    {
        $data['course'] = Course::with([
            'courseStructures.subHeadings.subHeadingsUnits',
            'courseStructuresFirst.subHeadingsFirst',
            'courseEnrollments',
            'courseFaq',
            'courseFeePackages',
            'dynamicLabel',
            'relatedCourses:id,title,slug,short_description,thumbnail'
        ])->where('slug', $slug)->firstOrFail();

        $instructorIds = array_filter(explode(',', $data['course']->instructor_id));

        // Fetch assigned instructor users
        $data['assignIntructors'] = User::whereIn('id', $instructorIds)->get();

        return view('course_detail', $data);
    }

    function optimize()
    {

        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('route:cache');
        //Artisan::call('storage:link');
    }


    function contactUs()
    {
        $data['title'] = 'Enquire';
        return view('contact_us')->with($data);
    }
    function studyAbroad()
    {
        $data['title'] = 'Study Abroad';
        return view('study_abroad')->with($data);
    }
    function generalPolicies()
    {
        $data['title'] = 'General Policy';
        return view('general-policy')->with($data);
    }
    function privacyPolicies()
    {
        $data['title'] = 'Privacy Policy';
        return view('privacy-policy')->with($data);
    }
    function termAndConditions()
    {
        $data['title'] = 'Term and Conditions';
        return view('term-and-condition')->with($data);
    }
    function complaintsAndMisconducts()
    {
        $data['title'] = 'Complaints and Misconducts';
        return view('complaints-and-misconducts')->with($data);
    }
    function admission()
    {
        return redirect()->route('contact');
    }
    function schoolCalender(Request $request)
    {
        // return $request;
        //$data['title'] = 'School Calender';
        $data['categories'] = Category::all();
        return view('school_calender')->with($data);
    }
    // function ExectiveEducation(){
    //     $data['title'] = 'Exective Education';
    //     return view('exective_education')->with($data);
    // }
    // function College($slug = 'diplomas'){

    //     $data['subject'] = Subject::with(['courses' => function($query) {
    //         $query->select('courses.id','courses.title', 'courses.slug','courses.thumbnail', 'courses.subject_id');
    //             //  ->where('courses.with_subject', true);
    //     }])
    //     ->where('slug', $slug)
    //     ->orderBy('priority')
    //     ->first();
    //     $data['title'] = $data['subject']->name;

    //     $data['title'] = 'College';
    //     return view('college')->with($data);
    // }
    function ourVision()
    {
        $data['title'] = 'Our Vision';
        return view('our_vision')->with($data);
    }
    // function calendarTermDates(){
    //     $data['title'] = 'Calendar Term Dates';
    //     return view('calendar_term_dates')->with($data);
    // }

    // function HomeSchoolParentalSupport(){
    //     $data['title'] = 'Home School Parental Support';
    //     return view('home_school_parental_support')->with($data);
    // }

    // function HomeSchoolSupporttingChild(){
    //     $data['title'] = 'Home School Parental Support';
    //     return view('home_school_supporting_child')->with($data);
    // }

    function LearnerStories(Request $request)
    {

        //return CourseTestimonial::with('course:id,title,slug', 'course.categories')->get();

        // Fetch all categories with courses and testimonials
        $data['categories'] = Category::with(['courses:id,title,slug', 'courses.testimonials'])->get();

        // Get the category and course slugs from the request
        $categorySlug = $request->query('category');
        $courseSlug = $request->query('course');
        //$alumniSlug = $request->query('alumni');
        $testimonialId = $request->query('testimonial-id');


        if ($categorySlug) {

            $data['category_testimonial'] = CourseTestimonial::with([
                'course' => function ($query) {
                    $query->select('id', 'title', 'slug', 'short_name') // Select necessary fields from course
                        ->with('categories:id,name,slug');   // Include category names as well
                }
            ])
                ->whereHas('course.categories', function ($query) use ($categorySlug) {
                    if ($categorySlug) { // Check if category_id exists in the request
                        $query->where('categories.slug', $categorySlug);
                    }
                })
                ->orderByRaw('ISNULL(priority), priority ASC')
                ->paginate(6);


        }

        if ($courseSlug) {
            // Step 3: Fetch the course directly
            $data['course_testimonial'] = CourseTestimonial::with([
                'course' => function ($query) {
                    $query->select('id', 'title', 'slug', 'short_name'); // Select necessary fields from course
                }
            ])
                ->whereHas('course', function ($query) use ($courseSlug) {
                    if ($courseSlug) { // Check if course_slug exists in the request
                        $query->where('slug', $courseSlug);
                    }
                })
                ->orderByRaw('ISNULL(priority), priority ASC')
                ->paginate(6);

        }
        if ($testimonialId) {

            $testimonialId = decrypt($testimonialId);
            $data['testimonial'] = CourseTestimonial::with('course:id,title,short_name,slug', 'course.categories')->where('id', $testimonialId)->first();

        }
        if (!$categorySlug && !$courseSlug && !$testimonialId) {

            $data['all_testimonial'] = CourseTestimonial::with('course:id,title,short_name,slug', 'course.categories')->orderByRaw('ISNULL(priority), priority ASC')->paginate(6);
        }

        // return $data;
        return view('learner_stories')->with($data);

    }



    function BerkeleySquare()
    {

        $data['title'] = 'Berkeley Square';
        $data['subjects'] = $this->getSubjectsWithCourses();
        return view('berkeley_usa')->with($data);
    }


    function BerkeleySquareLondon()
    {

        $data['title'] = 'Berkeley Square UK';
        $data['subjects'] = $this->getSubjectsWithCourses();
        return view('berkeley_uk')->with($data);
    }
    function BerkeleyChina()
    {
        $data['title'] = 'Berkeley China';
        $data['subjects'] = $this->getSubjectsWithCourses();
        return view('berkeley_china')->with($data);
    }
    function BerkeleyMiddleEastAndAfrica()
    {
        $data['title'] = 'Berkeley Middle East & Africa';
        $data['subjects'] = $this->getSubjectsWithCourses();
        return view('berkeley_middleEast&Africa')->with($data);
    }

    function Certifications()
    {

        $data['title'] = 'Certifications';
        $data['subject'] = Subject::with([
            'courses' => function ($query) {
                $query->select('courses.id', 'courses.title', 'courses.slug', 'courses.thumbnail', 'courses.subject_id');
                //  ->where('courses.with_subject', true);
            }
        ])
            ->where('slug', 'diplomas')
            ->orderBy('priority')
            ->first();
        $data['title'] = 'Professional Certifications';
        return view('certifications')->with($data);
    }


    function getSubjectsWithCourses()
    {

        return $data['subjects'] = Subject::with([
            'courses' => function ($query) {
                $query->select('courses.id', 'courses.title', 'courses.slug', 'courses.thumbnail', 'courses.subject_id');
                //  ->where('courses.with_subject', true);
            }
        ])
            ->orderBy('priority', 'ASC')
            ->get();
    }

    function getindiviualSubjectCourses($slug = 'diplomas')
    {

        return $data['subject'] = Subject::with([
            'courses' => function ($query) {
                $query->select('courses.id', 'courses.title', 'courses.slug', 'courses.thumbnail', 'courses.subject_id');
                //  ->where('courses.with_subject', true);
            }
        ])
            ->where('slug', $slug)
            ->orderBy('priority')
            ->first();
    }

    function Tutor()
    {
        $data['title'] = 'Our Faculty';
        return view('tutor')->with($data);
    }
}