@extends('layouts.app')
@push('style')
    <style>
        .hideme {
            display: none
        }

        
        html {
  scroll-behavior: smooth;
}
    </style>
@endpush

@section('content')

<!-- Add Slider Here start -->
<section class="flex flex-col-reverse justify-between relative min-h-[500px] md:max-h-[500px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex ">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Course</span>
            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">{{ $course->title }}</span>

        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[24px] leading-8 font-canela text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[32px] md:text-[36px] leading-[58px] font-canela text-white">{{ $course->title }}</h2>
        </div>
        <p clas="text-[18px] text-white">{!! $course->short_description !!}
        </p>
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
        <img src="{{ asset('frontend/images/jpg/60.jpg') }}" alt=""
            class="object-cover h-full md:max-h-full md:w-full md:h-full">
    </div>
</section>
@php
        $filteredCourseStructureExamFormat = $course->courseStructures->filter(function($structure) {
            return !empty($structure->exam_format);
        });

        $filteredCourseExamDuration = $course->courseStructures->filter(function($structure) {
            return !empty($structure->exam_duration);
        });
@endphp
<section
    class="flex sticky top-[69px] z-[99] lg:top-[83px] items-center px-4 md:px-8 lg:px-[120px] gap-3 md:gap-[120px] my-0 py-0 mt-4 bg-[#000435] hidden lg:block">
    <div class="flex justify-center flex-1 font-ghothic text-[15px] gap-2 md:gap-6 items-center text-white">
        @if( !empty($course->description) )
        <a href="#one" class="underline decoration-crimson hidden sm:block">Overview</a>
        @endif
        <!-- <a href="#two">Who Can Do</a> -->
        @if( !empty($course->eligibility) )
        <a href="#three">Eligibility</a>
        @endif
        @if(!$course->courseStructures->isEmpty())
        <a href="#four">Course Structure</a>
        @endif
        <!-- <a href="#five">Lecture Plan</a> -->
        @if($filteredCourseStructureExamFormat->isNotEmpty() && $filteredCourseExamDuration->isNotEmpty())
        <a href="#six">Examination</a>
        @endif
        <!-- <a href="#seven">Faculty</a> -->
        @if(!$course->feePackages->isEmpty())
        <a href="#eight">Fee</a>
        @endif
        <a href="#nine">Stories
        </a>
        <a href="#ten">Certificate</a>
        @if(!$course->courseFaq->isEmpty())
        <a href="#eleven">FAQs</a>
        @endif
        <button
            class="shrink-0 px-6 py-2 inline-flex gap-1 items-center font-ghothic text-[13px]  bg-crimson  text-white hover:bg-primary ">
            Apply Now
            <img src="{{ asset('frontend/images/svgs/caret-right.svg') }}" class="w-8 h-8" alt="">
        </button>
    </div>



</section>

<section id="one"
    class="bg-[#f4f4f4] px-6 my-0 py-10  md:px-16 lg:px-[120px]">
    @if( !empty($course->description) )
    <div class="flex flex-col pb-4">
        <div class="flex gap-3 items-center justify-center mb-6 ">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela">Overview</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <!-- <div class="flex flex-col items-center">
                {!! $course->description !!}
        </div> -->
        <div class="flex flex-col lg:flex-row-reverse items-center gap-x-24 gap-y-10">

            <div class="flex-1">
                <img src="{{ asset($course->thumbnail) }}" alt=""
                    class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
            </div>

            <div class="flex-1 font-semibold">
                {!! $course->description !!}

                @if(!empty($course->offered_by['institute']))
                <div class="flex flex-col pb-4">
                    <div class="flex gap-3 items-center justify-center mb-3 mt-3">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span class="font-semibold uppercase">Offered By</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    <div class="flex flex-col items-center">
                            {!! optional($course->offered_by)['institute'] !!}
                    </div>
                </div>
                @endif

                @if(!empty($course->offered_by['head_office']))
                    <div class="flex flex-col pb-4">
                        <div class="flex gap-3 items-center justify-center mb-3 ">
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                            <span class="font-semibold uppercase">Head Office</span>
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                        </div>
                        <div class="flex flex-col items-center">
                                {!! optional($course->offered_by)['head_office'] !!}
                        </div>
                    </div>
                @endif
                @if(!empty($course->offered_by['members']))
                    <div class="flex flex-col pb-4">
                        <div class="flex gap-3 items-center justify-center mb-3 ">
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                            <span class="font-semibold uppercase">Members</span>
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                        </div>
                        <div class="flex flex-col items-center">
                                {!! optional($course->offered_by)['members'] !!}
                        </div>
                    </div>
                @endif
                @if(!empty($course->offered_by['founded_in']))
                    <div class="flex gap-3 items-center justify-center mb-3 ">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span class="font-semibold uppercase">Founded In</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    <div class="flex flex-col items-center">
                            {!! optional($course->offered_by)['founded_in'] !!}
                    </div>
                @endif

                

            </div>

            

            
        </div>

        @if( !empty($course->vision_and_mission) )
            <div class="flex gap-3 items-center justify-center my-3 ">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">Vision & Mission</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <div class="flex flex-col items-center font-semibold">
                    {!! $course->vision_and_mission !!}
            </div>
        @endif

    </div>
    @endif
    {{--@if(!empty($course->offered_by['institute']))
    <div class="flex flex-col pb-4">
        <div class="flex gap-3 items-center justify-center mb-3 mt-3">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Offered By</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex flex-col items-center">
                {!! optional($course->offered_by)['institute'] !!}
        </div>
    </div>
    @endif 
    @if(!empty($course->offered_by['head_office']))
    <div class="flex flex-col pb-4">
        <div class="flex gap-3 items-center justify-center mb-3 ">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Head Office</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex flex-col items-center">
                {!! optional($course->offered_by)['head_office'] !!}
        </div>
    </div>
    @endif
    @if(!empty($course->offered_by['members']))
    <div class="flex flex-col pb-4">
        <div class="flex gap-3 items-center justify-center mb-3 ">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Members</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex flex-col items-center">
                {!! optional($course->offered_by)['members'] !!}
        </div>
    </div>
    @endif
    @if(!empty($course->offered_by['founded_in']))
    <div class="flex gap-3 items-center justify-center mb-3 ">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Founded In</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex flex-col items-center">
            {!! optional($course->offered_by)['founded_in'] !!}
    </div>
    @endif--}}
</section>
{{--@if( !empty($course->vision_and_mission) )
<section  class="bg-white px-6 my-0 pt-10  md:px-16 lg:px-[120px]">
    <div class="flex gap-3 items-center justify-center mb-3 ">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Vision & Mission</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex flex-col items-center">
            {!! $course->vision_and_mission !!}
    </div>
</section>
@endif--}}
{{--<section  class="bg-[#f4f4f4] px-6 my-0 pt-10  md:px-16 lg:px-[120px]">
    @foreach($course->benifits ?? [] as $index => $data)
        <div class="flex gap-3 items-center justify-center mb-3 ">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">{!! $data['title'] !!}</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex flex-col items-center pb-10">
            {!! $data['description'] !!}
        </div>
    @endforeach
</section>--}}
@if( !empty($course->eligibility) )
    <section id="three"  class="bg-white px-6 my-0 py-10  md:px-16 lg:px-[120px]">
        <div class="flex gap-3 items-center justify-center mb-4 ">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela capitalize">Eligibility</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex gap-3 items-center justify-center mb-3 ">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">WHO IS ELIGIBILE TO REGISTER FOR EXAMAMINATION?</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex flex-col items-center">
                {!! $course->eligibility !!}
        </div>
    </section>
@endif
@if(!empty($course->who_can_do['interested_to_learn']) && !empty($course->who_can_do['designation']) )
<section  class="bg-[#f5f5f5] px-6 pb-10 py-8 mb-12 md:px-16 lg:px-[120px]">
    <div class="flex gap-3 items-center justify-center mb-3 ">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela capitalize">Who can do?</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    
    <div class="card flex flex-col lg:flex-row items-center py-6 gap-x-24 gap-y-10">
            <div class="flex-1">
                <img src="{{ asset('/admin/courses/cma2.jpeg') }}" alt="" class="w-full object-cover min-h-[300px] xl:min-h-[400px]">
            </div>
            <div class="flex-1">
                
                <p class="text-[18px] font-semibold">Anyone who is interested to learn about following concepts can pursue {{ $course->title }}.</p>
                @if(!empty($course->who_can_do['interested_to_learn']))
                    @foreach($course->who_can_do['interested_to_learn'] ?? [] as $index => $fields)
                        <p class="text-[18px] inline ">{{ $fields}}</p>
                        @if(!$loop->last), @endif
                        @if($loop->last). @endif
                    @endforeach
                @endif
                <p class="text-[18px] font-semibold">Individuals with the following occupations or designations can also pursue.</p>
                @if(!empty($course->who_can_do['designation']))
                    @foreach($course->who_can_do['designation'] ?? [] as $index => $data)
                        <p class="text-[18px] inline">{{ $data }}</p>
                        @if(!$loop->last), @endif
                    @endforeach
                @endif
                
            </div>
        </div>
</section>
@endif
@if(!$course->courseStructures->isEmpty())
<section id="four"
    class="flex items-center justify-center flex-col pb-10 px-0 bg-white min-[1200px]:px-[72px] md:px-12  ">
    <div class="flex items-center flex-col gap-2 w-full">
        <div class="flex gap-3 items-center">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela capitalize">Course Structure (Lecture Plan)</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 gap-x-8 mt-4 w-full">
            @foreach($course->courseStructures ?? [] as $index => $courseStructure)
                <div class="flex flex-col gap-2 bg-[#f5f5f5] py-8 px-0 md:px-8">
                    <h1 class="text-[18px] font-semibold">
                    <span class="text-[18px] font-semibold text-crimson">{{ $courseStructure->title }}</span> 
                    {{ $courseStructure->heading }}</h1>
                    <div class="accordion flex flex-col gap-2 mt-0">
                        @foreach($courseStructure->subHeadings as $index => $subHeadings)
                        <!-- <p>{{ $subHeadings->sub_heading }}</p> -->
                            @if($subHeadings->subHeadingsUnits()->exists())
                            <div class="accordion-item px-3 py-2 bg-[#ffffff] ">
                                <button class="accordion-button hover:underline text-dark" aria-expanded="false">
                                    {{ $subHeadings->sub_heading }} ({{ $subHeadings->subHeadingsUnits->count() }})
                                </button>
                                <div class="accordion-content flex flex-col text-dark mt-1" aria-hidden="true">
                                    
                                    @foreach($subHeadings->subHeadingsUnits ?? [] as $index => $subHeadingsUnit)
                                        <!-- <p class="font-[14px] p-2 leading-5">{{ $subHeadingsUnit->unit_title }}</p> -->
                                        <div class="flex items-center gap-2">
                                            <img src="{{asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                            <a href="{{ $subHeadingsUnit->unit_video }}" target="_blank" class="text-[16px] hover:underline">{{ $subHeadingsUnit->unit_title }}</a>
                                        </div>
                                    @endforeach
                                    
                                </div>
                            </div>
                            @else
                                <p>{{ $subHeadings->sub_heading }}</p>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- <section class="card-hidden px-6 min-[1200px]:px-[72px] bg-[#0E0E0E] mb-8 mt-0 show">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 gap-x-8 w-full">
        <div class="flex flex-row items-start gap-4">
            <h3 class="font-bold text-[18px] md:text-[32px] text-white whitespace-nowrap">In-person Classes</h3>
            <p class="font-semibold text-[18px] text-white flex-1 whitespace-normal">Hosted in modern, comfortable facilities, providing a collaborative and engaging learning environment.</p>
        </div>
        <div class="flex flex-row items-start gap-4">
            <h3 class="font-bold text-[18px] md:text-[32px] text-white whitespace-nowrap">Live Online Classes</h3>
            <p class="font-semibold text-[18px] text-white flex-1 whitespace-normal">Provides an engaging experience, enabling you to attend classes from anywhere in the world.</p>
        </div>
        <div class="flex flex-row items-start gap-4">
            <h3 class="font-bold text-[18px] md:text-[32px] text-white whitespace-nowrap">Self-paced Packages</h3>
            <p class="font-semibold text-[18px] text-white flex-1 whitespace-normal">Offers flexible, self-paced packages, allowing you to learn at your own convenience from anywhere in the world.</p>
        </div>
    </div> 
    
</section>-->



<section id="five" class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 w-full">
    <!-- <div class="flex items-center flex-col gap-2 ">
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela capitalize">Lecture Plan</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
    </div> -->
    <div class="grid grid-cols-1 place-content-center md:grid-cols-3 gap-10 xl:grid-cols-3">
        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset('frontend/images/jpg/QualifiedEducators.jpg') }}"
                class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

            <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Lectures</span>
                <p class="font-semibold  group-hover:block text-white text-[16px]">
                    Our lecture plan integrates structured learning with interactive teaching methods, promoting
                    engagement and collaboration. This approach ensures a comprehensive understanding of concepts,
                    fostering critical thinking and practical application in real-world scenarios.</p>
                
            </div>

            <div
                class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
            </div>

        </div>

        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset('frontend/images/jpg/practice-session.jpg') }}"
                class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

            <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Practice Session</span>
                <p
                    class="font-semibold group-hover:block text-white delay-300  text-[16px] transition-all duration-400 ease-in-out">
                    Practice sessions offer hands-on experience through guided exercises, enhancing skills and
                    reinforcing knowledge. This practical approach ensures mastery of concepts, promoting confidence and
                    competence in real-world applications.
                </p>
                
            </div>

            <div
                class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
            </div>
        </div>

        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset('frontend/images/jpg/MockExamination.jpg') }}"
                class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

            <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Mock Examination</span>
                <p class="font-semibold group-hover:block text-white text-[16px]">Mock examinations simulate real test
                    conditions, providing valuable practice and assessment. This helps identify strengths and
                    weaknesses, ensuring thorough preparation and boosting confidence for actual exams.
                </p>
                
            </div>

            <div
                class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
            </div>
        </div>

    </div>

</section>


@if($filteredCourseStructureExamFormat->isNotEmpty() && $filteredCourseExamDuration->isNotEmpty())
    <section id="six"
        class="flex items-center justify-center flex-col mt-12 bg-[#f5f5f5] px-6 pb-12 min-[1200px]:px-[72px] md:px-12  ">
        <div class="flex items-center flex-col gap-2 ">
            <div class="flex gap-3 items-center my-8">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela capitalize">What Are The Exam Information?</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>

            <div class="flex flex-col lg:flex-row-reverse items-center gap-x-12 gap-y-10">

                <div class="flex-1">
                    <img src="{{ asset('admin/courses/cma1.jpeg') }}" alt=""
                        class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
                </div>
                <div class="flex-1">
                    <div class="flex gap-3 items-center justify-center">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span class="font-semibold uppercase">Exam Format & Duration</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>

                    <table class=" bg-white border border-gray-200 mt-6">
                        <thead>
                            <tr class="bg-gray-50 font-semibold">
                            <th class="py-2 px-4 border-b ">Part/Module</th>
                            <th class="py-2 px-4 border-b">Exam Format</th>
                            <th class="py-2 px-4 border-b">Exam Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($course->courseStructures ?? [] as $index => $courseStructure)
                            <tr class="bg-gray-50 text-[16px]">
                                <td class="py-2 px-4 border-b ">{{ $courseStructure->title }}</td>
                                <td class="py-2 px-4 border-b">{{ $courseStructure->exam_format }}</td>
                                <td class="py-2 px-4 border-b">{{ $courseStructure->exam_duration }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if(!empty($course->exam_dates))
                    <div class="flex gap-3 items-center justify-center  mt-10">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span class="font-semibold uppercase">exam dates</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    <div class="text-[18px] pt-4">{!! $course->exam_dates  !!}</div>
                    @endif

                    @if(!empty($course->exam_reg_deadline))
                    <div class="flex gap-3 items-center justify-center  mt-10">
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                        <span class="font-semibold uppercase">Exam Registration Deadline</span>
                        <div class="bg-yellow w-[50px] h-[2px]"></div>
                    </div>
                    <div class="text-[18px] pt-4">{!! $course->exam_reg_deadline  !!}</div>
                    @endif

                </div>

            </div>
            {{--@if($filteredCourseStructureExamFormat->isNotEmpty())
            <div class="flex gap-3 items-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">Exam Format</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            
            <div class="flex flex-row gap-4 gap-y-2 gap-x-16 my-4">
                @foreach($course->courseStructures ?? [] as $index => $courseStructure)
                    <div class="flex flex-col gap-2">
                        <h1 class="text-[16px] font-semibold">
                        <span class="text-[16px] font-semibold text-crimson">{{ $courseStructure->title }}</span> 
                        {{ $courseStructure->exam_format }}</h1>
                        
                    </div>
                @endforeach
                
            </div>
            @endif
            
            @if($filteredCourseExamDuration->isNotEmpty())
            <div class="flex gap-3 items-center ">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">Exam Duration</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <div class="flex flex-row gap-4 gap-y-2 gap-x-16 my-4">
                @foreach($course->courseStructures ?? [] as $index => $courseStructure)
                    <div class="flex flex-col gap-2">
                        <h1 class="text-[16px] font-semibold">
                        <span class="text-[16px] font-semibold text-crimson">{{ $courseStructure->title }}</span> 
                        {{ $courseStructure->exam_duration }}</h1>
                        
                    </div>
                @endforeach
                
            </div>
            @endif
            
            @if(!empty($course->exam_dates))
            <div class="flex gap-3 items-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">exam dates</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <span class="text-[18px] my-4">{!! $course->exam_dates  !!}</span>
            @endif
            @if(!empty($course->exam_reg_deadline))
            <div class="flex gap-3 items-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">Exam Registration Deadline</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <span class="text-[18px] my-4">{!! $course->exam_reg_deadline  !!}</span>
            @endif
            @if(!empty($course->exam_passing_criteria))
            <div class="flex gap-3 items-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">Passing Criteria</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <span class="text-[18px] my-4">{!! $course->exam_passing_criteria  !!}</span>
            @endif
            @if(!empty($course->exam_location))
            <div class="flex gap-3 items-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">EXAM LOCATIONS</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3  xl:grid-cols-5 gap-y-0">
                @foreach($course->exam_location ?? [] as $index => $data)
                    <div class="flex items-center gap-0">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                        <span class="text-[17px] max-w-[420px]">{{ $data }}</span>
                    </div>
                @endforeach
            </div>
            @endif--}}

            @if(!empty($course->exam_passing_criteria))
            <div class="flex gap-3 items-center mt-8">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">Passing Criteria</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <span class="text-[18px] my-4">{!! $course->exam_passing_criteria  !!}</span>
            @endif
            @if(!empty($course->exam_location))
            <div class="flex gap-3 items-center">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">EXAM LOCATIONS</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-y-4">
                @foreach($course->exam_location ?? [] as $index => $data)
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                        <span class="text-[17px] max-w-[420px]">{{ $data }}</span>
                    </div>
                @endforeach
            </div>
            @endif

        </div>


    </section>
@endif
@if(!$course->feePackages->isEmpty())
<section id="eight" class="flex flex-col  bg-[#eeeeee] py-[44px]  items-center overflow-x-auto px-6 min-[1200px]:px-[72px] md:px-12">
    <!-- <h1 class="text-[24px] text-white">Dates & Eligibilty</h1> -->
    <div class="flex items-center flex-col gap-2 mb-6">
        <div class="flex gap-3 items-center">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela capitalize">Fee Structure</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
    </div>
    <!-- Pricing cards start -->
    <div class='overflow-x w-full'>
        <table class="table-auto overflow-scroll w-full  border-gray-200">
            <tbody>
                <tr class="space-x-4">
                    @forelse($course->feePackages ?? [] as $key => $package )

                        @if($loop->first)
                            <td class="max-w-[130px] p-4">
                                <p>Exam Prep you'll have personalized coaching, navigation and tutoring from our team of CPA Exam experts</p>
                            </td>
                            <!-- <td>Course Access</td>
                            <td>Flexible Payment options (0% interest)</td>
                            <td>100% Pass Guarantee to get through your exam</td> -->
                        @endif

                        <td class="p-4 rounded-[5px] shadow bg-[#000435]">
                            <div class="flex items-center flex-col">
                                <span class="text-[22px] leading-[25px] font-semibold text-white">{{ $package->package_name }}</span>
                                <span class="text-[42px] font-medium leading-[70px] text-white">{{  $package->discounted_price }}</span>
                                <span class="text-[28px] font-medium leading-[40px] text-white line-through">{{  $package->price }}</span>
                            </div>
                        </td>

                        @empty
                        No Record yet added!
                        @endforelse
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Course access</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            <td class="p-4 text-center">{{ $feePackageDetail->packageFeatures->course_access }}</td>
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">100% Pass Guarantee to get through your exam</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->pass_guarantee['0'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">$1,000 back + Back-On-Track plan</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->pass_guarantee['1'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Complete 4-part course including Disciplines matching AICPA Exam blueprint</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->exam_day_ready_features['0'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Exam Day Ready toolkit </td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->exam_day_ready_features['1'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Team of Expert Instructors </td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->exam_day_ready_features['2'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">On-the-go Mobile app + Award-winning study game app</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->exam_day_ready_features['3'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Unlimited Adapt2U Technology driven, custom personalized practice tests </td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->exam_day_ready_features['4'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Accessible Discipline Sections</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            <td class="p-4 text-center">{{ $feePackageDetail->packageFeatures->exam_day_ready_features['5'] }}</td>
                            
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Best-in-class printed Textbooks</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['0'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Success Coaching sessions</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['1'] != 0)
                                <td class="p-4 text-center">{{ $feePackageDetail->packageFeatures->unmatched_resources_and_tools['1'] }}</td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">1-on-1 Tutoring sessions</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['2'] != 0)
                                <td class="p-4 text-center">{{ $feePackageDetail->packageFeatures->unmatched_resources_and_tools['2'] }}</td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">LiveOnline Exam classes</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['3'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">In-person Exam classes (limited locations)</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['4'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Final Review capstone course</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['5'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Printable Flashcards</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['6'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">1 year CPE subscription </td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['7'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Deep Dive Workshops Bundle</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['8'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">ExamSolver Videos Bundle</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['9'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    <tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">Licensing Navigator sessions</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['10'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>
                    {{--<tr style="border-bottom: 1px solid black;">
                            <td class="max-w-[130px] p-4 font-bold">CPE Certificate Program</td>
                        @foreach($course->feePackages ?? [] as $key => $feePackageDetail )
                            
                            @if( $feePackageDetail->packageFeatures->unmatched_resources_and_tools['11'] == 1)
                                <td class="p-4 text-center"><img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8 mx-auto" alt=""></td>
                                @else
                                <td class="p-4 text-center">N/A</td>
                            @endif
                        @endforeach
                    </tr>--}}
                    
            </tbody>
        </table>
    </div>
    

</section>
@endif
<section id="nine" class="card-hidden px-6 min-[1200px]:px-[72px] my-16 show">
    <div id="card-container" class="no-scrollbar">
        <!-- Card One start -->
        <div class="card flex flex-col lg:flex-row items-center gap-x-24 gap-y-10">
            <div class="flex-1">
                <img src="{{ asset('frontend/images/jpg/sheikh.jpg') }}" alt="" class="w-full object-cover">
            </div>
            <div class="relative flex-1 gap-2 flex flex-col">
                <h1
                    class="font-canela text-primary text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[60px] tracking-wide">
                    Learner Stories
                </h1>
                <div class="flex flex-col my-6">
                    <p class="text-[18px]">The Executive Training Centre isn't just about advancing careers; it's about
                        cultivating leadership excellence and driving meaningful change. Our students confidently say
                        that their experience here has been instrumental in propelling career to new heights.</p>
                </div>
                <a href="{{ route('learner-stories') }}" class="flex flex-1 items-center group gap-2">
                    <div
                        class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[420px] text-primary">Read about our student's success
                        stories as testimonials</h2>
                </a>
            </div>
        </div>
        <!-- Card One End -->

        <!-- Card Two start -->
        <div class="card flex flex-col lg:flex-row items-center gap-x-24 gap-y-10">
            <div class="flex-1">
                <img src="{{ asset('frontend/images/jpg/sheikh.jpg') }}" alt="" class="w-full object-cover">
            </div>
            <div class="relative flex-1 gap-2 flex flex-col">
                <h1
                    class="font-canela text-primary text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[60px] tracking-wide">
                    Learner Stories
                </h1>
                <div class="flex flex-col my-6">
                    <p class="text-[18px]">The Executive Training Centre isn't just about advancing careers; it's about
                        cultivating leadership excellence and driving meaningful change. Our students confidently say
                        that their experience here has been instrumental in propelling career to new heights.</p>
                </div>
                <a href="{{ route('learner-stories') }}" class="flex flex-1 items-center group gap-2">
                    <div
                        class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[420px] text-primary">Read about our student's success
                        stories as testimonials</h2>
                </a>
            </div>
        </div>
        <!-- Card Two End -->

        <!-- Card Three start -->
        <div class="card flex flex-col lg:flex-row items-center gap-x-24 gap-y-10">
            <div class="flex-1">
                <img src="{{ asset('frontend/images/jpg/sheikh.jpg') }}" alt="" class="w-full object-cover">
            </div>
            <div class="relative flex-1 gap-2 flex flex-col">
                <h1
                    class="font-canela text-primary text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[60px] tracking-wide">
                    Learner Stories
                </h1>
                <div class="flex flex-col my-6">
                    <p class="text-[18px]">The Executive Training Centre isn't just about advancing careers; it's about
                        cultivating leadership excellence and driving meaningful change. Our students confidently say
                        that their experience here has been instrumental in propelling career to new heights.</p>
                </div>
                <a href="{{ route('learner-stories') }}" class="flex flex-1 items-center group gap-2">
                    <div
                        class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[420px] text-primary">Read about our student's success
                        stories as testimonials</h2>
                </a>
            </div>
        </div>
        <!-- Card Three End -->
    </div>
    <div class="w-full flex mt-6 gap-4 justify-center">
        <button class="" id="prev-button">
            <img src="{{ asset('frontend/images/svgs/caret-prev.svg') }}" alt="" class="w-10 h-10">
        </button>
        <button class="" id="next-button">
            <img src="{{ asset('frontend/images/svgs/caret-next.svg') }}" alt="" class="w-10 h-10">
        </button>
    </div>
</section>

<section id="ten"
    class="flex items-center justify-center flex-col py-12 bg-[#eeeeee]  px-4 md:px-8 lg:px-[140px]">
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="text-[20px] sm:text-[24px] text-dark md:text-[32px] font-canela capitalize">
            What You Earn
        </span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p class="text-[17px] mt-3">You will get a certificate of completion, which is highly reputed and accepted by
        employers.</p>

    {{--<div class="flex flex-1 w-full flex-col sm:flex-row gap-20  justify-center items-center  pt-10">

        <div class="flex items-center  flex-col">
            <img src="{{ asset('frontend/images/pngs/Berkeley-Square-School-of-Arts-Certificate.png') }}" alt=""
                class="w-[275px] h-[350px] transition-all hover:scale-110 ease-in duration-200 delay-100">
            <img src="" alt="">
            <div class="flex justify-center items-center  text-center flex-col gap-1 mt-[20px] max-w-[310px]">
                <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-8 h-5 " alt="">
                <div class="flex flex-col gap-2"><span
                        class="font-bold text-[17px] leading-[24px] uppercase">Certificate of
                        Completion</span>
                </div>
            </div>
        </div>
    </div>--}}

    <div class="flex flex-col lg:flex-row gap-16 pt-12">
        <div class="flex-1 items-center w-full">
            <img src="{{ asset('frontend/images/pngs/Berkeley-Square-School-of-Arts-Certificate.png') }}" alt="" class="w-[320px] h-[420px] hover:scale-105 transition-all ease-in duration-200 mx-auto">
        </div>
        <div class="flex-1 h-[500px] overflow-y-auto">
            <div class="flex flex-col gap-2">
                @foreach($course->benifits ?? [] as $index => $data)
                <div class="flex flex-row justify-between gap-4">
                    <div class="flex gap-2 flex-col">
                        <div class="flex gap-1 flex-col">
                            <h3 class="font-semibold">{!! $data['title'] !!}</h3>
                            <div class="w-[80px] h-[2px] bg-yellow"></div>
                            <p>{!! $data['description'] !!}</p>
                        </div>
                    </div>
                </div>
                <!-- <div class="w-full bg-primary h-[1px]"></div> -->
                @endforeach
                
            </div>
        </div>
    </div>

</section>


@if(!$course->courseFaq->isEmpty())
<section id="eleven" class="flex flex-col px-4   bg-[#f5f5f5] py-[44px]  items-center min-[1200px]:px-[72px]">
    <div class="max-w-[780px]">
        <div class="flex justify-between border-dark border-b pb-3 w-full">
            <h1 class="text-dark text-[24px] ">{{ $course->title }} FAQs</h1>
            <!-- <a href="#" class="text-[24px] text-white hover:underline">All FAQs</a> -->
        </div>
        <div class="accordion flex flex-col gap-3 mt-4">

        <div class="accordion flex flex-col gap-3 mt-4">
            @foreach($course->courseFaq as $index => $courseFaq)
            <div class="accordion-item py-4 px-5 bg-[#ffffff] ">
                    <button class="accordion-button hover:underline text-dark" aria-expanded="false">
                        {{ $courseFaq->title }}
                    </button>
                    <div class="accordion-content flex flex-col text-dark mt-1" aria-hidden="true">
                        <p>{{ $courseFaq->short_description }}</p>
                    </div>
                </div>
            @endforeach
        </div>

    </div>

</section>
@endif
@endsection

@push('script')
    
<script>
    // Card scrolling start
    document.addEventListener('DOMContentLoaded', function () {
        const cardContainer = document.getElementById('card-container');
        const cards = document.querySelectorAll('.card');
        const nextButton = document.getElementById('next-button');
        const prevButton = document.getElementById('prev-button');
        let currentIndex = 0;

        function scrollToCard(index) {
            const cardWidth = cards[index].offsetWidth + 0; // Including the gap between cards
            const scrollPosition = index * cardWidth;
            cardContainer.scrollTo({
                left: scrollPosition,
                behavior: 'smooth'
            });
        }

        nextButton.addEventListener('click', function () {
            if (currentIndex < cards.length - 1) {
                currentIndex++;
                scrollToCard(currentIndex);
            }
        });

        prevButton.addEventListener('click', function () {
            if (currentIndex > 0) {
                currentIndex--;
                scrollToCard(currentIndex);
            }
        });
    });
</script>

<script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
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




@endpush

