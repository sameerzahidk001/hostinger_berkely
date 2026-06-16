<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OverlayCards extends Component
{
    public $id;
    public $columns;
    public $background;
    public $color;
    public $cards;
    
    /**
     * Create a new component instance.
     */
    public function __construct($id, $columns, $background, $color, $cards = [])
    {
        $this->id = $id;
        $this->columns = $columns;
        $this->background = $background;
        $this->color = $color;
        $this->cards = $cards;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.overlay-cards');
    }
}
