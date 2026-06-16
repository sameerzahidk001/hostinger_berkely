<section  id="section-{{$id}} hero-banner" class="relative overflow-hidden min-h-screen max-h-screen">
    @if (isset($video) || isset($background))
        <div class="absolute scroll-smooth bg-black top-0 flex justify-center flex-col z-30 h-full w-full items-center">
            @if(!empty($video))
                <iframe class="w-full min-h-screen max-h-screen object-cover" 
                    src="https://www.youtube.com/embed/{{ getYouTubeVideoID($video) }}?autoplay=1&mute=1&loop=1&playlist={{ getYouTubeVideoID($video) }}&controls=0&showinfo=0&modestbranding=1" 
                    frameborder="0" allowfullscreen>
                </iframe>
            @else
                <img src="{{ media_url($background) }}" class="min-h-screen max-h-screen object-cover w-full zoom-in" alt="Hero Banner Image">
            @endif
        </div>
    @endif

    <div class="absolute px-2 top-0 flex justify-center flex-col z-40 h-full w-full items-center inset-0 bg-black bg-opacity-50">
        @if (isset($title))
            <h1 class="text-white text-[68px] leading-[60px] md:text-[75px] md:leading-[60px] lg:text-[80px] xl:text-[116px] font-canela max-w-[490px] text-center lg:leading-[80px] xl:leading-[116px]">
                {{ $title }}
            </h1>
        @endif
        @if (isset($subtitle))
            <span class="text-[18px] leading-[27px] max-w-[520px] text-center text-white font-bold mt-6 md:mt-10">
                {{ $subtitle }}
            </span>
        @endif
        @if (isset($description))
            <div class="card-hidden px-6 min-[1200px]:px-[72px] mt-10 lg:pt-0 md:px-12 flex flex-col w-full">
                {!! $description !!}
            </div>
        @endif
        <div class="absolute min-h-[20%] w-px top-0 left-1/2 transform -translate-x-1/2 bg-white"></div>
        <div class="absolute min-h-[16%] w-px bottom-0 left-1/2 transform -translate-x-1/2 bg-white"></div>
    </div>
</section>