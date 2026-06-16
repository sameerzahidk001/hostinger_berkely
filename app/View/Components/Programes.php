<?php

namespace App\View\Components;

use App\Models\Category;
use App\Models\SiteSettings;
use Illuminate\View\Component;

class Programes extends Component
{
    public $id;
    public $columns;
    public $background;
    public $color;
    public $borderColor;
    public $cardBackground;
    public $orderby;
    public $pagination;
    public $pagination_num;
    public $url_target;
    public $title;
    public $description;

    public function __construct(
        $id = '', 
        $columns = '', 
        $background = 'transparent', 
        $color = '#000000', 
        $borderColor = 'transparent',
        $cardBackground = '#ffffff', 
        $orderby = 'title', 
        $pagination = 1,
        $pagination_num = 6,
        $url_target = '', 
        $title = '', 
        $description = ''
    ) {
        $this->id = $id;
        $this->columns = $columns;
        $this->background = $background;
        $this->color = $color;
        $this->borderColor = $borderColor;
        $this->cardBackground = $cardBackground;
        $this->orderby = $orderby;
        $this->pagination = $pagination;
        $this->pagination_num = $pagination_num;
        $this->url_target = $url_target;
        $this->title = $title;
        $this->description = $description;
    }

    public function render()
    {
        $siteSetting = SiteSettings::first();
        $category_perma = $siteSetting->category_perma ?? 'category';
        $orderColumn = 'id';
        $orderDirection = 'asc';

        if (!empty($this->orderby)) {
            $orderParts = explode(',', $this->orderby); // Split "id,asc" into ['id', 'asc']
            $orderColumn = $orderParts[0] ?? 'id'; // Default to 'id' if missing
            $orderDirection = $orderParts[1] ?? 'asc'; // Default to 'asc' if missing
        }

        // Check if pagination is 1 (get all categories) or paginate
        if ($this->pagination == 1) {
            $categories = Category::with('courses:id,title,slug')
                ->orderBy($orderColumn, $orderDirection)
                ->get();
        } else {
            $categories = Category::with('courses:id,title,slug')
                ->orderBy($orderColumn, $orderDirection)
                ->paginate($this->pagination_num);
        }

        return view('components.programes', [
            'categories' => $categories,
            'columns' => $this->columns,
            'category_perma' => $category_perma
        ]);
    }
}