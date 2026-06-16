<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Certificates extends Component
{
    public $id;
    public $title;
    public $image;
    public $color;
    public $background;
    public $borderColor;
    public $subtitle;
    public $certificateName;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $id, 
        $image = '', 
        $color = '', 
        $background = 'transparent', 
        $borderColor = 'transparent', 
        $title = 'Default Title', 
        $subtitle = '', 
        $certificateName = ''
    ) {
        $this->id = $id;
        $this->image = $image;
        $this->color = $color;
        $this->background = $background;
        $this->borderColor = $borderColor;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->certificateName = $certificateName;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        // echo $this->certificateName;
        // exit;
        return view('components.certificates');
    }
}