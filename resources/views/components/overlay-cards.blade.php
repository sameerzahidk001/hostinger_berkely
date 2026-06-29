@php
    $grid = $columns == 1 ? 'grid-cols-1 xl:grid-cols-1' : 
    ($columns == 2 ? 'grid-cols-1 md:grid-cols-2' : 
    ($columns == 3 ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3' : 
    ($columns == 4 ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4' : 
    ($columns == 5 ? 'grid-cols-1 md:grid-cols-2 xl:grid-cols-5' : ''))));
@endphp
<section class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 w-full mt-16 mb-16 show" style="background: {{ section_bg_color($background) }}">
    <div class="grid place-content-center gap-10 {{ $background != 'transparent' ? 'mb-16 mt-16' : '' }} {{ $grid }}">
        @foreach ($cards as $card)
            @php
                $hasImage = ! empty($card['image']);
                $cardBg = card_bg_color($card['background'] ?? null, $hasImage, '#ffffff');
            @endphp
            <div class="relative overflow-hidden group h-[500px]" data-cms-card style="background-color: {{ $cardBg }};">
            @if ($hasImage)
                <img src="{{ $card['image'] }}"
                    alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                    class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                    onerror="window.cmsCardImageError&&window.cmsCardImageError(this)">
            @endif
                
                <div class="card-image-overlay absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end h-full w-full bottom-0 {{ $hasImage ? 'bg-black/20' : '' }}">
                    <span class="text-[20px] sm:text-[24px] md:text-[32px] font-canela {{ $hasImage ? 'text-white' : 'text-dark' }}" style="color: {{ $color }}">{{ $card['title'] }}</span>
                    <p class="hidden font-semibold group-hover:block text-[18px] cms-html {{ $hasImage ? 'text-white' : 'text-dark' }}" style="color: {{ $color }}">{!! render_cms_html($card['description'] ?? '') !!}</p>
                    <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                        <div class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                        </div>
                        <h2 class="font-bold text-[18px] {{ $hasImage ? 'text-white' : 'text-dark' }}" style="color: {{ $color }}">{{ $card['buttonText'] }}</h2>
                    </a>
                </div>
                
                @if($hasImage)
                <div class="card-image-gradient absolute inset-0 pointer-events-none bg-gradient-to-b from-transparent via-black/10 to-black/75 z-30 transition-all duration-400 group-hover:to-black/90"></div>
                @endif
            </div>
        @endforeach
    </div>
</section>