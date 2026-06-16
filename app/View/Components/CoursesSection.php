<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CoursesSection extends Component
{
    public $courses;

    public function __construct($courses)
    {
        $this->courses = $courses;
    }

    public function render()
    {
        return view('components.courses-section');
    }
}

