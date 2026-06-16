@extends('layouts.app')
@push('style')

@endpush

@section('content')
<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Student Stories</span>
            
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Student Stories

            </h2>
        </div>
        <p clas="text-[18px] text-white">"Student Stories" features inspiring accounts from alumni and current students, offering insights into their academic and personal growth. By reading their experiences, you can gain valuable perspectives, learn from their challenges, and find motivation to pursue your own educational goals. These stories serve as a testament to the transformative power of education.</p>
        <div class="flex gap-6 mt-4 md:mt-auto">
            <a href="{{ route('contact') }}"
                class="text-center border py-4 px-3 w-full border-primary_orange bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                Enquire
            </a>
            <a href="{{ route('school-calender') }}"
                class="text-center border py-4 px-3 w-full border-primary_orange hover:bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                Join our next event
            </a>
        </div>

    </div>
    <div class=" flex xl:px-0 md:top-0 md:absolute md:right-0 z-40 md:w-[50%] m-0 flex-1 flex-col gap-4 md:h-full">
        <img src="{{ asset('frontend/images/jpg/60.jpg') }}"
            alt="" class="object-cover h-full md:max-h-full md:w-full md:h-full">
    </div>
</section>


<section class="flex flex-col gap-10  min-[1200px]:px-[30px] md:px-12 px-6 my-0 py-8  justify-between relative min-h-[230px]   bg-[#efefef]">
    <div class=" flex gap-2 flex-col min-[650px]:flex-row ">

        <!-- Check Boxes start -->
        <div class="min-w-[300px] max-w-[300px] hidden min-[650px]:block border-gray-500 bg-white p-3">
            <!-- <div class="flex flex-col   py-[22px] border-t-2 border-gray-500"> -->
                <div class="flex justify-start items-center w-full cursor-pointer pb-2">
                    <img  src="{{ asset('frontend/images/svgs/minus.svg') }}" class="w-5 h-5" alt="Toggle Icon">
                    <a href="{{ route('learner-stories') }}" 
                    class="capitalize text-[18px] underline">
                        All testimonials
                    </a>
                </div>
                <div class="flex items-center gap-1 ">
                    <img src="{{ asset('frontend/images/svgs/minus.svg') }}" class="w-5 h-5" alt="">
                    <a href="" class="uppercase font-canela text-[18px] hover:underline font-bold">PROGRAMS</a>
                </div>

                

                <div class="flex flex-col gap-2 mt-1 mb-3">
                    <!-- <div class="flex flex-col pb-[12px] border-t-1 border-gray-200"></div> -->
                    @foreach($categories as $index => $data)
                    <div class="flex flex-col gap-2 items-start border-b-2 border-gray-200">
                        <!-- Category Header -->
                        
                        <div class="flex justify-between items-center w-full cursor-pointer" onclick="toggleCategory({{ $index }})">
                            <a href="{{ route('learner-stories', ['category' => $data->slug]) }}" 
                            class="capitalize text-[18px] hover:underline {{ request('category') == $data->slug ? 'text-[#000435] font-semibold' : '' }}">
                                {{ $data->name }}
                            </a>
                            <img id="icon-{{ $index }}" src="{{ asset('frontend/images/svgs/plus.svg') }}" class="w-5 h-5" alt="Toggle Icon">
                        </div>
                        
                        <!-- Hidden Category Content -->
                        <div id="category-{{ $index }}" class="hidden pl-1">
                            @foreach($data->courses as $key => $course)
                                <a href="{{ route('learner-stories', ['course' => $course->slug]) }}"
                                class="text-dark text-[14px] hover:underline inline-block {{ request('course') == $course->slug ? 'text-[#000435] font-semibold' : '' }}">
                                    {{ $course->title }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                
            <!-- </div> -->
            
        </div>
        <!-- Check Boxes End -->
        <div class="flex flex-col w-full">
            <!-- all testimonial -->
            @if( isset($all_testimonial) && !empty($all_testimonial) )
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 w-full">
                    
                    @foreach($all_testimonial as $testimonialIndex => $testimonials)

                        <div class="flex flex-col bg-white">
                            <div class="p-2 "> <!-- Set a fixed height for the card -->
                                <div class="flex items-start gap-4 mb-2">
                                    <img src="{{ asset($testimonials->image) }}" alt=""
                                        class="w-20 h-20 shrink-0 object-cover self-center rounded-full">
                                    <div class="flex flex-col gap-0">
                                        <h3 class="text-[20px] text-dark font-semibold leading-[20px]">{{ $testimonials->name }}</h3>
                                        <p class="text-[14px] text-dark leading-normal">{{ $testimonials->city .', '. $testimonials->country }}</p>
                                        <a href="{{ route('learner-stories', ['course' => $testimonials->course->slug]) }}" class="text-[14px] text-dark leading-normal block">{{ $testimonials->course->short_name }}</a>
                                        @if(!is_null($testimonials->date))
                                        <p class="text-[14px] text-gray-400 leading-normal">{{ \Carbon\Carbon::parse($testimonials->date)->format('F j, Y') }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex flex-col gap-1 mt-0">
                                        <!-- <a href="{{ route('learner-stories', ['course' => $testimonials->course->slug]) }}" class="text-[13px] text-dark leading-normal block">{{ $testimonials->course->title }}</a> -->
                                        <!-- <a href="{{ route('learner-stories', ['category' => $testimonials->course->categories['0']->slug ?? '']) }}" class="text-[13px] leading-normal text-dark block">{{ $testimonials->course->categories['0']->name ?? '' }}</a> -->
                                    @if($testimonials->asset_path)
                                        <div class=" h-[300px] flex justify-center items-center">
                                            @php
                                                $youtubeUrl = $testimonials->asset_path;

                                                if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $youtubeUrl, $matches)) {
                                                    $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                                    // Convert to embed URL with autoplay, mute, and controls
                                                    $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?autoplay=1&mute=1&controls=1";
                                                } else {
                                                    $embedUrl = '';
                                                }
                                            @endphp

                                            @if($embedUrl)
                                                <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                            @else
                                                <p>Invalid YouTube URL</p>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="relative text-[16px] text-dark mt-0 {{ $testimonials->asset_path ? 'min-h-[95px] max-h-[95px]' : 'min-h-[395px] max-h-[395px]' }} overflow-hidden" id="testimonialContainer-{{ $testimonialIndex }}">
                                        
                                        <div class="relative" id="testimonialText-{{ $testimonialIndex }}">
                                            {!! $testimonials->text !!}
                                        </div>
                                        
                                        <a href="{{ route('learner-stories', ['course' => $testimonials->course->slug, 'testimonial-id' => encrypt($testimonials->id)]) }}" class="text-[#000435] absolute bottom-0 right-0 bg-white px-1 hidden underline font-semibold" id="readMoreLink-{{ $testimonialIndex }}">Read More</a>
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
            @if( isset($testimonial) && request()->has('testimonial-id') )
                
                <div class="grid grid-cols-1 gap-4 w-full">
                    
                    <div class="w-full ">
                        <div class="flex flex-col bg-white p-4">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-20 h-20"> <!-- Specific width for image div -->
                                    <img src="{{ asset($testimonial->image) }}" alt=""
                                        class="w-full h-full object-cover rounded-full"> <!-- Ensure the image covers the div -->
                                </div>
                                <div class="flex flex-col gap-0 flex-grow"> <!-- Make the text section grow to fill available space -->
                                    <h1 class="text-[23px] text-dark font-semibold">{{ $testimonial->name }}</h1>
                                    <p>{{ $testimonial->city .', '. $testimonial->country }}</p>
                                    <a href="{{ route('learner-stories', ['course' => $testimonial->course->slug]) }}" class="text-[13px] text-dark py-0 inline-block">
                                        {{ $testimonial->course->short_name ?? '' }}
                                    </a>
                                    {{--<a href="{{ route('learner-stories', ['category' => $testimonial->course->categories['0']->slug ?? '']) }}" class="text-[13px] text-dark py-0 inline-block">
                                        {{ $testimonial->course->categories['0']->name ?? '' }}
                                    </a>--}}
                                    
                                </div>
                            </div>
                            <div class="text-[16px]">
                                {!! $testimonial->text !!}
                            </div>
                            @if($testimonial->asset_path)
                                <div class=" h-[500px] mt-4">
                                    @php
                                        $youtubeUrl = $testimonial->asset_path;

                                        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/', $youtubeUrl, $matches)) {
                                            $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                            // Convert to embed URL with autoplay, mute, and controls
                                            $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?autoplay=1&mute=1&controls=1";
                                        } else {
                                            $embedUrl = '';
                                        }
                                    @endphp

                                    @if($embedUrl)
                                        <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
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
            @if( isset($course_testimonial) && !empty($course_testimonial) && !request()->has('testimonial-id')  )
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 w-full">
                    
                    @foreach($course_testimonial as $categoryTestimonialIndex => $testimonials)

                        <div class="flex flex-col bg-white">
                            <div class="p-2"> 
                                <div class="flex items-start gap-4 mb-2">
                                    <img src="{{ asset($testimonials->image) }}" alt=""
                                        class="w-20 h-20 shrink-0 object-cover self-center rounded-full">
                                    <div class="flex flex-col gap-0">
                                        <h3 class="text-[20px] text-dark font-semibold leading-[20px]">{{ $testimonials->name }}</h3>
                                        <p class="text-[14px] text-dark leading-normal">{{ $testimonials->city .', '. $testimonials->country }}</p>
                                        <a href="{{ route('learner-stories', ['course' => $testimonials->course->slug]) }}" class="text-[13px] text-dark pt-2 inline-block">{{ $testimonials->course->short_name }}</a>
                                        @if(!is_null($testimonials->date))
                                            <p class="text-[14px] text-gray-400 leading-normal">{{ \Carbon\Carbon::parse($testimonials->date)->format('F j, Y') }}</p>
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
                                                    $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?autoplay=1&mute=1&controls=1";
                                                } else {
                                                    $embedUrl = '';
                                                }
                                            @endphp

                                            @if($embedUrl)
                                                <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                            @else
                                                <p>Invalid YouTube URL</p>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="relative text-[16px] text-dark {{ $testimonials->asset_path ? 'min-h-[95px] max-h-[95px]' : 'min-h-[395px] max-h-[395px]' }} overflow-hidden" id="testimonialContainer-{{ $categoryTestimonialIndex }}">
                                        <div class="relative" id="testimonialText-{{ $categoryTestimonialIndex }}">
                                            {!! $testimonials->text !!}
                                        </div>
                                        <!-- Read More Link -->
                                        <a href="{{ route('learner-stories', ['course' => $testimonials->course->slug, 'testimonial-id' => encrypt($testimonials->id)]) }}" class="text-[#000435] absolute bottom-0 right-0 bg-white px-1 hidden underline font-semibold" id="readMoreLink-{{ $categoryTestimonialIndex }}">Read More</a>
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
            @if( isset($category_testimonial) && !empty($category_testimonial)  )
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 w-full">
                    
                    @foreach($category_testimonial as $categoryTestimonialIndex => $testimonials)

                        <div class="flex flex-col bg-white">
                            <div class="p-2"> <!-- Set a fixed height for the card -->
                                <div class="flex items-start gap-4 mb-2">
                                    <img src="{{ asset($testimonials->image) }}" alt=""
                                        class="w-20 h-20 shrink-0 object-cover self-center rounded-full">
                                    <div class="flex flex-col gap-0">
                                        <h3 class="text-[20px] text-dark font-semibold leading-[20px]">{{ $testimonials->name }}</h3>
                                        <p class="text-[14px] text-dark leading-normal">{{ $testimonials->city .', '. $testimonials->country }}</p>
                                        <a href="{{ route('learner-stories', ['course' => $testimonials->course->slug]) }}" class="text-[13px] text-dark pt-2 inline-block">{{ $testimonials->course->short_name }}</a>
                                        @if(!is_null($testimonials->date))
                                        <p class="text-[14px] text-gray-400 leading-normal">{{ \Carbon\Carbon::parse($testimonials->date)->format('F j, Y') }}</p>
                                        @endif
                                        {{-- <a href="{{ route('learner-stories', ['category' => $testimonials->course->categories['0']->slug ?? '']) }}" class="text-[13px] text-dark inline-block">{{ $testimonials->course->categories['0']->name ?? '' }}</a> --}}
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
                                                    $embedUrl = "https://www.youtube.com/embed/" . $videoId . "?autoplay=1&mute=1&controls=1";
                                                } else {
                                                    $embedUrl = '';
                                                }
                                            @endphp

                                            @if($embedUrl)
                                                <iframe class="w-full h-full object-cover" src="{{ $embedUrl }}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                            @else
                                                <p>Invalid YouTube URL</p>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="relative text-[16px] text-dark mt-0 {{ $testimonials->asset_path ? 'min-h-[95px] max-h-[95px]' : 'min-h-[300px] max-h-[300px]' }} overflow-hidden" id="testimonialContainer-{{ $categoryTestimonialIndex }}">
                                        <div class="relative" id="testimonialText-{{ $categoryTestimonialIndex }}">
                                            {!! $testimonials->text !!}
                                        </div>
                                        <!-- Read More Link -->
                                        <a href="{{ route('learner-stories', ['course' => $testimonials->course->slug, 'testimonial-id' => encrypt($testimonials->id)]) }}" class="text-[#000435] absolute bottom-0 right-0 bg-white px-1 hidden underline font-semibold" id="readMoreLink-{{ $categoryTestimonialIndex }}">Read More</a>
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



@endsection

@push('script')
<script>
    function toggleCategory(index) {
        const categoryContent = document.getElementById(`category-${index}`);
        const toggleIcon = document.getElementById(`icon-${index}`);

        // Toggle visibility of category content
        if (categoryContent.classList.contains('hidden')) {
            categoryContent.classList.remove('hidden');
            toggleIcon.src = "{{ asset('frontend/images/svgs/minus.svg') }}"; // Change icon to minus
        } else {
            categoryContent.classList.add('hidden');
            toggleIcon.src = "{{ asset('frontend/images/svgs/plus.svg') }}"; // Change icon to plus
        }
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
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

@endpush

