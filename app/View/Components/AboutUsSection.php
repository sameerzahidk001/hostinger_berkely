<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AboutUsSection extends Component
{
    public $aboutUs;

    public function __construct($aboutUs)
    {
        $this->aboutUs = $aboutUs;
    }

    public function render()
    {
        return view('components.about-us-section');
    }
}
