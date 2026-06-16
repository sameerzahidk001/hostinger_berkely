<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HeroBanner extends Component
{
    public $id;
    public $background;
    public $title;
    public $subtitle;
    public $description;
    public $video;

    /**
     * Create a new component instance.
     */
    public function __construct(
        int $id,
        ?string $background = null,
        ?string $title = null,
        ?string $subtitle = null,
        ?string $description = null,
        ?string $video = null
    )
    {
        $this->id = $id;
        $this->background = $background;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->description = $description;
        $this->video = $video;
    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.hero-banner');
    }
}
