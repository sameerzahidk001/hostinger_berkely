@extends('layouts.app')
@push('style')
<style>
    .active {
        background-color: #000435; /* Adjust the color as per your design */
        color: white;
    }
    .b-custom{
        border: 1px solid gray;
    }
    .active-01{
        background-color: #bc1701;
        color: white;
    }
</style>
@endpush

@section('content')
<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Categories</span>
            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Open Programs</span>
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <!-- <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Exective Education</h2> -->
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Explore our open programmes</h2>
        </div>
        <!-- <p clas="text-[18px] text-white"></p> -->
        <p clas="text-[18px] text-white">Explore our programmes and gain the expertise to lead, manage, and drive innovation, preparing you for the challenges and opportunities of the future.
        </p>
        <!-- <div class="flex gap-6 mt-4 md:mt-auto">
            <a href="{{ route('contact') }}"
                class="text-center border py-4 px-3 w-full border-primary_orange bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                Enquire
            </a>
            <a href="{{ route('school-calender') }}"
                class="text-center border py-4 px-3 w-full border-primary_orange hover:bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">
                Join our next event
            </a>
        </div> -->

    </div>
    <div class=" flex xl:px-0 md:top-0 md:absolute md:right-0 z-40 md:w-[50%] m-0 flex-1 flex-col gap-4 md:h-full">
        <img src="{{ asset('frontend/images/jpg/60.jpg') }}"
            alt="" class="object-cover h-full md:max-h-full md:w-full md:h-full">
    </div>
</section>
<section class="flex flex-col text-black card-hidden bg-white">

    <!-- <h1 class="mx-auto pt-10 text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Explore our open programmes</h1> -->
    <div class="flex flex-col items-center  px-2 sm:px-4 py-10 md:px-10 lg:px-32 lg:py-10 gap-8">
        <div class="bg-[#F3F3F1] p-4 b-custom">
            <h3 class="text-[18px] font-semibold pb-2">Browse Courses</h3>
            <div class="gap-2 grid grid-cols-1 md:grid-cols-3 w-full text-[16px] pb-6 cursor-pointer leading-normal">
                @foreach($schools as $index => $school)
                    <div class="b-custom school-item bg-white p-3 self-start flex flex-col justify-center items-center min-h-[72px]" data-school-id="{{ $school->id }}">
                        <a href="#" class="school-info text-center">{{ $school->name }}</a>
                    </div>
                @endforeach
            </div>

            <div id="school-categories-container" class="w-full">
            
            </div>
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

        <!-- <a href="http://127.0.0.1:8000/course-cat" class="flex  mt-10 items-center group  gap-2">
            <div
                class="flex  group-hover:bg-primary bg-gray_one justify-center items-center rounded-full  self-center min-h-10 min-w-10">
                <img src="https://training1.berkeleyme.com/public/frontend/images/svgs/arrow-right.svg" class="w-[28px] h-4" alt="" />
            </div>
            <h2 class="font-bold text-[18px] text-white max-w-[300px] ">Explore the Course Catalogue</h2>
        </a> -->
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
{{-- <script>
    $(document).ready(function () {
      // AJAX request on document load
      var schoolId = $('#school-info').data('school-id'); // Get school ID from data attribute
       // alert(schoolId);
      //console.log('School ID:', schoolId); // Debugging: print the school ID

      $.ajax({
        url: '{{ route("school-categories-ajax") }}',  // Replace with your actual endpoint URL
        method: 'GET',  // Can be 'POST' depending on the use case
        data: { school_id: schoolId }, // Send the school ID in the request
        success: function (response) {
          //console.log('Success:', response); // Handle success response
          $('#school-categories-container').html(response);
        },
        error: function (error) {
          console.error('Error:', error); // Handle error response
        }
      });
    });
</script> --}}

<script>
    $(document).ready(function () {
        // Function to handle AJAX request
        function fetchCategories(schoolId) {
            $.ajax({
                url: '{{ route("school-categories-ajax") }}',  // Your actual route URL
                method: 'GET',  // Can be 'POST' depending on the use case
                data: { school_id: schoolId }, // Send the school ID in the request
                success: function (response) {
                    // Replace the container with the response
                    $('#school-categories-container').html(response);
                },
                error: function (error) {
                    console.error('Error:', error); // Handle error response
                }
            });
        }

        // On document load, trigger AJAX for the first school
        var firstSchoolDiv = $('.school-item').first();  // Get the first school div
        var firstSchoolId = firstSchoolDiv.data('school-id');
        firstSchoolDiv.addClass('active');  // Add active class to the first school div
        fetchCategories(firstSchoolId); // Fetch categories for the first school on load

        // When any school div is clicked
        $(document).on('click', '.school-item', function (e) {
            e.preventDefault(); // Prevent default action
            
            var schoolId = $(this).data('school-id'); // Get school ID from the parent div
            
            // Remove active class from all school-item divs
            $('.school-item').removeClass('active');
            
            // Add active class to the clicked parent div
            $(this).addClass('active');
            
            // Fetch categories for the selected school
            fetchCategories(schoolId);
        });
    });
</script>
@endpush

