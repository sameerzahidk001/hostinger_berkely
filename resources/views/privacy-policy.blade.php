@extends('layouts.app')
@push('style')

@endpush

@section('content')

<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Privacy Policy</span>
            
        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white">Privacy Policy

            </h2>
        </div>
        <p clas="text-[18px] text-white">
            Find out what it's like to take a course from current and past learners.
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

<section class="bg-[#efefef] w-full flex flex-col gap-5 px-3 md:px-16 lg:px-[71px] md:flex-row">


    @include('components.website-privacy-sidebar')

    <main class="md:w-2/3 lg:w-3/4 w-full py-4 min-h-screen">
        
        <div class="px-4 py-2 bg-white">
            <!-- <p class="mb-4">This Privacy Policy describes how your personal information is collected, used, and shared when you visit or make a purchase from www.school.berkeleyme.com (the “Site”).</p>
            <h2 class="text-2xl font-bold mb-2">PERSONAL INFORMATION WE COLLECT:</h2> -->

            <p class="mb-2">This Privacy Policy describes how your personal information is collected, used, and shared when you visit or make a purchase from http://eduberkeley.com (the “<strong>Site</strong>”).</p>
            <p class="mb-2"><strong>PERSONAL INFORMATION WE COLLECT:</strong></p>
            <p class="mb-2">
                When you visit the Site, we automatically collect certain information about your device, including information about your web browser, IP address, time zone, and some of the cookies that are installed on your device. Additionally,
                as you browse the Site, we collect information about the individual web pages or products that you view, what websites or search terms referred you to the Site, and information about how you interact with the Site. We refer to this
                automatically-collected information as “Device Information.”
            </p>
            <p class="mb-2"><strong>We collect Device Information using the following technologies:</strong></p>
            <p class="mb-2">
                – “Cookies” are data files that are placed on your device or computer and often include an anonymous unique identifier. For more information about cookies, and how to disable cookies, visit http://www.allaboutcookies.org.<br />
                – “Log files” track actions occurring on the Site, and collect data including your IP address, browser type, Internet service provider, referring/exit pages, and date/time stamps.<br />
                – “Web beacons,” “tags,” and “pixels” are electronic files used to record information about how you browse the Site.
            </p>
            <p class="mb-2">
                Additionally when you make a purchase or attempt to make a purchase through the Site, we collect certain information from you, including your name, billing address, shipping address, payment information (including credit card
                numbers), email address, and phone number. We refer to this information as “Order Information.”
            </p>
            <p class="mb-2">When we talk about “Personal Information” in this Privacy Policy, we are talking both about Device Information and Order Information.</p>
            <p class="mb-2"><strong>HOW DO WE USE YOUR PERSONAL INFORMATION?</strong></p>
            <p class="mb-2">
                We use the Order Information that we collect generally to fulfill any orders placed through the Site (including processing your payment information, arranging for shipping, and providing you with invoices and/or order
                confirmations). Additionally, we use this Order Information to:<br />
                Communicate with you;<br />
                Screen our orders for potential risk or fraud; and<br />
                When in line with the preferences you have shared with us, provide you with information or advertising relating to our products or services.
            </p>
            <p class="mb-2">
                We use the Device Information that we collect to help us screen for potential risk and fraud (in particular, your IP address), and more generally to improve and optimize our Site (for example, by generating analytics about how our
                customers browse and interact with the Site, and to assess the success of our marketing and advertising campaigns).
            </p>
            <p class="mb-2"><strong>SHARING YOUR PERSONAL INFORMATION</strong></p>
            <p class="mb-2">
                We share your Personal Information with third parties to help us use your Personal Information, as described above. For example, we use Shopify to power our online store–you can read more about how Shopify uses your Personal
                Information here: https://www.shopify.com/legal/privacy. We also use Google Analytics to help us understand how our customers use the Site–you can read more about how Google uses your Personal Information here:
                https://www.google.com/intl/en/policies/privacy/. You can also opt-out of Google Analytics here: https://tools.google.com/dlpage/gaoptout.
            </p>
            <p class="mb-2">Finally, we may also share your Personal Information to comply with applicable laws and regulations, to respond to a subpoena, search warrant or other lawful request for information we receive, or to otherwise protect our rights.</p>
            <p class="mb-2">
                <strong>BEHAVIOURAL ADVERTISING</strong><br />
                As described above, we use your Personal Information to provide you with targeted advertisements or marketing communications we believe may be of interest to you. For more information about how targeted advertising works, you can
                visit the Network Advertising Initiative’s (“NAI”) educational page at http://www.networkadvertising.org/understanding-online-advertising/how-does-it-work.
            </p>
            <p class="mb-2">
                <strong>You can opt out of targeted advertising by:</strong><br />
                OPT-OUT LINKS FROM WHICHEVER SERVICES BEING USED.<br />
                COMMON LINKS INCLUDE:<br />
                FACEBOOK – https://www.facebook.com/settings/?tab=ads<br />
                GOOGLE – https://www.google.com/settings/ads/anonymous<br />
                BING – https://advertise.bingads.microsoft.com/en-us/resources/policies/personalized-ads
            </p>
            <p class="mb-2">Additionally, you can opt out of some of these services by visiting the Digital Advertising Alliance’s opt-out portal at: http://optout.aboutads.info/.</p>
            <p class="mb-2">
                <strong>DO NOT TRACK</strong><br />
                Please note that we do not alter our Site’s data collection and use practices when we see a Do Not Track signal from your browser.
            </p>
            <p class="mb-2">
                <strong>YOUR RIGHTS</strong><br />
                If you are a European resident, you have the right to access personal information we hold about you and to ask that your personal information be corrected, updated, or deleted. If you would like to exercise this right, please
                contact us through the contact information below.
            </p>
            <p class="mb-2">
                Additionally, if you are a European resident we note that we are processing your information in order to fulfill contracts we might have with you (for example if you make an order through the Site), or otherwise to pursue our
                legitimate business interests listed above. Additionally, please note that your information will be transferred outside of Europe, including to Canada and the United States.
            </p>
            <p class="mb-2">
                <strong>DATA RETENTION</strong><br />
                When you place an order through the Site, we will maintain your Order Information for our records unless and until you ask us to delete this information.
            </p>
            <p class="mb-2">
                <strong>MINORS</strong><br />
                The Site is not intended for individuals under the age of 16.
            </p>
            <p class="mb-2">
                <strong>CHANGES</strong><br />
                We may update this privacy policy from time to time in order to reflect, for example, changes to our practices or for other operational, legal or regulatory reasons.
            </p>
            <p class="mb-2">
                <strong>CONTACT US<br /></strong>For more information about our privacy practices, if you have questions, or if you would like to make a complaint, please contact us by e-mail at info@eduberkeley.com or by mail using the
                details provided below:
            </p>
            <!-- <hr class="wp-block-separator has-css-opacity" />
            <p class="mb-2"><strong>Global Head Office: </strong>Kemp House, 152 – 160 City Road, London EC1V 2NX, United Kingdom.</p>
            <p class="mb-2"><strong>Regional Head Office: </strong>2601, Floor #26, Sheikh Rashid Tower, Dubai World Trade Centre, PO Box 9385, Dubai, United Arab Emirates.</p> -->
            

            
        </div>

    </main>

    </div>
</section>

@endsection

@push('script')
    
@endpush