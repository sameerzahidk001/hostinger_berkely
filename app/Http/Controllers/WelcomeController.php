<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Client;
use App\Models\Page;
use App\Models\User;
use App\Models\School;
use App\Models\SiteSettings;
use App\Models\PageSection;
use Illuminate\Support\Facades\DB;
use App\Models\CourseAgenda;

class WelcomeController extends Controller
{
    public function index()
    {
        $siteSetting = SiteSettings::first();
        $homepageId = $siteSetting ? $siteSetting->home : null;

        $page = Page::where('id', $homepageId)->firstOrFail();
        $page = $this->hydratePageSections($page);

        return view('welcome', compact('page'));
    }

    public function pages($slug = null)
    {
        $siteSetting = SiteSettings::first();
        $homepageId = $siteSetting ? $siteSetting->home : null;
        $category_perma = $siteSetting->category_perma ?? 'category';

        $breadcrumb = [['title' => 'Home', 'url' => route('welcome')]];

        if (empty($slug)) {
            if (!$homepageId) {
                abort(404);
            }
            $page = Page::findOrFail($homepageId);
        } else {
            $slugParts = explode('/', $slug);
            $pageSlug = end($slugParts);

            $page = $this->resolvePageBySlug($pageSlug);
            if (!$page) {
                abort(404);
            }

            $breadcrumb[] = ['title' => $page->page_name, 'url' => url($page->full_url)];
        }

        $page = $this->hydratePageSections($page);

        return view('welcome', compact('page', 'breadcrumb'));
    }

    public function categoryDetails($categoryPerma, $slug)
    {
        $siteSetting = SiteSettings::first();
        $expectedPerma = $siteSetting->category_perma ?? 'category';

        if ($categoryPerma !== $expectedPerma) {
            abort(404);
        }

        $breadcrumb = [['title' => 'Home', 'url' => route('welcome')]];

        $basePageId = $siteSetting->categories ?? null;
        if ($basePageId) {
            $basePage = Page::find($basePageId);
            if ($basePage) {
                $breadcrumb[] = ['title' => $basePage->page_name, 'url' => url($basePage->full_url)];
            }
        }

        $page = $this->resolvePageBySlug($slug);
        if (!$page) {
            if (Category::where('slug', $slug)->exists()) {
                return redirect('/subject/' . $slug, 301);
            }
            abort(404);
        }

        $breadcrumb[] = ['title' => $page->page_name, 'url' => url($page->full_url)];
        $page = $this->hydratePageSections($page);

        return view('welcome', compact('page', 'breadcrumb'));
    }

    private function resolvePageBySlug(string $slug): ?Page
    {
        $query = Page::where('url', $slug);

        if (\Illuminate\Support\Facades\Schema::hasColumn('pages', 'status')) {
            $query->where('status', 1);
        }

        return $query
            ->withCount('sections')
            ->orderByDesc('sections_count')
            ->orderByDesc('id')
            ->first();
    }

    private function hydratePageSections(Page $page): Page
    {
        $page->load('sections');

        $page->sections = $page->sections->sortBy('order')->values()->map(function ($section) {
            $decoded = is_string($section->data) ? json_decode($section->data, true) : $section->data;
            $section->data = is_array($decoded) ? $decoded : [];

            if (empty($section->section_type)) {
                $section->section_type = $section->data['section_type'] ?? null;
            }

            // Normalize admin labels (e.g. "Hero Banner") to frontend keys (e.g. "hero-banner").
            if (!empty($section->section_type) && is_string($section->section_type)) {
                $raw = trim($section->section_type);
                $normalized = strtolower(str_replace(['_', ' '], '-', $raw));

                $aliases = [
                    'hero-banner' => 'hero-banner',
                    'banner-section' => 'banner',
                    'banner' => 'banner',
                    'school-category' => 'school-category',
                    'category' => 'category',
                    'grid-cards' => 'grid-cards',
                    'overlay-cards' => 'overlay-cards',
                    'title-section' => 'title-section',
                    'media-section' => 'media-section',
                    'cards' => 'cards',
                    'clients' => 'clients',
                    'list-section' => 'list',
                    'list' => 'list',
                    'programmes' => 'programmes',
                    'contact-us' => 'contactus',
                    'contactus' => 'contactus',
                    'separator' => 'separator',
                    'separator-section' => 'separator',
                    'certificate' => 'certificate',
                    'certificate-section' => 'certificate',
                    'filter-courses' => 'filter-courses',
                    'filter-courses-section' => 'filter-courses',
                    'career' => 'career',
                    'career-section' => 'career',
                    'search-bar' => 'search-bar',
                    'search-section' => 'search-section',
                    'course-agendas' => 'course-agendas',
                    'testimonials' => 'testimonials',
                    'content' => 'content',
                    'instructors' => 'instructors',
                ];

                $section->section_type = $aliases[$normalized] ?? $normalized;
            }

            if ($section->section_type === 'category' && isset($section->data['category'])) {
                $orderBy = explode(',', $section->data['orderby']);
                $orderColumn = $orderBy[0] ?? 'id';
                $orderDirection = $orderBy[1] ?? 'asc';

                $section->categoryDetails = Category::with([
                    'courses' => function ($query) use ($orderColumn, $orderDirection) {
                        $query->select('courses.id', 'courses.title', 'courses.slug', 'courses.thumbnail', 'courses.subject_id')
                            ->orderBy($orderColumn, $orderDirection);
                    }
                ])
                    ->where('id', $section->data['category'])
                    ->first();
            }

            if ($section->section_type === 'clients') {
                $section->clientDetails = Client::where('active', '1')->orderBy('id', 'asc')->get();
            }

            return $section;
        });

        return $page;
    }

    private function getPageIdFromSlug($slug)
    {
        return Page::where('url', $slug)->value('id');
    }

    public function agenda_search(Request $request)
    {
        try {
            $search = $request->only([
                'course',
                'subject',
                'class_type',
                'date_range',
                'country',
                'city',
                'school',
                'category',
                'keyword'
            ]);

            $query = CourseAgenda::query()
                ->with([
                    'course:id,title,slug',
                    'course.categories:id,name',
                    'course.categories.schools:id,name',
                    'country:id,name'
                ])
                ->whereDate('from', '>', now());

            // Filter by Course
            if ($request->filled('course')) {
                $query->where('course_id', $search['course']);
            }

            // Filter by Subject
            if ($request->filled('subject')) {
                $query->where('subject', 'like', '%' . $search['subject'] . '%');
            }

            // Filter by Class Type
            if ($request->filled('class_type') && !empty($search['class_type'])) {
                $query->where('delivery_type', $search['class_type']);
            }

            // Filter by Date Range
            if (!empty($search['date_range'])) {
                [$dateFrom, $dateTo] = explode(' to ', $search['date_range']);
                $query->whereDate('from', '>=', $dateFrom)
                    ->whereDate('to', '<=', $dateTo);
            }

            // Filter by Country
            if ($request->filled('country') && $search['country'] != '0') {
                $query->where('country_id', $search['country']);
            }

            // Filter by City
            if ($request->filled('city')) {
                $query->where('city', 'like', '%' . $search['city'] . '%');
            }

            // Filter by Category (via relationship)
            if ($request->filled('category')) {
                $query->whereHas('course.categories', function ($q) use ($search) {
                    $q->where('categories.id', $search['category']);
                });
            }

            // Filter by School
            if ($request->filled('school')) {
                $query->whereHas('course.categories.schools', function ($q) use ($search) {
                    $q->where('schools.id', $search['school']);
                });
            }

            // Filter by Keyword
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('subject', 'like', "%{$keyword}%")
                        ->orWhere('delivery_type', 'like', "%{$keyword}%")
                        ->orWhere('city', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhereHas('course', function ($q2) use ($keyword) {
                            $q2->where('title', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('course.categories', function ($q3) use ($keyword) {
                            $q3->where('name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('course.categories.schools', function ($q4) use ($keyword) {
                            $q4->where('name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('country', function ($q5) use ($keyword) {
                            $q5->where('name', 'like', "%{$keyword}%");
                        });
                });
            }

            // Sorting Logic
            $sortableFields = ['school', 'category', 'course', 'subject', 'deliveryType', 'location', 'dates'];
            $sortBy = $request->input('sort_by');
            $sortDir = $request->input('sort_dir', 'asc');

            if (in_array($sortBy, $sortableFields)) {
                switch ($sortBy) {
                    case 'course':
                        $query->join('courses', 'courses.id', '=', 'course_agendas.course_id')
                            ->orderBy('courses.title', $sortDir)
                            ->select('course_agendas.*');
                        break;

                    case 'subject':
                        $query->orderBy('subject', $sortDir);
                        break;

                    case 'deliveryType':
                        $query->orderBy('delivery_type', $sortDir);
                        break;

                    case 'location':
                        $query->orderBy('city', $sortDir);
                        break;

                    case 'dates':
                        $query->orderBy('from', $sortDir);
                        break;

                    case 'category':
                        $query->join('courses', 'courses.id', '=', 'course_agendas.course_id')
                            ->join('category_course', 'courses.id', '=', 'category_course.course_id')
                            ->join('categories', 'categories.id', '=', 'category_course.category_id')
                            ->orderBy('categories.name', $sortDir)
                            ->select('course_agendas.*')
                            ->distinct();
                        break;

                    case 'school':
                        $query->join('courses', 'courses.id', '=', 'course_agendas.course_id')
                            ->join('category_course', 'courses.id', '=', 'category_course.course_id')
                            ->join('categories', 'categories.id', '=', 'category_course.category_id')
                            ->join('school_category', 'categories.id', '=', 'school_category.category_id')
                            ->join('schools', 'schools.id', '=', 'school_category.school_id')
                            ->orderBy('schools.name', $sortDir)
                            ->select('course_agendas.*')
                            ->distinct();
                        break;
                }
            } else {
                $query->orderBy('from');
            }

            // Get final results
            $results = $query->get();

            // Return HTML Partial
            $html = view('partials.agenda_results', compact('results'))->render();
            return response()->json(['html' => $html]);

        } catch (\Exception $e) {
            // Log the exception
            \Log::error('Error in agenda search: ' . $e->getMessage());

            // Return a proper error response
            return response()->json(['error' => 'Error in agenda search: ' . $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        $siteSetting = SiteSettings::first();
        $searchId = $siteSetting ? $siteSetting->search : null;

        $page = Page::where('id', $searchId)->first();

        if ($page) {
            $page->load('sections');
            $page->sections = $page->sections->sortBy('order')->values()->map(function ($section) {
                $section->data = json_decode($section->data, true);

                if ($section->section_type === 'category' && isset($section->data['category'])) {
                    $orderBy = explode(',', $section->data['orderby']);
                    $orderColumn = $orderBy[0] ?? 'id';
                    $orderDirection = $orderBy[1] ?? 'asc';

                    $paginationCount = $section->data['pagination'] == 1
                        ? 6
                        : ($section->data['pagination_num'] ?? 10);

                    $section->categoryDetails = Category::with([
                        'courses' => function ($query) use ($orderColumn, $orderDirection) {
                            $query->select('courses.id', 'courses.title', 'courses.slug', 'courses.thumbnail', 'courses.subject_id')
                                ->orderBy($orderColumn, $orderDirection);
                        }
                    ])
                        ->where('id', $section->data['category'])
                        ->first();
                }

                if ($section->section_type === 'clients') {
                    $section->clientDetails = Client::where('active', '1')->orderBy('id', 'asc')->get();
                }

                return $section;
            });
        } else {
            $page = new \stdClass();
            $page->sections = collect([
                (object) [
                    'section_type' => 'search-section',
                    'order' => 0,
                    'data' => [],
                ],
            ]);
        }

        $courses = Course::query();

        if ($query) {
            $courses->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('short_name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('short_description', 'like', "%{$query}%");
            });
        }

        $results = $courses->get();

        return view('welcome', compact('page', 'results', 'query'));
    }

    public function instructorDetails($id) {

        $instructor = User::with(['countryarray:iso_code,name'])
            ->where('approved', 1)
            ->where('is_on_web', 1)
            ->where('id', $id)
            ->whereHas('roles', fn($q) => $q->where('name', 'instructor'))
            ->first();

        if (! $instructor) {
            abort(404, 'Instructor not found or not approved.');
        }

        $instructor->setRelation('courses', courses_for_instructor((int) $id));

        return view('instructor-profile', compact('instructor'));
    }

    public function faculty_search(Request $request)
    {
        $search = $request->only(['course', 'country', 'city', 'keyword']);

        $query = User::with('countryarray:iso_code,name')
            ->where('approved', 1)
            ->where('is_on_web', 1) 
            ->whereHas('roles', function ($q) {
                $q->where('name', 'instructor');
            });

        if ($request->filled('course')) {
            $course = \App\Models\Course::find($request->course);
            $instructorIds = $course ? course_instructor_ids($course) : [];

            if ($instructorIds !== []) {
                $query->whereIn('id', $instructorIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter by Country
        if ($request->filled('country') && $search['country'] != '0') {
            $query->where('country', $search['country']);
        }

        // Keyword Search
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('short_description', 'like', "%{$keyword}%")
                    ->orWhere('long_description', 'like', "%{$keyword}%")
                    ->orWhere('city', 'like', "%{$keyword}%");
            });
        }

        $results = $query->get();
        $html = view('partials.faculty_results', compact('results'))->render();
        return response()->json(['html' => $html]);
    }
}