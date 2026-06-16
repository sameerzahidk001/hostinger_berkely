<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Contactus extends Component
{
    public $id;
    public $title;
    public $iframe;
    public $background;
    public $color;

    /**
     * Create a new component instance.
     */
    public function __construct($id, $iframe, $title = '', $background = 'transparent', $color = '#000000')
    {
        $this->id = $id;
        $this->iframe = $iframe;
        $this->title = $title;
        $this->background = $background;
        $this->color = $color;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.contactus');
    }
}