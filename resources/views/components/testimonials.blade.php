@php
    $all_testimonial = $data['all_testimonial'] ?? null;
    $category_testimonial = $data['category_testimonial'] ?? null;
    $course_testimonial = $data['course_testimonial'] ?? null;
    $testimonial = $data['testimonial'] ?? null;
    $categories = $data['categories'] ?? [];
@endphp

<section
    class="flex flex-col gap-10 min-[1200px]:px-[30px] md:px-12 px-6 my-16 {{ $background != 'transparent' ? 'py-16' : '' }} justify-between relative min-h-[230px]"
    style="background-color: {{ $background }}">

    <!-- Search + Sort Form -->
    <form method="GET" action="{{ url()->current() }}"
        class="flex flex-col md:flex-row gap-3 items-start md:items-center">

        <!-- Search Box -->
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search testimonials..."
            class="px-4 py-2 border border-gray-300 rounded-md w-full md:w-1/3 focus:ring-2 focus:ring-blue-500">

        <!-- Sort Dropdown -->
        <select name="sort" class="px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            <option value="">Sort By</option>
            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest First</option>
            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
            <option value="rating_high" {{ request('sort') == 'rating_high' ? 'selected' : '' }}>Highest Rating</option>
            <option value="rating_low" {{ request('sort') == 'rating_low' ? 'selected' : '' }}>Lowest Rating</option>
        </select>

        <!-- Submit Button -->
        <button type="submit" class="border px-4 py-1 border-[#000435] bg-[#000435] transition-all delay-300 duration-300 content-center rounded uppercase text-white">
            Apply
        </button>
    </form>

    <div class=" flex gap-2 flex-col min-[650px]:flex-row ">

        <!-- Check Boxes start -->
        <div class="min-w-[300px] max-w-[300px] hidden min-[650px]:block p-3"
            style="background-color: {{ $sidebarBackground }}; border: 2px solid {{ $sidebarBorderColor }}">
            <!-- <div class="flex flex-col   py-[22px] border-t-2 border-gray-500"> -->
            <div class="flex justify-start items-center w-full cursor-pointer pb-2">
                <img src="{{ asset('frontend/images/svgs/minus.svg') }}" class="w-5 h-5" alt="Toggle Icon">
                <a href="{{ url()->current() }}" class="capitalize text-[18px] underline"
                    style="color: {{ $sidebarColor }}">
                    All testimonials
                </a>
            </div>
            <div class="flex items-center gap-1 ">
                <img src="{{ asset('frontend/images/svgs/minus.svg') }}" class="w-5 h-5" alt="">
                <a href="" class="uppercase font-canela text-[18px] hover:underline font-bold"
                    style="color: {{ $sidebarColor }}">PROGRAMS</a>
            </div>

            <div class="flex flex-col gap-2 mt-1 mb-3">
                <!-- <div class="flex flex-col pb-[12px] border-t-1 border-gray-200"></div> -->
                @foreach($categories as $index => $data)
                    <div class="flex flex-col gap-2 items-start border-b-2 border-gray-200">
                        <!-- Category Header -->
                        <div class="flex justify-between items-center w-full cursor-pointer"
                            onclick="toggleCategory({{ $index }})">
                            <a href="{{ url()->current() }}?category={{ $data->slug }}"
                                class="capitalize text-[18px] hover:underline {{ request('category') == $data->slug ? 'underline font-semibold' : '' }}"
                                style="color: {{ $sidebarColor }}">
                                {{ $data->name }}
                            </a>
                            <img id="icon-{{ $index }}" src="{{ asset('frontend/images/svgs/plus.svg') }}" class="w-5 h-5"
                                alt="Toggle Icon">
                        </div>
                        <!-- Hidden Category Content -->
                        <div id="category-{{ $index }}" class="hidden pl-1">
                            <ul class="list-disc list-inside ml-4">
                                @foreach($data->courses as $key => $course)
                                    <li>
                                        <a href="{{ url()->current() }}?course={{ $course->slug }}"
                                            class="text-[14px] hover:underline {{ request('course') == $course->slug ? 'underline font-semibold' : '' }}"
                                            style="color: {{ $sidebarColor }}">
                                            {{ $course->title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
            <!-- </div> -->
        </div>
        <!-- Check Boxes End -->
        <div class="flex flex-col w-full">
            <!-- all testimonial -->
            @if(isset($all_testimonial) && !empty($all_testimonial))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 w-full">
                    @foreach($all_testimonial as $testimonialIndex => $testimonials)
                        <div class="flex flex-col"
                            style="background-color: {{ $cardBackground }}; border: 2px solid {{ $cardBorderColor }};">
                            <div class="p-2">

                                <!-- Image + Name + City + Course -->
                                <div class="flex items-start gap-4 mb-2">
                                    <img src="{{ asset($testimonials->image) }}" alt=""
                                        class="w-20 h-20 shrink-0 object-cover self-center rounded-full">


                                    <div class="flex flex-col gap-0 w-full">
                                        <div class="flex flex-col gap-0">
                                            <h3 class="text-[20px] font-semibold leading-[20px]"
                                                style="color: {{ $cardColor }};">
                                                {{ $testimonials->name }}
                                            </h3>

                                            <p class="text-[14px] leading-normal" style="color: {{ $cardColor }};">
                                                {{ $testimonials->city . ', ' . $testimonials->country }}
                                            </p>

                                            <a href="{{ url()->current() }}?course={{ $testimonials->course->slug }}"
                                                class="text-[14px] leading-normal block" style="color: {{ $cardColor }};">
                                                {{ $testimonials->course->short_name }}
                                            </a>

                                            <!-- LinkedIn (left) + Rating (right) -->
                                            <div class="flex items-center justify-between mt-1 w-full">

                                                <!-- LinkedIn -->
                                                @if(!empty($testimonials->linkedin_url))
                                                    <a href="{{ $testimonials->linkedin_url }}" target="_blank" rel="noopener"
                                                        class="text-blue-600 hover:text-blue-800">
                                                        <svg width="30" xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                                                            <path fill="#00032d"
                                                                d="M512 96L127.9 96C110.3 96 96 110.5 96 128.3L96 511.7C96 529.5 110.3 544 127.9 544L512 544C529.6 544 544 529.5 544 511.7L544 128.3C544 110.5 529.6 96 512 96zM231.4 480L165 480L165 266.2L231.5 266.2L231.5 480L231.4 480zM198.2 160C219.5 160 236.7 177.2 236.7 198.5C236.7 219.8 219.5 237 198.2 237C176.9 237 159.7 219.8 159.7 198.5C159.7 177.2 176.9 160 198.2 160zM480.3 480L413.9 480L413.9 376C413.9 351.2 413.4 319.3 379.4 319.3C344.8 319.3 339.5 346.3 339.5 374.2L339.5 480L273.1 480L273.1 266.2L336.8 266.2L336.8 295.4L337.7 295.4C346.6 278.6 368.3 260.9 400.6 260.9C467.8 260.9 480.3 305.2 480.3 362.8L480.3 480z" />
                                                        </svg>
                                                    </a>
                                                @else
                                                    <span>
                                                        <svg width="30" xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                                                            <path fill="#9ca3af"
                                                                d="M512 96L127.9 96C110.3 96 96 110.5 96 128.3L96 511.7C96 529.5 110.3 544 127.9 544L512 544C529.6 544 544 529.5 544 511.7L544 128.3C544 110.5 529.6 96 512 96zM231.4 480L165 480L165 266.2L231.5 266.2L231.5 480L231.4 480zM198.2 160C219.5 160 236.7 177.2 236.7 198.5C236.7 219.8 219.5 237 198.2 237C176.9 237 159.7 219.8 159.7 198.5C159.7 177.2 176.9 160 198.2 160zM480.3 480L413.9 480L413.9 376C413.9 351.2 413.4 319.3 379.4 319.3C344.8 319.3 339.5 346.3 339.5 374.2L339.5 480L273.1 480L273.1 266.2L336.8 266.2L336.8 295.4L337.7 295.4C346.6 278.6 368.3 260.9 400.6 260.9C467.8 260.9 480.3 305.2 480.3 362.8L480.3 480z" />
                                                        </svg>
                                                    </span>
                                                @endif

                                                <!-- Rating -->
                                                <div class="flex">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        @if($i <= $testimonials->rating)
                                                            <i class="fa fa-star text-primary_orange"></i>
                                                        @else
                                                            <i class="fa fa-star text-gray-400"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                            </div>

                                            <!-- Date -->
                                            @if(!is_null($testimonials->date))
                                                <p class="text-[14px] text-gray-400 leading-normal">
                                                    {{ \Carbon\Carbon::parse($testimonials->date)->format('F j, Y') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Video -->
                                <div class="flex flex-col gap-1 mt-0">
                                    @if($testimonials->asset_path)
                                        <div class="h-[300px] flex justify-center items-center">
                                            @php
                                                $youtubeUrl = $testimonials->asset_path;
                                                if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $youtubeUrl, $matches)) {
                                                    $videoId = $matches[1];
                                                    $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?mute=1&controls=1";
                                                } else {
                                                    $embedUrl = '';
                                                }
                                            @endphp

                                            @if($embedUrl)
                                                <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0"
                                                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen></iframe>
                                            @else
                                                <p>Invalid YouTube URL</p>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Testimonial Text -->
                                    <div class="relative text-[16px] mt-0 
                                        {{ $testimonials->asset_path ? 'min-h-[95px] max-h-[95px]' : 'min-h-[395px] max-h-[395px]' }} 
                                        overflow-y-auto pr-1 scroll-smooth" id="testimonialContainer-{{ $testimonialIndex }}"
                                        style="color: {{ $cardColor }};">

                                        <div class="relative" id="testimonialText-{{ $testimonialIndex }}">
                                            {!! $testimonials->text !!}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>




                <!-- Pagination links -->
                <div class="pagination mt-3">
                    {{ $all_testimonial->links() }}
                </div>
            @endif

            <!-- indiviual test -->
            @if(isset($testimonial) && request()->has('testimonial-id'))
                <div class="grid grid-cols-1 gap-4 w-full">
                    <div class="w-full ">
                        <div class="flex flex-col p-4"
                            style="background-color: {{ $cardBackground }}; border: 2px solid {{ $cardBorderColor }};">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-20 h-20"> <!-- Specific width for image div -->
                                    <img src="{{ asset($testimonial->image) }}" alt=""
                                        class="w-full h-full object-cover rounded-full">
                                    <!-- Ensure the image covers the div -->
                                </div>
                                <div class="flex flex-col gap-0 flex-grow">
                                    <!-- Make the text section grow to fill available space -->
                                    <h1 class="text-[23px] font-semibold" style="color: {{ $cardColor }};">
                                        {{ $testimonial->name }}</h1>
                                    <p style="color: {{ $cardColor }};">
                                        {{ $testimonial->city . ', ' . $testimonial->country }}</p>
                                    <a href="{{ url()->current() }}?course={{ $testimonial->course->slug }}"
                                        class="text-[13px] py-0 inline-block" style="color: {{ $cardColor }};">
                                        {{ $testimonial->course->short_name ?? '' }}
                                    </a>
                                </div>
                            </div>
                            <div class="text-[16px]" style="color: {{ $cardColor }};">
                                {!! $testimonial->text !!}
                            </div>
                            @if($testimonial->asset_path)
                                <div class=" h-[500px] mt-4">
                                    @php
                                        $youtubeUrl = $testimonial->asset_path;
                                        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $youtubeUrl, $matches)) {
                                            $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                            // Convert to embed URL with autoplay, mute, and controls
                                            $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?mute=1&controls=1";
                                        } else {
                                            $embedUrl = '';
                                        }
                                    @endphp
                                    @if($embedUrl)
                                        <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0"
                                            allow="accelerometer;  encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>
                                    @else
                                        <p>Invalid YouTube URL</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- course testimonail -->
            @if(isset($course_testimonial) && !empty($course_testimonial) && !request()->has('testimonial-id'))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 w-full">
                    @foreach($course_testimonial as $categoryTestimonialIndex => $testimonials)
                        <div class="flex flex-col"
                            style="background-color: {{ $cardBackground }}; border: 2px solid {{ $cardBorderColor }};">
                            <div class="p-2">
                                <div class="flex items-start gap-4 mb-2">
                                    <img src="{{ asset($testimonials->image) }}" alt=""
                                        class="w-20 h-20 shrink-0 object-cover self-center rounded-full">
                                    <div class="flex flex-col gap-0">
                                        <h3 class="text-[20px] font-semibold leading-[20px]" style="color: {{ $cardColor }};">
                                            {{ $testimonials->name }}</h3>
                                        <p class="text-[14px] leading-normal" style="color: {{ $cardColor }};">
                                            {{ $testimonials->city . ', ' . $testimonials->country }}</p>
                                        <a href="{{ url()->current() }}?course={{ $testimonials->course->slug }}"
                                            class="text-[13px] pt-2 inline-block"
                                            style="color: {{ $cardColor }};">{{ $testimonials->course->short_name }}</a>
                                        @if(!is_null($testimonials->date))
                                            <p class="text-[14px] text-gray-400 leading-normal">
                                                {{ \Carbon\Carbon::parse($testimonials->date)->format('F j, Y') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1 mt-0">
                                    @if($testimonials->asset_path)
                                        <div class=" h-[300px] flex justify-center items-center">
                                            @php
                                                $youtubeUrl = $testimonials->asset_path;

                                                if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $youtubeUrl, $matches)) {
                                                    $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                                    // Convert to embed URL with autoplay, mute, and controls
                                                    $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?mute=1&controls=1";
                                                } else {
                                                    $embedUrl = '';
                                                }
                                            @endphp

                                            @if($embedUrl)
                                                <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0"
                                                    allow="accelerometer;  encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen></iframe>
                                            @else
                                                <p>Invalid YouTube URL</p>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="relative text-[16px] {{ $testimonials->asset_path ? 'min-h-[95px] max-h-[95px]' : 'min-h-[395px] max-h-[395px]' }} overflow-hidden"
                                        id="testimonialContainer-{{ $categoryTestimonialIndex }}"
                                        style="color: {{ $cardColor }};">
                                        <div class="relative" id="testimonialText-{{ $categoryTestimonialIndex }}">
                                            {!! $testimonials->text !!}
                                        </div>
                                        <!-- Read More Link -->
                                        <a href="{{ url()->current() }}?course={{ $testimonials->course->slug }}&testimonial-id={{ encrypt($testimonials->id) }}"
                                            class="text-[#000435] absolute bottom-0 right-0 bg-white px-1 hidden underline font-semibold"
                                            id="readMoreLink-{{ $categoryTestimonialIndex }}">Read More</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @endforeach
                </div>
                <!-- Pagination links -->
                <div class="pagination mt-3">
                    {{ $course_testimonial->appends(request()->query())->links() }}
                </div>
            @endif

            <!-- category testimonail -->
            @if(isset($category_testimonial) && !empty($category_testimonial))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 w-full">
                    @foreach($category_testimonial as $categoryTestimonialIndex => $testimonials)
                        <div class="flex flex-col"
                            style="background-color: {{ $cardBackground }}; border: 2px solid {{ $cardBorderColor }};">
                            <div class="p-2"> <!-- Set a fixed height for the card -->
                                <div class="flex items-start gap-4 mb-2">
                                    <img src="{{ asset($testimonials->image) }}" alt=""
                                        class="w-20 h-20 shrink-0 object-cover self-center rounded-full">
                                    <div class="flex flex-col gap-0">
                                        <h3 class="text-[20px] font-semibold leading-[20px]" style="color: {{ $cardColor }};">
                                            {{ $testimonials->name }}</h3>
                                        <p class="text-[14px] leading-normal" style="color: {{ $cardColor }};">
                                            {{ $testimonials->city . ', ' . $testimonials->country }}</p>
                                        <a href="{{ url()->current() }}?course={{ $testimonials->course->slug }}"
                                            class="text-[13px] pt-2 inline-block"
                                            style="color: {{ $cardColor }};">{{ $testimonials->course->short_name }}</a>
                                        @if(!is_null($testimonials->date))
                                            <p class="text-[14px] text-gray-400 leading-normal">
                                                {{ \Carbon\Carbon::parse($testimonials->date)->format('F j, Y') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1 mt-0">
                                    @if($testimonials->asset_path)
                                        <div class=" h-[300px] flex justify-center items-center">
                                            @php
                                                $youtubeUrl = $testimonials->asset_path;

                                                if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $youtubeUrl, $matches)) {
                                                    $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                                    // Convert to embed URL with autoplay, mute, and controls
                                                    $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?mute=1&controls=1";
                                                } else {
                                                    $embedUrl = '';
                                                }
                                            @endphp

                                            @if($embedUrl)
                                                <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0"
                                                    allow="accelerometer;  encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen></iframe>
                                            @else
                                                <p>Invalid YouTube URL</p>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="relative text-[16px] mt-0 {{ $testimonials->asset_path ? 'min-h-[95px] max-h-[95px]' : 'min-h-[300px] max-h-[300px]' }} overflow-hidden"
                                        id="testimonialContainer-{{ $categoryTestimonialIndex }}"
                                        style="color: {{ $cardColor }};">
                                        <div class="relative" id="testimonialText-{{ $categoryTestimonialIndex }}">
                                            {!! $testimonials->text !!}
                                        </div>
                                        <!-- Read More Link -->
                                        <a href="{{ url()->current() }}?course={{ $testimonials->course->slug }}&testimonial-id={{ encrypt($testimonials->id) }}"
                                            class="text-[#000435] absolute bottom-0 right-0 bg-white px-1 hidden underline font-semibold"
                                            id="readMoreLink-{{ $categoryTestimonialIndex }}">Read More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!-- Pagination links -->
                <div class="pagination mt-3">
                    {{ $category_testimonial->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</section>

<script>
    function toggleCategory(index) {
        const categoryContent = document.getElementById(`category-${index}`);
        const toggleIcon = document.getElementById(`icon-${index}`);

        // Toggle visibility of category content
        if (categoryContent.classList.contains('hidden')) {
            categoryContent.classList.remove('hidden');
            toggleIcon.src = "{{ asset('frontend/images/svgs/minus.svg') }}";
        } else {
            categoryContent.classList.add('hidden');
            toggleIcon.src = "{{ asset('frontend/images/svgs/plus.svg') }}";
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        // Check if testimonials are available
        @if(!empty($all_testimonial))
            @foreach($all_testimonial as $index => $testimonialItem)
                var testimonialContainer = document.getElementById("testimonialContainer-{{ $index }}");
                var testimonialText = document.getElementById("testimonialText-{{ $index }}");
                var readMoreLink = document.getElementById("readMoreLink-{{ $index }}");

                // Ensure elements exist before adding event listeners
                if (testimonialText && readMoreLink && testimonialContainer) {
                    // Check if the content overflows
                    if (testimonialText.scrollHeight > testimonialContainer.clientHeight) {
                        readMoreLink.classList.remove("hidden"); // Show the "Read More" link
                    }

                    // Expand the content when "Read More" is clicked
                    // readMoreLink.addEventListener("click", function() {
                    //     testimonialContainer.style.maxHeight = testimonialText.scrollHeight + "px"; 
                    //     readMoreLink.style.display = "none"; // Hide the "Read More" link
                    // });
                }
            @endforeach
        @else
            console.warn("No testimonials available.");
        @endif
    });
</script>