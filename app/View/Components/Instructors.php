<?php

namespace App\View\Components;

use App\Models\User;
use App\Models\SiteSettings;
use App\Models\Country;
use Illuminate\View\Component;

class Instructors extends Component
{
    public $id;
    public $background;
    public $color;
    public $cardBackground;
    public $cardColor;
    public $orderby;
    public $pagination;
    public $pagination_num;
    public $title;
    public $description;

    public function __construct(
        $id = '',
        $background = 'transparent',
        $color = '#000000',
        $cardBackground = '#ffffff',
        $cardColor = '#000000',
        $orderby = 'title',
        $pagination = 1,
        $pagination_num = 6,
        $title = '',
        $description = ''
    ) {
        $this->id = $id;
        $this->background = $background;
        $this->color = $color;
        $this->cardBackground = $cardBackground;
        $this->cardColor = $cardColor;
        $this->orderby = $orderby;
        $this->pagination = $pagination;
        $this->pagination_num = $pagination_num;
        $this->title = $title;
        $this->description = $description;
    }

    public function render()
    {
        $siteSetting = SiteSettings::first();
        $instructor_perma = $siteSetting->instructor_perma ?? 'instructors';
        $orderColumn = 'id';
        $orderDirection = 'asc';

        if (!empty($this->orderby)) {
            $orderParts = explode(',', $this->orderby);
            $orderColumn = $orderParts[0] ?? 'id';
            $orderDirection = $orderParts[1] ?? 'asc';
        }

        // Check if pagination is 1 (get all categories) or paginate
        $instructors = User::with('countryarray:iso_code,name')->where('is_on_web', 1)->where('approved', 1)->whereHas('roles', function ($query) {
            $query->where('name', 'instructor');
        })
            ->orderBy($orderColumn, $orderDirection);

        $instructors = $this->pagination == 1
            ? $instructors->get()
            : $instructors->paginate($this->pagination_num);

        $countries = Country::all();

        $cities = User::pluck('city')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('components.instructors', [
            'instructors' => $instructors,
            'instructor_perma' => $instructor_perma,
            'cities' => $cities,
            'countries' => $countries,
        ]);
    }
}