<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/' . $settings->favicon ?? 'default.jpeg') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! SEOTools::generate() !!}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('frontend/output.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @font-face {
            font-family: "canela";
            src: url("{{ asset('frontend/fonts/CanelaDeckFamily/CanelaDeck-Regular-Trial.otf') }}") format("opentype");
            font-display: swap;
        }
        @font-face {
            font-family: "Canela";
            src: url("{{ asset('frontend/fonts/CanelaDeckFamily/CanelaDeck-Regular-Trial.otf') }}") format("opentype");
            font-display: swap;
        }
        @font-face {
            font-family: "trade-ghothic";
            src: url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic.woff2') }}") format("woff2"),
                 url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic-Regular.otf') }}") format("opentype");
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: "trade-ghothic";
            src: url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic-Bold.woff2') }}") format("woff2"),
                 url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic-Bold.otf') }}") format("opentype");
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: "trade-ghtic";
            src: url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic.woff2') }}") format("woff2"),
                 url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic-Regular.otf') }}") format("opentype");
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: "trade-ghtic";
            src: url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic-Bold.woff2') }}") format("woff2"),
                 url("{{ asset('frontend/fonts/Trade_Gothic/Trade-Gothic-Bold.otf') }}") format("opentype");
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        .font-canela {
            font-family: canela, sans-serif !important;
        }
        .font-ghothic {
            font-family: trade-ghothic, sans-serif !important;
        }
    </style>
    <script src="{{ asset('frontend/analytics.js') }}" defer></script>
    <style>
        .accordion button {
            color: black;
            line-height: normal;
        }

        .accordion button::after {
            color: black;
            font-weight: 900;
            font-size: 16px;
        }

        .accordion [aria-expanded="true"]::after {
            color: black;
            font-weight: 900;
            font-size: 16px;
        }
    </style>
    @stack('style')


</head>
@php
    $settings = DB::table('site_settings')->first();
@endphp

<body class="min-h-screen flex flex-col">
    <header>
        {!! $settings->header_settings ?? '' !!}

        {{-- <div id="toggleDiv"
            class="mt-[70px] md:mt-[70px] lg:mt-[72px] min-[1200px]:px-[72px] bg-[#000435]  flex items-center z-[999] flex-col justify-center w-full transition-all duration-500 ease-in-out opacity-0 max-h-0 overflow-hidden">
            <div class="min-h-[670px] flex justify-center items-center my-10">
                <div class="flex flex-col gap-8 justify-center items-center">
                    <h1
                        class="bg-black font-bold text-white text-[36px] sm:text-[36px] lg:text-[52px] xl:text-[76px] xl:leading-tight px-4">
                        SEARCH COURSES
                    </h1>
                    <div
                        class="flex p-1 sm:min-w-[470px] md:min-w-[600px] min-h-[70px] lg:min-h-[80px] xl:min-w-[806px] bg-white border-white ">
                        <input type="text" placeholder="Find your dream course..."
                            class="w-full outline-none text-[18px] px-2">
                        <button class="bg-primary_orange p-1 px-3">
                            <img src="{{ asset('frontend/images/svgs/magnifierg.svg') }}"
                                class="w-6 h-6 md:w-10 md:h-10 shrink-0 " alt="">
                        </button>
                    </div>
                </div>
            </div>
        </div> --}}
        <!-- Main Navigation Start -->
        <div id="navbar"
            class="flex fixed bg min-h-[70px] bg-white  z-[999] w-full top-0 justify-between  px-4 md:px-8 lg:px-[30px] my-0 py-0 ">
            <a href="{{ url('/') }}" class="self-center">
                <img src="{{ asset('images/' . ($settings->header_logo == 'logo' ? $settings->logo : $settings->white_logo)) }}"
                    class="max-w-[220px] self-center object-fill min-h-[48px] sm:min-h-[48px] sm:min-w-[200px] lg:min-w-[240px] lg:min-h-[48px]"
                    alt="">
            </a>
            <ul class="hidden list-none  min-[980px]:flex  font-ghothic text-[19px] gap-4 ">
                @php
                    $filteredMenus = collect($menus)->where('menu_group', $settings->header_menu)->values();
                @endphp
                @foreach($filteredMenus as $menu)
                    <li class="relative group flex items-center justify-center">
                        @php
                            $cleanLink = Str::replace(['/course', '/diploma'], '', $menu->link);
                        @endphp
                        <a href="{{ $menu->link }}"
                            class="active:underline decoration-crimson underline-offset-4">{{ $menu->name }}</a>
                        @if ($menu->children->count() > 0)
                            <div
                                class="mega-menu-two absolute left-0 top-full hidden group-hover:flex flex-col shadow-xl bg-white min-w-[260px] border-t border-primary  mega-menu-style">
                                <ul class="list-none">
                                    @foreach ($menu->children->sortBy('menu_order') as $child)
                                        <li>
                                            <a href="{{ $child->link }}"
                                                class="flex gap-2 items-center text-black transition-all delay-150 duration-150 hover:bg-navy hover:text-white sm:px-6 px-4 py-3">
                                                <span class="text-[18px] font-medium">{{ $child->name }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endforeach

                <li class="group hoverable-del list-none relative flex items-center justify-center">
                    @if($settings && $settings->header_button == '1')
                        <a href="{{ $settings->header_button_url }}"
                            class="border  px-4 py-1 w-full border-[{{ $settings->header_button_bg }}] bg-[{{ $settings->header_button_bg }}] transition-all delay-300 duration-300 content-center rounded  uppercase"
                            style="color: {{ $settings->header_button_color }}">
                            {{ $settings->header_button_text }}
                        </a>
                    @endif
                </li>
            </ul>

            <div class="flex  min-[918px]:self-stretch  gap-2 sm:gap-4">
                @if($settings && ($settings->header_search == '1' || $settings->login == '1' || $settings->register == '1'))
                    <div class="flex items-center gap-2 sm:gap-4">

                        {{-- Header Search --}}
                        @if(optional($settings)->header_search == '1' && !empty($settings->header_search_url))
                            <a href="{{ url($settings->header_search_url) }}"
                                class="flex cursor-pointer no-underline justify-center items-center py-2 gap-3 text-lg font-medium">
                                @if(!empty($settings->header_search_image))
                                    <img src="{{ asset('images/' . $settings->header_search_image) }}" class="w-6 h-6 md:w-8 md:h-8"
                                        alt="Search Icon">
                                @endif
                            </a>
                        @endif

                        {{-- Auth Buttons --}}
                        @guest
                            @if($settings->login == '1')
                                <a href="{{ route('login') }}"
                                    class="items-center py-2 gap-3 text-lg font-medium active:underline decoration-crimson underline-offset-4 font-ghothic text-[19px]">
                                    {{ $settings->login_text ?? 'Login' }}
                                </a>
                            @endif
                            @if($settings->register == '1')
                                <a href="{{ route('register') }}"
                                    class="items-center py-2 gap-3 text-lg font-medium active:underline decoration-crimson underline-offset-4 font-ghothic text-[19px]">
                                    {{ $settings->register_text ?? 'Register' }}
                                </a>
                            @endif
                        @else
                            <div class="flex items-center gap-4">
                                <a href="{{ route('user.home') }}"
                                    class="items-center py-2 gap-3 text-lg font-medium underline decoration-crimson underline-offset-4 font-ghothic text-[19px]">
                                    {{ auth()->user()->name }}
                                </a>
                                <a href="{{ route('cart.index') }}"
                                    class="flex items-center gap-2 py-2 text-lg font-medium font-ghothic text-[19px] hover:underline decoration-crimson underline-offset-4">
                                    <img src="{{ asset('frontend/images/svgs/shopping-cart.svg') }}" class="w-6 h-6" alt="Cart">
                                    <span>Cart ({{ cart_item_count() }})</span>
                                </a>
                            </div>
                        @endguest

                    </div>
                @endif

                <!-- mobile button -->
                <button id="menuButton"
                    class="no-underline justify-center items-center py-2 gap-3 text-lg font-medium flex lg:hidden">
                    <div class="flex flex-col gap-1   text-lg font-medium">
                        <div class="w-5 h-0.5 bg-primary rounded-full"></div>
                        <div class="w-5 h-0.5 bg-primary rounded-full"></div>
                        <div class="w-5 h-0.5 bg-primary rounded-full"></div>
                    </div>
                </button>
            </div>

        </div>

        <!-- Mobile Side Menu -->
        <div id="mobileMenu"
            class="fixed top-[70px] right-0 w-3/4 max-w-xs bg-[#000435] h-full transform translate-x-full transition-transform duration-300 ease-in-out z-[999] overflow-y-auto max-h-screen">
            <div class="flex justify-between items-center flex-wrap gap-4 w-full mt-4 px-4">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/' . ($settings->header_logo == 'logo' ? $settings->logo : $settings->white_logo)) }}"
                        class="max-w-[160px] self-center  min-h-[55px] sm:min-h-[70px] sm:min-w-[200px] lg:min-w-[280px] lg:min-h-[90px]"
                        alt="">
                </a>

                <button id="closeMenu" class="text-white">
                    <img src="{{ asset('frontend/images/svgs/cross.svg') }}" class="w-8 h-8" alt="">
                </button>
            </div>
            <div class="flex flex-col px-6 mb-20 text-[20px]">
                @php
                    $filteredMenus = collect($menus)->where('menu_group', $settings->header_menu)->values();
                @endphp
                @foreach($filteredMenus as $key => $menu)

                    @if ($menu->children->count() > 0)
                        <div class="relative">
                            <button id="dropdownButton{{ $key }}"
                                class="text-white py-2 w-full text-left dropdownButton">{{ $menu->name }}</button>
                            <div id="dropdownMenu{{ $key }}"
                                class="hidden bg-white p-4 mt-2 text-black rounded-md dropdownMenu">
                                @foreach ($menu->children as $child)
                                    <a href="{{ $child->link }}" class="block py-2">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $menu->link }}" class="text-white py-2">{{ $menu->name }}</a>
                    @endif
                @endforeach
                @if($settings && $settings->header_button)
                    <a href="{{ $settings->header_button_url }}"
                        class="text-white py-2">{{ $settings->header_button_text }}</a>
                @endif
            </div>
        </div>


    </header>
    <!-- Drawer End -->

    @yield('content')

    @php
        $page_faqs = get_page_faqs(request());
    @endphp

    @if($page_faqs && isset($page_faqs->faqs) && $page_faqs->faqs->isNotEmpty())
        <section class="flex flex-col min-h-[53px] px-6 bg-[#f5f5f5] py-[44px] items-center min-[1200px]:px-[72px]">

            <div class="w-[100%] md:w-[75%]">
                @foreach($page_faqs->faqs as $faq)
                    <div class="mx-auto grid mt-2 max-w-4xl divide-y divide-neutral-200 space-y-2">
                        <div class="p-4 bg-white">
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
                                    <span> {!! $faq->question !!}</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor"
                                            stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            viewBox="0 0 24 24" width="24">
                                            <path d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </summary>
                                <p class="group-open:animate-fadeIn mt-3 text-neutral-600">
                                    {!! $faq->answer !!}
                                </p>
                            </details>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Floating WhatsApp -->
    <a href="{{ $settings->whatsapp_url ?? '#' }}" target="_blank" class="fixed bottom-20 right-2 rounded-full z-50">
        <img src="{{ asset('images/' . $settings->whatsapp_icon ?? 'default.jpeg') }}" class="w-16 h-16">
    </a>

    <!-- Fixed Social Section -->
    <div class="fixed z-50 bottom-0 w-full flex justify-end space-x-0 bg-black bg-opacity-50">
        <div class="flex gap-1 items-center py-2 px-2">
            {{-- <p class="text-white hidden sm:block">Follow Us:</p> --}}
            @php
                $socialMedia = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok'];
            @endphp
            @foreach ($socialMedia as $platform)
                @php
                    $smurl = $platform . '_url';
                    $smicon = $platform . '_icon';
                @endphp
                <a href="{{ $settings->$smurl ?? '#' }}" target="_blank" class="bg-[#000435] p-2"><img
                        src="{{ asset('images/' . $settings->$smicon ?? 'default.jpg') }}" class="w-8 h-8"></a>
            @endforeach
        </div>
        @php
            $cartCount = 0;

            if (Auth::check()) {
                $cartCount = \App\Models\CartItem::where('user_id', Auth::id())->sum('quantity');
            }
        @endphp

        <a href="{{ route('cart.index') }}" class="flex items-center gap-2 bg-[#bc1904] px-2 py-1">
            <img src="{{ asset('frontend/images/svgs/shopping-cart.svg') }}" class="w-8 h-8" alt="Cart">
            <p class="text-white">Cart ({{ $cartCount }})</p>
        </a>
    </div>

    <!-- Footer Section -->
    @php
        $footerBg = $settings->footer_bg ?: '#0e0e0e';
        $footerText = $settings->footer_text_color ?: '#8996a0';
        $footerTitle = $settings->footer_title_bg ?: '#ffffff';
    @endphp
    <footer
        class="card-hidden px-6 min-[1200px]:px-[72px] min-h-[465px] flex items-center flex-col justify-center w-full py-8 mt-auto"
        style="background-color: {{ $footerBg }}; margin-bottom: 0px !important;">
        @php
            $columns = $settings->footer_columns == null ? 4 : $settings->footer_columns;

            $gridClass = match ($columns) {
                1 => 'grid-cols-1',
                2 => 'md:grid-cols-2 ',
                3 => 'md:grid-cols-2 grid-cols-3',
                default => 'md:grid-cols-2 lg:grid-cols-4'
            };

            $limitedWidgets = $widgets->take($columns);
        @endphp

        <div class="grid w-full gap-4 {{ $gridClass }} place-items-start">
            @foreach ($limitedWidgets as $widget)
                <div class="flex flex-1 w-full flex-col justify-center items-center md:items-start gap-2">
                    <span class="font-bold text-[18px] leading-[27px]"
                        style="color: {{ $footerTitle }}">{{ $widget->title ?? 'Title' }}</span>
                    @if ($widget->description != null)
                        <div class="mt-2" style="color: {{ $footerText }}">{!! $widget->description !!}</div>
                    @endif
                    @if ($widget->menu != null)
                        @php
                            $filteredMenus = collect($menus)->where('menu_group', $widget->menu)->values();
                        @endphp
                        @foreach ($filteredMenus as $menu)
                            <a href="{{ $menu->link }}"
                                class="font-bold text-[16px] leading-[27px] hover:underline transition-all ease-in duration-200 delay-100 underline-offset-4"
                                style="color: {{ $footerText }}">
                                {{ $menu->name }}
                            </a>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
        <div class="flex flex-col md:flex-row  w-full justify-between gap-4 items-center my-8">
            <div
                class="flex flex-1 justify-center basis-100 flex-grow w-full text-white no-underline font-normal text-base items-center">
                @if($settings && $settings->footer_logo)
                    @php
                        $logo = $settings->footer_logo == 'logo' ? 'logo' : 'white_logo';
                    @endphp
                    <a href="{{ url('/') }}" class=" self-center">
                        <img src="{{ asset('images/' . $settings->$logo) }}"
                            class="max-w-[220px] self-center  object-fill min-h-[48px] sm:min-h-[48px] sm:min-w-[200px] lg:min-w-[240px] lg:min-h-[48px] "
                            alt="">
                    </a>
                @endif
            </div>
            <span
                class="flex flex-0 bg justify-center basis-100 flex-grow w-full no-underline font-bold text-base items-center text-[16px]"
                style="color: {{ $footerTitle }}">
                {{ $settings->copyright_message ?? 'Copyright © 1976 Company PVT. LTD' }}
            </span>
        </div>
        {!! $settings->footer_settings ?? '' !!}
    </footer>
    <!-- footer end -->

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-E7HT3E77XY"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-E7HT3E77XY');
    </script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        // JS for Search bar start
        document.addEventListener('DOMContentLoaded', function () {
            var toggleButton = document.getElementById('toggleButton');
            var toggleDiv = document.getElementById('toggleDiv');

            toggleButton.addEventListener('click', function (event) {
                event.preventDefault(); // Prevent the default action of the link
                if (toggleDiv.style.maxHeight) {
                    toggleDiv.style.maxHeight = null;
                    toggleDiv.style.opacity = '0';
                } else {
                    toggleDiv.style.maxHeight = toggleDiv.scrollHeight + 'px';
                    toggleDiv.style.opacity = '1';
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        });


        // JS for Search bar End



        window.addEventListener('scroll', function () {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 0) {
                navbar.classList.add('shadow-scroll');
            } else {
                navbar.classList.remove('shadow-scroll');
            }
        });

        // For Animation effect of Section
        const hidenElement = document.querySelectorAll(".card-hidden");
        console.log(hidenElement);
        const obsarver = new IntersectionObserver((entries) => {
            console.log(entries);
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("show");
                } else {
                    entry.target.classList.remove("show");
                }
            });
        });
        hidenElement.forEach((el) => obsarver.observe(el));

        const input = document.querySelector("#phone2");
        window.intlTelInput(input, {
            initialCountry: "gb", // Set default country to UK
            separateDialCode: true,
            utilsScript: "/intl-tel-input/js/utils.js?1716383386062" // just for formatting/placeholders etc
        });
        // tel End
    </script>

    <script>
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
    </script>

    <script>
        const menuButton = document.getElementById('menuButton');
        const closeMenu = document.getElementById('closeMenu');
        const mobileMenu = document.getElementById('mobileMenu');

        // Select all dropdown buttons and menus by class
        const dropdownButtons = document.querySelectorAll('.dropdownButton');
        const dropdownMenus = document.querySelectorAll('.dropdownMenu');

        // Toggle mobile menu
        menuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('translate-x-full');
        });

        closeMenu.addEventListener('click', () => {
            mobileMenu.classList.toggle('translate-x-full');
        });

        // Loop through each dropdown button and add a click event listener
        dropdownButtons.forEach((button, index) => {
            button.addEventListener('click', () => {
                // Close any other open dropdowns
                dropdownMenus.forEach((menu, menuIndex) => {
                    if (menuIndex !== index) {
                        menu.classList.add('hidden');
                    }
                });

                // Toggle the clicked dropdown menu
                dropdownMenus[index].classList.toggle('hidden');
            });
        });
    </script>
    @stack('script')
</body>

</html>