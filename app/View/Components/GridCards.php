<?php

namespace App\View\Components;

use Illuminate\View\Component;

class GridCards extends Component
{
    public $id;
    public $columns;
    public $layout;
    public $cards;
    public $title;
    public $subtitle;
    public $description;
    public $backgroundColor;
    public $backgroundImage;
    public $color;

    public function __construct(
        $id,
        $columns = 3, 
        $layout = '', 
        $cards = [], 
        $title = '', 
        $subtitle = '', 
        $description = '', 
        $backgroundColor = '', 
        $backgroundImage = '', 
        $color = '',
    ) {
        $this->id = $id;
        $this->columns = $columns;
        $this->layout = $layout;
        $this->cards = is_array($cards) ? $cards : [];
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->description = $description;
        $this->backgroundColor = $backgroundColor;
        $this->backgroundImage = $backgroundImage;
        $this->color = $color;
    }

    public function render()
    {
        return view('components.grid-cards');
    }
}