<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Careers extends Component
{
    public $id;
    public $title;
    public $description;
    public $background;
    public $color;
    public $cardBackground;
    public $cardColor;
    public $cardBorderColor;
    public $cardHoverBackground;
    public $cardHoverColor;
    public $cardHoverBorderColor;
    public $cards;

    /**
     * Create a new component instance.
     */
    public function __construct(
            $id,
            $title,
            $description,
            $background,
            $color,
            $cardBackground,
            $cardColor,
            $cardBorderColor,
            $cardHoverBackground,
            $cardHoverColor,
            $cardHoverBorderColor,
            $cards
        )
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->background = $background;
        $this->color = $color;
        $this->cardBackground = $cardBackground;
        $this->cardColor = $cardColor;
        $this->cardBorderColor = $cardBorderColor;
        $this->cardHoverBackground = $cardHoverBackground;
        $this->cardHoverColor = $cardHoverColor;
        $this->cardHoverBorderColor = $cardHoverBorderColor;
        $this->cards = $cards;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.careers');
    }
}