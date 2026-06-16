@extends('layouts.app')
@push('style')

@endpush

@section('content')
    
    <!-- section one start -->
    <section class="flex items-center  relative mt-[70px] md:mt-[70px] lg:mt-20  py-10">
        <img src="{{ asset('frontend/images/pngs/heroback.png') }}"
            class="h-full md:min-h-[411px] object-cover absolute object-top bottom-0 w-full z-0" alt="">
        <div class="lg:px-[121px] px-4 z-40 w-full flex flex-col">
            <div
                class="flex flex-col min-[920px]:items-center w-full z-[500px] min-[920px]:flex-row gap-10 justify-between">
                <div class="flex basis-full  m-0 py-0 z-20 flex-1 flex-col justify-center ">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-[28px] leading-[29px] font-bold text-dark ">{{ $subject->name }}</h1>
                        <span class="min-[920px]:max-w-[540px]">Interpret data to inform business decisions, explore the
                            economic
                            foundations of
                            strategy, and discover what’s behind
                            the numbers in financial statements.
                        </span>
                    </div>
                    <div class="w-[40px] h-[3px] hidden min-[920px]:block bg-[#3A9C9C] mt-8 mb-1"></div>
                    <div class="hidden min-[920px]:flex gap-8">
                        <div class="flex flex-col max-w-[162px] ">
                            <span class="text-[29px] text-[#3A9C9C]">
                                85%
                            </span>
                            <span class="text-[15px] text-dark_light">Say taking a course bolstered their resume
                            </span>
                        </div>
                        <div class="flex flex-col max-w-[162px] ">
                            <span class="text-[29px] text-[#3A9C9C]">
                                10X
                            </span>
                            <span class="text-[15px] text-dark_light">Return on investment with an average $17,000
                                salary
                                increase
                            </span>
                        </div>
                        <div class="flex flex-col max-w-[162px] ">
                            <span class="text-[29px] text-[#3A9C9C]">
                                88%
                            </span>
                            <span class="text-[15px] text-dark_light">Say taking a Feel better prepared for getting
                                their
                                MBA
                            </span>
                        </div>
                    </div>

                </div>
                <div class="flex-1 w-full max-w-[578px] min-[920px]:w-full   flex items-start basis-full z-10">
                    <video controls class="min-[920px]:w-auto">
                        <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                    </video>
                </div>
            </div>
            <div class="z-[333] hidden min-[920px]:block  mt-12"><a href=""
                    class="text-gray-500 teext-[13px]  hover:text-crimson underline decoration-dashed decoration-gray-500 hover:decoration-crimson underline-offset-2 ">
                    Source: 2018, 2022 surveys and course data
                </a></div>
        </div>
    </section>
    <!-- section one End -->

    <!-- section Two start -->
    <section
    class="mt-8 flex  m-0 py-0 z-20  flex-1 flex-row justify-center gap-4 lg:px-[121px] px-4 md:px-10">
        <div class="flex flex-col min-[650px]:flex-row gap-2   min-w-[340px]  border rounded max-w-[978px]">
            <div class="relative">
                <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--WIKjvN3p--/f_auto,c_fill,h_213,w_380,/v20200101/885741A19129793C3FD7A711E73C04DC.jpg"
                    alt="" class="
                    max-h-[144px] w-full
                    h-full 
                    min-[650px]:min-h-[262px] 
                    min-[650px]:min-w-[300px]
                    min-[650px]:max-h-[480px]
                    min-[651px]:max-h-full
                    object-cover">
                <span class="absolute z-10 top-4 bg-crimson text-white px-2 ">3 Courses
                </span>
            </div>
            <div class="flex h-full flex-col">
                <!-- text content start -->
                <div class="flex flex-col px-[30px] py-[20px]   ">
                    <h2 class="text-[18px] mt-[10px] pb-[10px] font-semibold">Credential of Readiness (CORe)</h2>
                    <p class="text-[17px] mt-[10px] text-dark_light ">Designed to help you
                        achieve fluency in
                        the language of businss, CORe is a
                        business fundamentals program that
                        combines Business Analytics, Economics
                        for Managers, and Financial Accounting
                        with a final exam.</p>
                </div>
                <div class="flex justify-end flex-1 h-full px-[30px] pb-[20px] mt-auto  flex-col">
                    <span class="text-[15px]">10-17 weeks, 8-15 hrs/week</span>
                    <span class="text-[15px]"> Apply by May 13</span>
                    <div class="flex mt-2 w-full justify-between items-center gap-1">
                        <span class="text-[17px] font-bold font-ghothic">$2,500</span>
                        <div class="flex items-center gap-1">
                            <span class="text-[14px] text-gray_one">Credential</span>
                            <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-7 h-5" alt="">
                        </div>
                    </div>
                </div>
                <!-- text content End -->
            </div>
        </div>
    </section>
    <!-- section Two End -->

    <!-- Section Two Start -->
    <section class="mt-8 flex  m-0 py-0 z-20  flex-1 flex-col justify-center gap-4 lg:px-[121px] px-4 md:px-10">
        
        <h2 class="text-[18px] font-semibold">Certificate Courses (2)</h2>
        <div class="w-[100%] h-[1px] bg-gray-200"></div>
        
    </section>
    <!-- Section Two End -->


    <!-- Section Three Start -->
    <section class="mt-8 flex mb-[18px] py-0 z-20  flex-1 gap-4 lg:px-[121px] px-4 md:px-10  ">
        <div class="grid grid-cols-1  w-full min-[550px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 place-content-between pb-2">
            <!-- card one start -->
            
            <!-- Card One end -->
            @for ($i = 0; $i < 8; $i++)
            <!-- card Two vertical start -->
            <div class="flex flex-col row-span-1  w-full  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[150px] min-[550px]:max-h-[100px] w-full   object-cover">
                </div>
                <a href="{{ route('course.details', ['course' => 'cima-uk-chartered-institute-of-management-accountants']) }}" class="flex flex-1 flex-col px-[20px] py-[20px]">
                    <!-- text content start -->
                    <div class="flex flex-col gap-1">
                        <h2 class="text-[18px] font-semibold ">Business Analytics</h2>
                        <p class="text-[14px] font-ghothic text-gray_one">Professor Janice Hammond</p>
                        <p class="text-[14px] font-ghothic text-dark">Designed to help you achieve fluency in
                            the language of </p>

                    </div>
                    <div class="flex justify-end  h-full   gap-1 flex-col">
                        <span class="text-[15px]">10-17 weeks, 8-15 hrs/week</span>
                        <span class="text-[15px]"> Apply by May 13</span>
                        <div class="flex mt-2 w-full justify-between items-center gap-1">
                            <span class="text-[17px] font-bold font-ghothic">$2,500</span>
                            <div class="flex items-center gap-1">
                                <span class="text-[14px] text-gray_one">Credential</span>
                                <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-7 h-5" alt="">
                            </div>
                        </div>
                    </div>
                    <!-- text content End -->
                </a>
            </div>
            <!-- Card Two vertical end -->
            @endfor
            
        </div>

    </section>
    <!-- Section Three End -->

    <!-- Section six start -->
    <section
        class="flex items-center justify-center flex-col py-12 bg-[#eeeeee] min-h-[174px]  px-4 md:px-8 lg:px-[120px] mt-4">
        <div class="flex gap-3 items-center">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold">
                WHAT YOU EARN
            </span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>

        <div class="flex flex-1 w-full flex-col sm:flex-row gap-20  justify-center items-center  pt-10">
            <div class="flex items-center flex-col">
                <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--twKZ9bKo--/f_auto,c_fill,e_sharpen:300,h_422,w_326,/v20200101/0207A8FB28AEC7D92AE1D9A3E0426A6F.jpg"
                    alt="" class="w-[275px] h-[350px] transition-all hover:scale-110 ease-in duration-200 delay-100">
                <div class="flex justify-center items-center  text-center flex-col gap-1 mt-[20px] max-w-[310px]">
                    <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-8 h-5 " alt="">
                    <div class="flex flex-col gap-2"><span class="font-bold text-[17px] leading-[24px] ">Certificate of
                            Completion</span>
                        <p class="text-[17px] ">
                            Boost your resume with a Certificate of Completion from HBS Online
                        </p>
                        <span class="text-[15px] mt-3">Earn by: completing this course
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center  flex-col">
                <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--twKZ9bKo--/f_auto,c_fill,e_sharpen:300,h_422,w_326,/v20200101/0207A8FB28AEC7D92AE1D9A3E0426A6F.jpg"
                    alt="" class="w-[275px] h-[350px] transition-all hover:scale-110 ease-in duration-200 delay-100">
                <img src="" alt="">
                <div class="flex justify-center items-center  text-center flex-col gap-1 mt-[20px] max-w-[310px]">
                    <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-8 h-5 " alt="">
                    <div class="flex flex-col gap-2"><span class="font-bold text-[17px] leading-[24px] ">Certificate of
                            Completion</span>
                        <p class="text-[17px] ">
                            Boost your resume with a Certificate of Completion from HBS Online
                        </p>
                        <span class="text-[15px] mt-3">Earn by: completing this course
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <a href=""
            class="inline-flex text-[13px] font-ghothic gap-2 items-center text-crimson hover:text-black hover:border-black border border-crimson rounded px-2 py-1 mt-12">
            LEARN MORE ABOUT WHAT YOU EARN
            <i class="fa fa-caret-right"></i>
        </a>
    </section>
    <!-- Section six start -->

    

    <!-- Section eight start -->
    <section class="flex gap-10 items-center justify-center  bg-yellow min-h-[174px]  px-4 md:px-6 lg:px-[120px] py-7">

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
        

    </section>
    <!-- section eight end -->
    <!-- Box Section Added start -->
    <section class="bg-[#f4f4f4] py-[44px]   min-[1200px]:px-[72px] ">
        <div class="flex justify-center gap-3 items-center">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold">
                You May Also Be Interested In
            </span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>

        <div class="flex flex-wrap pt-10 px-6 gap-3 font-bold justify-center text-[#A41034] text-[17px] font-ghothic items-stretch">
            <a href="" class=" w-full hover:bg-black break-words sm:max-w-[240px] text-center flex justify-center items-center py-[30px] px-6 border bg-white rounded transition-all duration-300 ease-in delay-100">
                Accounting & Finance
            </a>
            <a href="" class="w-full hover:bg-black sm:max-w-[240px] flex justify-center text-center items-center py-[30px] px-6 border bg-white rounded transition-all duration-300 ease-in delay-100">
                Business
            </a>
            <a href="" class="w-full hover:bg-black sm:max-w-[100px] flex justify-center text-center items-center py-[30px] px-6 border bg-white rounded transition-all duration-300 ease-in delay-100">
                Design
            </a>
            <a href="" class="w-full hover:bg-black sm:max-w-[240px] flex justify-center text-center items-center py-[30px] px-6 border bg-white rounded transition-all duration-300 ease-in delay-100`">
                Development
            </a>
            <a href="" class="w-full hover:bg-black sm:max-w-[170px] flex justify-center text-center items-center py-[30px] px-6 border bg-white rounded transition-all duration-300 ease-in delay-100">
                Health & Fitness
            </a>
        </div>

    </section>
    <!-- Box Section Added End -->
    
    <!-- Section Fifteen start Winning with digital  -->
    <section class="flex flex-col min-h-[653px] px-6  bg-[#1d1d24] py-[44px]  items-center min-[1200px]:px-[72px]">
        <div class="max-w-[780px]">
            <div class="flex justify-between border-[#1D1D24] border-b pb-3 w-full">
                <h1 class="text-white text-[24px]">Top FAQs
                </h1>
                <a href="#" class="text-[24px] text-white hover:underline">All FAQs</a>
            </div>
            <div class="accordion flex flex-col gap-3 mt-4">
                <div class="accordion-item border-[#8E8E92]  border-b">
                    <button class="accordion-button hover:underline" aria-expanded="false">
                        What are the learning requirements to successfully complete the course?
                    </button>
                    <div class="accordion-content flex flex-col gap-2 text-white mt-4" aria-hidden="true">
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                    </div>
                </div>
                <!-- Repeat for other questions with unique content -->
                <div class="accordion-item border-[#8E8E92]  border-b">
                    <button class="accordion-button hover:underline" aria-expanded="false">
                        What are the learning requirements in order to successfully complete the course, and how are
                        grades assigned??
                    </button>
                    <div class="accordion-content flex mt-4 flex-col gap-2 text-white" aria-hidden="true">
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                    </div>
                </div>
                <div class="accordion-item border-[#8E8E92]  border-b">
                    <button class="accordion-button hover:underline" aria-expanded="false">
                        What are the learning requirements to successfully complete the course?
                    </button>
                    <div class="accordion-content flex mt-4 flex-col gap-2 text-white" aria-hidden="true">
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                    </div>
                </div>
                <div class="accordion-item border-[#8E8E92]  border-b">
                    <button class="accordion-button hover:underline" aria-expanded="false">
                        What are the learning requirements to successfully complete the course?
                    </button>
                    <div class="accordion-content flex mt-4 flex-col gap-2 text-white" aria-hidden="true">
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                        <p>Participants are expected to fully complete all coursework in a thoughtful and timely
                            manner. This
                            includes meeting each week’s module deadlines and participating in discussions. No
                            grades are assigned;
                            participants will be evaluated as complete or not complete.</p>
                    </div>
                </div>
                <!-- Repeat for other questions -->
            </div>
        </div>
    </section>
    <!-- Section Fifteen end -->

@endsection

@push('script')
<script>
    // Accordian start
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
    // Accordian end
</script>
@endpush
