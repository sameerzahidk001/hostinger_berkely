@php
    $grid = $columns == 1 ? 'grid-cols-1 xl:grid-cols-1' : 
    ($columns == 2 ? 'grid-cols-1 md:grid-cols-2' : 
    ($columns == 3 ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3' : 
    ($columns == 4 ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4' : 
    ($columns == 5 ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-5' : ''))));
@endphp
<section class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 w-full mt-16 mb-16 show" style="background: {{ $background }}">
    <div class="grid place-content-center gap-10 {{ $background != 'transparent' ? 'mb-16 mt-16' : '' }} {{ $grid }}">
        @foreach ($cards as $card)
            <div class="relative overflow-hidden group h-[500px] bg-primary">
            @if (!empty($card['image']))
                <img src="{{ $card['image'] }}"
                    alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
            @endif
                
                <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                    <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela" style="color: {{ $color }}">{{ $card['title'] }}</span>
                    <p class="hidden font-semibold group-hover:block text-white text-[18px]" style="color: {{ $color }}">{!! $card['description'] !!}</p>
                    <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                        <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                        </div>
                        <h2 class="font-bold text-[18px] text-white" style="color: {{ $color }}">{{ $card['buttonText'] }}</h2>
                    </a>
                </div>
                
                <div class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                </div>
            </div>
        @endforeach
    </div>
</section>