<?php

namespace App\View\Components;

use App\Models\School;
use Illuminate\View\Component;

class FilterCourses extends Component
{
    public $id;
    public $color;
    public $background;
    public $contentColor;
    public $contentBackground;
    public $contentBorderColor;
    public $tabColor;
    public $tabBackground;
    public $tabBorderColor;
    public $activeTabColor;
    public $activeTabBackground;
    public $activeTabBorderColor;
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
        $contentColor = '',
        $contentBackground = 'transparent',
        $contentBorderColor = '',
        $tabColor = '',
        $tabBackground = 'transparent',
        $tabBorderColor = '',
        $activeTabColor = '',
        $activeTabBackground = 'transparent',
        $activeTabBorderColor = '',
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
        $this->contentColor = $contentColor;
        $this->contentBackground = $contentBackground;
        $this->contentBorderColor = $contentBorderColor;
        $this->tabColor = $tabColor;
        $this->tabBackground = $tabBackground;
        $this->tabBorderColor = $tabBorderColor;
        $this->activeTabColor = $activeTabColor;
        $this->activeTabBackground = $activeTabBackground;
        $this->activeTabBorderColor = $activeTabBorderColor;
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

        $schools = School::with('categories.courses:id,title')
            ->orderBy($orderColumn, $orderDirection)
            ->get();

        return view('components.filter-courses', [
            'schools' => $schools,
        ]);
    }
}