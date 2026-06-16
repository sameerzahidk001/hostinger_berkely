@extends('layouts.app')
@push('style')

@endpush

@section('content')



<section class="flex flex-col-reverse mb-24 justify-between relative min-h-[491px] md:max-h-[491px]  bg-[#1a1819]">
    <div class="flex text-white  min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6  md:w-[50%] m-0  flex-1 flex-col ">

        <div class="flex items-center gap-1">
            <a href="#">Home</a>

            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
            <span class="font-bold">Executive Education</span>
        </div>

        <div class="flex flex-col gap-1 mt-12 mb-10">
            <h1 class="text-[20px] leading-6 font-canela text-primary_orange">WE WILL TEST YOU, CHALLENGE YOU, ENCOURAGE
                YOU AND
                INSPIRE YOU
            </h1>
            <h2 class="text-[48px] leading-[58px] font-canela text-white">Executive Education

            </h2>
        </div>
        <p clas="text-[18px] text-white ">Secure the knowledge and skills to lead, manage and innovate for the
            future.
        </p>
        <div class="flex gap-6 mt-auto">
            <button
                class="border py-4 px-3 w-full border-primary_orange hover:bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">For
                Individual</button>
            <button
                class="border py-4 px-3 w-full border-primary_orange hover:bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300">For
                Organisation</button>
        </div>

    </div>
    <div class=" flex xl:px-6 md:top-0 md:absolute md:right-0 z-40 md:w-[50%] m-0 flex-1 flex-col gap-4 md:h-full">
        <img src="https://www.jbs.cam.ac.uk/wp-content/uploads/2023/05/executive-education-homepage-600x600-1.jpg"
            alt="" class="object-cover h-full md:max-h-full md:w-full md:h-full">
    </div>
</section>

<section class="flex flex-col text-black z-[99999]card-hidden min-[1200px]:px-[72px] md:px-12 px-6 mb-24">

    <div class="flex flex-col items-center bg-[#f9f9f9] px-2 sm:px-4 py-10 md:px-10 lg:px-14 lg:py-20 gap-20">
        <h1 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]">Explore our open programmes
        </h1>
        <div class=" gap-8  grid grid-cols-1 md:grid-cols-2">
            <div class="bg-white p-8 self-start felx flex-col  ">
                <a href="" class="hover:underline underline-offset-2 text-[28px] text-dark font-canela">Leadership</a>
                <p class="mt-10 text-[18px] font-canela text-dark mb-10">Designed for senior leaders and ambitious
                    executives who wish to build
                    their management
                    skills, and
                    fine-tune
                    and
                    refresh their leadership approach.
                </p>
                <div class="tab w-full overflow-hidden border-b-2 border-dark">
                    <input class="absolute opacity-0" id="tab-single-one" type="radio" name="tabs2">
                    <label class="block text-[18px] font-canela text-dark leading-normal cursor-pointer"
                        for="tab-single-one">Leadership programmes</label>
                    <div class="tab-content overflow-hidden flex flex-col my-3 gap-4  leading-normal">
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white p-8 self-start felx flex-col  ">
                <a href="" class="hover:underline underline-offset-2 text-[28px] text-dark font-canela">Leadership</a>
                <p class="mt-10 text-[18px] font-canela text-dark mb-10">Designed for senior leaders and ambitious
                    executives who wish to build
                    their management
                    skills, and
                    fine-tune
                    and
                    refresh their leadership approach.
                </p>
                <div class="tab w-full overflow-hidden border-b-2 border-dark">
                    <input class="absolute opacity-0" id="tab-single-two" type="radio" name="tabs2">
                    <label class="block text-[18px] font-canela text-dark leading-normal cursor-pointer"
                        for="tab-single-two">Label One</label>
                    <div class="tab-content overflow-hidden flex flex-col my-3 gap-4  leading-normal">
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white p-8 self-start felx flex-col  ">
                <a href="" class="hover:underline underline-offset-2 text-[28px] text-dark font-canela">Leadership</a>
                <p class="mt-10 text-[18px] font-canela text-dark mb-10">Designed for senior leaders and ambitious
                    executives who wish to build
                    their management
                    skills, and
                    fine-tune
                    and
                    refresh their leadership approach.
                </p>
                <div class="tab w-full overflow-hidden border-b-2 border-dark">
                    <input class="absolute opacity-0" id="tab-single-three" type="radio" name="tabs2">
                    <label class="block text-[18px] font-canela text-dark leading-normal cursor-pointer"
                        for="tab-single-three">Label One</label>
                    <div class="tab-content overflow-hidden flex flex-col my-3 gap-4  leading-normal">
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white p-8 self-start felx flex-col  ">
                <a href="" class="hover:underline underline-offset-2 text-[28px] text-dark font-canela">Leadership</a>
                <p class="mt-10 text-[18px] font-canela text-dark mb-10">Designed for senior leaders and ambitious
                    executives who wish to build
                    their management
                    skills, and
                    fine-tune
                    and
                    refresh their leadership approach.
                </p>
                <div class="tab w-full overflow-hidden border-b-2 border-dark">
                    <input class="absolute opacity-0" id="tab-single-four" type="radio" name="tabs2">
                    <label class="block text-[18px] font-canela text-dark leading-normal cursor-pointer"
                        for="tab-single-four">Label One</label>
                    <div class="tab-content overflow-hidden flex flex-col my-3 gap-4  leading-normal">
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                        <a href="#" class="flex group gap-2 items-center h">
                            <span
                                class="text-[20px] group-hover:underline underline-offset-2 font-semibold leading-[30px] text-dark">Cambridge
                                Advanced Leadership
                                Programme</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>



</section>


@endsection

@push('script')
    <script>
        var myRadios = document.getElementsByName('tabs2');
        var setCheck;
        var x = 0;
        for (x = 0; x < myRadios.length; x++) {
            myRadios[x].onclick = function () {
                if (setCheck != this) {
                    setCheck = this;
                } else {
                    this.checked = false;
                    setCheck = null;
                }
            };
        }

    </script>
@endpush