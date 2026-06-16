<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Categories extends Component
{
    public $id;
    public $title;
    public $description;
    public $categories;
    public $background;
    public $color;
    public $layout;
    public $columns;
    public $seperator;

    /**
     * Create a new component instance.
     */
    public function __construct(
            $id,
            $color,
            $background,
            $title,
            $description,
            $layout,
            $columns,
            $seperator,
            $categories
        )
    {
        $this->id = $id;
        $this->color = $color;
        $this->background = $background;
        $this->title = $title;
        $this->description = $description;
        $this->categories = $categories;
        $this->layout = $layout;
        $this->columns = $columns;
        $this->seperator = $seperator;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.categories');
    }
}