<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Banner extends Component
{
    public $id;
    public $breadcrumb;
    public $title;
    public $subtitle;
    public $description;
    public $backgroundColor;
    public $image;
    public $color;
    public $solidButtonText;
    public $solidButtonUrl;
    public $solidUrlTarget;
    public $outlineButtonText;
    public $outlineButtonUrl;
    public $outlineUrlTarget;

    /**
     * Create a new component instance.
     */
    public function __construct($id, $breadcrumb, $title, $subtitle, $description, $backgroundColor, $image, $color, $solidButtonText, $solidButtonUrl, $solidUrlTarget, $outlineButtonText, $outlineButtonUrl, $outlineUrlTarget)
    {
        $this->id = $id;
        $this->breadcrumb = $breadcrumb;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->description = $description;
        $this->backgroundColor = $backgroundColor;
        $this->image = $image;
        $this->color = $color;
        $this->solidButtonText = $solidButtonText;
        $this->solidButtonUrl = $solidButtonUrl;
        $this->solidUrlTarget = $solidUrlTarget;
        $this->outlineButtonText = $outlineButtonText;
        $this->outlineButtonUrl = $outlineButtonUrl;
        $this->outlineUrlTarget = $outlineUrlTarget;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.banner');
    }
}