@extends('layouts.app')
@push('style')
    <style>
        .hideme {
            display: none
        }
    </style>
@endpush

@section('content')
<!-- section one video start -->
<section
    class="mt-[70px] md:mt-[70px] lg:mt-20 flex flex-col min-[650px]:flex-row  bg-[#f4f4f4] justify-between items-start px-6 gap-6 my-0  py-10 min-[920px]:gap-[110px] md:px-16 lg:px-[120px]">
    <div class="flex flex-col gap-1 py-1  rounded-full ">
        <video controls class="">
            <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
        </video>
        <span class="py-1">AD</span>
        <div class="w-full h-[1px] bg-gray_one"></div>
        <span class="mt-1">Introduction to Business Analytics
        </span>
    </div>
    <div
        class="flex flex-col items-center  rounded-md bg-white px-5 pt-12  min-[650px]:max-w-[380px] w-full min-w-[280px] pb-8">
        <h1 class="leading-[46px] max-w-[186px] font-semibold text-[46px]">Business Analytics
        </h1>
        <span class="font-ghothic text-[17px] mt-[26px] text-primary font-bold">$1,750
        </span>
        <p class="text-[17px] mt-[26px]">Next 8-week session starts June 12th
        </p>
        <button
            class="px-6 py-2 mt-[26px] font-ghothic text-[23px] font-semibold bg-crimson rounded text-white hover:bg-primary hover">Apply</button>
        <p class="text-[17px] mt-[26px]">Apply fundamental data analysis to real business problems.
        </p>
        <div class="grid bg-slate-100 gap-1 w-full mt-[26px] grid-cols-2">
            <div class="flex flex-col items-center min-h-[84px] justify-center text-center gap-2 bg-[#B5D7D8]">
                <span class="text-[29px] text-[#1a7c7c]">8</span>
                <span class="text-[#181818]">weeks</span>
            </div>
            <div class="flex flex-col items-center min-h-[84px] justify-center text-center gap-2 bg-[#B5D7D8]">
                <span class="text-[29px] text-[#1a7c7c]">5</span>
                <span class="text-[#181818]">hours per week</span>
            </div>
            <div class="flex flex-col items-center min-h-[84px] justify-center text-center gap-2 bg-[#B5D7D8]">
                <span class="text-[29px] text-[#1a7c7c]">5
                </span>
                <span class="text-[#181818]">modules
                </span>
            </div>
            <div class="flex flex-col items-center min-h-[84px] justify-center text-center gap-2 bg-[#B5D7D8]">
                <span class="text-[29px] text-[#1a7c7c]">Self-Paced</span>
                <span class="text-[#181818] ">with regular deadlines</span>
            </div>
        </div>
        <div class="flex justify-center items-center  text-center flex-col gap-1 mt-[20px]">
            <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-8 h-5 " alt="">
            <div class="inline-block text-[13px] leading-[17px] ">This course earns you a Certificate of
                Completion
                from HBS Online.
                <a class="decoration-crimson underline  text-crimson" href="">
                    What you earn.
                </a>
            </div>
        </div>
    </div>
</section>
<!-- section one video end -->

<!-- section two start -->
<section
    class="flex sticky top-[69px] z-[99] lg:top-[83px] items-center px-4 md:px-8 lg:px-[120px] gap-3 md:gap-[120px] my-0 py-0 mt-10 bg-yellow">
    <div class="flex justify-center flex-1 font-ghothic text-[15px] gap-2 md:gap-6">
        <a href="#key-concept" class="underline decoration-crimson hidden sm:block">Overview
        </a>
        <a href="#syllabus">Syllabus</a>
        <a href="#enrollment">Enrollment</a>
        <a href="#stories">Stories
        </a>
        <a href="#faq">FAQs
        </a>
    </div>

    <button
        class="shrink-0 px-6 py-2 inline-flex gap-1 items-center font-ghothic text-[13px]  bg-crimson  text-white hover:bg-primary ">
        Apply Now
        <img src="{{ asset('frontend/images/svgs/caret-right.svg') }}" class="w-8 h-8" alt="">
    </button>

</section>
<!-- section two end -->

<!-- section three start -->
<section id="key-concept"
    class="flex flex-col    bg-[#f4f4f4] justify-between items-center px-6 gap-6 my-0 py-0 pt-10 min-[920px]:gap-[110px] md:px-16 lg:px-[120px] min-h-[310px]">
    <div class="flex gap-3 items-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold">KEY CONCEPT</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="flex flex-col sm:flex-row gap-4 gap-x-12">
        <div class="flex  flex-col flex-1 gap-3">
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Interpret data to inform business decisions

                </span>
            </div>
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Recognize trends, detect outliers, and summarize data sets

                </span>
            </div>
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Analyze relationships between variables

                </span>
            </div>
        </div>
        <div class="flex  flex-col flex-1 gap-3">
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Develop and test hypotheses

                </span>
            </div>
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Craft sound survey questions and draw conclusions from
                    population samples

                </span>
            </div>
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">Implement regression analysis and other analytical
                    techniques in Excel

                </span>
            </div>
        </div>
    </div>
</section>
<!-- section three End -->

<!-- Section four start -->
<section class="bg-[#f4f4f4]  ">
    <div class="flex items-center justify-center flex-col pb-12   container mx-auto">
        <div class="flex gap-3 items-center pt-10">
            <div class="bg-yellow w-[50px] h-[2px]"></div>
            <span class="font-semibold">
                WHO WILL BENEFIT
            </span>
            <div class="bg-yellow w-[50px] h-[2px]"></div>
        </div>
        <div class="flex flex-col items-center pt-8 gap-8">
            <div class="flex gap-4">
                <button
                    class="font-bold text-crimson text-[13px] min-[400px]:text-[15px] bg-white rounded-full py-1 px-2">Collage
                    Students
                    ans
                    Recents
                    Graduates</button>
                <button
                    class="font-bold text-crimson text-[13px] min-[400px]:text-[15px] bg-white rounded-full py-1 px-2">Those
                    Considering
                    Graduate</button>
                <button
                    class="font-bold text-crimson text-[13px] min-[400px]:text-[15px] bg-white rounded-full py-1 px-2">Mid-Career
                    Professionals</button>
            </div>
            <div class="flex flex-col  md:flex-row gap-y-8  gap-x-16 md:p-[30px] p-[20px] 
                    min-[1200px]:px-[100px] bg-white min-h-[256px]">
                <div class="flex-1 basis-full">
                    <span class="text-[24px] text-dark">Prepare for your next opportunity by learning how to apply
                        basic statistics to real
                        business
                        problems.
                    </span>
                </div>
                <div class="flex  flex-col gap-6 flex-1 basis-full  justify-center md:justify-start md:items-start ">
                    <div class="flex flex-col  justify-center md:justify-start md:items-start gap-4">
                        <video controls class="min-[650px]:w-[180px] min-[650px]:max-w-[180px] min-[650px]:h-[120px]">
                            <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                        </video>
                        <span class="max-w-[320px]">Meet Arjun, an HBS Online participant who took Business
                            Analytics to
                            better understand
                            content creation research in his
                            industry
                        </span>
                    </div>
                    <a href="#d" class="flex gap-2">
                        <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--aDooYbGu--/f_auto,c_fill,w_110,/v20200101/2B69EC4C91D72889CD2460051F2D6C77.jpg"
                            alt="" class="max-h-[50px] ">
                        <div class="flex flex-col">
                            <span class="font-bold text-[13px]">Arjun Bhandegaonkar</span>
                            <span class="text-[13px]">Film studio executive</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- Section four End -->

<!-- Section Five start -->
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
<!-- Section five End -->

<!-- section seven start -->
<section id="syllabus"
    class="flex items-center justify-center flex-col py-10 bg-[#ffff] min-h-[174px] lg:px-[120px]  px-4 md:px-8  ">
    <div class="flex items-center flex-col gap-2">
        <span class="text-[32px] font-bold text-crimson">Syllabus</span>
        <span class="text-[15px]">5 module , 40 hours</span>
        <a href="" class="text-crimson font text-[15px] underline ">Download full syllabus (pdf)</a>
    </div>

    <div class="flex flex-col justify-center items-center w-full mt-10 gap-6  ">
        <!-- Horizantal video  Card One start-->
        <div class="border w-full max-w-[980px]  shadow-card  rounded-md  p-[30px] flex flex-col  gap-4">
            <div class="flex flex-col overflow-hidden">
                <div class="flex flex-col min-[870px]:flex-row overflow-hidden gap-10">
                    <div class="flex flex-col flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                        <div class="flex flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                            <div
                                class="flex flex-col justify-center items-center min-w-[50px] bg-yellow py-3 px-2 rounded">
                                <span class="text-[24px] font-semibold">8</span>
                                <span class="text-[15px]">hrs</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13px] text-[#59666f] ">Module 1</span>
                                <h1 class="text-[24px] text-[#181818]">Describing and Summarizing Data
                                </h1>
                                <p class="text-[15px] mt-6 hidden min-[870px]:block">trends in data and detect outliers,
                                    summarize data
                                    sets
                                    concisely, and analyze
                                    relationships between
                                    variables.
                                </p>
                            </div>
                        </div>
                        <p class="block min-[870px]:hidden">trends in data and detect outliers, summarize data
                            sets
                            concisely, and analyze
                            relationships between
                            variables.</p>
                    </div>
                    <div class="flex flex-col overflow-x-scroll flex-1 basis-full gap-3">
                        <h2 class="text-[15px]">Highlightsa</h2>
                        <div class="flex gap-2 max-w-0 pb-2">
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="bg-gray-300 h-[1px] w-full "></div>
            <div class=" flex flex-col gap-3">


                <button class="self-start text-crimson showBtn">+ Show Details</button>

                <div class="showContent hidden">
                    <div
                        class="flex flex-col bg-[#f4f4f4] justify-between items-center px-6 my-0  py-4  md:px-16 lg:px-[120px] gap-8">
                        <div class="flex gap-3 items-center">
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                            <span class="font-semibold">KEY CONCEPT</span>
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4 gap-x-12">
                            <div class="flex  flex-col flex-1 gap-3">
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Interpret data to inform business decisions
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Recognize trends, detect outliers, and
                                        summarize data sets
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Analyze relationships between variables
                                    </span>
                                </div>
                            </div>
                            <div class="flex  flex-col flex-1 gap-3">
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Develop and test hypotheses
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Craft sound survey questions and draw
                                        conclusions from
                                        population samples
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Implement regression analysis and other
                                        analytical
                                        techniques in Excel
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- Horizantal video  Card One end-->
        <!-- Horizantal video  Card Two start-->
        <div class="border shadow-card w-full max-w-[980px]   rounded-md p-[30px] flex flex-col  gap-4">
            <div class="flex flex-col overflow-hidden">
                <div class="flex flex-col min-[870px]:flex-row overflow-hidden gap-10">
                    <div class="flex flex-col flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                        <div class="flex flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                            <div
                                class="flex flex-col justify-center items-center min-w-[50px] bg-yellow py-3 px-2 rounded">
                                <span class="text-[24px] font-semibold">8</span>
                                <span class="text-[15px]">hrs</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13px] text-[#59666f] ">Module 1</span>
                                <h1 class="text-[24px] text-[#181818]">Describing and Summarizing Data
                                </h1>
                                <p class="text-[15px] mt-6 hidden min-[870px]:block">trends in data and detect outliers,
                                    summarize data
                                    sets
                                    concisely, and analyze
                                    relationships between
                                    variables.
                                </p>
                            </div>
                        </div>
                        <p class="block min-[870px]:hidden">trends in data and detect outliers, summarize data
                            sets
                            concisely, and analyze
                            relationships between
                            variables.</p>
                    </div>
                    <div class="flex flex-col overflow-x-scroll flex-1 basis-full gap-3">
                        <h2 class="text-[15px]">Highlightsa</h2>
                        <div class="flex gap-2 max-w-0 pb-2">
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="bg-gray-300 h-[1px] w-full "></div>
            <div class=" flex flex-col gap-3">


                <button class="self-start text-crimson showBtn">+ Show Details</button>

                <div class="showContent hidden ">
                    <div
                        class="flex flex-col bg-[#f4f4f4] justify-between items-center px-6 my-0  py-4  md:px-16 lg:px-[120px] gap-8">
                        <div class="flex gap-3 items-center">
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                            <span class="font-semibold">KEY CONCEPT</span>
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4 gap-x-12">
                            <div class="flex  flex-col flex-1 gap-3">
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Interpret data to inform business decisions
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Recognize trends, detect outliers, and
                                        summarize data sets
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Analyze relationships between variables
                                    </span>
                                </div>
                            </div>
                            <div class="flex  flex-col flex-1 gap-3">
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Develop and test hypotheses
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Craft sound survey questions and draw
                                        conclusions from
                                        population samples
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Implement regression analysis and other
                                        analytical
                                        techniques in Excel
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- Horizantal video  Card Two end-->

        <!-- Horizantal video  Card Three start-->
        <div class="border shadow-card w-full max-w-[980px]   rounded-md  p-[30px] flex flex-col  gap-4">
            <div class="flex flex-col overflow-hidden">
                <div class="flex flex-col min-[870px]:flex-row overflow-hidden gap-10">
                    <div class="flex flex-col flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                        <div class="flex flex-1 grow-0  items-start basis-full font-ghothic gap-3">
                            <div
                                class="flex flex-col justify-center items-center min-w-[50px] bg-yellow py-3 px-2 rounded">
                                <span class="text-[24px] font-semibold">8</span>
                                <span class="text-[15px]">hrs</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13px] text-[#59666f] ">Module 1</span>
                                <h1 class="text-[24px] text-[#181818]">Describing and Summarizing Data
                                </h1>
                                <p class="text-[15px] mt-6 hidden min-[870px]:block">trends in data and detect outliers,
                                    summarize data
                                    sets
                                    concisely, and analyze
                                    relationships between
                                    variables.
                                </p>
                            </div>
                        </div>
                        <p class="block min-[870px]:hidden">trends in data and detect outliers, summarize data
                            sets
                            concisely, and analyze
                            relationships between
                            variables.</p>
                    </div>
                    <div class="flex flex-col overflow-x-scroll flex-1 basis-full gap-3">
                        <h2 class="text-[15px]">Highlightsa</h2>
                        <div class="flex gap-2 max-w-0 pb-2">
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                            <div class="flex gap-1 max-w-[150px] flex-col ">
                                <video controls class="max-w-[150px] ">
                                    <source src="{{ asset('frontend/videos/promo-video.mp4') }}">
                                </video>
                                <p class="text-[13px]">Histograms and Summarizing Data
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="bg-gray-300 h-[1px] w-full "></div>
            <div class=" flex flex-col gap-3">


                <button class="self-start text-crimson showBtn">+ Show Details</button>

                <div class="showContent hidden">
                    <div
                        class="flex flex-col bg-[#f4f4f4] justify-between items-center px-6 my-0  py-4  md:px-16 lg:px-[120px] gap-8">
                        <div class="flex gap-3 items-center">
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                            <span class="font-semibold">KEY CONCEPT</span>
                            <div class="bg-yellow w-[50px] h-[2px]"></div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4 gap-x-12">
                            <div class="flex  flex-col flex-1 gap-3">
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Interpret data to inform business decisions
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Recognize trends, detect outliers, and
                                        summarize data sets
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Analyze relationships between variables
                                    </span>
                                </div>
                            </div>
                            <div class="flex  flex-col flex-1 gap-3">
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Develop and test hypotheses
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Craft sound survey questions and draw
                                        conclusions from
                                        population samples
                                    </span>
                                </div>
                                <div class="flex items-start gap-2 ">
                                    <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                                    <span class="text-[17px] max-w-[420px]">Implement regression analysis and other
                                        analytical
                                        techniques in Excel
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- kasj -->
        </div>
        <!-- Horizantal video  Card Three end-->
    </div>
</section>
<!-- section seven end -->

<!-- Section eight start -->
<section class="flex gap-10 items-center justify-center  bg-yellow min-h-[174px]  px-4 md:px-6 lg:px-[120px] py-7">

    <div class="flex  flex-col items-center md:flex-row gap-10 p-[25px] rounded-lg shadow-card bg-white w-full">
        <div class="max-w-[350px] min-[500px]:min-w-[365px] max-h-[300px] max-[400px]:max-h-[480px]">
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

<!-- section nine start -->
<section class="flex gap-10 items-center justify-center  px-4 md:px-8 lg:px-[120px] py-[44px]">
    <div class="flex flex-col gap-10">
        <div class="flex flex-col">
            <h1 class="text-[32px] text-dark_light">Our difference</h1>
            <p class="text-[17px]">The most engaging, interactive way to master business analytics.</p>
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
</section>
<!-- section nine end -->
<!-- section ten start -->
<section class="flex gap-10 items-center justify-center  px-4 md:px-8 lg:px-[120px] py-[48px] bg-[#F4F4F4]">
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
</section>
<!-- section ten end -->


<!-- Section eleven start -->
<section id="enrollment"
    class="flex flex-col min-h-[653px] bg-crimson py-[44px]  items-center px-6 min-[1200px]:px-[120px]">
    <h1 class="text-[24px] text-white">Dates & Eligibilty</h1>
    <!-- Pricing cards start -->
    <div class="flex flex-wrap justify-around gap-10 mt-8">
        <!-- card One start -->
        <div
            class="flex flex-col items-center rounded-[5px] shadow bg-white py-[36px] px-[16px] w-full min-[600px]:max-w-[253px]">
            <div class="flex items-center flex-col">
                <span class="text-[15px] leading-[19px] font-semibold text-[#59666f]">Starting</span>
                <span class="text-[32px] font-medium leading-[40px]">MAY</span>
                <span class="text-[32px] font-medium leading-[40px]">01</span>
            </div>
            <div class="w-10 bg-yellow h-[2px] my-10"></div>
            <p class="text-[15px] font-medium text-center"><span class="font-normal">5 Weeks</span> $1,750 +
                applicable
                international taxes</p>
            <p class="text-[15px] font-medium text-center mb-[5px]">Application deadline is closed</p>
            <a href="" class="text-crimson underline text-[15px] mt-3 leading-[19px]">View weekly calendar
                &#x28;pdf&#x29;</a>
            <button
                class="bg-crimson text-white font-ghothic rounded-[5px] shadow px-6 py-3 text-[23px]  mt-10">Closed</button>
        </div>
        <!-- card One End -->

        <!-- card Two start -->
        <div
            class="flex flex-col items-center rounded-[5px] shadow bg-white py-[36px] px-[16px] w-full min-[600px]:max-w-[253px]">
            <div class="flex items-center flex-col">
                <span class="text-[15px] leading-[19px] font-semibold text-[#59666f]">Starting</span>
                <span class="text-[32px] font-medium leading-[40px]">MAY</span>
                <span class="text-[32px] font-medium leading-[40px]">01</span>
            </div>
            <div class="w-10 bg-yellow h-[2px] my-10"></div>
            <p class="text-[15px] font-medium text-center"><span class="font-normal">5 Weeks</span> $1,750 +
                applicable
                international taxes</p>
            <p class="text-[15px] font-medium text-center mb-[5px]">Application deadline is closed</p>
            <a href="" class="text-crimson underline text-[15px] mt-3 leading-[19px]">View weekly calendar
                &#x28;pdf&#x29;</a>
            <button
                class="bg-crimson text-white font-ghothic rounded-[5px] shadow px-6 py-3 text-[23px]  mt-10">Closed</button>
        </div>
        <!-- card Two End -->

        <!-- card Three start -->
        <div
            class="flex flex-col items-center rounded-[5px] shadow bg-white py-[36px] px-[16px] w-full min-[600px]:max-w-[253px]">
            <div class="flex items-center flex-col">
                <span class="text-[15px] leading-[19px] font-semibold text-[#59666f]">Starting</span>
                <span class="text-[32px] font-medium leading-[40px]">MAY</span>
                <span class="text-[32px] font-medium leading-[40px]">01</span>
            </div>
            <div class="w-10 bg-yellow h-[2px] my-10"></div>
            <p class="text-[15px] font-medium text-center"><span class="font-normal">5 Weeks</span> $1,750 +
                applicable
                international taxes</p>
            <p class="text-[15px] font-medium text-center mb-[5px]">Application deadline is closed</p>
            <a href="" class="text-crimson underline text-[15px] mt-3 leading-[19px]">View weekly calendar
                &#x28;pdf&#x29;</a>
            <button
                class="bg-crimson text-white font-ghothic rounded-[5px] shadow px-6 py-3 text-[23px]  mt-10">Closed</button>
        </div>
        <!-- card Three End -->

    </div>
    <!-- Pricing cards End -->

    <!-- Start -->
    <div class="flex flex-col sm:flex-row w-full max-w-[1180px] gap-y-10 gap-x-[150px] mt-12">
        <div class="flex text-white max-w-[170px] flex-col flex-1 gap-3">
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">
                    Free application

                </span>
            </div>
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">
                    No documentation required
                </span>
            </div>
            <div class="flex items-start gap-2 ">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="text-[17px] max-w-[420px]">
                    Confirmation within one week
                </span>
            </div>

        </div>
        <div class="flex  flex-col flex-1 gap-3">
            <p class="text-white text-[17px]">All applicants must be at least 18 years of age, proficient in
                English, and
                committed to learning and
                engaging with
                fellow participants throughout the course.
            </p>
            <a href="" class="underline text-white text-[17px]">Learn about bringing this course to your
                organization.</a>
        </div>
    </div>
    <!-- End -->
</section>
<!-- Section eleven end -->

<!-- section twelve start -->
<section id="stories" class="flex flex-col gap-10  pt-[44px] pb-[24px] bg-[#F4F4F4]">
    <div class="flex w-full flex-col gap-10">
        <h1 class="text-[32px] text-dark_light text-center w-full">Learner Stories</h1>
        <div class="flex flex-col items-center lg:flex-row lg:items-start gap-[112px]">
            <div class="relative ">
                <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--Rxf2H9HW--/f_auto,c_fill,w_608,/v20200101/C8BAAEB2FC4E60BE7E6F2DC80C10ABBD.jpg"
                    class="object-cover min-[768px]:max-w-[608px]" alt="">
                <div class="absolute flex justify-center  w-full   bottom-[-18px]">
                    <div class=" bg-yellow px-4 self-center rounded min-h-[36px] flex justify-center items-center">
                        strengthened
                        analytical
                        skills
                    </div>
                </div>
                <div class="absolute w-full h-full top-0 flex justify-center items-center">
                    <span class="bg-black bg-opacity-75 rounded-md px-6 p-4 text-[72px] leading-[72px] text-white">92%
                </div>

            </div>

            <div class="flex  items-center flex-col">
                <div class="flex flex-col gap-4">
                    <div
                        class="flex items-center flex-col gap-2 bg-white w-full max-w-[580px] rounded-[8px] px-[25px] py-[20px]">
                        <p class="text-[15px] text-dark_light">I'm using regression analysis and other
                            statistical
                            concepts
                            from Business Analytics to build
                            predictive models, analyze
                            trends, and conduct surveys and hypothesis testing. Business Analytics has helped me
                            pursue
                            a
                            career in data analytics
                            and land a job as a Business Intelligence Analyst at Ministry of Justice New
                            Zealand.
                            I'm
                            leading a few modelling and
                            reporting projects across the Ministry using the knowledge I gained from the course.
                        </p>
                        <div class="flex flex-col">
                            <span class="text-[17px]">Hién NguyÅn Thé</span>
                            <span class="text-[15px] text-dark_light">Business Intelligence Analyst at New
                                Zealand's
                                Ministry of Justice</span>
                        </div>
                    </div>
                    <div
                        class="flex items-center flex-col gap-2 bg-white w-full max-w-[580px] rounded-[8px] px-[25px] py-[20px]">
                        <p class="text-[15px] text-dark_light">I'm using regression analysis and other
                            statistical
                            concepts
                            from Business Analytics to build
                            predictive models, analyze
                            trends, and conduct surveys and hypothesis testing. Business Analytics has helped me
                            pursue
                            a
                            career in data analytics
                            and land a job as a Business Intelligence Analyst at Ministry of Justice New
                            Zealand.
                            I'm
                            leading a few modelling and
                            reporting projects across the Ministry using the knowledge I gained from the course.
                        </p>
                        <div class="flex flex-col">
                            <span class="text-[17px]">Hién NguyÅn Thé</span>
                            <span class="text-[15px] text-dark_light">Business Intelligence Analyst at New
                                Zealand's
                                Ministry of Justice</span>
                        </div>
                    </div>
                </div>
                <a href=""
                    class="inline-flex gap-2 items-center text-crimson mt-10 hover:text-black hover:border-black border border-crimson rounded text-[13px] px-2 py-1">
                    READ MORE LEARNER STORIES
                    <i class="fa fa-caret-right"></i>
                </a>
            </div>

        </div>
    </div>
    <span class="px-4 md:px-8 lg:px-[120px] py-[28px] ">
        * Source: 2022 surveys and course data
    </span>
</section>
<!-- section twelve End -->

<!-- Section Thirteen start Winning with digital  -->
<section id="faq" class="flex flex-col px-4  bg-crimson py-[44px]  items-center min-[1200px]:px-[72px]">
    <div class="max-w-[780px]">
        <div class="flex justify-between border-[#930E31] border-b pb-3 w-full">
            <h1 class="text-white text-[24px] ">Winning with Digital Platforms FAQs</h1>
            <a href="#" class="text-[24px] text-white hover:underline">All FAQs</a>
        </div>
        <div class="accordion flex flex-col gap-3 mt-4">
            <div class="accordion-item border-[#930E31]  border-b">
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
            <div class="accordion-item border-[#930E31]  border-b">
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
            <div class="accordion-item border-[#930E31]  border-b">
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
            <div class="accordion-item border-[#930E31]  border-b">
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
<!-- Section Thirteen end -->

<!-- Section Fourteen start -->
<section class="mt-8 flex mb-[18px] py-0 z-20  flex-1 gap-4 lg:px-[121px] px-4 md:px-10 flex-col items-centers">
    <div class="flex 100 justify-center gap-3 items-center mb-12">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold text-primary ">
            RELATED PROGRAMS

        </span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <div class="">
        <div class="flex gap-3 overflow-x-scroll pb-2">
            <!-- Card One start -->
            <div class="flex flex-col gap-2 max-w-[280px] min-w-[280px]  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[100px] w-full   object-cover">
                </div>
                <a href="" class="flex flex-1 flex-col px-[20px] py-[20px]">
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
            <!-- Card One End -->
            <!-- card Two vertical start -->
            <div class="flex flex-col gap-2 max-w-[280px] min-w-[280px]  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[100px] w-full   object-cover">
                </div>
                <a href="" class="flex flex-1 flex-col px-[20px] py-[20px]">
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
            <!-- card Three vertical start -->
            <div class="flex flex-col gap-2 max-w-[280px] min-w-[280px]  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[100px] w-full   object-cover">
                </div>
                <a href="" class="flex flex-1 flex-col px-[20px] py-[20px]">
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
            <!-- Card Three vertical end -->
            <!-- card four vertical start -->
            <div class="flex flex-col gap-2 max-w-[280px] min-w-[280px]  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[100px] w-full   object-cover">
                </div>
                <a href="" class="flex flex-1 flex-col px-[20px] py-[20px]">
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
            <!-- Card four vertical end -->
            <!-- card Five vertical start -->
            <div class="flex flex-col gap-2 max-w-[280px] min-w-[280px]  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[100px] w-full   object-cover">
                </div>
                <a href="" class="flex flex-1 flex-col px-[20px] py-[20px]">
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
            <!-- Card Five vertical end -->
        </div>
    </div>
</section>
<!-- Section Fourteen End -->

<!-- Section Fifteen start Winning with digital  -->
<section class="flex flex-col min-h-[653px] px-4 bg-[#1d1d24] py-[44px]  items-center min-[1200px]:px-[72px]">

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
@endpush