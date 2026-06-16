@extends('layouts.app')
@push('style')

@endpush

@section('content')


<!-- section one start -->
<section class="flex flex-col  mt-[70px] md:mt-[70px] lg:mt-20  relative min-h-[271px]">
    <img src="{{ asset('frontend/images/pngs/heroback.png') }}"
        class="h-full min-h-[300px] md:max-h-[291px]   object-cover absolute object-top bottom-0 w-full z-0" alt="">
    <div class="flex m-0 py-6 z-20  flex-1 flex-col justify-center gap-4 lg:px-[121px] px-4 md:px-10">
        <!-- <div class="flex flex-row items-center flex-wrap gap-2">
            <a href="" class="text-[13px] hidden md:block">Harvard Buisness School</a>
            <span class="md:block hidden">&#x2192;</span>
            <a href="" class="text-[13px] hidden md:block">HBS Online</a>
            <span class="border-[#808080] border px-2 md:hidden">...</span>
            <span>&#x2192;</span>

            <div class="dropdown ">
                <button class="dropbtn inline-flex items-center justify-between text-[13px]">
                    Online Business Certificate Courses
                    <img src="{{asset('frontend/images/svgs/arrowsud.svg')}}" class="w-6 h-6" alt="">
                </button>
                <div class="dropdown-content ">
                    <span class="bg-white text-black ">
                        <a href="#" class="">Business in Society</a>
                    </span>
                    <a class="text-white hover:bg-crimson" href="#">Business Strategy</a>
                    <a class="text-white hover:bg-crimson" href="#">Digital Transformation</a>
                    <a class="text-white hover:bg-crimson" href="#">Entrepreneurship & Innovation</a>
                    <a class="text-white hover:bg-crimson" href="#">Finance & Accounting</a>
                    <a class="text-white hover:bg-crimson" href="#">Leadership & Managment</a>
                    <a class="text-white hover:bg-crimson" href="#">Leadership, Ethics, and Corporate Accountability</a>
                    <a class="text-white hover:bg-crimson" href="#">Marketing</a>
                    <a class="text-white hover:bg-crimson" href="#">Strategy</a>
                </div>
            </div>
            <span>&#x2192;</span>

            <a href="" class="text-[13px]">Business Essentials</a>
        </div> -->
        <h1 class="text-[44px] font-bold text-dark">Courses</h1>
        <span class="max-w-[540px]">Gain applicable skills, build new business capabilities, and tap into the
            confidence you need to
            improve your
            organization and advance your career.
        </span>
    </div>
</section>
<!-- section one End -->
@foreach($subjects as $index => $data)
    <!-- Section Two Start -->
    <section class="mt-8 flex  m-0 py-0 z-20  flex-1 flex-col justify-center gap-4 lg:px-[121px] px-4 md:px-10">
        <div class="w-[50px] h-[2px] bg-yellow">
        </div>
        <div class="flex flex-col items-start sm:flex-row gap-y-4 gap-x-12 sm:items-center ">
            <div class="flex flex-col ">
                <a href="" class="text-[24px] text-crimson hover:text-dark_light font-bold">{{ $data->name }}</a>
                <p class="text-[17px]">Interpret data to inform business decisions, explore the economic foundations of
                    strategy, and
                    discover
                    what’s behind
                    the numbers in financial statements.
                </p>
            </div>
            <a href="{{ route('subject.details', ['name' => $data->slug]) }}"
                class="shrink-0 border-none font-ghothic inline-flex gap-2 items-center bg-crimson  hover:bg-black  border text-white rounded px-2 py-1">
                EXPLORE SUBJECT
                <i class="fa fa-chevron-right text-[16px]"></i>
            </a>
        </div>

    </section>
    <!-- Section Two End -->

    <!-- Section Three Start -->
    <section class="mt-8 flex mb-[18px] py-0 z-20  flex-1 gap-4 lg:px-[121px] px-4 md:px-10  ">
        <div class="flex gap-3 overflow-x-scroll pb-2">
            <!-- card one start -->
            <div
                class="flex flex-col  min-w-[280px] md:flex-row gap-2 md:max-w-[485px] md:min-w-[485px]  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--WIKjvN3p--/f_auto,c_fill,h_213,w_380,/v20200101/885741A19129793C3FD7A711E73C04DC.jpg"
                        alt="" class="w-full max-h-[100px] md:min-w-[180px] md:max-w-[180px] md:min-h-[392px] object-cover">
                    <span class="absolute z-10 top-4 bg-crimson text-white px-2 ">3 Courses</span>
                </div>
                <a href="{{ route('course.details', ['course' => 'cma-usa-certified-management-accountant']) }}" class="flex flex-col px-[20px] py-[20px]">
                    <!-- text content start -->
                    <div class="flex flex-col ">
                        <h2 class="text-[18px] font-semibold">Credential of Readiness (CORe)</h2>
                        <p class="text-[14px] font-ghothic text-gray_one">Designed to help you achieve fluency in
                            the language of businss, CORe is a
                            business fundamentals program that
                            combines Business Analytics, Economics
                            for Managers, and Financial Accounting
                            with a final exam.</p>
                    </div>
                    <div class="flex  mt-auto gap-1 flex-col">
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
            <!-- Card One end -->

            <!-- card Two vertical start -->
            <div class="flex flex-col gap-2 max-w-[280px] min-w-[280px]  border rounded overflow-hidden">
                <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[100px] w-full   object-cover">
                </div>
                <a href="{{ route('course.details', ['course' => 'cima-chartered-institute-of-management-accountants']) }}"
                    class="flex flex-1 flex-col px-[20px] py-[20px]">
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
                <a href="{{ route('course.details', ['course' => 'cima-chartered-institute-of-management-accountants']) }}"
                    class="flex flex-1 flex-col px-[20px] py-[20px]">
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
                <a href="{{ route('course.details', ['course' => 'cima-chartered-institute-of-management-accountants']) }}"
                    class="flex flex-1 flex-col px-[20px] py-[20px]">
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
        </div>

    </section>
    <!-- Section Three End -->

    <!-- Section four start -->
    <section class="flex m-0 py-0 z-20  flex-1 flex-col justify-center gap-4 lg:px-[121px] px-4 md:px-10 mb-4">
        <div class="flex items-center gap-3">
            <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-7 h-5" alt="">
            <div class="text-[14px] text-black">
                <span class="grow-0">Earn the</span>
                <a href="" class="text-crimson underline grow-0">Credential of Readiness</a>
                <span class="grow-0">
                    by taking all three courses in
                    tandem, plus a
                    final exam.
                </span>
            </div>
        </div>

    </section>
    <!-- section four end -->
@endforeach
@endsection

@push('script')
@endpush