@extends('layouts.app')

@section('content')
<!-- Hero section start  -->
  
    <section id="header-course" class="relative  overflow-hidden min-h-screen max-h-screen">
        <div
            class="absolute scroll-smooth bg-black  top-0 flex justify-center flex-col  z-30 h-full w-full items-center">
            <img src="{{ asset('frontend/images/jpg/welcome-banner.jpg') }}" class="min-h-screen max-h-screen object-cover w-full"
                alt="">
        </div>
        <div class="absolute px-2   top-0 flex justify-center flex-col  z-40 h-full w-full items-center ">
            <h1
                class="text-white text-[28px] leading-[42px] md:text-[28px] md:leading-[48px] lg:text-[80px] xl:text-[116px] font-canela    max-w-[490px]  text-center lg:leading-[80px] xl:leading-[116px]">
                Knowledge Universe
            </h1>
            <span class="text-[18px] leading-[27px] max-w-[520px] text-center text-white font-bold mt-10">
            The World's Best Education with the best-in-class faculty and students from across the globe.</span>

            <!-- <a href="{{ route('courses') }}" class="flex  mt-10 items-center group  gap-2">
                <div
                    class="flex  group-hover:bg-primary bg-gray_one justify-center items-center rounded-full  self-center min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                </div>
                <h2 class="font-bold text-[18px] text-white max-w-[300px] ">Explore the Course Catalogue</h2>
            </a> -->
            <div class="absolute min-h-[20%] w-px top-0 left-1/2 transform -translate-x-1/2 bg-white">
            </div>
            <div class="absolute min-h-[16%] w-px bottom-0 left-1/2 transform -translate-x-1/2 bg-white">
            </div>
        </div>
    </section>
<!-- Hero section End  -->
<!-- First section start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] mt-10 
    lg:pt-0 md:px-12 flex w-full flex-1 justify-between  min-h-[148px]">
        <div class="flex flex-col w-full justify-start   md:flex-row gap-4 lg:gap-12">
            <h1 class="font-canela flex-1 basis-full text-primary
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">
                The World’s Best Collection of Courses
            </h1>
            <a href="{{ route('courses') }}" class="flex md:mt-6  bg basis-full flex-1 items-center group  gap-2">
                <div
                    class="flex group-hover:bg-primary justify-center items-center rounded-full bg-secondary  min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                </div>
                <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Search courses by index</h2>
            </a>
        </div>
    </section>
    <!-- First section End -->

    <!-- Line start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] mt-10">
        <div class="w-full bg-primary h-1"></div>
    </section>
    <!-- line end -->

    <!-- Section two start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] mt-[60px]">
        <div class="flex flex-col md:flex-row items-start min-[968px]:items-center gap-x-24 gap-y-10">
            <div class="flex-1 w-full ">
                <img src="{{ asset('frontend/images/jpg/Berkeley-Square-USA.jpg') }}"
                    alt="" class="w-full h-full object-cover">
            </div>

            <div class="relative mt-10 flex-1 w-full">
                <div class="absolute  z-20 top-[-52px]"><img src="{{ asset('frontend/images/svgs/quote.svg') }}"
                        class="  w-[80px] h-[80px]">
                </div>
                <div class="flex gap-8 flex-col">
                    <h1 class="font-canela text-[36px] leading-[41px] lg:leading-[43px]">
                    Berkeley Square School of Management, Arts and Sciences”</h1>
                        
                    <p class="text-[18px] font-medium ">Experience the transformative power of education at our center, where we empower minds to create a better future. Immerse yourself in the academic excellence at Berkeley Hills, located in the heart of California. Our center offers a dynamic learning environment, fostering innovation and critical thinking. Join us and be part of a community dedicated to making a difference in the world. Your journey to a brighter future starts here.
                    </p>
                </div>
                <a href="#" class="flex flex-1 items-center group mt-10 gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Explore Executive Development Center
</h2>
                </a>
            </div>
        </div>
    </section>
    <!-- Section two end -->

    <!-- section three start  -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 w-full mt-16">
        <div class="grid grid-cols-1 place-content-center md:grid-cols-2 gap-10 xl:grid-cols-3">
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Executive-Education.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Exective Education</span>
                    <p class="hidden font-semibold  group-hover:block text-white text-[14px]">
                    Enhance your leadership skills and business acumen with our Executive Education programs. Designed for professionals seeking to accelerate their careers, our courses offer practical insights, strategic thinking, and networking opportunities to drive personal and organizational success. Elevate your career today!</p>
                    <a href="{{ route('exective-education') }}" class="flex items-center gap-2">
                        <div
                            class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[16px] text-white">Executive Education Centre focuses on developing minds to lead innovation and creativity at work place
                        </h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>

            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/College-1.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">College</span>
                    <p
                        class="hidden  font-semibold group-hover:block text-white delay-300  text-[14px] transition-all duration-400 ease-in-out">
                        Experience a transformative college journey at our institution. With a focus on academic excellence, holistic development, and innovative learning, we provide a vibrant campus life, expert faculty, and diverse opportunities to prepare you for a successful future. Join us and unleash your potential!
                    </p>
                    <a href="{{ route('college') }}" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[16px]  text-white">Our Colleges are a great source of affordable education bypassing traditional barriers in education field</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/School.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">School</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[14px]">Discover an enriching educational experience at our school. We offer a nurturing environment, dedicated teachers, and a well-rounded curriculum to inspire a lifelong love for learning. Prepare for a bright future with us, where every student thrives and achieves their full potential.
                    </p>
                    <a href="{{ route('school') }}" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[16px]  text-white">Our schools offer affordable education, breaking traditional barriers in the field. Join us to access quality education without the hefty price tag!</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>

        </div>

    </section>
    <!-- section three end -->

    <!-- Section four start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] mt-16 md:px-12">
        <div class="flex flex-col lg:flex-row-reverse items-center gap-x-24 gap-y-10">

            <div class="flex-1 ">
                <img src="{{ asset('frontend/images/jpg/Berkeley-Square-UK.jpg') }}" alt=""
                    class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
            </div>
            <div class="relative flex-1 flex flex-col gap-1">

                <h1 class="font-canela text-primary
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                ">Berkeley Square London, United Kingdom
                </h1>
                <div class="flex flex-col">
                    <p class="text-[18px] font-medium text-primary my-6">Experience the vibrant academic atmosphere of London at Berkeley Square as your study destination. Located in the heart of London, United Kingdom, our institution offers a prestigious learning environment surrounded by cultural landmarks and opportunities for personal and professional growth.
                    </p>
                    <!-- <p class="text-[18px] font-medium text-primary">Dimick’s recommendations for engaging with climate anxiety, or
                        be transported with climate fiction recommended by
                        English Professor James Engell.
                    </p> -->
                </div>
                <a href="{{ route('courses') }}" class="flex flex-1 items-center group  gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[420px] text-primary">Search Courses in United Kingdom & Europe</h2>
                </a>
            </div>
        </div>
    </section>
    <!-- Section four end -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 w-full mt-16 show">
        <div class="grid grid-cols-1 place-content-center md:grid-cols-2 gap-4 xl:grid-cols-3">

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('/frontend/images/jpg/CFO-Academy.jpg') }}" class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <!-- <span class="text-white">
                        KEY STAGE 3: YEARS 7-9 | AGES 11-14
                    </span> -->
                    <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">CFO Academy</span>
                    <p class="hidden font-semibold  group-hover:block text-white text-[18px]">
                    CFO Academy offers advanced financial training programs for Chief Financial Officers to enhance strategic and leadership skills.</p>
                    <a href="#" class="flex items-center gap-2">
                        <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('/frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                        </div>
                        <h2 class="font-bold text-[18px] text-white">Learn More
                        </h2>
                    </a>
                </div>

                <div class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>

            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('/frontend/images/jpg/CEO-Academy.jpg') }}" class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <!-- <span class="text-white">
                    Key Stage 4: Years 10-11 | Ages 14-16
                    </span> -->
                    <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">CEO Academy</span>
                    <p class="hidden  font-semibold group-hover:block text-white delay-300  text-[18px] transition-all duration-400 ease-in-out">
                    CEO Academy provides executive training programs for CEOs to develop strategic leadership, decision-making, and organizational management skills.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('/frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Learn More</h2>
                    </a>
                </div>

                <div class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('/frontend/images/jpg/Entrepreneurs.jpg') }}" class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <!-- <span class="text-white">
                    Key Stage 5:  A Level College | Ages 16-18
                    </span> -->
                    <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Entrepreneurs Academy</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Entrepreneurs Academy delivers comprehensive training programs for aspiring entrepreneurs, focusing on business development, innovation, and startup management skills.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('/frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Learn More</h2>
                    </a>
                </div>

                <div class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>

        </div>
    </section>
    
    <section class="card-hidden px-6 min-[1200px]:px-[72px]  md:px-12  my-16">
        <div class="flex flex-col md:flex-row items-start gap-4">
            <h1 class="flex-1   m-0 p-0 
                font-canela text-primary
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]
              ">
              Home Schools
            </h1>
            <div class="flex flex-1 gap-10 flex-col">
                <p class="font-medium text-[18px] text-priamry">Enroll your child in the UK's premier school for tailored education and flexible learning. Watch them thrive in our supportive community with a top-notch British education accessible globally. Join us to unlock your child's full potential!
                </p>
                <a href="{{ route('school') }}" class="flex flex-1 items-center group  gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Join our global school community today!

                    </h2>
                </a>
            </div>
        </div>
    </section>
    <!-- New Start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 w-full">
        <div class="grid grid-cols-1 place-content-center md:grid-cols-2 gap-4 xl:grid-cols-3">

            <!-- <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Primary-1.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="text-white">
                        KEY STAGE 2: YEARS 3-6 | AGES 7-11
                    </span>
                    <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Primary</span>
                    <p class="hidden font-semibold  group-hover:block text-white text-[18px]">
                    Our primary school prioritizes your child's holistic development, fostering a nurturing community environment with personalized support from dedicated class teachers.</p>
                    <a href="{{ route('primary-school-online') }}" class="flex items-center gap-2">
                        <div
                            class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px] text-white">Learn More
                        </h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>

            </div> -->

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Secondary.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="text-white">
                        KEY STAGE 3: YEARS 7-9 | AGES 11-14
                    </span>
                    <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Secondary</span>
                    <p class="hidden font-semibold  group-hover:block text-white text-[18px]">
                    Secondary school learning offers high-quality instruction, reduced distractions, and a plethora of extracurricular activities beyond the traditional classroom setting.</p>
                    <a href="{{ route('secondary-school-online') }}" class="flex items-center gap-2">
                        <div
                            class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px] text-white">Learn More
                        </h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>

            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/GCSE.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="text-white">
                    Key Stage 4: Years 10-11 | Ages 14-16
                    </span>
                    <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">GCSE</span>
                    <p
                        class="hidden  font-semibold group-hover:block text-white delay-300  text-[18px] transition-all duration-400 ease-in-out">
                        Select from our wide array of International GCSEs for a comprehensive two-year study program, taught by subject-matter experts.
                    </p>
                    <a href="{{ route('gcse-online') }}" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Learn More</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/six-form.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="text-white">
                    Key Stage 5:  A Level College | Ages 16-18
                    </span>
                    <span class=" text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">Sixth form</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Our A Levels program prepares Sixth Form students for future careers and university, providing the independence and flexibility they need.
                    </p>
                    <a href="{{ route('six-form') }}" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Learn More</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>

        </div>
    </section>
    <!-- new ends -->
    <!-- SECTION FIVE NEW ADDED START -->
    <!-- <section class="card-hidden px-6 min-[1200px]:px-[72px]  md:px-12  mt-[70px] md:my-[80px]">
        <div class="flex flex-col md:flex-row items-start gap-4">
            <h1 class="flex-1   m-0 p-0 
                font-canela text-primary
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]
              ">
              Explore Courses
            </h1>
            <div class="flex flex-1 gap-10 flex-col">
                <p class="font-medium text-[18px] text-priamry">The Berkeley community, from the Innovation Labs to the Salata Institute, is working on climate solutions that can address the issues of today and tomorrow the world.
                </p>
                <a href="#" class="flex flex-1 items-center group  gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Explore all the sustainable work happening on Berkeley campus

                    </h2>
                </a>
            </div>
        </div>
    </section> -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] flex items-center  bg-[#0E0E0E]  mb-16 mt-16 min-h-[340px]">
        <div class="flex flex-col md:flex-row gap-4">
            <h1 class="flex-1  font-canela
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px] text-white">
                Women Entrepreneurs Support Centre
            </h1>
            <div class="flex flex-col gap-12 flex-1">
                <p class="font-bold text-[18px]  text-white">
                    The Women Entrepreneurs Support Centre is dedicated to empowering women in business by providing essential resources, training, and networking opportunities.
                </p>
                <a href="https://women.berkeleyme.com/" class="flex  items-center group gap-6" target="blank">
                    <div
                        class="flex  group-hover:bg-primary justify-center items-center rounded-full bg-secondary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[300px] text-white">Learn more about Women Entrepreneurs Support Centre
                    </h2>
                </a>
            </div>
        </div>
    </section>
    <!-- SECTION FIVE NEW ADDED END -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px]  w-full">
        <div class="grid grid-cols-1 place-content-center sm:grid-cols-3 gap-10 ">

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Diplomas.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Diplomas</span>
                    <p class="hidden font-semibold  group-hover:block text-white text-[18px]">
                    Embark on a journey of learning and growth with our diverse diploma programs. Whether you're starting your career or seeking to enhance your skills, our courses offer practical knowledge and industry-relevant skills to help you succeed. Start your diploma program with us today!</p>
                    <a href="{{ route('courses') }}" class="flex items-center gap-2">
                        <div
                            class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px] text-white">Explore the Diplomas
                        </h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>

            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Graduate-Courses.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Graduate Courses</span>
                    <p
                        class="hidden  font-semibold group-hover:block text-white delay-300  text-[18px] transition-all duration-400 ease-in-out">
                        Elevate your career with our graduate and master's courses. Designed for working professionals, our programs offer flexible schedules and practical knowledge to help you advance in your field. Take the next step in your career journey today!
                    </p>
                    <a href="{{ route('courses') }}" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Explore Graduate Courses</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>

            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Masters.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Master Courses</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Unlock your full potential with our master's courses. Designed for ambitious professionals, our programs offer advanced knowledge and skills to propel your career forward. Benefit from expert instruction and a supportive learning environment. Pursue excellence with us today!
                    </p>
                    <a href="{{ route('courses') }}" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Explore Master Courses</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>
            <!-- <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="https://www.harvard.edu/wp-content/uploads/2024/04/092509_Features_KS_016-900x1350.jpeg"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Business Support centre </span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Divinity School alum Aliyah
                        Collins
                        launched the Eco-Healing Project
                        to help historically Black colleges
                        and universities (HBCUs) plant on-
                        campus gardens.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Our business support entrepreneurs and executives for  putting minds together for a better solution</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div> -->
        </div>
    </section>
    <!-- section six start -->
    <!-- <section class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12  mt-[52px]">
        <div class="flex flex-col lg:flex-row  gap-6">
            <div class="flex-1 w-full">
                <div class="min-w-[300px] xl:min-w-[770px] overflow-hidden group w-full">
                    <img src="https://www.harvard.edu/wp-content/uploads/2024/03/MethaneSAT-Model-Credit-MethaneSAT-736x489.png"
                        alt="" class="xl:min-w-[770px] hover:scale-105 transition-all ease-in duration-200  w-full">
                </div>
                <h3 class=" mt-5 text-[20px] font-canela sm:text-[28px] md:text-[42px] text-primary  bg lg:text-[56px]">
                Berkeley Courses</h3>
                <p class="font-semibold ">Unlock your potential and celebrate your achievements with us as you embark on the next chapter of your academic journey.</p>
            </div>
            <div class="flex-1">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-row  justify-between gap-4">
                        <div class="flex  gap-3 flex-col ">
                            <div class="flex gap-2 flex-col ">
                                <a href="" class="text-[18px] font-bold">Professional Courses</a>
                                <p class="font-semibold">This is an age of professional certification. Almost every employer is looking for individuals having professional certification as part of their strive for excellence and compliance with best practices. Explore the range of professional certifications lead to a regulatory license.</p>
                            </div>
                        </div>
                        <div class="max-w-[125px] min-w-[125px] group overflow-hidden">
                            <img src="https://www.harvard.edu/wp-content/uploads/2024/03/20190328_Memme-Onwudiwe-19_LGranger002-2-375x281.png"
                                alt=""
                                class="min-w-[125px] max-w-[125px] object-cover h-24 group-hover:scale-105 transition-all ease-in duration-200">
                        </div>

                    </div>
                    <div class="w-full bg-primary h-[1px] "></div>
                    <div class="flex flex-row  justify-between gap-4">
                        <div class="flex  gap-3 flex-col ">
                            <div class="flex gap-2 flex-col ">
                                <a href="" class="text-[18px] font-bold">Masters Degree</a>
                                <p class="font-semibold">We have Collaborated with world's best universities to provide online master's degrees. you can take admissions from anywhere in the world. You are part of the global community alumni and will get the same benefits as any other student gets on studying in campus, at much more economical prices. Explore the available master's degrees.</p>
                            </div>
                        </div>
                        <div class="max-w-[125px] min-w-[125px] group overflow-hidden">
                            <img src="https://www.harvard.edu/wp-content/uploads/2024/03/20190328_Memme-Onwudiwe-19_LGranger002-2-375x281.png"
                                alt=""
                                class="min-w-[125px] max-w-[125px] object-cover h-24 group-hover:scale-105 transition-all ease-in duration-200">
                        </div>

                    </div>
                    <div class="w-full bg-primary h-[1px] "></div>
                    <div class="flex flex-row  justify-between gap-4">
                        <div class="flex  gap-3 flex-col ">
                            <div class="flex gap-2 flex-col ">
                                <a href="" class="text-[18px] font-bold">Graduation</a>
                                <p class="font-semibold">Our graduation programs are designed to empower individuals with the knowledge, skills, and confidence to excel in their chosen fields and make meaningful contributions to society.</p>
                            </div>
                        </div>
                        <div class="max-w-[125px] min-w-[125px] group overflow-hidden">
                            <img src="https://www.harvard.edu/wp-content/uploads/2024/03/20190328_Memme-Onwudiwe-19_LGranger002-2-375x281.png"
                                alt=""
                                class="min-w-[125px] max-w-[125px] object-cover h-24 group-hover:scale-105 transition-all ease-in duration-200">
                        </div>

                    </div>
                    <div class="w-full bg-primary h-[1px] "></div>
                    <div class="flex flex-row  justify-between gap-4">
                        <div class="flex  gap-3 flex-col ">
                            <div class="flex gap-2 flex-col ">
                                <a href="" class="text-[18px] font-bold">Diplomas & Certificates</a>
                                <p class="font-semibold">With a diverse range of diplomas and certificate programs led by esteemed faculty members who are experts in their fields, students have the opportunity to engage in rigorous coursework, hands-on learning experiences, and cutting-edge research initiatives.</p>
                            </div>
                        </div>
                        <div class="max-w-[125px] min-w-[125px] group overflow-hidden">
                            <img src="https://www.harvard.edu/wp-content/uploads/2024/03/20190328_Memme-Onwudiwe-19_LGranger002-2-375x281.png"
                                alt=""
                                class="min-w-[125px] max-w-[125px] object-cover h-24 group-hover:scale-105 transition-all ease-in duration-200">
                        </div>

                    </div>
                    <div class="w-full bg-primary h-[1px] "></div>



                </div>
            </div>
        </div>


    </section> -->
    <!-- section six end -->

    <!-- section seven start -->
    <!-- <section class="card-hidden px-6 min-[1200px]:px-[72px] flex items-center  bg-[#0E0E0E]  mb-8 mt-20 min-h-[340px]">
        <div class="flex flex-col md:flex-row gap-4">
            <h1 class="flex-1  font-canela
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px] text-white">
                Women Entrepreneurs Support Centre
            </h1>
            <div class="flex flex-col gap-12 flex-1">
                <p class="font-bold text-[18px]  text-white">
                    The Women Entrepreneurs Support Centre is dedicated to empowering women in business by providing essential resources, training, and networking opportunities.
                </p>
                <a href="https://women.berkeleyme.com/" class="flex  items-center group gap-6" target="blank">
                    <div
                        class="flex  group-hover:bg-primary justify-center items-center rounded-full bg-secondary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[300px] text-white">Learn more about Women Entrepreneurs Support Centre
                    </h2>
                </a>
            </div>
        </div>
    </section> -->
    <!-- section seven end -->

    <!-- Section eight start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px]  mt-16">
        <div class="flex flex-col lg:flex-row items-center gap-x-24 gap-y-10">
            <div class="relative flex-1 gap-2 flex flex-col">
                <h1 class="font-canela text-primary
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[60px] tracking-wide">
                Berkeley Middle East & Africa

                </h1>
                <div class="flex flex-col">
                    <p class="text-[18px] ">The Executive Education Centre in the Middle East serves as a beacon of excellence, offering tailored programs designed to meet the dynamic needs of professionals in the region. With a focus on fostering leadership, innovation, and strategic thinking, our center provides cutting-edge executive education initiatives that empower individuals and organizations to thrive in today's competitive landscape.
                    </p>

                </div>
                <a href="{{ route('courses') }}" class="flex flex-1 items-center group  gap-2 mt-6">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[420px] text-primary">Search Courses in Berkeley Middle East</h2>
                </a>
            </div>

            <div class="flex-1">
                <img src="{{ asset('frontend/images/jpg/sheikh.jpg') }}" alt=""
                    class="w-full  object-cover">
            </div>
    </section>
    <!-- Section eight end -->

    <!-- section nine start -->
    <!-- <section class="card-hidden px-6 min-[1200px]:px-[72px]  mb-8 mt-20">
        <div class="flex flex-col md:flex-row gap-4">
            <h1
                class="flex-1 font-thin text-[20px]  sm:text-[28px] md:text-[42px]  text-primary bg lg:text-[56px] font-canela ">
                Primary & Secondary Education

            </h1>
            <div class="flex flex-col gap-4 flex-1">
                <p class="font-medium text-[18px]  text-primary">Welcome to our vibrant community of learners, where every child's journey begins with a foundation built on curiosity, creativity, and collaboration. At our Primary and Secondary Schools, we are committed to providing a nurturing and stimulating environment where students thrive academically, socially, and emotionally.
                </p>
                <a href="#" class="flex items-center group gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Learn more about Primary & Secondary Education
                        action</h2>
                </a>
            </div>
        </div>
    </section> -->
    <!-- section nine End -->

    <!-- section Ten start -->
    <!-- <section class="card-hidden px-6 min-[1200px]:px-[72px]  my-8">
        <div class="flex flex-col lg:flex-row gap-5 mt-5">
            <div class="flex-1 group">
                <div class="min-w-[300px]  group: overflow-hidden bg-black">
                    <img src="https://www.harvard.edu/wp-content/uploads/2024/03/tge_istock-1435661952-736x491.png"
                        alt=""
                        class="w-full hover:scale-105 transition-all ease-in duration-200    xl:min-w-[770px] xl:min-h-[500px]">
                </div>
                <h3 class="font-canela text-primary
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[48px] min-[960px]:leading-[67px]">
                Child Training and Development
                </h3>
                <p class="font-semibold">
                At our Child Training and Development Centre, we celebrate the uniqueness of every child and embrace their individuality as we guide them on the path to success. Join us as we inspire, empower, and uplift children to reach new heights and fulfill their potential in a nurturing and supportive environment.
                </p>
            </div>
            <div class="flex-1">
                <div class="flex flex-col gap-4">
                    <div class="flex gap-3 flex-col ">
                        <a href="" class="text-[18px] font-bold">Nursey</a>
                        <p class="font-semibold">HIn our Nursery School, we prioritize play-based learning experiences that ignite imagination, foster creativity, and promote social-emotional development. Through hands-on activities, interactive play, and age-appropriate curriculum, we encourage children to develop essential skills such as communication, problem-solving, and collaboration.</p>
                    </div>
                    <div class="w-full bg-primary h-[1px] "></div>
                    <div class="flex gap-3 flex-col ">
                        <a href="" class="text-[18px] font-bold">Special Needs Education</a>
                        <p class="font-semibold">Welcome to our Special Needs Education Centre, where we believe in the potential of every individual and strive to create an inclusive and supportive environment where all students can thrive. At our center, we are dedicated to providing personalized and comprehensive educational programs tailored to meet the unique needs of each learner.</p>
                    </div>
                    <div class="w-full bg-primary h-[1px] "></div>
                    <div class="flex gap-3 flex-col ">
                        <a href="" class="text-[18px] font-bold">Tuition</a>
                        <p class="font-semibold">With a supportive and inclusive community of teachers, staff, and peers, our Academy for Tuition is more than just a place of learning—it's a home away from home where students feel valued, empowered, and inspired to reach their full potential.</p>
                    </div>
                    <div class="w-full bg-primary h-[1px] "></div>
                </div>
            </div>
        </div>


    </section> -->
    <!-- section Ten end -->

    

    <!-- Section twelve start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] mt-16">
        <div class="flex flex-col md:flex-row gap-4">
            <h1 class="flex-1 
                font-canela text-primary
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">
                Success Stories
            </h1>
            <div class="flex flex-1 gap-10 flex-col">
                <p class="font-ghothic text-[18px] card-hidden">The Executive Training Centre isn't just about advancing careers; it's about cultivating leadership excellence and driving meaningful change. Our students confidently say that their experience here has been instrumental in propelling career to new heights."
                </p>
                <a href="#" class="flex flex-1 items-center group  gap-2">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Explore our student's success stories as testimonials
                    </h2>
                </a>
            </div>
        </div>
        </div>
    </section>
    <!-- Section twelve End -->
    <!-- section eleven start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] mt-16">
        <div class="flex flex-col lg:flex-row-reverse  items-center gap-x-24 gap-y-10">
            <div class="flex-1 shrink-0">

                <img src="{{ asset('frontend/images/jpg/chinese.jpg') }}" alt=""
                    class="w-full min-h-[300px] xl:min-h-[464px] xl:min-w-[620px] object-cover">
            </div>

            <div class="flex flex-1 shrink-0 flex-col gap-4">
                <div class="flex gap-4 flex-1 items-start flex-col ">
                    <h1 class=" text-[24px] md:text-[36px] lg:text-[48px] font-canela to-primary">Berkeley China
                    </h1>
                    <div class="flex  flex-col">
                        <p class="text-[18px] font-medium ">Welcome to our Executive Education Centre in China, where we are dedicated to empowering professionals with the knowledge, skills, and insights needed to excel in today's rapidly evolving business landscape. Situated in the heart of one of the world's most dynamic economic hubs, our center serves as a premier destination for executive development, leadership training, and lifelong learning.
                        </p>
                        <!-- <p class="text-[18px] font-medium ">Dimick’s recommendations for engaging with climate anxiety,
                            or
                            be transported with climate fiction recommended by
                            English Professor James Engell.
                        </p> -->
                    </div>
                </div>
                <a href="{{ route('courses') }}" class="flex flex-1 items-center group  gap-2 ">
                    <div
                        class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                    </div>
                    <h2 class="font-bold text-[18px] max-w-[420px] text-primary">Search Courses in Berkeley China</h2>
                </a>
            </div>
        </div>
        </div>
    </section>
    <!-- section eleven End -->
    <!-- section thirteen start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px]  w-full mb-[80px] mt-[80px]">
        <div class="grid grid-cols-1 place-content-center sm:grid-cols-2 gap-10 ">
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Study-Abroad.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Study Abroad</span>
                    <p class="hidden font-semibold  group-hover:block text-white text-[18px]">
                        Embark on a life-changing adventure with our study abroad programs. Immerse yourself in new cultures, gain global perspectives, and expand your academic horizons. With expert guidance and support, we make studying abroad a seamless and enriching experience. Broaden your worldview and enhance your education today!</p>
                    <a href="{{ route('study-abroad') }}" class="flex items-center gap-2">
                        <div
                            class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px] text-white">Explore the options to study Abroad
                        </h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>

            </div>
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Immigration-Service.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Migartion</span>
                    <p
                        class="hidden  font-semibold group-hover:block text-white delay-300  text-[18px] transition-all duration-400 ease-in-out">
                        Navigate the complexities of migration with our comprehensive services. Whether you're seeking to immigrate, emigrate, or relocate, our expert team provides tailored solutions for a smooth transition. From visa assistance to settlement support, we're here to ensure your migration journey is seamless and successful.
                    </p>
                    <a href="https://law.berkeleyme.com/" target="blank" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Migrate into a country gives you better security of life</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>
            <!-- <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Public-Training.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-6 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Public training,  seminars,  boot camps and workshops</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Empower yourself with our dynamic range of public training, seminars, boot camps, and workshops. Designed to enhance your skills, knowledge, and professional network, our programs offer practical insights and hands-on learning experiences. Elevate your career and personal development with our engaging and informative sessions.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Explore our public course Calendar</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/support-center.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Business Support centre </span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Boost your business success with our comprehensive support center. From expert advice and resources to networking opportunities and tailored solutions, we provide the tools you need to thrive. Let us help you navigate challenges and unlock new opportunities for growth and success.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Our business support entrepreneurs and executives for  putting minds together for a better solution</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div> -->
        </div>
    </section>
    <!-- section thirteen end -->

    <!-- Section fourteen start -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px] min-h-[445px] flex items-center bg-[#000435] w-full">
        <div class="flex flex-col md:flex-row flex-1 gap-x-20 gap-y-10 justify-between ">
            <div class=" flex flex-col gap-2">
                <h6 class=" text-[14px] font-bold text-white">YOU MAY ALSO LIKE</h6>
                <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Related In
                    Focus
                    topics</span>
            </div>
            <ul class="flex-1 flex flex-col gap-6  font-bold text-[18px] sm:text-[20px] md:text-[24px] text-white">
                <li class="pb-6 lg:leading-[24px] border-b border-secondary">
                    <a href="">Environment</a>
                </li>
                <li class="pb-6 lg:leading-[24px] border-b border-secondary">
                    <a href="">Social</a>
                </li>
                <li class="pb-6 lg:leading-[24px] border-b border-secondary">
                    <a href="">Governance</a>
                </li>
            </ul>
        </div>
    </section>
    <!-- Section fourteen end -->
    <section class="card-hidden px-6 min-[1200px]:px-[72px]  w-full mb-[80px] mt-[80px]">
        <div class="grid grid-cols-1 place-content-center sm:grid-cols-2 gap-10 ">
            <!-- <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Study-Abroad.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Study Abroad</span>
                    <p class="hidden font-semibold  group-hover:block text-white text-[18px]">
                        Embark on a life-changing adventure with our study abroad programs. Immerse yourself in new cultures, gain global perspectives, and expand your academic horizons. With expert guidance and support, we make studying abroad a seamless and enriching experience. Broaden your worldview and enhance your education today!</p>
                    <a href="#" class="flex items-center gap-2">
                        <div
                            class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px] text-white">Explore the options to study Abroad
                        </h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>

            </div>
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Immigration-Service.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Migartion</span>
                    <p
                        class="hidden  font-semibold group-hover:block text-white delay-300  text-[18px] transition-all duration-400 ease-in-out">
                        Navigate the complexities of migration with our comprehensive services. Whether you're seeking to immigrate, emigrate, or relocate, our expert team provides tailored solutions for a smooth transition. From visa assistance to settlement support, we're here to ensure your migration journey is seamless and successful.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Migrate into a country gives you better security of life</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div> -->
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/Public-Training.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-6 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Public training,  seminars,  boot camps and workshops</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Empower yourself with our dynamic range of public training, seminars, boot camps, and workshops. Designed to enhance your skills, knowledge, and professional network, our programs offer practical insights and hands-on learning experiences. Elevate your career and personal development with our engaging and informative sessions.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Explore our public course Calendar</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset('frontend/images/jpg/support-center.jpg') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">

                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end   bg-opacity-45 h-full w-full bottom-0">
                    <span class="font-canela text-white
                text-[32px] leading-[40px]
                md:text-[48px] md:leading-[58px]
                min-[960px]:text-[56px] min-[960px]:leading-[67px]">Business Support centre </span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">Boost your business success with our comprehensive support center. From expert advice and resources to networking opportunities and tailored solutions, we provide the tools you need to thrive. Let us help you navigate challenges and unlock new opportunities for growth and success.
                    </p>
                    <a href="#" class="flex items-center gap-2">
                        <div
                            class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="" />
                        </div>
                        <h2 class="font-bold text-[18px]  text-white">Our business support entrepreneurs and executives for  putting minds together for a better solution</h2>
                    </a>
                </div>

                <div
                    class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>
        </div>
    </section>

    <section class="relative flex w-full flex-col gap-20 px-10 py-20 sm:hide md:block">
    <!-- Left Side Navigation -->
        <div class="fixed top-[50%] p-3 z-[88888] bg-yellow translate-y-[-50%] mb-8 left-0 lg:mb-0 min-w-[100px] max-w-[100px] rounded-r-lg">
            <nav id="sideNav" class="space-y-4">
                <a href="#one" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Overview</a>
                <a href="#two" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Who Can Do</a>
                <a href="#three"
                    class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Eligibility</a>
                <a href="#four" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Course Structure</a>
                <a href="#five" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Lecture Plan</a>
                <a href="#six" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Examination</a>
                <a href="#seven" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Faculty</a>
                <a href="eight" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Outcome</a>
                <a href="nine" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Stories</a>
                <a href="ten" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">Certificate</a>
                <a href="elevene" class="nav-link block text-[12px] font-semibold text-primary hover:text-gray-200">FAQs</a>
            </nav>
        </div>
    </section>
@endsection