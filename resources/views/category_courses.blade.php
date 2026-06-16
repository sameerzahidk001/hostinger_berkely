@extends('layouts.app')
@push('style')
<style>
    .accordion button {
    color: black;
    }
    .accordion button::after{
        color: black;
        font-weight: 900;
        font-size: 16px;
    }
    .accordion [aria-expanded="true"]::after{
        color: black;
        font-weight: 900;
        font-size: 16px;
    }
</style>
@endpush

@section('content')

<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex ">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Categories</span>
            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">{{ $subject->name }}</span>
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">{{ $subject->name }}

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
        
        <h2 class="text-[18px] font-semibold font-canela">{{ $subject->name }} Courses ({{ $subject->courses->count() }})</h2>
        <!-- <div class="w-[100%] h-[1px] bg-gray-200"></div> -->
        <div class="bg-yellow w-[100%] h-[2px]"></div>
        
    </section>
    <!-- Section Two End -->
    
    <!-- Section Three Start -->
    <section class="mt-4 flex mb-[18px] py-0 z-20  flex-1 gap-4 min-[1200px]:px-[72px] md:px-12 px-6  ">
        <div class="grid grid-cols-1  w-full min-[550px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 place-content-between pb-2">
            
            @foreach($subject->courses as $key => $course)
            
            <div class="flex flex-col row-span-1  w-full  border rounded overflow-hidden">
                
                <a href="{{ route('course.details', $course->slug) }}" class="flex flex-1 flex-col px-[20px] py-[20px]">
                    
                    <div class="flex flex-col gap-1">
                        <h2 class="text-[18px] font-semibold  sm:min-h-auto sm:max-h-auto md:min-h-[53px] md:max-h-[53px] overflow-hidden">{{ $course->title }}</h2>
                            <div class="w-[80px] h-[3px] bg-yellow mt-2"></div>
                        <p class="text-[15px] ">{!! $course->short_description !!}</p>
                    </div>
                    
                </a>
            </div>
            
            @endforeach
            
        </div>

    </section>
    <!-- Section Three End -->

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
<!-- <script>
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
</script> -->
@endpush
