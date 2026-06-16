@extends('layouts.app')
@push('style')
<style>
      .outline-custom {
          outline: 2px solid #e5e7eb;
      }
      #our_clients{
        margin-top: 0;
      }
    </style>
@endpush

@section('content')
<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#000435]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="items-center gap-1 hidden md:flex ">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Executive Education</span>
            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Visa</span>

        </div>

        <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
            <h1 class="text-[20px] leading-8  text-primary_orange">BERKELEY SCHOOL  OF BUSINESS, ARTS & SCIENCES</h1>
            <h2 class="text-[42px] md:text-[48px] leading-[58px] font-canela text-white font-semibold">Visas for Executive Education programmes</h2>
        </div>
        <p clas="text-[18px] text-white">Visas for executive education programs streamline international participation, ensuring seamless entry for global professionals advancing their careers.</p>
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
                Visas for Executive Education programmes”</h1>

                <p class="text-[18px] font-medium ">Executive Education visas play a crucial role in enabling ambitious professionals to pursue advanced learning opportunities and propel their careers forward. These visas grant access to esteemed academic resources and industry experts, empowering individuals to acquire strategic insights and cultivate essential leadership qualities. By facilitating lifelong learning and encouraging international collaboration, these visas create a vibrant environment where innovation and professional growth thrive.
                </p>
            </div>
            
        </div>
    </div>
</section>

<section class="flex flex-col text-black card-hidden">

    <div class="flex flex-col bg-[#efefef] px-2 sm:px-4 py-10 md:px-10 lg:px-16 lg:py-10 gap-4">
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Do I need to apply for a visa?</h1>
        <p class="text-[18px]">Participants who lack a UK or Irish passport will require immigration authorization to enter the United Kingdom.</p>
        <p class="text-[18px]">The permission you need will vary depending on the course you plan to pursue.</p>
        <ul class="list-disc list-inside">
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="font-bold">Student visa </span> &nbsp;– for full-time courses longer than six months
            </li>
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <span class="font-bold"> Visitor visa </span> &nbsp;– for courses or periods of study less than six months
            </li>
        </ul>
        <p class="text-[18px]">If your country is included in the list of <a class="underline" href="https://www.gov.uk/guidance/immigration-rules/immigration-rules-appendix-visitor-visa-national-list">visa nationals</a>, it is mandatory to obtain a visa before traveling to the UK. In such a situation, kindly get in touch with the British Embassy in your country of residence. Please be aware that visa processing times have been extended to an average of six weeks due to a significant backlog of applications at British Embassies worldwide, as opposed to the usual three weeks. Hence, we recommend that you submit your visa application at the earliest convenience.</p>
        <p class="text-[18px]">If your country is not on the list, you can request entry as a visitor at immigration control on arrival.</p>
        <p class="text-[18px]">Citizens of the EEA, Switzerland, Australia, Canada, Japan, New Zealand, Singapore, South Korea, and the USA are eligible to utilize the eGates upon arrival in the UK and will be granted automatic visitor status if they do not possess any other form of permission.</p>
    </div>

    <div class="flex flex-col bg-[#efefef] px-2 sm:px-4 pb-10 md:px-10 lg:px-16 lg:pb-10 gap-4">
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">What is a short period of study?</h1>
        <ul class="list-disc list-inside">
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <p class="font-semibold">A short course where the duration is less than six months.</p>
            </li>
        </ul>
    </div>

    <div class="flex flex-col bg-[#efefef] px-2 sm:px-4 pb-10 md:px-10 lg:px-16 lg:pb-10 gap-4">
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Visitor route</h1>
            <p class="text-[18px]">A short period of study, as outlined above, can be supported under the visitor immigration route.</p>
            <p class="text-[18px]">As status as a visitor in the UK has the following restrictions:</p>
        <ul class="list-disc list-inside">
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <p class="font-semibold">Employment in the UK, whether part-time or full-time vacation employment, is not permitted.</p>
            </li>

            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <p class="font-semibold">As part of the course of study, it is not possible to engage in a work placement or internship, whether paid or unpaid.</p>
            </li>
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <p class="font-semibold">Working independently or engaging in entrepreneurial endeavors is not permitted in the UK.</p>
            </li>
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <p class="font-semibold">It is not possible to prolong your stay in the United Kingdom.</p>
            </li>
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <p class="font-semibold">It is required that you show evidence of having sufficient funds to sustain yourself throughout your academic pursuits in the United Kingdom.</p>
            </li>
            <li class="flex items-center space-x-2">
                <img src="{{ asset('/frontend/images/svgs/tick.svg') }}" class="w-8 h-8" alt="">
                <p class="font-semibold">Your hosting academic institution will provide you with a tailored visa letter to assist with your entry into the UK as a student visitor.</p>
            </li>
        </ul>
        <p class="text-[18px]">If you are coming as a visitor to the UK, you are advised to take out medical insurance for your visit unless your country has a reciprocal agreement with the UK which may entitle you to free healthcare. <a class="underline" href="https://www.ukcisa.org.uk/Information--Advice/Studying--living-in-the-UK/Health-and-healthcare#layer-4055">Further information can be found on the UKCISA website</a></p>
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Applying before travelling to the UK: Visa Nationals</h1>
        <p class="text-[18px]">A Visa National refers to an individual who is required to obtain a visa prior to their journey to the United Kingdom. The current fee for applying for a six-month Standard Visitor visa at the embassy is £95. Upon approval, a multi-entry sticker will be affixed to your passport, granting a validity of six months. This allows you to depart and re-enter the UK using the same Standard Visitor visa, provided you can demonstrate your ongoing educational commitments. For further information, <a class="underline" href="https://www.gov.uk/standard-visitor">please visit the official gov.uk website to learn more</a>.</p>
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">As an EU/EEA/Swiss citizen, will I require a visa after Brexit?</h1>
        <p class="text-[18px]">As of 1 January 2021, individuals from the EU/EEA must obtain immigration authorization to study in the UK, unless they possess a recognized status through the EU Settlement Scheme, following the UK's departure from the EU on 31 December 2020.</p>
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Citizens of the European Union and the European Economic Area.</h1>
        <p class="text-[18px]">Travelling to the UK on your National ID card is being phased out and you should check in advance if you require a passport to enter the UK. You may also wish to obtain travel insurance as use of the EHIC was also phased out in 2021.</p>
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Providers of external information</h1>
        <p class="text-[18px]">The university is affiliated with the <a class="underline" href="https://www.ukcisa.org.uk/">UK Council for International Student Affairs (UKCISA)</a>, which serves as the primary advisory organization for international students in the UK. UKCISA offers valuable information and telephone assistance to students throughout their journey, from initial planning and preparation to post-study transitions. This includes up-to-date guidance on visas, tuition fees, living expenses, and practical matters like accommodation, healthcare, and council tax.</p>
        <p> <a class="underline" href="https://study-uk.britishcouncil.org/why-study/student-life">Visit the British Council website </a> for practical guidance for international students on living and studying in the UK.</p>
        <p> <a class="underline" href="https://www.gov.uk/government/organisations/uk-visas-and-immigration">Visit the Home Office website </a> for information on all aspects of immigration.</p>
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
            <span>Global Education,</span> 
            <span> Endless Possibilities</span>
        </h1>
        <span class="text-[18px] leading-[27px] max-w-[520px] text-center text-white font-bold mt-10">
            Embark on a transformative journey through global education, where each experience opens doors to infinite learning and opportunities.
        </span>
        <div class="absolute min-h-[20%] w-px top-0 left-1/2 transform -translate-x-1/2 bg-white">
        </div>
        <div class="absolute min-h-[16%] w-px bottom-0 left-1/2 transform -translate-x-1/2 bg-white">
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

@include('components.request_callback_form')

@endsection



@push('script')
@endpush