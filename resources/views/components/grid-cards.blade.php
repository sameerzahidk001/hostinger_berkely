@php
    $grid = $columns == 1 ? 'lg:grid-cols-1' : 
        ($columns == 2 ? 'md:grid-cols-2' : 
        ($columns == 3 ? 'md:grid-cols-2 lg:grid-cols-3' : 
        ($columns == 4 ? 'md:grid-cols-2 lg:grid-cols-4' : 
        ($columns == 5 ? 'md:grid-cols-2 lg:grid-cols-5' : ''))));
@endphp

<section id="section-{{$id}}" class="px-6 min-[1200px]:px-[72px] md:px-12 w-full mt-16 mb-16 {{ $backgroundColor || $backgroundImage ? 'py-16' : '' }}" style="
        background-size: cover; 
        background-position: center; 
        background-color: {{ section_bg_color($backgroundColor ?? null) }};
        background-image: {{ $backgroundImage ? 'url(' . $backgroundImage . ')' : 'none' }};
    ">

    @if($title || $subtitle || $description)
        @if (in_array($layout, ['layout-1', 'layout-3', 'layout-5']))
            <div class="text-center mb-8">
                @if($subtitle)
                    <h3 class="text-[18px] text-primary" style="color: {{ $color ?? 'inherit' }};">{{ $subtitle }}</h3>
                @endif
                @if($title)
                    <h2 class="text-3xl font-bold mt-2" style="color: {{ $color ?? 'inherit' }};">{{ $title }}</h2>
                @endif
                @if($description)
                    <div class="mt-2 cms-html" style="color: {{ $color ?? 'inherit' }};">
                        {!! render_cms_html($description) !!}
                    </div>
                @endif
            </div>
        @elseif(in_array($layout, ['layout-2', 'layout-4', 'layout-6']))
            <div class="flex flex-col md:flex-row gap-4 mb-8">
                <div class="flex-1">
                    @if($subtitle)
                        <h3 class="text-[18px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[67px]"
                            style="color: {{ $color ?? 'inherit' }};">
                            {{ $subtitle }}
                        </h3>
                    @endif
                    @if($title)
                        <h2 class="font-canela text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[67px] mt-2"
                            style="color: {{ $color ?? 'inherit' }};">
                            {{ $title }}
                        </h2>
                    @endif
                </div>
                <div class="flex flex-col gap-12 flex-1">
                    @if($description)
                        <div class="mt-2 cms-html" style="color: {{ $color ?? 'inherit' }};">
                            {!! render_cms_html($description) !!}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <div class="grid gap-10 {{ $grid }}">
        @foreach ($cards as $card)
            @php
                $hasImage = ! empty($card['image']);
                $cardBg = card_bg_color($card['background'] ?? null, $hasImage, '#ffffff');
                $cardTextColor = section_color($card['color'] ?? null, 'inherit');
            @endphp
            @if (in_array($layout, ['layout-1', 'layout-2']))
                <div class="border shadow-md rounded-lg overflow-hidden" style="background-color: {{ $cardBg }}; border-color: {{ section_color($card['background'] ?? null, '#e5e7eb') }}">
                    @if(isset($card['image']))
                        <div class="overflow-hidden max-h-[300px] sm:max-h-[480px]">
                            <img src="{{ $card['image'] }}" alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                                class="h-full w-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105">
                        </div>
                    @endif
                    <div class="p-4">
                        @if(isset($card['title']))
                            <h3 class="text-2xl font-semibold text-center lg:text-left mt-2" style="color: {{ $cardTextColor }}">
                                {{ $card['title'] }}
                            </h3>
                        @endif
                        @if(isset($card['description']))
                            <div class="text-lg mt-2 cms-html" style="color: {{ $cardTextColor }}">{!! render_cms_html($card['description'] ?? '') !!}</div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}"
                                class="inline-block mt-4 px-4 py-2 bg-secondary text-white rounded-lg hover:bg-primary transition duration-300"
                                target="{{ $card['url_target'] == '0' ? '_blank' : '' }}"
                                style="color: {{ $cardTextColor }}">
                                {{ $card['url_text'] ?? $card['buttonText'] ?? 'Learn More' }}
                            </a>
                        @endif
                    </div>
                </div>
            @elseif (in_array($layout, ['layout-3', 'layout-4']))
                <div class="relative overflow-hidden group h-[500px]" data-cms-card style="background-color: {{ $cardBg }};">
                    @if($hasImage)
                        <img src="{{ $card['image'] }}"
                            alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                            class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover"
                            onerror="window.cmsCardImageError&&window.cmsCardImageError(this)">
                    @endif
                    <div class="card-image-overlay absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end h-full w-full bottom-0 {{ $hasImage ? 'bg-black/20' : '' }}">
                        @if(isset($card['title']))
                            <span class="text-[20px] sm:text-[24px] md:text-[32px] font-canela {{ $hasImage ? 'text-white' : 'text-dark' }}"
                                style="color: {{ $cardTextColor }}">{{ $card['title'] }}</span>
                        @endif
                        @if(isset($card['description']))
                            <div class="hidden font-semibold group-hover:block text-[18px] {{ $hasImage ? 'text-white' : 'text-dark' }}" style="color: {{ $cardTextColor }}">
                                {!! render_cms_html($card['description'] ?? '') !!}</div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] {{ $hasImage ? 'text-white' : 'text-dark' }}" style="color: {{ $cardTextColor }}">{{ $card['url_text'] ?? $card['buttonText'] ?? 'Learn More' }}</h2>
                            </a>
                        @endif
                    </div>

                    @if($hasImage)
                    <div class="card-image-gradient absolute inset-0 pointer-events-none bg-gradient-to-b from-transparent via-black/10 to-black/75 z-30 transition-all duration-400 group-hover:to-black/90"></div>
                    @endif
                </div>
            @elseif (in_array($layout, ['layout-5', 'layout-6']))
                <div style="background-color: {{ $cardBg }};">
                    @if(isset($card['image']))
                        <div class="overflow-hidden h-[500px]">
                            <img src="{{ $card['image'] }}" alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}" class="h-full w-full transition-all delay-300 duration-400 ease-in group-hover:scale-105 object-cover">
                        </div>
                    @endif
                    @if(isset($card['title']) || isset($card['description']) || isset($card['url']))
                    <div class="mt-2">
                        @if(isset($card['title']))
                            <h2 class="font-canela text-[32px] leading-[41px] lg:leading-[40px] text-dark text-center lg:text-left mt-2" style="color: {{ $cardTextColor }}">
                                {{ $card['title'] }}
                            </h2>
                        @endif
                        @if(isset($card['description']))
                            <div class="text-[18px] leading-[41px] lg:leading-[40px] text-dark text-center lg:text-left mt-2" style="color: {{ $cardTextColor }}">
                                {!! render_cms_html($card['description'] ?? '') !!}
                            </div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] text-dark" style="color: {{ $cardTextColor }}">{{ $card['url_text'] ?? $card['buttonText'] ?? 'Learn More' }}</h2>
                            </a>
                        @endif
                    </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</section>