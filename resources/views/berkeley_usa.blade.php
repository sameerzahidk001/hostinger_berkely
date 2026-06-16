@extends('layouts.app')
@push('style')

@endpush

@section('content')
<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">USA</span>
           
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Exective Education

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

<section class="card-hidden px-6 min-[1200px]:px-[72px] my-16">
    <div class="flex flex-col md:flex-row items-start min-[968px]:items-center gap-x-24 gap-y-10">
        <div class="flex-1 w-full ">
            <img src="{{ asset('frontend/images/jpg/Berkeley-Square-USA.jpg') }}" alt=""
                class="w-full h-full object-cover">
        </div>

        <div class="relative mt-10 flex-1 w-full">
            <div class="absolute  z-20 top-[-52px]"><img src="{{ asset('frontend/images/svgs/quote.svg') }}"
                    class="  w-[80px] h-[80px]">
            </div>
            <div class="flex gap-8 flex-col">
                <h1 class="font-canela text-[36px] leading-[41px] lg:leading-[43px]">
                    Berkeley Square School of Management, Arts and Sciences”</h1>

                <p class="text-[18px] font-medium ">Experience the transformative power of education at our center,
                    where we empower minds to create a better future. Immerse yourself in the academic excellence at
                    Berkeley Hills, located in the heart of California. Our center offers a dynamic learning
                    environment, fostering innovation and critical thinking. Join us and be part of a community
                    dedicated to making a difference in the world. Your journey to a brighter future starts here.
                </p>
            </div>
            
        </div>
    </div>
</section>

<section class="flex flex-col text-black card-hidden">

    <div class="flex flex-col items-center bg-[#efefef] px-2 sm:px-4 py-10 md:px-10 lg:px-16 lg:py-10 gap-12">
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Explore our open programmes
        </h1>
        <div class=" gap-2  grid grid-cols-1 md:grid-cols-2">
            @foreach($subjects as $index => $data) 
                <div class="bg-white p-8 self-start felx flex-col  ">
                    <a href="{{ route('subject.details', ['name' => $data->slug]) }}" class="hover:underline underline-offset-2 text-[22px] text-dark font-canela">{{ $data->name }}</a>
                    <div class="w-[80px] h-[3px] bg-yellow mt-3"></div>
                    <p class="mt-6 text-[18px] font-canela text-dark mb-10">
                        {{ $data->description }}
                    </p>
                    @if ($data->courses->isNotEmpty())
                    <div class="tab w-full overflow-hidden border-b-2 border-dark">
                        <input class="absolute opacity-0" id="tab-single-{{$index}}" type="radio" name="tabs2">
                        <label class="block text-[16px] font-canela text-dark leading-normal cursor-pointer"
                            for="tab-single-{{$index}}">{{ $data->name }} programmes</label>
                            <div class="tab-content overflow-hidden flex flex-col my-3 gap-4  leading-normal">
                            @foreach($data->courses as $key => $course)
                            <a href="{{ route('course.details', ['course' => $course->slug]) }}" class="flex group gap-2 items-center h">
                                <span
                                    class="text-[18px] group-hover:underline underline-offset-2 font-semibold leading-[24px] text-dark">{{ $course->title }}</span>
                                <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                            </a>
                            
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            
            @endforeach
        </div>
    </div>

</section>

<section id="header-course" class="relative overflow-hidden min-h-screen max-h-screen">
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

<x-clients :id="1"
    :background="'#eeeeee'"
    :title="'Most of the Students are from'"
    :description="'Our students excel, securing positions at top companies, driving innovation, and achieving remarkable success.'"
    :logos="$clients"
/>
<section class="card-hidden px-6  lg:px-[48px]  my-8 show">
    <div class="flex flex-col gap-5 xl:gap-0 lg:flex-row items-stretch  gap-y-10">

        <div class="relative flex justify-center items-center py-4 xl:bg-[#f0f6ff] w-full basis-full">
            <div
                class="flex-1 xl:px-8  bg-white w-full xl:py-2  min-[1340px]:py-8 my-2 xl:absolute xl:-right-20 gap-2 flex flex-col">
                <h1 class="font-canela text-primary
                        text-[32px] leading-[40px]
                        md:text-[48px] md:leading-[58px]
                        min-[960px]:text-[36px] min-[960px]:leading-[55px] tracking-wide">
                    How to Apply?
                </h1>
                <div class="flex flex-col gap-2">
                    <p class="text-[16px] ">Click the button to explore exciting opportunities and take the first step
                        towards your dream job. Learn more about the application process and join our dynamic team
                        today! Don't miss out on your chance to shine.
                    </p>
                    
                </div>
                <a href="{{ route('admission') }}"
                    class="shrink-0 border-none font-ghothic inline-flex gap-2 items-center bg-crimson justify-center hover:bg-black  border text-white rounded px-2 py-3 self-start min-w-[180px]">
                    Apply Now
                    <i class="fa fa-chevron-right text-[16px]"></i>
                </a>
            </div>
        </div>

        <div class="flex-1 w-full basis-full">
            <img src="{{ asset('frontend/images/jpg/home-schooling-works.jpg') }}" alt=""
                class="w-full h-full object-cover">
        </div>
    </div>
</section>
@include('components.request_callback_form')
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