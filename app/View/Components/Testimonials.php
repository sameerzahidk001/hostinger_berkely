<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\CourseTestimonial;
use App\Models\Category;

class Testimonials extends Component
{
    public $id;
    public $color;
    public $background;
    public $cardColor;
    public $cardBackground;
    public $cardBorderColor;
    public $sidebarColor;
    public $sidebarBackground;
    public $sidebarBorderColor;
    public $paginationNumber;
    public $columns;
    public $categoryOrderby;
    public $testimonialsOrderby;
    public $urlTarget;
    public $category;
    public $course;
    public $testimonialId;
    public $search;
    public $sort;

    public function __construct(
        $id,
        $color = '#000000',
        $background = 'transparent',
        $cardColor = '',
        $cardBackground = 'transparent',
        $cardBorderColor = '',
        $sidebarColor = '',
        $sidebarBackground = 'transparent',
        $sidebarBorderColor = '',
        $paginationNumber = 6,
        $columns = '3',
        $categoryOrderby = 'id,asc',
        $testimonialsOrderby = 'priority,asc',
        $urlTarget = '',
        $category = null,
        $course = null,
        $testimonialId = null,
        $search = null,
        $sort = null
    ) {
        $this->id = $id;
        $this->color = $color;
        $this->background = $background;
        $this->cardColor = $cardColor;
        $this->cardBackground = $cardBackground;
        $this->cardBorderColor = $cardBorderColor;
        $this->sidebarColor = $sidebarColor;
        $this->sidebarBackground = $sidebarBackground;
        $this->sidebarBorderColor = $sidebarBorderColor;
        $this->paginationNumber = (int) $paginationNumber;
        $this->columns = $columns;
        $this->categoryOrderby = $categoryOrderby;
        $this->testimonialsOrderby = $testimonialsOrderby;
        $this->urlTarget = $urlTarget;
        $this->category = $category;
        $this->course = $course;
        $this->testimonialId = $testimonialId;
        $this->search = $search;
        $this->sort = $sort;
    }

    public function render()
    {
        return view('components.testimonials', [
            'data' => $this->getViewData(),
        ]);
    }

    protected function getViewData()
    {
        $pagination = $this->paginationNumber ?? 6;

        // Parse category ordering
        [$categoryOrderColumn, $categoryOrderDirection] = explode(',', $this->categoryOrderby . ',asc');
        // Parse testimonial ordering
        [$testimonialOrderColumn, $testimonialOrderDirection] = explode(',', $this->testimonialsOrderby . ',asc');

        $data['categories'] = Category::with(['courses:id,title,slug', 'courses.testimonials'])
            ->orderBy($categoryOrderColumn, $categoryOrderDirection)
            ->get();

        // Filters
        $categorySlug = $this->category;
        $courseSlug = $this->course;
        $testimonialId = $this->testimonialId;

        // Helper: Build base query
        $testimonialQuery = CourseTestimonial::with([
            'course:id,title,short_name,slug',
            'course.categories'
        ])->where('status', 'show');

        // Apply search if present
        if ($this->search) {
            $testimonialQuery->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('city', 'like', "%{$this->search}%")
                    ->orWhere('country', 'like', "%{$this->search}%")
                    ->orWhereHas('course', function ($sub) {
                        $sub->where('title', 'like', "%{$this->search}%")
                            ->orWhere('short_name', 'like', "%{$this->search}%");
                    });
            });
        }

        // Apply sorting
        switch ($this->sort) {
            case 'latest':
                $testimonialQuery->orderBy('date', 'desc');
                break;
            case 'oldest':
                $testimonialQuery->orderBy('date', 'asc');
                break;
            case 'rating_high':
                $testimonialQuery->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $testimonialQuery->orderBy('rating', 'asc');
                break;
            default:
                $testimonialQuery
                    ->orderByRaw('priority IS NULL')
                    ->orderBy('priority', 'asc')
                    ->orderByRaw('date IS NULL')
                    ->orderByRaw("STR_TO_DATE(date, '%Y-%m-%d') DESC");
                break;
        }

        // Apply filters
        if ($categorySlug) {
            $testimonialQuery->whereHas('course.categories', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
            $data['category_testimonial'] = $testimonialQuery->paginate($pagination);
        } elseif ($courseSlug) {
            $testimonialQuery->whereHas('course', function ($q) use ($courseSlug) {
                $q->where('slug', $courseSlug);
            });
            $data['course_testimonial'] = $testimonialQuery->paginate($pagination);
        } elseif ($testimonialId) {
            $decryptedId = decrypt($testimonialId);
            $data['testimonial'] = CourseTestimonial::with([
                'course:id,title,short_name,slug',
                'course.categories'
            ])->where('id', $decryptedId)->first();
        } else {
            $data['all_testimonial'] = $testimonialQuery->paginate($pagination);
        }

        return $data;
    }
}