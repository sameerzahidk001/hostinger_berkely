<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SuccessStories extends Component
{
    public $id;
    public $title;
    public $description;
    public $url;
    public $buttonText;
    public $testimonials;
    public $status;

    /**
     * Create a new component instance.
     */
    public function __construct($id, $title, $description, $url, $buttonText, $testimonials)
{
    $this->id = $id;
    $this->title = $title;
    $this->description = $description;
    $this->url = $url;
    $this->buttonText = $buttonText;

    // Filter testimonials where status = 'show'
    $this->testimonials = collect($testimonials)->where('status', 'show');
}


    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.success-stories');
    }
}
