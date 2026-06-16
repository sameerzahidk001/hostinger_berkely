<section class="card-hidden px-6 min-[1200px]:px-[72px] mt-16 w-full">
    <div class="grid grid-cols-1 place-content-center sm:grid-cols-3 gap-10">
        @foreach ($courses as $course)
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ asset($course['image']) }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
                
                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela">{{ $course['title'] }}</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">
                        {{ $course['description'] }}
                    </p>
                    <a href="{{ $course['url'] }}" class="flex items-center gap-2">
                        <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="Arrow">
                        </div>
                        <h2 class="font-bold text-[18px] text-white">{{ $course['buttonText'] }}</h2>
                    </a>
                </div>
                <div class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>
        @endforeach
    </div>
</section>