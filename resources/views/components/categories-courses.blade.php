<div  class="grid grid-cols-1 md:grid-cols-[25%,75%] gap-2 w-full">
    <!-- Category List as Tab Buttons -->
    <div class="p-0  rounded-lg">
        <ul class="list-none space-y-2">
            @foreach($categories as $key => $category)
            <li class="w-full b-custom">
                <button class="custom-tab-title w-full relative flex items-center p-2 tab-btn" 
                    data-target="custom-tab{{ $key }}">
                    <div class="w-full flex items-center justify-between">
                        <h3 class="text-[16px] text-left">{{ $category->name }}</h3>
                    </div>
                </button>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- Course Lists as Tab Content -->

    <div class="custom-tab-content">
        @foreach($categories as $key => $category)
        <div id="custom-tab{{ $key }}" class="custom-tab-pane px-0 @if($loop->first) show @else hidden @endif">
            <div class="flex flex-col lg:flex-row gap-2 pb-2 min-h-[300px]">
                <div class="gap-2 grid grid-cols-1 w-full text-[16px] cursor-pointer p-2">
                    <ul class="space-y-0 font-medium">
                        @foreach($category->courses as $course)
                        <li class="flex lg:col-span-1 items-center gap-0">
                            <div class="flex-shrink-0">
                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" alt="" class="w-8 h-8">
                            </div>
                            <a href="{{ route('course.details', ['course' => $course->slug]) }}" target="{{ $urlTarget == '0' ? '_blank' : '' }}" class="hover:underline hover:font-semibold">
                                {{ $course->title }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
</div>

<script>
    $(document).ready(function () {
        const tabTitles = $('.custom-tab-title'); // Tab buttons
        const tabPanes = $('.custom-tab-pane');   // Tab content

        // Handle tab click
        tabTitles.on('click', function () {
            const targetId = $(this).data('target'); // Get the target tab content ID

            // Hide all tab panes and remove active class from all tab buttons
            tabPanes.addClass('hidden').removeClass('block');
            tabTitles.removeClass('active'); // Remove custom active class

            // Show the target tab pane
            $('#' + targetId).removeClass('hidden').addClass('block');

            // Add active class to the clicked tab title
            $(this).addClass('active');
        });

        // Set the first tab as active by default on page load
        if (tabTitles.length > 0) {
            tabTitles.first().click();
        }
    });
</script>