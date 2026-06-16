@extends('layouts.app')
@push('style')
    <style>
        .section-heading::first-letter {
            text-transform: uppercase;
        }

        .section-subheading::first-letter {
            text-transform: uppercase;
        }

        .hideme {
            display: none
        }

        html {
            scroll-behavior: smooth;
        }

        .editor ul {
            list-style-type: disc;
            margin-left: 20px;
        }
        @media (min-width: 1200px) {
    .min-\[1200px\]\:px-\[72px\] {
        padding-left: 72px;
        padding-right: 72px;
        margin-bottom: 90px;
    }
}
    </style>
@endpush

@section('content')
    <div class="h-[72px]"></div>
    @if ($course->banner_section === 1)
        <!-- Add Slider Here start -->
        <section class="flex flex-col-reverse justify-between relative min-h-[500px] md:max-h-[500px] bg-[#000435]">
            <div class="flex text-white min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6 md:w-[50%] m-0  flex-1 flex-col">
                @if ($course->dynamicLabel->banner_breadcrumb === 1)
                    <div class="items-center gap-1 hidden md:flex ">
                        <a href="#">Home</a>
                        <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
                        <span class="font-bold">Course</span>
                        <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
                        <span class="font-bold">{{ $course->title }}</span>
                    </div>
                @endif

                <div class="flex flex-col gap-4 my-4">
                    @if ($course->dynamicLabel->banner_sub_title_placement === 1)
                        <h1 class="text-[20px] leading-8 text-primary_orange">{{ $course->dynamicLabel->banner_sub_title }}
                        </h1>
                    @endif
                    <h2 class="text-[32px] md:text-[36px] leading-[58px] font-canela text-white">
                        {{ $course->dynamicLabel->banner_title }}</h2>
                    @if ($course->dynamicLabel->banner_sub_title_placement !== 1)
                        <h1 class="text-[20px] leading-8 text-primary_orange">{{ $course->dynamicLabel->banner_sub_title }}
                        </h1>
                    @endif
                </div>
                <div clas="text-[18px] text-white editor">
                    {!! $course->short_description !!}
                </div>
                <div class="flex gap-6 mt-4 md:mt-auto">
                    @if ($course->dynamicLabel->banner_button_1_text && $course->dynamicLabel->banner_button_1_url)
                        <a href="{{ $course->dynamicLabel->banner_button_1_url }}"
                            class="text-center border py-4 px-3 w-full border-primary_orange bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                            {{ $course->dynamicLabel->banner_button_1_text }}
                        </a>
                    @endif
                    @if ($course->dynamicLabel->banner_button_1_text && $course->dynamicLabel->banner_button_1_url)
                        <a href="{{ $course->dynamicLabel->banner_button_2_url }}"
                            class="text-center border py-4 px-3 w-full border-primary_orange hover:bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                            {{ $course->dynamicLabel->banner_button_2_text }}
                        </a>
                    @endif
                </div>

            </div>
            <div class=" flex xl:px-0 md:top-0 md:absolute md:right-0 z-40 md:w-[50%] m-0 flex-1 flex-col gap-4 md:h-full">
                <img src="{{ asset($course->dynamicLabel->banner_image) }}" alt=""
                    class="object-cover h-full md:max-h-full md:w-full md:h-full">
            </div>
        </section>
    @endif

    @if ($course->contact_us_section == 1)
        <section
            class="flex sticky top-[69px] z-[99] lg:top-[83px] items-center px-4 md:px-8 lg:px-[120px] gap-3 md:gap-[120px] my-0 {{ $course->contact_us_section == 1 ? 'py-0' : 'py-[12px]' }} mt-4 bg-[#000435] hidden lg:block">
            <div class="flex justify-center flex-1 font-ghothic text-[15px] gap-2 md:gap-6 items-center text-white">
                @if (!empty($course->description) && $course->overview_section == 1)
                    <a href="#one" class="underline decoration-crimson hidden sm:block">Overview</a>
                @endif
                <!-- <a href="#two">Who Can Do</a> -->
                @if (!empty($course->eligibility) && $course->eligibility_section === 1)
                    <a href="#three">Eligibility</a>
                @endif
                @if (!$course->courseStructuresFirst->isEmpty())
                    <a href="#four">Course Structure</a>
                @endif
                <!-- <a href="#five">Lecture Plan</a> -->
                @if ($course->exam_section == 1)
                    <a href="#six">Examination</a>
                @endif
                <!-- <a href="#seven">Faculty</a> -->
                @if (!$course->courseFeePackages->isEmpty() && $course->fee_visibility == 1)
                    <a href="#eight">Fee</a>
                @endif
                @if ($course->success_stories == 1)
                    <a href="#nine">Stories
                    </a>
                @endif
                @if ($course->benefits_section == 1)
                    <a href="#ten">Certificate</a>
                @endif
                @if (!$course->courseFaq->isEmpty() && $course->faq_section == 1)
                    <a href="#eleven">FAQs</a>
                @endif
                @if ($course->contact_us_section == 1)
                    <a href="#apply"
                        class="shrink-0 px-6 py-2 inline-flex gap-1 items-center font-ghothic text-[13px]  bg-crimson  text-white hover:bg-primary ">
                        Apply Now
                        <img src="{{ asset('frontend/images/svgs/caret-right.svg') }}" class="w-8 h-8" alt="">
                    </a>
                @endif

            </div>

        </section>
    @endif

    @if ($course->overview_section == 1)
        <section id="one" class="card-hidden bg-[#f4f4f4] px-6 my-0 py-10  md:px-16 lg:px-[120px]">
            <div class="flex flex-col pb-4">
                <div class="flex gap-3 items-center justify-center mb-6 ">
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                    <span
                        class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela  text-center section-heading">{{ $course->dynamicLabel->overview ?? 'overview' }}</span>
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                </div>

                <div class="flex flex-col lg:flex-row-reverse items-center gap-x-24 gap-y-10">

                    <div class="flex-1">
                        @if ($course->overview_video_url)
                            @php

                                $youtubeUrl = $course->overview_video_url;

                                if (
                                    preg_match(
                                        '/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/',
                                        $youtubeUrl,
                                        $matches,
                                    )
                                ) {
                                    $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                    // Convert to embed URL with autoplay, mute, and controls
                                    $embedUrl =
                                        'https://www.youtube.com/embed/' . $videoId . '?autoplay=1&mute=1&controls=1';
                                } else {
                                    $embedUrl = '';
                                }

                            @endphp

                            @if ($embedUrl)
                                <iframe class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover"
                                    src="{{ $embedUrl }}" frameborder="0"
                                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                            @else
                                <p>Invalid YouTube URL</p>
                            @endif
                        @elseif($course->overview_img)
                            <img src="{{ asset($course->overview_img) }}" alt=""
                                class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
                        @else
                            <img src="{{ asset('admin/courses/course.png') }}" alt=""
                                class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
                        @endif
                        <!-- <img src="{{ asset($course->overview_img ? $course->overview_img : 'admin/courses/course.png') }}" alt=""
                            class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover"> -->
                    </div>

                    <div class="flex-1">
                        <div class="editor">
                            {!! $course->description !!}
                        </div>


                        @if (!empty($course->offered_by['institute']))
                            <div class="flex flex-col pb-4">
                                <div class="flex gap-3 items-center justify-center mb-3 mt-3">
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                    <span
                                        class="font-semibold section-subheading">{{ $course->dynamicLabel->offered_by ?? 'Offered by' }}</span>
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="editor">
                                        {!! optional($course->offered_by)['institute'] !!}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (!empty($course->offered_by['head_office']))
                            <div class="flex flex-col pb-4">
                                <div class="flex gap-3 items-center justify-center mb-3 ">
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                    <span
                                        class="font-semibold section-subheading">{{ $course->dynamicLabel->head_office ?? 'Head office' }}</span>
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="editor">
                                        {!! optional($course->offered_by)['head_office'] !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (!empty($course->offered_by['members']))
                            <div class="flex flex-col pb-4">
                                <div class="flex gap-3 items-center justify-center mb-3 ">
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                    <span
                                        class="font-semibold section-subheading">{{ $course->dynamicLabel->members ?? 'Members' }}</span>
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="editor">
                                        {!! optional($course->offered_by)['members'] !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (!empty($course->offered_by['founded_in']))
                            <div class="flex gap-3 items-center justify-center mb-3 ">
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                                <span
                                    class="font-semibold section-subheading">{{ $course->dynamicLabel->founded_in ?? 'Founded in' }}</span>
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="editor">
                                    {!! optional($course->offered_by)['founded_in'] !!}
                                </div>
                            </div>
                        @endif



                    </div>

                </div>

                @if (!empty($course->vision_and_mission))
                    <div class="flex gap-3 items-center justify-center mt-6 mb-3 ">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span
                            class="font-semibold section-subheading">{{ $course->dynamicLabel->vission_mission ?? 'Vision & mission' }}</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="editor">
                            {!! $course->vision_and_mission !!}
                        </div>
                    </div>
                @endif

            </div>
        </section>
    @endif

    @if (!empty($course->eligibility) && $course->eligibility_section === 1)
        <section id="three" class="card-hidden bg-white px-6 my-0 py-10  md:px-16 lg:px-[120px]">
            <div class="flex gap-3 items-center justify-center mb-4 ">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span
                    class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center">{{ $course->dynamicLabel->eligibility ?? 'Eligibility' }}</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>

            <div class="text-left">
                <div class="editor">
                    {!! $course->eligibility !!}
                </div>
            </div>
        </section>
    @endif

    @if (
        !empty($course->who_can_do['interested_to_learn']) &&
            !empty($course->who_can_do['designation']) &&
            $course->who_can_do_section === 1)
        <section class="card-hidden bg-[#000435] px-6 pb-0 py-8 mb-12 md:px-16 lg:px-[72px]">
            <div class="flex gap-3 items-center justify-center mb-3 ">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span
                    class="text-[20px] sm:text-[24px] md:text-[32px] font-canela text-white section-heading text-center">{{ $course->dynamicLabel->who_can_do ?? 'Who can do?' }}</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>

            <div class="card flex flex-col lg:flex-row items-center py-6 gap-x-16 gap-y-10">
                <div class="flex-1">
                    <img src="{{ isset($course->dynamicLabel) && $course->dynamicLabel->who_can_do_img ? asset($course->dynamicLabel->who_can_do_img) : asset('/admin/courses/cma2.jpeg') }}"
                        alt="" class="w-full object-cover min-h-[300px] xl:min-h-[400px]">
                </div>
                <div class="flex-1 text-white">

                    <!-- <p class="text-[16px] font-semibold mb-0">{{ $course->dynamicLabel->who_can_do_subh01 ?? 'Anyone who is interested to learn about following concepts can pursue ' . $course->title . ':' }}</p> -->
                    <div class="flex gap-3 items-center justify-center mb-0 ">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span
                            class="text-[18px] sm:text-[18px] font-canela text-white  text-center section-subheading">{{ $course->dynamicLabel->who_can_do_subh01 ?? 'anyone who is interested to learn about following concepts can pursue ' . $course->title . ':' }}</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    @if (!empty($course->who_can_do['interested_to_learn']))
                        @foreach ($course->who_can_do['interested_to_learn'] ?? [] as $index => $fields)
                            <span class="text-[16px] inline">
                                {{ $fields }}{{ $loop->last ? '.' : ',' }}
                            </span>
                        @endforeach
                    @endif
                    <!-- <p class="text-[16px] font-semibold mt-5 mb-0">{{ $course->dynamicLabel->who_can_do_subh02 ?? 'Individuals with the following occupations or designations:' }}</p> -->
                    <div class="flex gap-3 items-center justify-center mt-8 mb-0">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span
                            class="text-[18px] sm:text-[18px] font-canela text-white  text-center section-subheading">{{ $course->dynamicLabel->who_can_do_subh02 ?? 'individuals with the following designations:' }}</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    @if (!empty($course->who_can_do['designation']))
                        @foreach ($course->who_can_do['designation'] ?? [] as $index => $data)
                            <span class="text-[16px] inline">
                                {{ $data }}{{ $loop->last ? '.' : ',' }}
                            </span>
                        @endforeach
                    @endif

                </div>
            </div>
        </section>
    @endif


    <section id="four"
        class="card-hidden flex items-center justify-center flex-col py-10 px-4 bg-white min-[1200px]:px-[72px] md:px-12  ">
        <div class="flex items-center flex-col gap-2 w-full">
            @if (!$course->courseStructuresFirst->isEmpty() && $course->course_structure_section == 1)
                <div class="flex gap-3 items-center">
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                    <h3
                        class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center">
                        {{ $course->dynamicLabel->course_structure ?? 'Course structure' }}</h3>
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                </div>
                @if (!empty($course->course_structure_overview_first))
                    <div class="text-[16px] editor">
                        {!! $course->course_structure_overview_first !!}
                    </div>
                @endif
                <div
                    class="grid 
                grid-cols-1 
                @if (count($course->courseStructuresFirst) == 2) md:grid-cols-2 
                @elseif(count($course->courseStructuresFirst) == 3) md:grid-cols-3 
                @elseif(count($course->courseStructuresFirst) >= 4) md:grid-cols-4 @endif
                gap-4 gap-x-4 mt-4 w-full">
                    @foreach ($course->courseStructuresFirst ?? [] as $index => $courseStructure)
                        <div class="flex flex-col gap-0 w-full max-w-[600px] mx-auto">
                            <h1 class="text-[16px] font-semibold text-crimson leading-normal">
                                {{ $courseStructure->title . ' ' . $courseStructure->heading }}
                            </h1>
                            <div class="pb-0 editor">{!! $courseStructure->overview !!}</div>

                            <div class="flex  flex-col flex-1 gap-0 justify-start">


                                @foreach ($courseStructure->subHeadingsFirst as $index => $subHeadings)
                                    <div class="flex items-center gap-2 ">
                                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="max-w-8 h-8"
                                            alt="">
                                        <p class="text-[16px]">{{ $subHeadings->sub_heading }}</p>
                                    </div>
                                @endforeach

                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
            @if (!$course->courseStructures->isEmpty() && $course->lecture_plan_section == 1)
                <div class="flex gap-3 items-center justify-center mb-3 mt-6">
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                    <h3
                        class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center">
                        {{ $course->dynamicLabel->lecture_plan ?? 'Lecture plan' }}</h3>
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                </div>
                @if (!empty($course->course_structure_overview))
                    <div class="text-[16px] editor">
                        {!! $course->course_structure_overview !!}
                    </div>
                @endif
                <div
                    class="grid grid-cols-1 
                @if (count($course->courseStructures) == 2) md:grid-cols-2 
                @elseif(count($course->courseStructures) == 3) md:grid-cols-3
                @elseif(count($course->courseStructures) >= 4) md:grid-cols-4 @endif gap-4 gap-x-2 mt-4 w-full">
                    @foreach ($course->courseStructures ?? [] as $index => $LecturePlan)
                        <div class="flex flex-col gap-0 bg-[#f5f5f5] py-4 px-4 md:px-4 w-full max-w-[600px] mx-auto">
                            <h1 class="text-[16px] font-semibold text-crimson leading-normal">
                                {{ $LecturePlan->title . ' ' . $LecturePlan->heading }}
                            </h1>
                            <div class="pb-4">{!! $LecturePlan->overview !!}</div>

                            <div class="accordion flex flex-col gap-2 mt-0">
                                @foreach ($LecturePlan->subHeadings as $index => $subHeadings)
                                    @if ($subHeadings->subHeadingsUnits()->exists())
                                        <div class="accordion-item px-3 py-2 bg-[#ffffff]">
                                            <button
                                                class="accordion-button hover:underline text-dark flex items-center justify-between"
                                                aria-expanded="false">
                                                <p class="mb-0 text-[16px]">{{ $subHeadings->sub_heading }}
                                                    ({{ $subHeadings->subHeadingsUnits->count() }})</p>
                                            </button>
                                            <div class="accordion-content flex flex-col text-dark mt-1"
                                                aria-hidden="true">
                                                @foreach ($subHeadings->subHeadingsUnits ?? [] as $index => $subHeadingsUnit)
                                                    <div class="flex items-center gap-2">
                                                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}"
                                                            class="w-8 h-8 hidden md:block" alt="">
                                                        @if ($subHeadingsUnit->thumbnail)
                                                            <a href="{{ $subHeadingsUnit->unit_video }}" target="_blank">
                                                                <img src="{{ asset($subHeadingsUnit->thumbnail) }}"
                                                                    class="min-w-8 max-w-8 min-h-8 max-h-8"
                                                                    alt="">
                                                            </a>
                                                        @endif
                                                        <a href="{{ $subHeadingsUnit->unit_video }}" target="_blank"
                                                            class="text-[14px] md:text-[16px] leading-[18px] md:leading-normal @if ($subHeadingsUnit->unit_video) underline @endif">{{ $subHeadingsUnit->unit_title }}</a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <p class="px-3 py-2 bg-[#ffffff] text-[16px]">{{ $subHeadings->sub_heading }}</p>
                                    @endif
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

            @if ($course->custom_videos_section == 1)
                <div class="flex gap-3 items-center justify-center mb-3 mt-6">
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                    <h3
                        class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center">
                        {{ $course->dynamicLabel?->custom_videos_heading ?? 'Videos' }}</h3>
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                </div>
                @if (!empty($course->custom_videos_desc))
                    <div class="text-[16px] editor">
                        {!! $course->custom_videos_desc !!}
                    </div>
                @endif


                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 w-full">

                    @if (!empty($course->custom_videos))
                        @foreach (json_decode($course->custom_videos, true) as $index => $video)
                            <div class="video-item flex flex-col mb-4">

                                <div class="flex gap-3 items-center justify-center mb-3 mt-3">
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                    <span class="font-semibold section-subheading">{{ $video['title'] }}</span>
                                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                                </div>
                                @php

                                    $youtubeUrl = $video['path'];

                                    if (
                                        preg_match(
                                            '/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/',
                                            $youtubeUrl,
                                            $matches,
                                        )
                                    ) {
                                        $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                        // Convert to embed URL with autoplay, mute, and controls
                                        $embedUrl =
                                            'https://www.youtube.com/embed/' .
                                            $videoId .
                                            '?autoplay=1&mute=1&controls=1';
                                    } else {
                                        $embedUrl = ''; // Set empty if URL is invalid
                                    }
                                @endphp

                                @if ($embedUrl)
                                    <iframe class="w-full min-h-[300px] xl:min-h-[350px] object-cover"
                                        src="{{ $embedUrl }}" frameborder="0"
                                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                                @else
                                    <p class="text-red-500">Invalid YouTube URL</p>
                                @endif
                            </div>
                        @endforeach
                    @endif

                </div>




            @endif

        </div>
    </section>
    @if ($assignIntructors && count($assignIntructors) > 0)
        <section class="card-hidden px-6 min-[1200px]:px-[72px] mt-10 lg:pt-0 md:px-12 flex flex-col gap-16 w-full my-16">
            <div class="flex gap-3 items-center justify-center pb-4">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <h3 class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center">
                    Instructors</h3>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>

            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($assignIntructors as $instructor)
                    <div class="relative overflow-hidden group h-[500px]">
                        @if ($instructor->image)
                            <img src="{{ asset($instructor->image ?? '/images/profiles/user.png') }}"
                                alt="{{ $instructor->name }}"
                                class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
                        @endif
                        <div
                            class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                            <div class="flex items-center justify-start mt-2 gap-2">
                                <h3 class="text-[20px] sm:text-[24px] md:text-[32px] font-canela w-full text-left"
                                    style="color: #ffffff">
                                    {{ $instructor->name }}
                                </h3>
                            </div>
                            <div class="hidden group-hover:block text-white text-[18px] text-left" style="color: #ffffff">
                                @if ($instructor->experience)
                                    <p class="text-white text-[16px] mt-1">
                                        Experience: {{ $instructor->experience }}
                                    </p>
                                @endif
                                @php
                                    $educationList = explode(',', $instructor->education ?? '');
                                @endphp
                                @if (!empty($educationList[0]))
                                    <ul class="text-white text-[14px] mt-2 list-disc list-inside">
                                        @foreach ($educationList as $edu)
                                            <li>🎓 {{ trim($edu) }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-white text-[14px] mt-2">No education info</p>
                                @endif
                            </div>
                        </div>
                        <div
                            class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section id="five" class="card-hidden px-4 min-[1200px]:px-[72px] md:px-12 w-full">

        <div class="flex gap-3 items-center justify-center pb-4">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <h3 class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center">
                {{ $course->dynamicLabel->learning_methodology ?? 'Learning methodology' }}</h3>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="text-left pb-6 editor">
            {!! $course->dynamicLabel->learning_methodology_overview ?? '' !!}
        </div>
        <div class="grid grid-cols-1 place-content-center md:grid-cols-3 gap-4 gap-x-2 xl:grid-cols-3">

            <div class="relative overflow-hidden group h-[600px] bg-primary">
                <!-- <img src="{{ isset($course->dynamicLabel) && $course->dynamicLabel->practice_session_img ? $course->dynamicLabel->practice_session_img : asset('frontend/images/jpg/practice-session.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"> -->
                @if (isset($course->dynamicLabel) &&
                        $course->dynamicLabel->lectures_img &&
                        (in_array($course->dynamicLabel->lectures_img_file_type, ['jpg', 'jpeg', 'png', 'gif']) ||
                            ($course->dynamicLabel->lectures_img_file_type === null &&
                                in_array(pathinfo($course->dynamicLabel->lectures_img, PATHINFO_EXTENSION), [
                                    'jpg',
                                    'jpeg',
                                    'png',
                                    'gif',
                                ]))))
                    <img src="{{ asset($course->dynamicLabel->lectures_img) }}"
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                        alt="Lecture Image">
                    <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                        <span
                            class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">{{ $course->dynamicLabel->lectures }}</span>
                        <div class="group-hover:block text-white text-[16px]">
                            {!! $course->dynamicLabel->lectures_des !!}</div>

                    </div>
                    <div
                        class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                @elseif(isset($course->dynamicLabel) &&
                        $course->dynamicLabel->lectures_img &&
                        in_array($course->dynamicLabel->lectures_img_file_type, ['mp4', 'webm', 'ogg']))
                    <video
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                        autoplay="autoplay" muted>
                        <source src="{{ asset($course->dynamicLabel->lectures_img) }}"
                            type="video/{{ $course->dynamicLabel->lectures_img_file_type }}">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <img src="{{ asset('frontend/images/jpg/practice-session.jpg') }}"
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
                    <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                        <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Lectures</span>
                        <p class="group-hover:block text-white text-[16px]">
                            Our lecture plan integrates structured learning with interactive teaching methods, promoting
                            engagement and collaboration. This approach ensures a comprehensive understanding of concepts,
                            fostering critical thinking and practical application in real-world scenarios</p>

                    </div>
                    <div
                        class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                @endif


                <!-- <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div> -->

            </div>

            <div class="relative overflow-hidden group h-[600px] bg-primary">

                @if (isset($course->dynamicLabel) &&
                        $course->dynamicLabel->practice_session_img &&
                        (in_array($course->dynamicLabel->practice_session_img_file_type, ['jpg', 'jpeg', 'png', 'gif']) ||
                            ($course->dynamicLabel->practice_session_img_file_type === null &&
                                in_array(pathinfo($course->dynamicLabel->practice_session_img, PATHINFO_EXTENSION), [
                                    'jpg',
                                    'jpeg',
                                    'png',
                                    'gif',
                                ]))))
                    <img src="{{ asset($course->dynamicLabel->practice_session_img) }}"
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                        alt="Lecture Image">
                    <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                        <span
                            class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">{{ $course->dynamicLabel->practice_session }}</span>
                        <div class="group-hover:block text-white text-[16px]">
                            {!! $course->dynamicLabel->practice_session_des !!}</div>

                    </div>
                    <div
                        class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                @elseif(isset($course->dynamicLabel) &&
                        $course->dynamicLabel->practice_session_img &&
                        in_array($course->dynamicLabel->practice_session_img_file_type, ['mp4', 'webm', 'ogg']))
                    <video
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                        autoplay="autoplay" muted>
                        <source src="{{ asset($course->dynamicLabel->practice_session_img) }}"
                            type="video/{{ $course->dynamicLabel->practice_session_img_file_type }}">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <img src="{{ asset('frontend/images/jpg/practice-session.jpg') }}"
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
                    <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                        <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Practice
                            session</span>
                        <p
                            class="group-hover:block text-white delay-300  text-[16px] transition-all duration-400 ease-in-out">
                            Practice sessions offer hands-on experience through guided exercises, enhancing skills and
                            reinforcing knowledge. This practical approach ensures mastery of concepts, promoting confidence
                            and
                            competence in real-world applications
                        </p>
                    </div>
                    <div
                        class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                @endif

            </div>

            <div class="relative overflow-hidden group h-[600px] bg-primary">

                @if (isset($course->dynamicLabel) &&
                        $course->dynamicLabel->mock_examination_img &&
                        (in_array($course->dynamicLabel->mock_examination_img_file_type, ['jpg', 'jpeg', 'png', 'gif']) ||
                            ($course->dynamicLabel->mock_examination_img_file_type === null &&
                                in_array(pathinfo($course->dynamicLabel->mock_examination_img, PATHINFO_EXTENSION), [
                                    'jpg',
                                    'jpeg',
                                    'png',
                                    'gif',
                                ]))))
                    <img src="{{ asset($course->dynamicLabel->mock_examination_img) }}"
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                        alt="Lecture Image">
                    <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                        <span
                            class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">{{ $course->dynamicLabel->mock_examination }}</span>
                        <div class=" group-hover:block text-white text-[16px]">
                            {!! $course->dynamicLabel->mock_examination_description !!}</div>

                    </div>
                    <div
                        class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                @elseif(isset($course->dynamicLabel) &&
                        $course->dynamicLabel->mock_examination_img &&
                        in_array($course->dynamicLabel->mock_examination_img_file_type, ['mp4', 'webm', 'ogg']))
                    <video
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                        autoplay="autoplay" muted>
                        <source src="{{ asset($course->dynamicLabel->mock_examination_img) }}"
                            type="video/{{ $course->dynamicLabel->mock_examination_img_file_type }}">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <img src="{{ asset('frontend/images/jpg/MockExamination.jpg') }}"
                        class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
                    <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                        <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Mock
                            examination</span>
                        <p class="group-hover:block text-white text-[16px]">Mock examinations simulate real test
                            conditions, providing valuable practice and assessment. This helps identify strengths and
                            weaknesses, ensuring thorough preparation and boosting confidence for actual exams
                        </p>
                    </div>
                    <div
                        class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                @endif


                <!-- <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div> -->
            </div>

        </div>

    </section>

    @if ($course->performance_standard_section === 1)
        <section class="card-hidden px-6 lg:px-[72px] bg-crimson mb-8 mt-8 py-8">
            <div class="flex gap-3 items-center justify-center pb-4">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <h3 class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela section-heading text-center">
                    {{ $course->performance_standard_heading ?? "berkeley's performance standards" }}</h3>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            @if ($course->performance_standard_description)
                <div class="text-left pb-6 text-white editor">
                    {!! $course->performance_standard_description !!}
                </div>
            @else
                <div class="text-left pb-6 text-white editor">
                    <p>Evaluates and ensure the quality of the training program and all its deliverables. &nbsp;This is
                        measured through the following indicators:<br>‣ Instructors' experience and style in presenting and
                        explaining topics.<br>‣ Variety and balance of teaching methods (such as discussions, case studies,
                        mock exams and videos) used in the course to ensure retention and to match the learning
                        objectives.<br>‣ Level of interactivity.<br>‣ Feedback from program participants<br>‣ Full
                        compliance with Institute standards and guidelines for preparation and study requirements and
                        methodology.<br>‣ Progress reports from the training program provider.</p>
                </div>
            @endif
        </section>
    @endif

    <!-- hide but useful start -->
    {{-- <section class="card-hidden px-6 lg:px-[72px] bg-[#0E0E0E] mb-0 mt-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 gap-y-12 w-full py-24">
        <!-- Card 1 -->
        <div class="flex flex-col items-start gap-4 text-center">
            <img src="{{ asset('frontend/images/svgs/inperson.svg') }}" alt="" class="mx-auto">
            <h3 class="mx-auto font-bold text-[24px] lg:text-[32px] text-white whitespace-nowrap">In-person Classes</h3>
            <p class="font-semibold text-[16px] md:text-[18px] text-white flex-1">Hosted in modern, comfortable facilities, providing a collaborative and engaging learning environment.</p>
        </div>
        <!-- Card 2 -->
        <div class="flex flex-col items-start gap-4 text-center">
            <img src="{{ asset('frontend/images/svgs/onlineclass.svg') }}" alt="" class="mx-auto">
            <h3 class="mx-auto font-bold text-[24px] lg:text-[32px] text-white whitespace-nowrap">Live Online Classes</h3>
            <p class="font-semibold text-[16px] md:text-[18px] text-white flex-1">Provides an engaging experience, enabling you to attend classes from anywhere in the world.</p>
        </div>
        <!-- Card 3 -->
        <div class="flex flex-col items-start gap-4 text-center">
            <img src="{{ asset('frontend/images/svgs/packages.svg') }}" alt="" class="mx-auto">
            <h3 class="mx-auto font-bold text-[24px] lg:text-[32px] text-white whitespace-nowrap">Self-paced Packages</h3>
            <p class="font-semibold text-[16px] md:text-[18px] text-white flex-1">Offers flexible, self-paced packages, allowing you to learn at your own convenience from anywhere in the world.</p>
        </div>
    </div>
</section> --}}
    <!-- hide but useful start -->

    @if ($course->exam_section == 1)
        <section id="six"
            class="card-hidden flex items-center justify-center flex-col mt-12 bg-[#f5f5f5] px-6 pb-12 min-[1200px]:px-[72px] md:px-12  ">
            <div class="flex items-center flex-col gap-2 ">
                <div class="flex gap-3 items-center my-8">
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                    <h3
                        class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center">
                        {{ $course->dynamicLabel->exam_information ?? 'what are the exam information?' }}</h3>
                    <div class="bg-yellow w-[50px] h-[2px]"></div>
                </div>

                <div class="flex flex-col lg:flex-row-reverse items-center gap-x-12 gap-y-10">

                    <div class="flex-1">
                        @if (isset($course->dynamicLabel) && $course->dynamicLabel->exam_information_section_video_url)
                            @php

                                $youtubeUrl = $course->dynamicLabel->exam_information_section_video_url;

                                if (
                                    preg_match(
                                        '/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^\?&]+)/',
                                        $youtubeUrl,
                                        $matches,
                                    )
                                ) {
                                    $videoId = $matches[1]; // Extract the video ID from different YouTube URL formats
                                    // Convert to embed URL with autoplay, mute, and controls
                                    $embedUrl =
                                        'https://www.youtube.com/embed/' . $videoId . '?autoplay=1&mute=1&controls=1';
                                } else {
                                    $embedUrl = '';
                                }
                            @endphp

                            @if ($embedUrl)
                                <iframe class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover"
                                    src="{{ $embedUrl }}" frameborder="0"
                                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                            @else
                                <p>Invalid YouTube URL</p>
                            @endif
                        @elseif(isset($course->dynamicLabel) && $course->dynamicLabel->exam_information_section_img)
                            <img src="{{ asset($course->dynamicLabel->exam_information_section_img) }}" alt=""
                                class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
                        @else
                            <img src="{{ asset('admin/courses/cma1.jpeg') }}" alt=""
                                class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
                        @endif
                        <!-- <img src="{{ isset($course->dynamicLabel) && $course->dynamicLabel->exam_information_section_img ? asset($course->dynamicLabel->exam_information_section_img) : asset('admin/courses/cma1.jpeg') }}" alt=""
                            class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover"> -->
                    </div>
                    <div class="flex-1">
                        <div class="editor pb-4">
                            {!! $course->exam_info_custom_01 !!}
                        </div>
                        @php

                            $allEmpty = true;
                            foreach ($course->courseStructuresFirst as $courseStructure) {
                                if (!empty($courseStructure->exam_format) || !empty($courseStructure->exam_duration)) {
                                    $allEmpty = false;
                                    break;
                                }
                            }

                        @endphp
                        @if (
                            !$allEmpty ||
                                (!empty($course->course_exam_format_duration_overview) &&
                                    $course->course_exam_format_duration_overview !== null))
                            <div class="flex gap-3 items-center justify-center">
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                                <span
                                    class="font-semibold section-subheading text-center">{{ $course->dynamicLabel->exam_format_duration ?? 'Exam format & duration' }}</span>
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                            </div>
                            <div class="text-[16px] mt-4 editor">
                                {!! $course->course_exam_format_duration_overview !!}
                            </div>


                            @if (!$allEmpty)
                                <table class=" bg-white border border-gray-200 mt-2">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="p-2 border-b">
                                                {{ $course->dynamicLabel->exam_format_duration_01 ?? 'Part/Module' }}</th>
                                            <th class="p-2 border-b">
                                                {{ $course->dynamicLabel->exam_format_duration_02 ?? 'Exam format' }}</th>
                                            <th class="p-2 border-b">
                                                {{ $course->dynamicLabel->exam_format_duration_03 ?? 'Exam duration' }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($course->courseStructuresFirst ?? [] as $index => $courseStructure)
                                            @if (empty($courseStructure->exam_format) && empty($courseStructure->exam_duration))
                                                @continue
                                            @endif
                                            <tr class="bg-gray-50 text-[16px]">
                                                <td class="py-2 px-4 border-b ">
                                                    <div class="text-[16px]">
                                                        {{ $courseStructure->title . ' ' . $courseStructure->heading }}</div>
                                                </td>
                                                <td class="py-2 px-4 border-b">
                                                    <div class="text-[16px] editor">{!! $courseStructure->exam_format !!}</div>
                                                </td>
                                                <td class="py-2 px-4 border-b">
                                                    <div class="text-[16px] editor">{!! $courseStructure->exam_duration !!}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        @endif
                        @if (!empty($course->exam_dates))
                            <div class="flex gap-3 items-center justify-center  mt-10">
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                                <span
                                    class="font-semibold section-subheading">{{ $course->dynamicLabel->exam_dates ?? 'exam dates' }}</span>
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                            </div>
                            <div class="text-[16px] pt-4 editor">{!! $course->exam_dates !!}</div>
                        @endif

                        @if (!empty($course->exam_reg_deadline))
                            <div class="flex gap-3 items-center justify-center  mt-10">
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                                <span
                                    class="font-semibold section-subheading">{{ $course->dynamicLabel->exam_dates_registration ?? 'Exam registration deadline' }}</span>
                                <div class="bg-yellow w-[50px] h-[2px]"></div>
                            </div>
                            <div class="text-[16px] pt-4 editor">{!! $course->exam_reg_deadline !!}</div>
                        @endif

                    </div>

                </div>


                @if (!empty($course->exam_passing_criteria))
                    <div class="flex gap-3 items-center mt-8">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span
                            class="font-semibold section-subheading">{{ $course->dynamicLabel->passing_criteria ?? 'Passing criteria' }}</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    <span class="text-[16px] mt-0 mb-4 editor">{!! $course->exam_passing_criteria !!}</span>
                @endif

                @if (!empty($course->exam_location_paragraph) || !empty($course->exam_location))
                    <div class="flex gap-3 items-center pt-[16px]">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span
                            class="font-semibold section-subheading">{{ $course->dynamicLabel->exam_location ?? 'Exam locations' }}</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>

                    <div class="text-[16px] mb-2 editor">
                        {!! $course->exam_location_paragraph !!}
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 xl:grid-cols-6 gap-y-4 w-full">
                        @foreach ($course->exam_location ?? [] as $index => $data)
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                <span class="text-[16px] max-w-[420px]">{{ $data }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>


        </section>
    @endif

    @if (!$course->courseFeePackages->isEmpty() && $course->fee_visibility == 1)
        <section id="eight" class="flex flex-col items-center bg-[#000435] py-12 px-6 sm:px-12 lg:px-16">

            <!-- Section Heading -->
            <div class="flex flex-col items-center gap-2 mb-10">
                <div class="flex gap-3 items-center">
                    <div class="bg-yellow w-12 h-[2px]"></div>
                    <span class="text-[22px] sm:text-[28px] md:text-[34px] text-white font-canela tracking-wide">
                        {{ $course->dynamicLabel?->fee_strucutre ?? 'Fee Structure' }}
                    </span>
                    <div class="bg-yellow w-12 h-[2px]"></div>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 w-full max-w-[1400px]">

                @foreach ($course->courseFeePackages as $index => $package)
                    @if ($package->showonwebsite == 1 && $package->currency == 'AED')
                        <div
                            class="relative group bg-white flex flex-col p-6 text-center rounded-2xl shadow-lg border border-gray-100 
                        hover:shadow-2xl hover:border-yellow transition duration-300 ease-in-out w-full max-w-sm mx-auto">

                            <!-- Package Title -->
                            <h3 class="mb-3 text-2xl font-bold text-[#000435] min-h-[50px]">
                                {{ $package->package_name }}
                            </h3>

                            <!-- Price -->
                            <div class="flex justify-center items-baseline mb-2">
                                <span class="text-4xl sm:text-5xl font-extrabold text-[#000435] leading-tight">
                                    AED {{ $package->price }}
                                </span>
                            </div>

                            <!-- Short Description -->
                            @if ($package->short_description)
                                <p class="text-gray-600 mb-4 leading-relaxed text-sm sm:text-base">
                                    {{ $package->short_description }}
                                </p>
                            @endif

                            @if ($package->key_point)
                                <div class="mb-4">
                                    @foreach (explode(',', $package->key_point) as $point)
                                        <span
                                            class="bg-yellow text-[#000435] text-xs sm:text-sm font-bold py-1 px-3 rounded-full shadow-md">{{ $point }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Divider -->
                            <div class="w-full h-[2px] bg-yellow mb-3"></div>

                            <!-- Features -->
                            @if ($package->package_feature)
                                <ul class="mb-6 space-y-3 text-left px-2">
                                    @foreach ($package->package_feature as $data)
                                        <li class="flex items-start space-x-2">
                                            <img src="{{ asset('frontend/images/svgs/tick.svg') }}"
                                                class="w-5 h-5 mt-0.5" alt="">
                                            <span
                                                class="text-gray-700 text-sm sm:text-base leading-snug">{{ $data }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <!-- CTA Button -->
                            <form method="POST" action="{{ route('cart.create') }}" class="mt-auto">
                                @csrf
                                <input type="hidden" name="package_id" value="{{ $package->id }}">
                                <button type="submit"
                                    class="w-full py-3 bg-[#000435] text-white font-semibold rounded-xl 
                                    hover:bg-yellow hover:text-[#000435] transition duration-300 transform hover:scale-105">
                                    Add To Cart
                                </button>
                            </form>
                        </div>
                    @endif
                @endforeach

            </div>

        </section>
    @endif

    @if ($course->success_stories == 1)
        <section id="nine" class="card-hidden px-6 min-[1200px]:px-[72px] mb-8 mt-8  show">
            <div class="card grid grid-cols-1 sm:grid-cols-2 gap-10">
                <div class="relative overflow-hidden group min-h-[500px] rounded-xl bg-primary">
                    <img src="{{ isset($course->dynamicLabel) && $course->dynamicLabel->learner_stories_img
                        ? asset($course->dynamicLabel->learner_stories_img)
                        : asset('frontend/images/jpg/sheikh.jpg') }}"
                        class="h-full w-full absolute object-cover transition-all duration-500 ease-in-out group-hover:scale-105"
                        alt="">

                    <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black"></div>

                    <div class="absolute p-8 z-50 flex flex-col justify-end h-full w-full">

                        <span class="font-canela text-white text-[26px] md:text-[36px] lg:text-[44px]">
                            {{ $course->dynamicLabel->success_stories ?? 'Success Stories' }}
                        </span>

                        <p class="font-semibold text-white text-[18px] mt-3">
                            {{ strip_tags($course->dynamicLabel->alumni_benefits) }}
                        </p>
                        <a href="{{ $course->dynamicLabel->success_stories_link ?? 'javascript:void(0)' }}" class="flex items-center gap-2 mt-4" target="_blank">
                            <div
                                class="flex justify-center items-center rounded-full bg-white/20 backdrop-blur-md min-h-10 min-w-10 group-hover:bg-secondary transition">
                                <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}"
                                    class="w-[28px] h-4 invert">
                            </div>

                            <h2 class="font-bold text-[18px] text-white">
                                {{ $course->dynamicLabel->success_stories_link_text ?? 'Success Stories Link' }}
                            </h2>
                        </a>

                    </div>
                </div>

                <div class="relative flex-1 gap-2 flex flex-col">
                    @if (empty($course->alumni_benefits_description))
                        <div class="flex flex-col flex-1 gap-3 justify-start mt-2">
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="max-w-8 h-8"
                                    alt="" />
                                <p>Exclusive Networking Events: Access invitations to industry-leading events and
                                    thought-leadership gatherings featuring renowned speakers.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="max-w-8 h-8"
                                    alt="" />
                                <p>Monthly Updates: Stay informed with a newsletter highlighting the latest research,
                                    events, and activities from the school.</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="max-w-8 h-8"
                                    alt="" />
                                <p>LinkedIn Community Access: Join the Executive Education LinkedIn group for networking and
                                    professional development opportunities.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="max-w-8 h-8"
                                    alt="" />
                                <p>Educational Discounts: Enjoy a 20% discount on open-enrollment programs and access to
                                    workshops focused on emerging trends.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="max-w-8 h-8"
                                    alt="" />
                                <p>Global Alumni Network: Connect with a diverse alumni community through the Berkeley
                                    School’s online network and engage in country and interest groups.</p>
                            </div>
                        </div>
                    @else
                        <div class="editor">
                            {!! $course->alumni_benefits_description !!}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif


    @if ($course->career_path_section == 1)
        <section class="card-hidden px-6  lg:px-[48px]  mb-8 show">
            <div class="flex flex-col gap-5 xl:gap-0 lg:flex-row items-stretch  gap-y-10 min-h-[645px]">

                <div class="relative flex justify-center items-center py-4 xl:bg-[#f0f6ff] w-full basis-full">
                    <div
                        class="flex-1 xl:px-8  bg-white w-full xl:py-2  min-[1340px]:py-8 my-2 xl:absolute xl:-right-20 gap-2 flex flex-col">

                        <div class="flex gap-3 items-center">
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                            <h3
                                class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading text-center sm:text-left">
                                {{ $course->dynamicLabel->career_path_heading ?? 'how the ' . $course->title . ' serves your career path' }}
                            </h3>
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <p class="text-[16px] mb-0 editor">{!! $course->career_path !!}</p>
                        </div>
                    </div>
                </div>

                <div class="flex-1 w-full basis-full">
                    <img src="{{ isset($course->dynamicLabel) && $course->dynamicLabel->career_path_section_img ? asset($course->dynamicLabel->career_path_section_img) : asset('admin/courses/cma2.jpeg') }}"
                        alt="" class="w-full h-full object-cover">
                </div>
            </div>
        </section>
    @endif

    @if ($course->custom_section_01 == 1)
        <section class="card-hidden px-6 lg:px-[72px] bg-crimson mb-0 mt-8 py-8">
            <div class="flex gap-3 items-center justify-center pb-4">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <h3 class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela section-heading text-center">
                    {{ $course->dynamicLabel->custom_section_01 ?? 'custom section 01' }}</h3>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>

            <div class="text-left pb-6 text-white editor">
                {!! $course->custom_section_01_description !!}
            </div>

        </section>
    @endif

    @if ($course->benefits_section == 1)
        <section id="ten"
            class="card-hidden flex items-center justify-center flex-col py-12  bg-[#f5f5f5] px-4 md:px-8 lg:px-[140px]">
            <div class="flex gap-3 items-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <h3 class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading">
                    {{ $course->dynamicLabel->what_you_earn ?? 'what you earn' }}
                </h3>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <p class="text-[16px] mt-3">
                {!! $course->dynamicLabel->what_you_earn_des ??
                    'You will get a certificate of completion, which is highly reputed and accepted by
                            employers.' !!}</p>
            <div class="max-w-[900px]">
                <div class="flex flex-col lg:flex-row gap-16 pt-6">
                    <div class="flex items-center justify-center md:justify-start">
                        <img src="{{ isset($course->dynamicLabel) && $course->dynamicLabel->what_you_earn_img ? asset($course->dynamicLabel->what_you_earn_img) : asset('frontend/images/jpg/Berkeley-Square-School-of-Arts-Certificate.jpg') }}"
                            alt=""
                            class="w-[275px] h-[350px] transition-all hover:scale-110 ease-in duration-200 delay-100">
                    </div>

                    <div class="flex-1">
                        <div class="flex flex-col gap-2">
                            @foreach ($course->benifits ?? [] as $index => $data)
                                <div class="flex flex-row justify-between gap-4">
                                    <div class="flex gap-2 flex-col">
                                        <div class="flex gap-1 flex-col">
                                            <h3 class="font-semibold">{!! $data['title'] !!}</h3>
                                            <div class="w-[80px] h-[2px] bg-yellow"></div>
                                            <div class="text-[16px] editor">{!! $data['description'] !!}</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="w-full bg-primary h-[1px]"></div> -->
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>


        </section>
    @endif

    @if (!$course->relatedCourses->isEmpty())
        <section class="card-hidden px-4 bg-[#eeeeee]   py-[44px] min-[1200px]:px-[72px]">

            <div class="flex gap-3 items-center w-full justify-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <h3 class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela section-heading">
                    {{ $course->dynamicLabel?->related_courses ?? 'Related courses' }}</h3>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>

            <div
                class="grid grid-cols-1  w-full min-[550px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 place-content-between pt-8">

                @foreach ($course->relatedCourses as $key => $related_course)
                    <div class="flex flex-col row-span-1  w-full  border rounded overflow-hidden bg-white p-[20px]">

                        <div class="flex flex-col gap-2 ">
                            <a href="{{ route('course.details', $related_course->slug) }}"
                                class=" text-[20px] capitalize min-h-[52px]"
                                target="_blank">{{ $related_course->title }}</a>
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                            <div class="text-[16px] min-h-[195px] max-h-[195px]" style="overflow:hidden;">
                                {!! $related_course->short_description !!}</div>
                            <a href="{{ route('course.details', $related_course->slug) }}"
                                class="mt-auto w-full gap-2 shadow capitalize p-2 bg-[#000435] text-white text-center"
                                target="_blank">Read More</a>
                        </div>


                    </div>
                @endforeach

            </div>

        </section>
    @endif

    @if (!$course->courseFaq->isEmpty() && $course->faq_section == 1)
        <section id="eleven"
            class="card-hidden flex flex-col px-4 bg-[#000435] py-[44px] items-center min-[1200px]:px-[72px]" style="margin-bottom: 0px !important;">
            <div class="max-w-[900px]">
                <div class="flex justify-between border-white border-b pb-3 w-full">
                    <h1
                        class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela section-heading text-center">
                        {{ $course->dynamicLabel?->faq_heading ?? 'FAQ: ' . $course->title }}</h1>
                    <!-- <a href="#" class="text-[24px] text-white hover:underline">All FAQs</a> -->
                </div>
                <div class="accordion flex flex-col gap-3">

                    <div class="accordion flex flex-col gap-3 mt-4">
                        @foreach ($course->courseFaq as $index => $courseFaq)
                            <div class="accordion-item py-4 px-5 bg-[#ffffff] ">
                                <button class="accordion-button hover:underline text-dark text-[14px] md:text-[16px]"
                                    aria-expanded="false">
                                    {{ $courseFaq->title }}
                                </button>
                                <div class="accordion-content flex flex-col text-dark mt-1 text-[16px] editor"
                                    aria-hidden="true">
                                    {!! $courseFaq->short_description !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </section>
    @endif

    @if ($course->contact_us_section == 1)
        <section id="apply"
            class="card-hidden flex flex-col px-4 bg-[#e7a70b] py-[44px] items-center min-[1200px]:px-[72px]" style="margin-bottom: 0px !important;">
            <div class="max-w-[845px]">
                <h1 class="text-dark text-[18px] sm:text-[20px] md:text-[28px] section-heading text-center">
                    {!! $course->contact_us_text ??
                        'contact us for more information or to apply for admission. Seats fill up quickly, so we encourage early registration!' !!}
                </h1>
            </div>
        </section>

        {!! $course->reg_iframe !!}
    @endif



@endsection

@push('script')
    <script>
        // Card scrolling start
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const packageId = this.dataset.packageId;

                    fetch('/cart/add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                package_id: packageId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Package added to cart!');
                                window.location.href = '/cart';
                            }
                        });
                });
            });
        });
    </script>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                const headerOffset = 125; // Adjust this value as needed
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            });
        });
    </script>
    <!-- vidoe section
    lecture plan 1/0
    image or youtube -->
@endpush