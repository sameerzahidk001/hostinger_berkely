@extends('layouts.app')
@push('style')
    <style>
        .hideme {
            display: none
        }

        .accordion button {
            color: black;
        }

        .accordion button::after {
            color: black;
            font-weight: 900;
            font-size: 16px;
        }

        .accordion [aria-expanded="true"]::after {
            color: black;
            font-weight: 900;
            font-size: 16px;
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
            <!-- <h1 class="text-[20px] leading-8 font-canela text-primary_orange">Next {{ $course->course_duration . '-' . $course->duration_unit }} session starts {!! $course->starting_date->format('d M') !!}</h1> -->
            <h1 class="text-[24px] leading-8 font-canela text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">{{ $course->title }}</h2>
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

<!-- Add Slider Here End -->





<section
    class="flex sticky top-[69px] z-[99] lg:top-[83px] items-center px-4 md:px-8 lg:px-[120px] gap-3 md:gap-[120px] my-0 py-0 mt-4 bg-[#000435] hidden lg:block">
    <div class="flex justify-center flex-1 font-ghothic text-[15px] gap-2 md:gap-6 items-center text-white">
        <a href="#one" class="underline decoration-crimson hidden sm:block">Overview
        </a>
        <!-- <a href="#two">Who Can Do</a> -->
        <a href="#three">Eligibility</a>

        <a href="#four">Course Structure</a>
        <a href="#five">Lecture Plan</a>
        <a href="#six">Examination</a>
        <!-- <a href="#seven">Faculty</a> -->
        <a href="#eight">Fee</a>
        <a href="#nine">Stories
        </a>
        <a href="#ten">Certificate</a>
        <a href="#eleven">FAQs
        </a>
        <button
            class="shrink-0 px-6 py-2 inline-flex gap-1 items-center font-ghothic text-[13px]  bg-crimson  text-white hover:bg-primary ">
            Apply Now
            <img src="{{ asset('frontend/images/svgs/caret-right.svg') }}" class="w-8 h-8" alt="">
        </button>
    </div>



</section>

<section id="one"
    class="flex flex-col  bg-[#f4f4f4]  items-center px-6 gap-6 my-0 py-0 pt-10 min-[920px]:gap-[40px] md:px-16 lg:px-[120px] min-h-[310px]">
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Offered By</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>

    <div class="flex flex-col gap-4 gap-y-2 gap-x-3">
        <p>
            Founded over 100 years ago, IMA (Institute of Management Accountants, United States of America) is the
            worldwide association of accounting and finance professionals in business.
        </p>
        <!-- <p>
            {!! $course->awarded_by !!}
        </p> -->
    </div>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Head Office</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex flex-col gap-4 gap-y-2 gap-x-3">

        <p>
            Head office is locatated at New Jersey, United States of America.
        </p>
    </div>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Members</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex flex-col gap-4 gap-y-2 gap-x-3">

        <p>
            With more than 140,000 members in 150 countries, IMA is one of the largest and most respected associations
            focused on advancing the management accounting profession.
        </p>
    </div>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Founded In</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex flex-col gap-4 gap-y-2 gap-x-3">

        <p>
            March 1919
        </p>
    </div>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Vision & Mission</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex flex-col gap-4 gap-y-2 gap-x-3">

        <p>
            To provide a forum for research, practice development, education, knowledge sharing, and advocacy of the
            highest ethical and best business practices in management accounting and finance and our mission is To
            empower and connect business professionals to build a positive future
        </p>
    </div>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Be in Demand</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p>Employers, recruiters, and the leaders of your company know that CMAs don't just bring skills to the table, they
        bring strategies.</p>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Earn More</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p>The IMA 2023 Salary Survey shows CMAs earn 21% more in median total compensation compared to non-CMAs.</p>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Global Recognition</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p>The CMA opens doors to exciting career opportunities, including high level positions at multinational
        corporations. </p>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Powerful Network</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p>Becoming a CMA connects you to IMA’s global network of about 140,000 accounting and finance professionals. </p>
</section>

<!-- <section id="two"
    class="flex flex-col  bg-[#f4f4f4]  items-center px-6 gap-6 my-0 py-10  min-[920px]:gap-[40px] md:px-16 lg:px-[120px] min-h-[100px]">
    
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Who can do?</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex  gap-4 gap-y-2 gap-x-3">
        <p class="text-[17px</p>
    </div>
</section> -->

<section id="three"
    class="flex flex-col  bg-[#f4f4f4]  items-center px-6 gap-6 my-0 py-10  min-[920px]:gap-[40px] md:px-16 lg:px-[120px] min-h-[100px]">
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Eligibility</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>

    <!-- <div class="flex  gap-4 gap-y-2 gap-x-3">
        <p class="text-[17px]">{!! $course->eligibility !!}</p>
    </div> -->
    <!-- <div class="flex flex-col gap-4 gap-y-2 gap-x-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            
            <div class="flex flex-1 basis-full items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Active membership in IMA</span>
            </div>

            <div class="flex flex-1 basis-full items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Active CMA entrance fee</span>
            </div>

            <div class="flex flex-1 basis-full items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Satisfy the education qualification </span>
            </div>

            <div class="flex flex-1 basis-full items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Satisfy the experience qualification</span>
            </div>

            <div class="flex flex-1 basis-full items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Complete all required examination parts </span>
            </div>

            <div class="flex flex-1 basis-full items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Comply with the IMA Statement of Ethical Professional Practice </span>
            </div>
           
        </div>

    </div> -->
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold uppercase">Who is eligibile to register for Examamination?</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p class="text-[17px]">{!! $course->eligibility !!}</p>
    <!-- <p class="mx-auto my-2 text-[17px] font-semibold">Anyone who is interested to learn about following concepts can do CMA-USA</p> -->

    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-normal uppercase">Who can do?</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <!-- <div class="flex  gap-4 gap-y-2 gap-x-3">
        <p class="text-[17px]"></p>
    </div> -->
    <div class="flex flex-col gap-4 gap-y-2 gap-x-3">
        <p class="mx-auto my-2 text-[17px] font-semibold">Anyone who is interested to learn about following concepts can
            do CMA-USA</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 ">
            @foreach($course->courseObjectivePoints as $key => $rec)
                <div class="flex flex-1 basis-full items-start gap-2 ">
                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                    <span class="text-[17px] max-w-[420px]">{{ $rec->description }}</span>
                </div>
            @endforeach
        </div>

    </div>
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-normal uppercase">Which designations pursue?</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p class="text-[17px]">who can do details</p>

</section>


<section id="four"
    class="flex items-center justify-center flex-col py-10 bg-[#ffff] min-h-[174px] lg:px-[120px]  px-4 md:px-8  ">
    <div class="flex items-center flex-col gap-2">
        <div class="flex gap-3 items-center">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Course Structure</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex flex-row gap-4 gap-y-2 gap-x-3 mt-6">

            <div class="flex flex-col">
                <h1 class="text-[18px] font-semibold">CMA Part 1: Financial Planning, Performance, and Analytics</h1>
                <p>15% External Financial Reporting Decisions</p>
                <p>20% Planning, Budgeting, and Forecasting</p>
                <p>20% Performance Management</p>
                <p>15% Cost Management</p>
                <p>15% Internal Controls</p>
                <p>15% Technology and Analytics</p>
            </div>
            <div class="flex flex-col">
                <h1 class="text-[18px] font-semibold">CMA Part 2: Strategic Financial Management</h1>
                <p>20% Financial Statement Analysis</p>
                <p>20% Corporate Finance</p>
                <p>25% Decision Analysis</p>
                <p>10% Risk Management</p>
                <p>10% Investment Decisions</p>
                <p>15% Professional Ethics</p>
            </div>

        </div>
    </div>


    <div class="flex items-center flex-col gap-2 ">
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Gallery</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
    </div>

    <div class="flex flex-col justify-center items-center w-full mt-10 gap-6  ">
        <!-- Horizantal video  Card One start-->
        @foreach($course->courseSyllabus as $key => $courseSyllabus)
            <div class="border w-full max-w-[980px]  shadow-card  rounded-md  p-[30px] flex flex-col  gap-4 mb-4">
                <div class="flex flex-col overflow-hidden">
                    <div class="flex flex-col min-[870px]:flex-row overflow-hidden gap-10">
                        <div class="flex flex-col flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                            <div class="flex flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                                <div
                                    class="flex flex-col justify-center items-center min-w-[50px] bg-yellow py-3 px-2 rounded">
                                    <span class="text-[24px] font-semibold">{{ $courseSyllabus->duration }}</span>
                                    <span class="text-[15px]">{{ $courseSyllabus->duration_unit }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[13px] text-[#59666f] ">{{ $courseSyllabus->subtitle }}</span>
                                    <h1 class="text-[24px] text-[#181818]">{{ $courseSyllabus->title }}
                                    </h1>
                                    <p class="text-[15px] mt-6 hidden min-[870px]:block">{!! $courseSyllabus->description !!}</p>

                                </div>
                            </div>
                            <p class="block min-[870px]:hidden">{!! $courseSyllabus->description !!}</p>
                        </div>
                        <div class="flex flex-col overflow-x-scroll flex-1 basis-full gap-3">
                            <h2 class="text-[15px]">Highlights</h2>
                            <div class="flex gap-2 max-w-0 pb-2">
                                @foreach($courseSyllabus->courseSyllabusHighlights as $key => $highlights)
                                    <div class="flex gap-1 max-w-[150px] flex-col ">
                                        <!-- <video controls class="max-w-[150px] ">
                                                                                                                                                                                                                                                                                                                                                                                    <source src="{{ asset($highlights->image) }}">
                                                                                                                                                                                                                                                                                                                                                                                </video> -->
                                        <img src="{{ asset($highlights->image) }}" class="max-w-[150px] " alt="">
                                        <p class="text-[13px]">{{ $highlights->title }}</p>

                                    </div>
                                @endforeach

                            </div>
                        </div>

                    </div>
                </div>
                <div class="bg-gray-300 h-[1px] w-full "></div>
                <!-- <div class=" flex flex-col gap-3">                                                                                                                                                                   </div> -->
            </div>
        @endforeach
        <!-- Horizantal video  Card One end-->
    </div>
</section>

<!-- <section id="five"
    class="flex items-center justify-center flex-col pb-10 bg-[#ffff] min-h-[174px] lg:px-[120px]  px-4 md:px-8  ">
    <div class="flex items-center flex-col gap-2 ">
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Gallery</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
    </div>
    
</section> -->

<!-- <section id="five"
    class="flex items-center justify-center flex-col pb-10 bg-[#ffff] min-h-[174px] lg:px-[120px]  px-4 md:px-8  ">
    <div class="flex items-center flex-col gap-2 ">
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Lecture Plan</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
    </div>
    
</section> -->

<section id="five" class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 w-full mt-16">
    <div class="flex items-center flex-col gap-2 ">
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Lecture Plan</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
    </div>
    <div class="grid grid-cols-1 place-content-center md:grid-cols-2 gap-10 xl:grid-cols-3">
        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset('frontend/images/jpg/Executive-Education.jpg') }}"
                class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

            <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Lectures</span>
                <p class="font-semibold  group-hover:block text-white text-[16px]">
                    Our lecture plan integrates structured learning with interactive teaching methods, promoting
                    engagement and collaboration. This approach ensures a comprehensive understanding of concepts,
                    fostering critical thinking and practical application in real-world scenarios.</p>
                <!-- <a href="" class="flex items-center gap-2">
                    <div
                        class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[16px] text-white">Read More
                    </h2>
                </a> -->
            </div>

            <div
                class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
            </div>

        </div>

        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset('frontend/images/jpg/College-1.jpg') }}"
                class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

            <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Practice Session</span>
                <p
                    class="font-semibold group-hover:block text-white delay-300  text-[16px] transition-all duration-400 ease-in-out">
                    Practice sessions offer hands-on experience through guided exercises, enhancing skills and
                    reinforcing knowledge. This practical approach ensures mastery of concepts, promoting confidence and
                    competence in real-world applications.
                </p>
                <!-- <a href="{{ route('college') }}" class="flex items-center gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[16px]  text-white">Read More</h2>
                </a> -->
            </div>

            <div
                class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
            </div>
        </div>

        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset('frontend/images/jpg/School.jpg') }}"
                class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

            <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Mock Examination</span>
                <p class="font-semibold group-hover:block text-white text-[16px]">Mock examinations simulate real test
                    conditions, providing valuable practice and assessment. This helps identify strengths and
                    weaknesses, ensuring thorough preparation and boosting confidence for actual exams.
                </p>
                <!-- <a href="{{ route('school') }}" class="flex items-center gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[16px]  text-white">Read More</h2>
                </a> -->
            </div>

            <div
                class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
            </div>
        </div>

    </div>

</section>

<section id="six"
    class="flex items-center justify-center flex-col pb-10 bg-[#ffff] min-h-[174px] lg:px-[120px]  px-4 md:px-8  ">
    <div class="flex items-center flex-col gap-2 ">
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">WHAT ARE THE EXAM INFORMATION?</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>

        <!-- <span class="text-[15px]">{!! $course->exam_information !!}</span> -->
        <!-- <h3 class="text-[22px] font-semibold">What is the structure and format of the exam?</h3> -->
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Exam Format</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex gap-3 items-center my-4">
            <div>
                <p class="text-crimson">Part 1:</p>
            </div>
            <div>
                <p>100 Multiple-Choice Questions and Two Essay Questions</p>
            </div>
            <div>
                <p class="text-crimson">Part 2:</p>
            </div>
            <div>
                <p>100 Multiple-Choice Questions and Two Essay Questions</p>
            </div>
        </div>
        <!-- <p class="text-[18px]">A total of 100 multiple-choice questions along with two essay prompts.</p> -->

        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Exam Duration</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex gap-3 items-center my-4">
            <div>
                <p class="text-crimson">Part 1:</p>
            </div>
            <div>
                <p>4 hours ( 100 Multiple-Choice Questions (3 hours ) and Two Essay Questions (1 hour) )</p>
            </div>
            <div>
                <p class="text-crimson">Part 2:</p>
            </div>
            <div>
                <p>4 hours ( 100 Multiple-Choice Questions (3 hours ) and Two Essay Questions (1 hour) )</p>
            </div>
        </div>
        <!-- <p class="text-[18px]">Students will have a generous four-hours to complete the comprehensive exam.</p> -->

        <!-- <h3 class="text-[22px] font-semibold">When are the exam dates scheduled?</h3> -->
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">exam dates</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <p class="text-[18px]">Exams are offered in yearly 3 exam windows: January – February / May – June / September –
            October.
            <br>Any date in these months. You can choose based on your availability.
        </p>
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Exam Registration Deadline</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <p class="text-[18px]">For exam registration, students are required to fill out the online form available on the
            website and submit it before the specified deadline.</p>
        <!-- <h3 class="text-[22px] font-semibold">How does the process of scaling work for the exams?</h3> -->
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Passing Criteria</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <p class="text-[18px]">You need 360 score to pass the examination.</p>

        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">EXAM LOCATIONS</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <span class="text-[15px]">{!! $course->exam_location !!}</span>

    </div>


</section>

<!-- <section class="flex gap-10 items-center justify-center  bg-yellow min-h-[174px]  px-4 md:px-6 lg:px-[120px] py-7">

    <div class="flex  flex-col items-center md:flex-row gap-10 p-[25px] rounded-lg shadow-card bg-white w-full">
        <div class="max-w-[350px] min-[500px]:min-w-[365px] max-h-[300px]  max-[400px]:max-h-[480px]">
            <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--SQ5HUKsP--/f_auto,c_fill,h_265,w_390,/v20200101/B3D02BCA614E95B4586D5E6EB5D453D5.jpg"
                alt="" class=" max-w-[330px] min-[500px]:min-w-[385px]  max-h-[480px] min-h-full object-cover">
        </div>

        <div class="flex flex-col  flex-1 gap-2">
            <div class="flex  flex-col gap-1">
                <span class="text-[15px] font-ghothic text-dark_light">Free E-Book</span>
                <a href="" class="text-crimson text-[24px] leading-[30px] hover:underline">
                    Advance Your Career with Essential Business Skills
                </a>
            </div>
            <div class="flex  items-start flex-col">
                <span class="text-[17px] ">Learn how to contribute to key business discussions and drive
                    strategic
                    decision-making with
                    this free guide.</span>
                <a href=""
                    class="inline-flex gap-2 items-center text-crimson mt-3 hover:text-black hover:border-black border border-crimson font-ghothic rounded px-2 py-1 text-[13px]">
                    ACCESS YOUR FREE E-BOOK
                    <i class="fa fa-caret-right"></i>
                </a>
            </div>
        </div>
    </div>

</section> -->

<!-- <section class="flex gap-10 items-center justify-center  px-4 md:px-8 lg:px-[120px] py-[44px]">
    <div class="flex flex-col gap-10">
        

        <div class="flex items-center flex-col gap-2">
            <div class="flex gap-3 items-center mt-8">
                <div class="bg-yellow w-[50px] h-[2px]"></div>
                <span class="font-semibold uppercase">Our differences</span>
                <div class="bg-yellow w-[50px] h-[2px]"></div>
            </div>
            <p class="text-[17px]">The most engaging, interactive way to master business analytics.</p>
            <span class="text-[15px] mt-8">{!! $course->other_benifits !!}</span>
        </div>

        <div class="flex flex-col lg:flex-row gap-10 gap-x-20 lg:mt-10">
            <div class="flex flex-col gap-4 flex-1 basis-full">
                <button class="text-crimson text-[17px] text-start">Stay active by engaging in a new activity
                    every
                    three to
                    five
                    minutes.</button>
                <div class="bg-gray_one h-[1px] w-full"></div>
                <button class="text-dark_light text-[17px] text-start">Get social by collaborating with a global
                    community of
                    peers before, during, and after your course. Learners who
                    successfully complete an HBS Online program will be added to the HBS Online Community's
                    Official
                    Networking Group and
                    gain exclusive access to events and other networking opportunities.</button>
                <div class="bg-gray_one h-[1px] w-full"></div>

                <button class="text-start text-[17px]">Immerse yourself in real-world, case-based examples
                    brought
                    to life by industry-leading companies, including Amazon,
                    Walt Disney Studios, and Caesars Entertainment.</button>

            </div>
            <div class="flex-1 basis-full">
                <video controls class="lg:max-w-[680px] w-full  bg-black">
                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                </video>
            </div>
        </div>
    </div>
</section> -->

<!-- <section id="seven" class="flex gap-10 items-center justify-center  px-4 md:px-8 lg:px-[120px] py-[48px] bg-[#F4F4F4]">
    <div class="flex items-center flex-col max-w-[394px]">
        <div class="flex gap-3 items-center">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold text-[17px]">
                ABOUT THE PROFESSOR
            </span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex items-center flex-col ">
            <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--wzKOzL2h--/f_auto,c_fill,g_face:auto,h_120,w_120,/v20200101/C6F64EBD226C400791DAA4E3C17F0BB3.jpg"
                class="w-[120px] h-[120px] rounded-full overflow-hidden" alt="">
            <div class="flex items-center flex-col pt-4">
                <span class="text-[17px] font-bold text-dark_light">
                    Janice Hammond
                </span>
                <span class="text-[17px] font-bold text-dark_light">
                    Business Analytics
                </span>
            </div>
            <div class="flex flex-col gap-2 pt-4">
                <p class="text-center">Jesse Philips Professor of Manufacturing and Senior Associate Dean for
                    Culture and Community at
                    Harvard Business School</p>
                <p class="text-center">“Business Analytics will help demystify data and strengthen your analytical
                    skills. Beginning
                    with basic descriptive
                    statistics and progressing to regression analysis, you’ll implement analytical techniques in
                    Excel and apply fundamental
                    quantitative methods to real business problems—from performing A/B testing on a website to
                    using
                    sampling to check
                    warehouse inventory.”
                </p>
            </div>
        </div>
    </div>
</section> -->

<!-- <section id="eight"
    class="flex items-center justify-center flex-col pb-10 bg-[#ffff] min-h-[174px] lg:px-[120px]  px-4 md:px-8  ">
    <div class="flex items-center flex-col gap-2 ">
        <div class="flex gap-3 items-center my-8">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase">Fee Structure</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
    </div>
</section> -->

<section id="eight" class="flex flex-col  bg-[#eeeeee] py-[44px]  items-center px-6 min-[1200px]:px-[80px]">
    <!-- <h1 class="text-[24px] text-white">Dates & Eligibilty</h1> -->
    <div class="flex items-center flex-col gap-2">
        <div class="flex gap-3 items-center">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold uppercase text-dark">Fee Structure</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>


    </div>
    <!-- Pricing cards start -->
    <div class="flex flex-wrap justify-center w-full gap-10 mt-6">
        <!-- card One start -->
        @foreach($course->courseEnrollments as $key => $courseEnrollments)
            <div
                class="flex flex-col items-center rounded-[5px] shadow bg-[#000435] py-[36px] px-[16px] w-full min-[600px]:max-w-[253px]">
                <div class="flex items-center flex-col">
                    <span class="text-[15px] leading-[25px] font-semibold text-white">Study Option</span>
                    @if($key == 0)
                        <span class="text-[32px] font-medium leading-[40px] text-white">Online</span>
                    @elseif($key == 1)
                        <span class="text-[32px] font-medium leading-[40px] text-white">Face to Face</span>
                    @elseif($key == 2)
                        <span class="text-[32px] font-medium leading-[40px] text-white">On Campus</span>
                        <!-- <span class="text-[32px] font-medium leading-[40px] text-white">{!! $courseEnrollments->starting_date->format('d') !!}</span> -->
                    @endif
                </div>
                <div class="w-10 bg-yellow h-[2px] my-10"></div>
                <!-- <p class="text-[15px] font-medium text-center text-white"><span class="font-normal">{{ $courseEnrollments->discount }}% Early Bird Discount</span> {{ $course->currency . '' . $course->price }} +
                                                                                                                                                                                    applicable
                                                                                                                                                                                    international taxes</p> -->
                <!-- <a href="{{ asset($courseEnrollments->brochure) }}" class="text-crimson underline text-[15px] mt-3 leading-[19px]">View weekly calendar
                                                                                                                                                                                    &#x28;pdf&#x29;</a> -->
                <button
                    class="bg-primary_orange text-white font-ghothic rounded-[5px] shadow px-6 py-3 text-[23px]  mt-10">Enroll
                    Now</button>
            </div>
        @endforeach
        <!-- card One End -->

    </div>

</section>

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
    class="flex items-center justify-center flex-col py-12 bg-[#eeeeee] min-h-[174px]  px-4 md:px-8 lg:px-[120px]">
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold">
            WHAT YOU EARN
        </span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p class="text-[17px] mt-3">You will get a certificate of completion, which is highly reputed and accepted by
        employers.</p>

    <div class="flex flex-1 w-full flex-col sm:flex-row gap-20  justify-center items-center  pt-10">

        <div class="flex items-center  flex-col">
            <img src="{{ asset('frontend/images/pngs/Berkeley-Square-School-of-Arts-Certificate.png') }}" alt=""
                class="w-[275px] h-[350px] transition-all hover:scale-110 ease-in duration-200 delay-100">
            <img src="" alt="">
            <div class="flex justify-center items-center  text-center flex-col gap-1 mt-[20px] max-w-[310px]">
                <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-8 h-5 " alt="">
                <div class="flex flex-col gap-2"><span
                        class="font-bold text-[17px] leading-[24px] uppercase">Certificate of
                        Completion</span>
                    <!-- <p class="text-[17px] ">
                        Boost your resume with a Certificate of Completion from HBS Online
                    </p>
                    <span class="text-[15px] mt-3">Earn by: completing this course
                    </span> -->
                </div>

            </div>
        </div>
    </div>

    <div class="flex items-center flex-col gap-2">
        <div class="flex flex-col gap-3 items-center my-8">
            <!-- <div class="bg-yellow w-[50px] h-[2px]"></div> -->
            <span class="font-normal uppercase">OTHER BENEFITS</span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>

        <span class="text-[15px]">{!! $course->other_benifits !!}</span>
    </div>

</section>
<!-- Section Thirteen start Winning with digital  -->
<section id="eleven" class="flex flex-col px-4  bg-[#000435] py-[44px]  items-center min-[1200px]:px-[72px]">
    <div class="max-w-[780px]">
        <div class="flex justify-between border-white border-b pb-3 w-full">
            <h1 class="text-white text-[24px] ">{{ $course->title }} FAQs</h1>
            <!-- <a href="#" class="text-[24px] text-white hover:underline">All FAQs</a> -->
        </div>

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
<!-- Section Thirteen end -->

<!-- Section Fourteen start -->
<section class="mt-8 flex mb-[18px] py-0 z-20  flex-1 gap-4 lg:px-[121px] px-4 md:px-10 flex-col items-centers">
    <div class="flex 100 justify-center gap-3 items-center mb-6">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold text-primary ">
            RELATED PROGRAMS

        </span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <!-- <div class="">
        <div class="flex gap-3 flex-wrap pb-2">
            
        </div>
    </div> -->
    <div
        class="grid grid-cols-1  w-full min-[550px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 place-content-between pb-2">
        @for($i = 0; $i < 6; $i++)
            <div class="flex flex-col row-span-1  w-full  border rounded overflow-hidden">
                <a href="#" class="flex flex-1 flex-col px-[20px] py-[20px]">
                    <div class="flex flex-col gap-1">
                        <h2
                            class="text-[18px] font-semibold  sm:min-h-auto sm:max-h-auto md:min-h-[53px] md:max-h-[53px] overflow-hidden">
                            CMA USA - Certified Management Accountant-{{$i}}</h2>
                        <div class="w-[80px] h-[3px] bg-yellow mt-2"></div>
                        <p class="text-[15px] ">The best course combines practical skills, expert insights, and hands-on
                            experience for comprehensive professional development.</p>

                    </div>
                </a>
            </div>
        @endfor
    </div>
</section>

@endsection

@push('script')
    <script defer>
        document.querySelectorAll('.accordion-button').forEach(button => {
            button.addEventListener('click', () => {
                const expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', !expanded);
                const content = button.nextElementSibling;
                content.setAttribute('aria-hidden', expanded);

                if (!expanded) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                } else {
                    content.style.maxHeight = 0;
                }
            });
        });


        const showDiv = document.querySelectorAll(".showContent");

        const showContentBtn = document.querySelectorAll(".showBtn");

        showContentBtn.forEach(button => {
            button.addEventListener("click", () => {
                const showContent = button.nextElementSibling;  // assuming .showContent is always next
                showContent.classList.toggle('hidden');  // Toggles the hidden class on and off
            });
        });

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const links = document.querySelectorAll('.nav-link');
            const sections = document.querySelectorAll('section > div');

            function activateLink(targetId) {
                links.forEach(link => {
                    link.classList.toggle('active-link', link.getAttribute('href') === `#${targetId}`);
                });
            }

            // Add click event listeners to the navigation links
            links.forEach(link => {
                link.addEventListener('click', function () {
                    activateLink(this.getAttribute('href').substring(1));
                });
            });

            // Create an Intersection Observer to observe sections
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        activateLink(entry.target.id);
                    }
                });
            }, { threshold: 0.5 });

            // Observe each section
            sections.forEach(section => {
                observer.observe(section);
            });
        });

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
@endpush


<!-- Exam Format:100 Multiple-Choice Questions and Two Essay Questions
Exam Dates:Exams are offered in yearly 3 exam windows: January – February / May – June / September – October
Any date in these months you can choose based on your time management.

You can take exams in any sequence
Exams are scaled, based on their respective difficulty, to scores ranging from 0 to 500 to maintain uniformity and consistency regardless of the number of items present on your particular test form.
You need a 360 to pass, but note that, because of statistical equating, a scaled score of 360 does not necessarily convert to a 72%. A candidate with a more difficult test might only score 68% of the total points available and still wind up with a passing scaled score of 360. -->

<!-- @php
$dummyCourseEnrollments = [
    ['type' => 'Online'],
    ['type' => 'Face to Face'],
    ['type' => 'On Campus']
];
@endphp

<div class="flex gap-4">
    @foreach($dummyCourseEnrollments as $key => $courseEnrollment)
        <div class="flex flex-col items-center rounded-[5px] shadow bg-[#000435] py-[36px] px-[16px] w-full min-w-[270px]">
            <div class="flex items-center flex-col">
                <span class="text-[15px] leading-[25px] font-semibold text-white">Study Option</span>
                <span class="text-[32px] font-medium leading-[40px] text-white">{{ $courseEnrollment['type'] }}</span>
            </div>
            <div class="w-10 bg-yellow h-[2px] my-10"></div>
            <button class="bg-primary_orange text-white font-ghothic rounded-[5px] shadow px-6 py-3 text-[23px] mt-10">
                Enroll Now
            </button>
        </div>
    @endforeach
</div> -->