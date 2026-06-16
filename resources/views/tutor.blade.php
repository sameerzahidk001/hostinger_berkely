@extends('layouts.app')
@push('style')
<style>
      .outline-custom {
          outline: 2px solid #e5e7eb;
      }
    </style>
@endpush

@section('content')
<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex ">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Tutors</span>

        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8 font-canela text-primary_orange font-semibold">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Tutors</h2>
        </div>
        <p clas="text-[18px] text-white">Discover a nearby personal instructor. Our online directory of home tutors features a vast array of local private tutors available for your convenience.
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

<section class="card-hidden px-6 min-[1200px]:px-[140px] my-8 show  ">
    <div class=" text-[14px] text-black border-b-[2px] pb-4  flex  items-end gap-3">
        <div class="flex flex-col gap-2 text-[16px] min-w-[170px] font-semibold">
            <label for="cars">I'm looking for</label>
            <select name="cars" class="outline-custom outline-2 bg-white rounded-sm  h-[40px] p-2" id="cars">
                <option>Please Select</option>
                <option>Private Tutor Jobs</option>
                <option>Private Tutor</option>
                <option>Out of School Club</option>
            </select>
        </div>

        <div class="flex flex-col gap-2 text-[16px] min-w-[170px] font-semibold">
            <label for="cars">Within</label>
            <select name="cars" class="outline-custom outline-2 bg-white rounded-sm  h-[40px] p-2" id="cars">
                    <option value="1">1 Mile</option>
                    <option value="2">2 Miles</option>
                    <option value="3">3 Miles</option>
                    <option value="4">4 Miles</option>
                    <option value="5" selected="">5 Miles</option>
                    <option value="7">7 Miles</option>
                    <option value="10">10 Miles</option>
                    <option value="20">20 Miles</option>
                    <option value="30">30 Miles</option>
                    <option value="40">40 Miles</option>
            </select>
        </div>
        <div class="flex flex-col gap-2 text-[16px] w-full font-semibold">
            <label for="Postcode">Postcode</label>
            <div class="flex gap-2 outline-custom outline-2 rounded-sm">

                <input type="text" class="outline-custom outline-2 bg-white rounded-sm  h-[40px] p-2 w-full">

                <button class="">
                    <img src="{{ asset('/frontend/images/svgs/map-up.svg') }}" alt=""
                        class="w-6 h-6 p-1 max-w-6 max-h-6  rotate-45 min-w-6 min-h-6">
                </button>
            </div>
        </div>
        <div class="flex flex-col gap-2 text-[16px] w-full font-semibold">
            <label for="cars">Filter</label>
            <select name="cars" class="outline-custom outline-2 bg-white rounded-sm  h-[40px] p-2" id="cars">
                <option value="">All Results</option>
                <option value="">Private Tutors with DBS Checks</option>
                <option value="">Virtual Tutors</option>
                <option value="">Home School Helpers</option>
                <option value="">Maths Tutors</option>
                <option value="">English Tutors</option>
                <option value="">Biology Tutors</option>
                <option value="">Chemistry Tutors</option>
                <option value="">Physics Tutors</option>
                <option value="">French Tutors</option>
            </select>
        </div>
        <!-- <div class="flex flex-col gap-2 text-[16px] min-w-[170px] font-semibold">
            <label for="cars">Sort by</label>
            <select name="cars" class="outline-custom outline-2 bg-white rounded-sm  h-[40px] p-2" id="cars">
                <option value="volvo">Most Relevent</option>
                <option value="saab">Saab</option>
                <option value="mercedes">Mercedes</option>
                <option value="audi">Audi</option>
            </select>
        </div> -->

        <button class="outline-custom outline-2 bg-white rounded-sm  h-[40px] p-2 hover:bg-gray-200">
            <img src="{{ asset('/frontend/images/svgs/magnifierg.svg') }}" alt=""
                class="w-5 h-5 max-w-5 max-h-5 min-w-5 min-h-5">
        </button>
    </div>
</section>

<section class="card-hidden px-6 min-[1200px]:px-[140px] show mb-8">
    <div class="bg-[#000435] text-[14px] text-white p-6 rounded-lg flex flex-col gap-3">
        <h1 class="text-[20px] font-canela">Private Tutors</h1>
        <p>1. Discover a personal instructor in your vicinity. Our online database features numerous local private tutors available for your convenience.</p>
        <p>2. To find local tutor options, please input your complete postal code into the search field located at the top of the page.</p>
        <p>3. If you are a parent seeking a tutor, you have the opportunity to <a href="" class="underline">register for free</a> at no cost and publish an advertisement outlining your unique educational needs.</p>
        <p>4. To explore additional tutor search options, we recommend utilizing our  <a href="" class="underline">Advanced Search feature</a>.</p>
        <p>5. The private tutors who have most recently joined our platform are displayed above. If you are a tutor interested in adding your information, you have the option to <a class="underline" href="">register for free.</a>
        </p>
    </div>
</section>

<section class="card-hidden px-6 min-[1200px]:px-[140px] show mb-8">
    <div class="flex flex-col gap-4 pb-[24px] border-b-[2px]">
        <h1 class="text-[20px] text-secondary font-canela">Popular Searches</h1>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-0 gap-x-8">
            
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Private Tutors with DBS Checks</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Latin Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Primary Tutors up to 11 years</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Virtual Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Italian Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Secondary Tutors 12+</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Home School Helpers</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Law Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">11 Plus Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Maths Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Russian Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">GCSE Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">English Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Mandarin Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">A-Level Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Biology Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Japanese Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Further Education and University Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Chemistry Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Languages Tutors</a>
            </div>
            <div class="flex items-center gap-0">
                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <a href="" class="text-[16px] max-w-[420px]">Special Needs Tutors</a>
            </div>
           
        </div>
    </div>
</section>

<section class="card-hidden px-6 min-[1200px]:px-[140px] show mb-8">

    <div class="flex gap-10 flex-col lg:flex-row mb-8 pb-[12px] border-b-[2px]">
        <a href="" class="flex flex-col shrink-0 items-center gap-2">
            <img src="{{ asset('/frontend/images/jpg/Home-School-1.jpg') }}" alt=""
                class="w-40 h-40 object-cover ring-2 ring-primary_orange rounded-full overflow-hidden">
            <div class="flex items-center flex-col gap-1">
                <div class="flex gap-2">
                    <img src="{{ asset('/frontend/images/svgs/verified.svg') }}" alt="" class="w-6 h-6 ">
                    <img src="{{ asset('/frontend/images/svgs/id.svg') }}" alt="" class="w-6 h-6  ">
                </div>
                <span class="text-gray-400"> From £40.00/hour</span>
            </div>
        </a>
        <div class="flex flex-col gap-2">
            <div class="flex flex-col gap-1">
                <a href="" class="flex gap-2 justify-between">
                    <div class="inline-flex gap-2 font-bold text-[18px]">
                        ADZ<img src="{{ asset('/frontend/images/svgs/verified.svg') }}" alt=""
                            class="w-6 h-6 object-cover ">
                    </div>
                    <div class="flex gap-[2px]">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <span>8</span>
                    </div>
                </a>
                <div class="flex justify-between gap-2">
                    <a href="" class="flex justify-between gap-2">
                        Private Tutor in London Borough Of Islington
                    </a>
                    <a href="">Logged in today</a>
                </div>
                <div class="flex items-center gap-1">
                    <img src="{{ asset('/frontend/images/svgs/volt.svg') }}" alt="" class="w-3 h-6 object-cover ">
                    <p>Usually responds within 30 minutes</p>
                </div>
                <a href="" class="pt-2">Hi ! I am a wife, mother of two (aged 8 & 5) and a qualified teacher (QTS). If you were to
                    ask anyone
                    to describe me in
                    3 words it will be: Patient, Caring and committed. The ai…</a>
            </div>
            <div class="flex mt-auto flex-col gap-8 md:flex-row ">
                <button
                    class="inline-flex font-bold text-[18px] text-white hover:text-navy hover:bg-white gap-2 md:min-w-[200px] py-2 justify-center items-center bg-navy border-navy border-2">
                    <img src="{{ asset('/frontend/images/svgs/pers.svg') }}" alt="" class="w-6 h-6 ">
                    Read More</button>
                <button
                    class="inline-flex font-bold text-[18px] hover:text-white text-navy hover:bg-navy bg-white gap-2 md:min-w-[200px] py-2 justify-center items-center  border-2 border-navy ">
                    <img src="{{ asset('/frontend/images/svgs/pers.svg') }}" alt="" class="w-6 h-6 ">
                    Like</button>
                <button
                    class="inline-flex font-bold text-[18px] hover:text-white text-navy hover:bg-navy bg-white gap-2 md:min-w-[200px] py-2 justify-center items-center  border-2 border-navy ">
                    <img src="{{ asset('/frontend/images/svgs/pers.svg') }}" alt="" class="w-6 h-6 ">
                    Add to List</button>

            </div>
        </div>
    </div>

    <div class="flex gap-10 flex-col lg:flex-row mb-8 pb-[12px] border-b-[2px]">
        <a href="" class="flex flex-col shrink-0 items-center gap-2">
            <img src="{{ asset('/frontend/images/jpg/Home-School-1.jpg') }}" alt=""
                class="w-40 h-40 object-cover ring-2 ring-primary_orange rounded-full overflow-hidden">
            <div class="flex items-center flex-col gap-1">
                <div class="flex gap-2">
                    <img src="{{ asset('/frontend/images/svgs/verified.svg') }}" alt="" class="w-6 h-6 ">
                    <img src="{{ asset('/frontend/images/svgs/id.svg') }}" alt="" class="w-6 h-6  ">
                </div>
                <span class="text-gray-400"> From £40.00/hour</span>
            </div>
        </a>
        <div class="flex flex-col gap-2">
            <div class="flex flex-col gap-1">
                <a href="" class="flex gap-2 justify-between">
                    <div class="inline-flex gap-2 font-bold text-[18px]">
                        ADZ<img src="{{ asset('/frontend/images/svgs/verified.svg') }}" alt=""
                            class="w-6 h-6 object-cover ">
                    </div>
                    <div class="flex gap-[2px]">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <img src="{{ asset('/frontend/images/svgs/star.svg') }}" alt="" class="w-5 h-5 object-cover ">
                        <span>8</span>
                    </div>
                </a>
                <div class="flex justify-between gap-2">
                    <a href="" class="flex justify-between gap-2">
                        Private Tutor in London Borough Of Islington
                    </a>
                    <a href="">Logged in today</a>
                </div>
                <div class="flex items-center gap-1">
                    <img src="{{ asset('/frontend/images/svgs/volt.svg') }}" alt="" class="w-3 h-6 object-cover ">
                    <p>Usually responds within 30 minutes</p>
                </div>
                <a href="" class="pt-2">Hi ! I am a wife, mother of two (aged 8 & 5) and a qualified teacher (QTS). If you were to
                    ask anyone
                    to describe me in
                    3 words it will be: Patient, Caring and committed. The ai…</a>
            </div>
            <div class="flex mt-auto flex-col gap-8 md:flex-row ">
                <button
                    class="inline-flex font-bold text-[18px] text-white hover:text-navy hover:bg-white gap-2 md:min-w-[200px] py-2 justify-center items-center bg-navy border-navy border-2">
                    <img src="{{ asset('/frontend/images/svgs/pers.svg') }}" alt="" class="w-6 h-6 ">
                    Read More</button>
                <button
                    class="inline-flex font-bold text-[18px] hover:text-white text-navy hover:bg-navy bg-white gap-2 md:min-w-[200px] py-2 justify-center items-center  border-2 border-navy ">
                    <img src="{{ asset('/frontend/images/svgs/pers.svg') }}" alt="" class="w-6 h-6 ">
                    Like</button>
                <button
                    class="inline-flex font-bold text-[18px] hover:text-white text-navy hover:bg-navy bg-white gap-2 md:min-w-[200px] py-2 justify-center items-center  border-2 border-navy ">
                    <img src="{{ asset('/frontend/images/svgs/pers.svg') }}" alt="" class="w-6 h-6 ">
                    Add to List</button>

            </div>
        </div>
    </div>
</section>

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
@endpush