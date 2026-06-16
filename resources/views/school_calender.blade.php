@extends('layouts.app')
@push('style')

@endpush

@section('content')

<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex ">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            @php
                $eventType = request()->query('event_type');
            @endphp

            @if ($eventType === 'six-form')
                <h1 class="font-bold">Six Form Calendar</h1>
            @elseif($eventType === 'igcse')
                <h1 class="font-bold">IGCSE Calendar</h1>
            @else
                <h1 class="font-bold">Calendar Live</h1>
            @endif

            
            
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Calender Live

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

<section class="min-[1200px]:px-[48px]">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="flex gap-6 w-full p-4 items-start sticky top-20">

            <div class="flex flex-[2.5] pb-1 flex-col  gap-3">
                <!-- Catagory start -->
                <div class="flex flex-[2.5] pb-1 flex-col bg-[#efefef] gap-3">
                    <div class="flex items-center  shrink-0  gap-3 p-4">
                        <h3 class="shrink-0 w-1/3 font-semibold">Category: </h3>
                        <select name="" class="p-1 px-2  w-full" id="">
                            <option value=""> -- Select --</option>
                            @foreach($categories as $index => $category)
                                <option value="">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="px-3 py-4 bg-[#ffffff] flex gap-3 font-semibold">
                        Calander
                    </div>
                </div>
                <!-- Catagory End -->

                <!--Boxes and Paragraph start -->
                <div class="border-y border-dashed px-3 py-2 flex gap-4 flex-wrap">
                    
                    @foreach($categories as $index => $category)
                        <div class="flex gap-2 items-center">
                            <div class="min-h-[10px] min-w-[10px] bg-[#898989]"></div>
                            <a href="#" class="text-[14px] underline font-semibold">{{ $category->name }}</a>
                        </div>
                    @endforeach
                </div>
                <!--Boxes and Paragraph End -->

            </div>



            <div class="flex-[6] flex-col shrink-0 flex gap-4 justify-end">

            </div>
        </div>


    </div>

</section>

@endsection

@push('script')
    <script>
        // Smooth scroll to anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();

                const offsetTop = document.querySelector(this.getAttribute('href')).offsetTop;
                window.scrollTo({
                    top: offsetTop - 80, // Adjusted scroll position
                    behavior: 'smooth'
                });
            });
        });
    </script>
@endpush