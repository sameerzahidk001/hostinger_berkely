@extends('layouts.app')
@push('style')

@endpush

@section('content')
<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Certifications</span>
            
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Professional Certifications

            </h2>
        </div>
        <p clas="text-[18px] text-white">Secure the knowledge and skills to lead, manage and innovate for the
            future.
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
        <img src="{{ asset('frontend/images/jpg/60.jpg') }}"
            alt="" class="object-cover h-full md:max-h-full md:w-full md:h-full">
    </div>
</section>

<!-- Section Two Start -->
<section class="mt-8 flex  m-0 py-0 z-20  flex-1 flex-col justify-center gap-4 min-[1200px]:px-[72px] md:px-12 px-6">
        
        <h2 class="text-[18px] font-semibold font-canela">Courses ({{ $subject->courses->count() }})</h2>
        <!-- <div class="w-[100%] h-[1px] bg-gray-200"></div> -->
        <div class="bg-yellow w-[100%] h-[2px]"></div>
        
    </section>
    <!-- Section Two End -->
    
    <!-- Section Three Start -->
    <section class="mt-4 flex mb-[18px] py-0 z-20  flex-1 gap-4 min-[1200px]:px-[72px] md:px-12 px-6  ">
        <div class="grid grid-cols-1  w-full min-[550px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 place-content-between pb-2">
            <!-- card one start -->
            
            <!-- Card One end -->
            @foreach($subject->courses as $key => $course)
            <!-- card Two vertical start -->
            <div class="flex flex-col row-span-1  w-full  border rounded overflow-hidden">
                <!-- <div class="relative">
                    <img src="https://cloudinary.hbs.edu/hbsit/image/upload/s--UBT6FF8p--/f_auto,c_fill,h_213,w_380,/v20200101/69A86A2A6F8536F0A1EDA2A48248D965.jpg"
                        alt="" class="max-h-[150px] min-[550px]:max-h-[100px] w-full   object-cover">
                </div> -->
                <a href="{{ route('course.details', $course->slug) }}" class="flex flex-1 flex-col px-[20px] py-[20px]">
                    <!-- text content start -->
                    <div class="flex flex-col gap-1">
                        <h2 class="text-[18px] font-semibold  sm:min-h-auto sm:max-h-auto md:min-h-[53px] md:max-h-[53px] overflow-hidden">{{ $course->title }}</h2>
                        <div class="w-[80px] h-[3px] bg-yellow mt-2"></div>
                        <!-- <p class="text-[14px] font-ghothic text-gray_one">Professor Janice Hammond</p> -->
                        <p class="text-[15px] ">The best course combines practical skills, expert insights, and hands-on experience for comprehensive professional development.</p>

                    </div>
                    <!--<div class="flex justify-end  h-full   gap-1 flex-col">
                        <span class="text-[15px]">10-17 weeks, 8-15 hrs/week</span>
                        <span class="text-[15px]"> Apply by May 13</span>
                         <div class="flex mt-2 w-full justify-between items-center gap-1">
                            <span class="text-[17px] font-bold font-ghothic">$2,500</span>
                            <div class="flex items-center gap-1">
                                <span class="text-[14px] text-gray_one">Credential</span>
                                <img src="{{ asset('frontend/images/svgs/certificate.svg') }}" class="w-7 h-5" alt="">
                            </div>
                        </div> 
                    </div>-->
                    <!-- text content End -->
                </a>
            </div>
            <!-- Card Two vertical end -->
            @endforeach
            
        </div>

    </section>
    <!-- Section Three End -->

<section id="header-course" class="relative  overflow-hidden min-h-screen max-h-screen">
    <div class="absolute scroll-smooth bg-black  top-0 flex justify-center flex-col  z-30 h-full w-full items-center">
        <img src="{{ asset('frontend/images/jpg/60.jpg') }}"
            class="min-h-screen max-h-screen object-cover w-full zoom-in" alt="">
    </div>
    <div class="absolute px-2   top-0 flex justify-center flex-col  z-40 h-full w-full items-center bg-black bg-opacity-50">
        <h1
            class="text-white inline-flex flex-col text-[40px] leading-[42px] md:text-[52px] md:leading-[48px] lg:text-[82px] xl:text-[116px] font-canela     text-center lg:leading-[80px] xl:leading-[116px]">
            <span>Leading Minds,</span> 
            <span> Elevating Futures</span>
        </h1>
        <span class="text-[18px] leading-[27px] max-w-[520px] text-center text-white font-bold mt-10">
            Empowering girls through education is essential for a brighter, more equitable future worldwide.</span>

        
        <div class="absolute min-h-[20%] w-px top-0 left-1/2 transform -translate-x-1/2 bg-white">
        </div>
        <div class="absolute min-h-[16%] w-px bottom-0 left-1/2 transform -translate-x-1/2 bg-white">
        </div>
    </div>
</section>



@include('components.certificate')

<x-success-stories :id="2" 
    :title="'Success Stories'" 
    :description="'The Executive Training Centre is not just about advancing careers; it is about cultivating leadership excellence and driving meaningful change.'"
    :url="route('learner-stories')" 
    :buttonText="'Explore our students success stories as testimonials'" 
    :testimonials="[
        [
            'title' => 'Study Abroad',
            'description' => 'Embark on a life-changing adventure with our study abroad programs. Immerse yourself in new cultures, gain global perspectives, and expand your academic horizons.',
            'url' => route('study-abroad'),
            'buttonText' => 'Explore the options to study abroad',
            'image' => asset('frontend/images/jpg/Study-Abroad.jpg')
        ],
        [
            'title' => 'Migration',
            'description' => 'Navigate the complexities of migration with our comprehensive services. From visa assistance to settlement support, we ensure your migration journey is smooth and successful.',
            'url' => 'https://law.berkeleyme.com/',
            'buttonText' => 'Migrate for a better security of life',
            'image' => asset('frontend/images/jpg/Immigration-Service.jpg')
        ],
        [
            'title' => 'Public Training',
            'description' => 'Enhance your skills with our dynamic public training programs, seminars, and workshops. Elevate your career and personal development with hands-on learning.',
            'url' => '#',
            'buttonText' => 'Explore our public course calendar',
            'image' => asset('frontend/images/jpg/Public-Training.jpg')
        ],
        [
            'title' => 'Business Support Centre',
            'description' => 'Boost your business success with our comprehensive support center. From expert advice to tailored solutions, we provide the tools you need to thrive.',
            'url' => '#',
            'buttonText' => 'Our business support for entrepreneurs and executives',
            'image' => asset('frontend/images/jpg/support-center.jpg')
        ]
    ]"
/>

@php
    $clients = App\Models\Client::where('active', '1')->orderBy('id', 'asc')->get()->toArray();
    $clients = collect($clients)->map(function ($client) {
        return [
            'image' => (!empty($client->image) && is_string($client->image)) ? asset('images/clients/' . $client->image) : null,
            'url' => $client->url ?? '#',
            'target' => $client->open_new_tab ?? '',
            'no_follow' => $client->no_follow ?? '',
        ];
    })->toArray();
@endphp

@php
    $clients = App\Models\Client::where('active', 1)->orderBy('id', 'asc')->get()->map(function ($client) {
        return [
            'image' => $client->image ? asset('images/clients/' . $client->image) : '',
            'url' => $client->url ?? '#',
            'target' => $client->open_new_tab === '1' ? '_blank' : '_self',
            'no_follow' => $client->no_follow === '1' ? 'nofollow' : 'follow',
        ];
    })->toArray();
@endphp

<x-clients :id="1"
    :background="'#eeeeee'"
    :title="'Most of the Students are from'"
    :description="'Our students excel, securing positions at top companies, driving innovation, and achieving remarkable success.'"
    :logos="$clients"
/>

@endsection

@push('script')
    <script>
        var myRadios = document.getElementsByName('tabs2');
        var setCheck;
        var x = 0;
        for (x = 0; x < myRadios.length; x++) {
            myRadios[x].onclick = function () {
                if (setCheck != this) {
                    setCheck = this;
                } else {
                    this.checked = false;
                    setCheck = null;
                }
            };
        }

    </script>
@endpush