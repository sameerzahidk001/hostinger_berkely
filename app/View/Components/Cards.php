<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Cards extends Component
{
    public $id;
    public $columns;
    public $layout;
    public $cards;
    public $backgroundColor;
    public $backgroundImage;
    public $color;
    public $alignment;

    public function __construct($id, $columns = 3, $layout = 'layout-1', $cards = [], $backgroundColor = '', $backgroundImage = '', $color = '', $alignment = '')
    {
        $this->id = $id;
        $this->columns = $columns;
        $this->layout = $layout;
        $this->cards = is_array($cards) ? $cards : []; // Ensure it's always an array
        $this->backgroundColor = $backgroundColor;
        $this->backgroundImage = $backgroundImage;
        $this->color = $color;
        $this->alignment = $alignment;
    }

    public function render()
    {
        return view('components.cards');
    }
}