<?php

namespace App\View\Components;

use App\Models\Category;
use App\Models\CourseAgenda;
use App\Models\School;
use Illuminate\View\Component;
use App\Models\Country;
use Carbon\Carbon;

class CourseAgendaComponent extends Component
{
    public $id;
    public $color;
    public $background;
    public $cardColor;
    public $cardBackground;
    public $cardBorderColor;
    public $filterColor;
    public $filterBackground;
    public $filterBorderColor;
    public $activeFilterColor;
    public $activeFilterBackground;
    public $activeFilterBorderColor;
    public $orderby;
    public $urlTarget;
    public $title;
    public $description;

    public function __construct(
        $id,
        $color = '#000000',
        $background = 'transparent',
        $cardColor = '',
        $cardBackground = 'transparent',
        $cardBorderColor = '',
        $filterColor = '',
        $filterBackground = 'transparent',
        $filterBorderColor = '',
        $activeFilterColor = '',
        $activeFilterBackground = 'transparent',
        $activeFilterBorderColor = '',
        $orderby = '',
        $urlTarget = '',
        $title = '',
        $description = ''
    ) {
        $this->id = $id;
        $this->color = $color;
        $this->background = $background;
        $this->cardColor = $cardColor;
        $this->cardBackground = $cardBackground;
        $this->cardBorderColor = $cardBorderColor;
        $this->filterColor = $filterColor;
        $this->filterBackground = $filterBackground;
        $this->filterBorderColor = $filterBorderColor;
        $this->activeFilterColor = $activeFilterColor;
        $this->activeFilterBackground = $activeFilterBackground;
        $this->activeFilterBorderColor = $activeFilterBorderColor;
        $this->orderby = $orderby;
        $this->urlTarget = $urlTarget;
        $this->title = $title;
        $this->description = $description;
    }

    public function render()
    {
        $orderColumn = 'id';
        $orderDirection = 'asc';

        if (!empty($this->orderby)) {
            $orderParts = explode(',', $this->orderby);
            $orderColumn = $orderParts[0] ?? 'id';
            $orderDirection = $orderParts[1] ?? 'asc';
        }

        $agenda_subjects = CourseAgenda::pluck('subject')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $agenda_cities = CourseAgenda::pluck('city')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $course_agendas = CourseAgenda::with(['course:id,title,slug','course.categories:id,name','course.categories.schools:id,name'])
            ->whereDate('from', '>', Carbon::today())
            ->orderBy($orderColumn, $orderDirection)
            ->get();

        $countries = Country::all();

        $schools = School::all();

        $categories = Category::all();

        // dd($course_agendas->toArray());

        return view('components.course-agenda-component', [
            'course_agendas' => $course_agendas,
            'agenda_subjects' => $agenda_subjects,
            'agenda_cities' => $agenda_cities,
            'countries' => $countries,
            'schools' => $schools,
            'categories' => $categories,
        ]);
    }
}