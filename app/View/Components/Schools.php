<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Schools extends Component
{
    public $id;
    public $title;
    public $background;
    public $color;
    public $columns;
    public $courses;

    /**
     * Create a new component instance.
     */
    public function __construct($id, $background, $color, $columns, $title = 'Default Title', $courses = [])
    {
        $this->id = $id;
        $this->title = $title; // Provide a default value to avoid null errors
        $this->background = $background;
        $this->color = $color;
        $this->columns = $columns;
        $this->courses = $courses;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.schools');
    }
}
