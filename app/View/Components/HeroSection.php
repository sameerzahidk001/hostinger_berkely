<?php

namespace App\View\Components;

use Illuminate\View\Component;

class HeroSection extends Component
{
    public $id;
    public $hero;

    public function __construct($id, $hero)
    {
        $this->id = $id;
        $this->hero = $hero;
    }

    public function render()
    {
        return view('components.hero-section');
    }
}
