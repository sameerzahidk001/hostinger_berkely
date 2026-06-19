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
        background-color: {{ $backgroundColor ?? 'transparent' }};
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
                    <div class="mt-2" style="color: {{ $color ?? 'inherit' }};">
                        {!! $description !!}
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
                        <div class="mt-2" style="color: {{ $color ?? 'inherit' }};">
                            {!! $description !!}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <div class="grid gap-10 {{ $grid }}">
        @foreach ($cards as $card)
            @if (in_array($layout, ['layout-1', 'layout-2']))
                <div class="border shadow-md rounded-lg overflow-hidden" style="background-color: {{ $card['background'] ?? 'inherit' }}; border-color: {{ $card['background'] ?? 'inherit' }}">
                    @if(isset($card['image']))
                        <div class="overflow-hidden max-h-[300px] sm:max-h-[480px]">
                            <img src="{{ $card['image'] }}" alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                                class="h-full w-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105">
                        </div>
                    @endif
                    <div class="p-4">
                        @if(isset($card['title']))
                            <h3 class="text-2xl font-semibold text-center lg:text-left mt-2" style="color: {{ $card['color'] ?? 'inherit' }}">
                                {{ $card['title'] }}
                            </h3>
                        @endif
                        @if(isset($card['description']))
                            <div class="text-lg mt-2" style="color: {{ $card['color'] ?? 'inherit' }}">{!! $card['description'] !!}</div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}"
                                class="inline-block mt-4 px-4 py-2 bg-secondary text-white rounded-lg hover:bg-primary transition duration-300"
                                target="{{ $card['url_target'] == '0' ? '_blank' : '' }}"
                                style="color: {{ $card['color'] ?? 'inherit' }}">
                                {{ $card['url_text'] ?? 'Learn More' }}
                            </a>
                        @endif
                    </div>
                </div>
            @elseif (in_array($layout, ['layout-3', 'layout-4']))
                <div class="relative overflow-hidden group h-[500px]" style="background-color: {{ $card['background'] ?? 'inherit' }}; border-color: {{ $card['background'] ?? 'inherit' }}">
                    @if(isset($card['image']))
                        <img src="{{ $card['image'] }}"
                            alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                            class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
                    @endif
                    <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                        @if(isset($card['title']))
                            <span class="text-[20px] sm:text-[24px] text-white md:text-[32px] font-canela"
                                style="color: {{ $card['color'] ?? 'inherit' }}">{{ $card['title'] }}</span>
                        @endif
                        @if(isset($card['description']))
                            <div class="hidden font-semibold group-hover:block text-white text-[18px]" style="color: {{ $card['color'] ?? 'inherit' }}">
                                {!! $card['description'] !!}</div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] text-white" style="color: {{ $card['color'] ?? 'inherit' }}">{{ $card['url_text'] }}</h2>
                            </a>
                        @endif
                    </div>

                    <div class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                </div>
            @elseif (in_array($layout, ['layout-5', 'layout-6']))
                <div style="background-color: {{ $card['background'] ?? 'inherit' }}; border-color: {{ $card['background'] ?? 'inherit' }}">
                    @if(isset($card['image']))
                        <div class="overflow-hidden h-[500px]">
                            <img src="{{ $card['image'] }}" alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}" class="h-full w-full transition-all delay-300 duration-400 ease-in group-hover:scale-105 object-cover">
                        </div>
                    @endif
                    @if(isset($card['title']) || isset($card['description']) || isset($card['url']))
                    <div class="mt-2">
                        @if(isset($card['title']))
                            <h2 class="font-canela text-[32px] leading-[41px] lg:leading-[40px] text-dark text-center lg:text-left mt-2" style="color: {{ $card['color'] ?? 'inherit' }}">
                                {{ $card['title'] }}
                            </h2>
                        @endif
                        @if(isset($card['description']))
                            <div class="text-[18px] leading-[41px] lg:leading-[40px] text-dark text-center lg:text-left mt-2" style="color: {{ $card['color'] ?? 'inherit' }}">
                                {!! $card['description'] !!}
                            </div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] text-white" style="color: {{ $card['color'] ?? 'inherit' }}">{{ $card['url_text'] }}</h2>
                            </a>
                        @endif
                    </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</section>