@extends('layouts.app')
@push('style')

@endpush

@section('content')
<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Contact Us</span>

        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Contact Us
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
        <img src="{{ asset('frontend/images/jpg/60.jpg') }}" alt=""
            class="object-cover h-full md:max-h-full md:w-full md:h-full">
    </div>
</section>



<div class="min-[1200px]:px-[72px] md:px-12 px-6 mx-auto p-5">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-y-6">
        <div class="bg-[#000435] md:col-span-4 p-10 text-white">
            <p class="mt-4 text-sm leading-7 font-regular uppercase">
                Contact
            </p>
            <h3 class="text-3xl sm:text-4xl leading-normal font-extrabold tracking-tight">
                Get In <span class="text-primary_orange">Touch</span>
            </h3>
            <p class="mt-4 leading-7 text-gray-200">
                Berkeley Middle East is the largest Executive Development Center in the Gulf region, promoting American education with a commitment to excellence, integrity, and professional development. We educate individuals who make a global impact and offer career growth opportunities tailored to distinct goals.
            </p>

            <div class="flex flex-col items-start mt-4">
                <span class="text-sm font-bold uppercase text-primary_orange">United Kingdom </span>
                <a href="mailto:admissions@eduberkeley.com" class="font-normal hover:underline">admissions@eduberkeley.com</a>
                <a href="#" class="font-normal hover:underline">+44 7306 279111</a>
                <a href="#" class="font-normal hover:underline">Berkeley Square, Mayfair, London, W1J, United Kingdom</a>

            </div>
            <div class="flex flex-col items-start mt-4">
                <span class="text-sm font-bold uppercase text-primary_orange">United Arab Emirates</span>
                <a href="mailto:admissions@eduberkeley.com" class="font-normal hover:underline">admissions@eduberkeley.com</a>
                <a href="#" class="font-normal hover:underline">+971 58 555 5657</a>
                <a href="#" class="font-normal hover:underline">2501, Floor #25, Sheikh Rashid Tower, Dubai World Trade Centre, PO Box 9385, Dubai, United Arab Emirates.</a>

            </div>
            

            <div class="flex flex-wrap justify-between flex-1 gap-2 mt-8">
                <a href="https://www.linkedin.com/company/berkeleyme/" target="_blank">
                    <img src="{{ asset('frontend/images/svgs/linkedin.svg') }}" alt="" class="w-[35px] h-[35px]">
                </a>
                <a href="https://web.facebook.com/berkeleyme" target="_blank">
                    <img src="{{ asset('frontend/images/svgs/facebook-sticky.svg') }}" alt="" class="w-[35px] h-[35px]">
                </a>
                <a href="https://www.instagram.com/berkeleymiddleeast/" target="_blank">
                    <img src="{{ asset('frontend/images/svgs/instagram.svg') }}" alt="" class="w-[35px] h-[35px]">
                </a>
                <a href="https://www.youtube.com/user/TheBerkeleyme" target="_blank">
                    <img src="{{ asset('frontend/images/svgs/youtube.svg') }}" alt="" class="w-[35px] h-[35px]">
                </a>
                <a href="https://www.tiktok.com/@berkeleyme" target="_blank">
                    <img src="{{ asset('frontend/images/svgs/tiktok.svg') }}" alt="" class="w-[35px] h-[35px]">
                </a>
                <a href="https://www.tiktok.com/@berkeleyme" target="_blank">
                    <img src="{{ asset('frontend/images/svgs/twitter.svg') }}" alt="" class="w-[35px] h-[35px]">
                </a>
                
            </div>

        </div>

        <form  id="contactUsForm" action="{{ route('zoho.contact-us') }}" method="POST" class="md:col-span-8 sm:px-0 md:px-10 md:pb-10">
            @csrf

            <input type="hidden" name="Inquiry_Source" value="Website">
            <input type="hidden" name="Date_of_Entry" value="">

            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                        for="grid-first-name">
                        First Name
                    </label>
                    <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        id="grid-first-name" name="Student_First_Name" type="text" placeholder="Your First Name">
                </div>
                <div class="w-full md:w-1/2 px-3">
                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                        for="grid-last-name">
                        Last Name
                    </label>
                    <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        id="grid-last-name" name="Student_Last_Name" type="text" placeholder="Your Last Name">
                </div>
            </div>

            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                        for="grid-password">
                        Email Address
                    </label>
                    <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        id="grid-email" name="Parent_Email" type="email" placeholder="Your Email address">
                </div>
                <div class="w-full md:w-1/2 px-3">
                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                        for="grid-password">
                        Mobile No
                    </label>

                    <input type="tel" id="phone2" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                         name="Phone_No" placeholder="Your Mobile No">
                </div>
            </div>

            <div class="flex flex-wrap -mx-3 mb-6">
                <div class="w-full px-3">
                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                        for="grid-password">
                        Your Message
                    </label>
                    <textarea class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        name="Query" rows="10">
                    </textarea>
                </div>

                <div class="flex flex-col justify-between w-full px-3">
                    
                    <button type="submit" id="submitButton" class="shadow bg-[#000435] hover:bg-primary_orange focus:shadow-outline focus:outline-none text-white font-bold py-2 px-6 rounded">
                        
                        <span id="buttonText">Send Message</span>
                        <span id="spinner" class="hidden ml-2 spinner-border" role="status" aria-hidden="true"></span>
                    </button>

                    <div id="responseMessage2" class="flex items-center justify-start text-center text-dark font-bold mt-4"></div>

                </div>

            </div>

        </form>

    </div>

    <div style="width: 100%" class="my-8">
        <iframe width="100%" height="400" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com/maps?width=100%25&amp;height=400&amp;hl=en&amp;q=%20Berkeley%20Square,%20Mayfair,%20London,%20W1J,%20United%20Kingdom+(Berkeley%20School%20of%20Business,%20Arts%20&amp;%20Sciences)&amp;t=&amp;z=14&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"><a href="https://www.gps.ie/">gps vehicle tracker</a></iframe>
    </div>
</div>



@endsection

@push('script')

    <script>
        const input = document.querySelector("#phone");
        window.intlTelInput(input, {
            initialCountry: "ae",
            separateDialCode: true,
            utilsScript: "/intl-tel-input/js/utils.js?1716383386062" // just for formatting/placeholders etc
        });

        // Js for Captchaue

    </script>



@endpush