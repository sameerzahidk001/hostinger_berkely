@extends('layouts.app')

@section('content')

@php
    $hero = $sections['hero_section'] ?? null;
    $aboutUs = $sections['about_us'] ?? null;
    $courses = $sections['courses'] ?? null;
@endphp

<!-- Hero Section -->
<section id="header-course" class="relative overflow-hidden min-h-screen max-h-screen">
    <div class="absolute scroll-smooth bg-black top-0 flex justify-center flex-col z-30 h-full w-full items-center">
        <img src="{{ asset($hero->image ?? 'frontend/images/jpg/60.jpg') }}"
            class="min-h-screen max-h-screen object-cover w-full zoom-in" alt="">
    </div>
    <div class="absolute px-2 top-0 flex justify-center flex-col z-40 h-full w-full items-center inset-0 bg-black bg-opacity-50">
        <h1 class="text-white text-[68px] leading-[60px] md:text-[75px] md:leading-[60px] lg:text-[80px] xl:text-[116px] font-canela max-w-[490px] text-center lg:leading-[80px] xl:leading-[116px]">
            {{ $hero->title ?? 'Knowledge Universe' }}
        </h1>
        <span class="text-[18px] leading-[27px] max-w-[520px] text-center text-white font-bold mt-6 md:mt-10">
            {{ $hero->subtitle ?? 'Educating people who make a difference in the world' }}
        </span>
    </div>
</section>

<!-- Courses Section -->
<section class="card-hidden px-6 min-[1200px]:px-[72px] mt-10 lg:pt-0 md:px-12 flex w-full flex-1 justify-between min-h-[148px]">
    <div class="flex flex-col w-full justify-start md:flex-row gap-4 lg:gap-12">
        <h1 class="font-canela flex-1 basis-full text-primary text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[67px]">
            {{ $courses->title ?? 'The World’s Best Collection of Courses' }}
        </h1>
        <a href="{{ $courses->link ?? route('courses') }}" class="flex md:mt-6 bg basis-full flex-1 items-center group gap-2">
            <div class="flex group-hover:bg-primary justify-center items-center rounded-full bg-secondary min-h-10 min-w-10">
                <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
            </div>
            <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Search courses by index</h2>
        </a>
    </div>
</section>

<!-- About Us Section -->
<section class="card-hidden px-6 min-[1200px]:px-[72px] mt-[60px]">
    <div class="flex flex-col md:flex-row items-start min-[968px]:items-center gap-x-24 gap-y-10">
        <div class="flex-1 w-full">
            <img src="{{ asset($aboutUs->image ?? 'frontend/images/jpg/Berkeley-Square-USA.jpg') }}" alt=""
                class="w-full h-full object-cover">
        </div>
        <div class="relative flex-1 w-full">
            <div class="flex gap-2 flex-col">
                <h2 class="font-canela text-[24px] sm:text-[28px] md:text-[32px] lg:text-[36px] leading-normal">
                    {{ $aboutUs->title ?? 'BERKELEY SCHOOL OF BUSINESS, ARTS & SCIENCES' }}
                </h2>
                <p class="text-[18px] font-medium">
                    {{ $aboutUs->description ?? 'Experience the transformative power of education at our center, where we empower minds to create a better future.' }}
                </p>
            </div>
            <a href="{{ $aboutUs->link ?? route('berkeley-square') }}" class="flex flex-1 items-center group mt-4 gap-2">
                <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                </div>
                <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Explore Executive Development Center</h2>
            </a>
        </div>
    </div>
</section>

@php
    $centreForEducation = $sections['centre_for_education'] ?? null;
@endphp
<section class="card-hidden px-6 min-[1200px]:px-[72px] py-24 flex items-center bg-[#000435] w-full mt-16">
    <div class="flex flex-col md:flex-row flex-1 gap-x-20 gap-y-10 justify-between">
        <div class="flex flex-1 flex-col gap-2">
            <h1 class="font-canela text-white text-[32px] leading-[40px] md:text-[48px] md:leading-[58px]">
                {{ $centreForEducation->title ?? 'Centre for Education' }}
            </h1>
            <p class="font-bold text-[16px] text-white">
                {{ $centreForEducation->description ?? 'Enhance your leadership skills and business acumen with our Executive Education programs. Designed for professionals seeking to accelerate their careers.' }}
            </p>
        </div>
    </div>
</section>

{{-- Courses Section --}}
@php
    $courses = [
        'diplomas' => $sections['diplomas'] ?? null,
        'graduate_courses' => $sections['graduate_courses'] ?? null,
        'master_courses' => $sections['master_courses'] ?? null,
    ];
@endphp
<section class="card-hidden px-6 min-[1200px]:px-[72px] mt-16">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
        @foreach($courses as $key => $course)
        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset($course->image ?? 'frontend/images/jpg/'.$key.'.jpg') }}" class="h-full w-full absolute group-hover:scale-105 object-cover">
            <div class="absolute p-6 z-50 flex flex-col justify-end h-full w-full bottom-0">
                <span class="font-canela text-white text-[32px]">{{ $course->title ?? ucfirst(str_replace('_', ' ', $key)) }}</span>
                <p class="hidden font-semibold group-hover:block text-white text-[18px]">
                    {{ $course->description ?? 'Explore our specialized courses in this category.' }}
                </p>
                <a href="{{ $course->link ?? '#' }}" class="flex items-center gap-2">
                    <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4">
                    </div>
                    <h2 class="font-bold text-[18px] text-white">Explore {{ $course->title ?? ucfirst($key) }}</h2>
                </a>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- Women Entrepreneurs Support Centre --}}
@php
    $womenEntrepreneurs = $sections['women_entrepreneurs'] ?? null;
@endphp
<section class="card-hidden px-6 min-[1200px]:px-[72px] flex items-center bg-[#0E0E0E] mt-16">
    <div class="flex flex-col md:flex-row gap-4">
        <h1 class="flex-1 font-canela text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] text-white">
            {{ $womenEntrepreneurs->title ?? 'Women Entrepreneurs Support Centre' }}
        </h1>
        <div class="flex flex-col gap-12 flex-1">
            <p class="font-bold text-[18px] text-white">
                {{ $womenEntrepreneurs->description ?? 'Empowering women in business with essential resources, training, and networking opportunities.' }}
            </p>
            <a href="{{ $womenEntrepreneurs->link ?? 'https://women.berkeleyme.com/' }}" class="flex items-center group gap-6" target="_blank">
                <div class="flex group-hover:bg-primary justify-center items-center rounded-full bg-secondary self-center min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4">
                </div>
                <h2 class="font-bold text-[18px] max-w-[300px] text-white">Learn More</h2>
            </a>
        </div>
    </div>
</section>

{{-- Academy Section --}}
@php
    $academies = [
        'cfo_academy' => $sections['cfo_academy'] ?? null,
        'ceo_academy' => $sections['ceo_academy'] ?? null,
        'entrepreneurs_academy' => $sections['entrepreneurs_academy'] ?? null,
    ];
@endphp
<section class="card-hidden px-6 min-[1200px]:px-[72px] mt-16">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
        @foreach($academies as $key => $academy)
        <div class="relative overflow-hidden group h-[500px] bg-primary">
            <img src="{{ asset($academy->image ?? 'frontend/images/jpg/'.$key.'.jpg') }}" class="h-full w-full absolute group-hover:scale-105 object-cover">
            <div class="absolute p-6 z-50 flex flex-col justify-end h-full w-full bottom-0">
                <span class="font-canela text-white text-[32px]">{{ $academy->title ?? ucfirst(str_replace('_', ' ', $key)) }}</span>
                <p class="hidden font-semibold group-hover:block text-white text-[18px]">
                    {{ $academy->description ?? 'Join this academy to gain specialized training and leadership skills.' }}
                </p>
                <a href="{{ $academy->link ?? '#' }}" class="flex items-center gap-2">
                    <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10">
                        <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4">
                    </div>
                    <h2 class="font-bold text-[18px] text-white">Learn More</h2>
                </a>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- Success Stories --}}
@php
    $successStories = $sections['success_stories'] ?? null;
@endphp
<section class="card-hidden px-6 min-[1200px]:px-[72px] my-16">
    <div class="flex flex-col md:flex-row gap-4">
        <h1 class="flex-1 font-canela text-primary text-[32px] leading-[40px] md:text-[48px] md:leading-[58px]">
            {{ $successStories->title ?? 'Success Stories' }}
        </h1>
        <div class="flex flex-1 gap-4 flex-col">
            <p class="font-ghothic text-[18px]">
                {{ $successStories->description ?? 'Read how our students have advanced their careers through our training programs.' }}
            </p>
            <a href="{{ $successStories->link ?? route('learner-stories') }}" class="flex flex-1 items-center group gap-2">
                <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4">
                </div>
                <h2 class="font-bold text-[18px] max-w-[300px] text-primary">Explore Stories</h2>
            </a>
        </div>
    </div>
</section>


<x-clients :id="1"
    :background="'#eeeeee'"
    :title="'Most of the Students are from'"
    :description="'Our students excel, securing positions at top companies, driving innovation, and achieving remarkable success.'"
    :logos="[
        'frontend/images/clients/c1.png',
        'frontend/images/clients/c2.png',
        'frontend/images/clients/c3.png',
        'frontend/images/clients/c4.png',
        'frontend/images/clients/c5.png',
        'frontend/images/clients/c6.png',
        'frontend/images/clients/c7.png',
        'frontend/images/clients/c8.png',
        'frontend/images/clients/c9.png',
        'frontend/images/clients/c10.png',
        'frontend/images/clients/c11.png',
        'frontend/images/clients/c12.png',
        'frontend/images/clients/c13.png',
        'frontend/images/clients/c14.png',
        'frontend/images/clients/c15.png',
        'frontend/images/clients/c16.png',
        'frontend/images/clients/c17.png',
        'frontend/images/clients/c18.png',
        'frontend/images/clients/c19.png',
        'frontend/images/clients/c20.png',
        'frontend/images/clients/c21.png',
        'frontend/images/clients/c22.png',
        'frontend/images/clients/c23.png',
        'frontend/images/clients/c24.png',
        'frontend/images/clients/c25.png',
        'frontend/images/clients/c26.png',
        'frontend/images/clients/c27.png',
        'frontend/images/clients/c28.png',
        'frontend/images/clients/c29.png',
        'frontend/images/clients/c30.png',
        'frontend/images/clients/c31.png',
        'frontend/images/clients/c32.png',
        'frontend/images/clients/c33.png',
        'frontend/images/clients/c34.png',
    ]"
/>


@endsection

@push('script')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const links = document.querySelectorAll('.nav-link');
            const sections = document.querySelectorAll('section > div');

            function activateLink(targetId) {
                links.forEach(link => {
                    link.classList.toggle('active-link', link.getAttribute('href') === `#${targetId}`);
                });
            }

            // Add click event listeners to the navigation links
            links.forEach(link => {
                link.addEventListener('click', function () {
                    activateLink(this.getAttribute('href').substring(1));
                });
            });

            // Create an Intersection Observer to observe sections
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        activateLink(entry.target.id);
                    }
                });
            }, { threshold: 0.5 });

            // Observe each section
            sections.forEach(section => {
                observer.observe(section);
            });
        });


        // tel start
        const input = document.querySelector("#phone2");
        window.intlTelInput(input, {
            initialCountry: "ae",
            separateDialCode: true,
            utilsScript: "/intl-tel-input/js/utils.js?1716383386062" // just for formatting/placeholders etc
        });
        // tel End


        // Option start
        var x, i, j, selElmnt, a, b, c;
        x = document.getElementsByClassName("tt-select");

        for (i = 0; i < x.length; i++) {
            selElmnt = x[i].getElementsByTagName("select")[0];
            a = document.createElement("DIV");
            a.setAttribute("class", "select-selected");
            a.innerHTML = selElmnt.options[selElmnt.selectedIndex].innerHTML;
            x[i].appendChild(a);
            b = document.createElement("DIV");
            b.setAttribute("class", "select-items select-hide");
            for (j = 0; j < selElmnt.length; j++) {
                /*for each option in the original select element,
                create a new DIV that will act as an option item:*/
                c = document.createElement("DIV");
                c.innerHTML = selElmnt.options[j].innerHTML;
                c.addEventListener("click", function (e) {
                    var y, i, k, s, h;
                    s = this.parentNode.parentNode.getElementsByTagName("select")[0];
                    h = this.parentNode.previousSibling;
                    for (i = 0; i < s.length; i++) {
                        if (s.options[i].innerHTML == this.innerHTML) {
                            s.selectedIndex = i;
                            h.innerHTML = this.innerHTML;
                            y = this.parentNode.getElementsByClassName("same-as-selected");
                            for (k = 0; k < y.length; k++) {
                                y[k].removeAttribute("class");
                            }
                            this.setAttribute("class", "same-as-selected");
                            break;
                        }
                    }
                    h.click();
                });
                b.appendChild(c);
            }
            x[i].appendChild(b);
            a.addEventListener("click", function (e) {
                e.stopPropagation();
                closeAllSelect(this);
                this.nextSibling.classList.toggle("select-hide");
                this.classList.toggle("select-arrow-active");
            });
        }
        function closeAllSelect(elmnt) {
            var x, y, i, arrNo = [];
            x = document.getElementsByClassName("select-items");
            y = document.getElementsByClassName("select-selected");
            for (i = 0; i < y.length; i++) {
                if (elmnt == y[i]) {
                    arrNo.push(i)
                } else {
                    y[i].classList.remove("select-arrow-active");
                }
            }
            for (i = 0; i < x.length; i++) {
                if (arrNo.indexOf(i)) {
                    x[i].classList.add("select-hide");
                }
            }
        }
        document.addEventListener("click", closeAllSelect);
        // Option End
    </script>

@endpush