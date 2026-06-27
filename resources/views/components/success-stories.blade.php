<section class="card-hidden px-6 min-[1200px]:px-[72px] my-16">
    <!-- Main Title and Description Section -->
    <div class="flex flex-col md:flex-row gap-4 mb-16">
        <h2 class="flex-1 font-canela text-primary text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[67px]">
            {{ $title }}
        </h2>
        <div class="flex flex-1 gap-4 flex-col">
            <p class="font-ghothic text-[18px] card-hidden">
                {!! $description !!}
            </p>
            <a href="{{ $url }}" class="flex items-center group gap-2">
                <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="Arrow Right" />
                </div>
                <h2 class="font-bold text-[18px] max-w-[300px] text-primary">{{ $buttonText }}</h2>
            </a>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-10">
        @foreach($testimonials->where('status', 'show') as $testimonial)
            <div class="relative overflow-hidden group h-[500px] bg-primary">
                <img src="{{ $testimonial['image'] }}" alt="{{ image_alt($testimonial['image_alt'] ?? null, $testimonial['title'] ?? 'Success story') }}" class="h-full w-full absolute object-cover transition-all duration-400 ease-in group-hover:scale-105">
                <div class="absolute p-8 z-50 gap-4 flex flex-col justify-end h-full w-full">
                    <span class="font-canela text-white text-[24px] md:text-[36px] lg:text-[43px]">{{ $testimonial['title'] }}</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]">
                        {{ $testimonial['description'] }}
                    </p>
                    <a href="{{ $testimonial['url'] }}" class="flex items-center gap-2" target="_blank">
                        <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="Arrow Right" />
                        </div>
                        <h2 class="font-bold text-[18px] text-white">{{ $testimonial['buttonText'] }}</h2>
                    </a>
                </div>
                <div class="absolute bg-gradient-to-b from-transparent to-black h-full w-full"></div>
            </div>
        @endforeach
    </div>
</section>