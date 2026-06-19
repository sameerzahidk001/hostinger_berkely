@php
    $grid = $columns == 1 ? 'lg:grid-cols-1' : 
        ($columns == 2 ? 'md:grid-cols-2' : 
        ($columns == 3 ? 'md:grid-cols-2 lg:grid-cols-3' : 
        ($columns == 4 ? 'md:grid-cols-2 lg:grid-cols-4' : 
        ($columns == 5 ? 'md:grid-cols-2 lg:grid-cols-5' : ''))));
    $textAlignment = $alignment == 'left' ? 'text-left' : 
        ($alignment == 'center' ? 'text-center' : 
        ($alignment == 'right' ? 'text-right' : ''));
    $rowAlignment = $alignment == 'left' ? 'justify-start' : 
        ($alignment == 'center' ? 'justify-center' : 
        ($alignment == 'right' ? 'justify-end' : ''));
@endphp
<section id="section-{{$id}}" class="card-hidden px-6 min-[1200px]:px-[72px] mt-10 lg:pt-0 md:px-12 flex flex-col gap-16 w-full my-16 {{ $backgroundColor == '' || $backgroundImage == '' ? 'py-16' : '' }}" style="background-color: {{ $backgroundColor ?? 'transparent' }};">
    <div class="grid gap-10 {{ $grid }}">
        @foreach ($cards as $card)
            @if (in_array($layout, ['layout-1']))
                <div class="bg-white border border-gray-300 shadow-md rounded-lg overflow-hidden">
                    @if(isset($card['image']))
                        <div class="overflow-hidden max-h-[300px] sm:max-h-[480px]">
                            <img src="{{ $card['image'] }}" alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                                class="h-full w-full object-cover transition-transform duration-300 ease-in-out group-hover:scale-105">
                        </div>
                    @endif
                    <div class="p-4">
                        @if(isset($card['title']) || (isset($card['icon']) && $card['icon'] != ''))
                            <div class="flex items-center justify-start mt-2 gap-2">
                                @if(isset($card['icon']) && $card['icon'] != '')
                                    <img src="{{ $card['icon'] }}" alt="{{ image_alt($card['icon_alt'] ?? null, ($card['title'] ?? 'Card') . ' icon') }}" class="w-20 h-20 object-contain mb-2 lg:mb-0">
                                @endif
                                @if(isset($card['title']))
                                    <h3 class="font-canela text-[28px] leading-[41px] lg:leading-[40px] {{ $card['icon'] == '' ? 'w-full ' . $textAlignment : '' }}">
                                        {{ $card['title'] }}
                                    </h3>
                                @endif
                            </div>
                        @endif
                        @if(isset($card['description']) && $card['description'] != '')
                            <div class="text-[18px] mt-2 {{ $textAlignment }}" style="color: {{ $color ?? 'inherit' }}">{!! $card['description'] !!}</div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] text-white" style="color: {{ $color ?? 'inherit' }}">{{ $card['buttonText'] }}</h2>
                            </a>
                        @endif
                    </div>
                </div>
            @elseif (in_array($layout, ['layout-2']))
                <div class="relative overflow-hidden group h-[500px]">
                    @if(isset($card['image']))
                        <img src="{{ $card['image'] }}"
                            alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}"
                            class="h-full transition-all delay-300 duration-400 ease-in w-full absolute group-hover:scale-105 object-cover">
                    @endif
                    <div class="absolute px-4 py-8 z-50 gap-4 flex flex-col justify-end bg-opacity-45 h-full w-full bottom-0">
                        @if(isset($card['title']) || (isset($card['icon']) && $card['icon'] != ''))
                            <div class="flex items-center justify-start mt-2 gap-2">
                                @if(isset($card['icon']) && $card['icon'] != '')
                                    <img src="{{ $card['icon'] }}" alt="{{ image_alt($card['icon_alt'] ?? null, ($card['title'] ?? 'Card') . ' icon') }}" class="w-20 h-20 object-contain mb-2 lg:mb-0">
                                @endif
                                @if(isset($card['title']))
                                    <h3 class="text-[20px] sm:text-[24px] md:text-[32px] font-canela {{ $card['icon'] == '' ? 'w-full ' . $textAlignment : '' }}" style="color: {{ $color ?? 'inherit' }}">
                                        {{ $card['title'] }}
                                    </h3>
                                @endif
                            </div>
                        @endif
                        @if(isset($card['description']) && $card['description'] != '')
                            <div class="hidden group-hover:block text-white text-[18px] {{ $textAlignment }}" style="color: {{ $color ?? 'inherit' }}">
                                {!! $card['description'] !!}</div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] text-white" style="color: {{ $color ?? 'inherit' }}">{{ $card['buttonText'] }}</h2>
                            </a>
                        @endif
                    </div>

                    <div class="absolute transition-all duration-400 ease-in bg-gradient-to-b from-transparent to-black min-h-[650px] text-white bottom-0 group-hover:bottom-0 group-hover:min-h-[900px] w-full z-30">
                    </div>
                </div>
            @elseif (in_array($layout, ['layout-3']))
                <div>
                    @if(isset($card['image']))
                        <div class="overflow-hidden h-[500px]">
                            <img src="{{ $card['image'] }}" alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}" class="h-full w-full transition-all delay-300 duration-400 ease-in group-hover:scale-105 object-cover">
                        </div>
                    @endif
                    @if(isset($card['title']) || isset($card['description']) || isset($card['url']))
                    <div class="mt-2">
                        @if(isset($card['title']) || (isset($card['icon']) && $card['icon'] != ''))
                            <div class="flex items-center justify-start mt-2 gap-2">
                                @if(isset($card['icon']) && $card['icon'] != '')
                                    <img src="{{ $card['icon'] }}" alt="{{ image_alt($card['icon_alt'] ?? null, ($card['title'] ?? 'Card') . ' icon') }}" class="w-20 h-20 object-contain mb-2 lg:mb-0">
                                @endif
                                @if(isset($card['title']))
                                    <h3 class="font-canela text-[32px] leading-[41px] lg:leading-[40px] text-dark  {{ $card['icon'] == '' ? 'w-full ' . $textAlignment : '' }}" style="color: {{ $color ?? 'inherit' }}">
                                        {{ $card['title'] }}
                                    </h3>
                                @endif
                            </div>
                        @endif
                        @if(isset($card['description']) && $card['description'] != '')
                            <div class="text-[18px] leading-[41px] lg:leading-[40px] mt-2 {{ $textAlignment }}" style="color: {{ $color ?? 'inherit' }}">
                                {!! $card['description'] !!}
                            </div>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] text-white" style="color: {{ $color ?? 'inherit' }}">{{ $card['buttonText'] }}</h2>
                            </a>
                        @endif
                    </div>
                    @endif
                </div>
            @elseif (in_array($layout, ['layout-4']))
                <div class="flex flex-col lg:flex-row items-start gap-6 border border-gray-200 rounded-lg shadow-md bg-white">
                    @if(isset($card['image']))
                        <div class="max-w-[125px] h-full">
                            <img src="{{ $card['image'] }}" alt="{{ image_alt($card['image_alt'] ?? null, $card['title'] ?? 'Card image') }}" class="h-full w-full object-cover rounded-md">
                        </div>
                    @endif
                    <div class="flex-1 py-4 pr-3">
                        @if(isset($card['title']) || (isset($card['icon']) && $card['icon'] != ''))
                            <div class="flex items-center justify-start mt-2 gap-2">
                                @if(isset($card['icon']) && $card['icon'] != '')
                                    <img src="{{ $card['icon'] }}" alt="{{ image_alt($card['icon_alt'] ?? null, ($card['title'] ?? 'Card') . ' icon') }}" class="w-20 h-20 object-contain mb-2 lg:mb-0">
                                @endif
                                @if(isset($card['title']))
                                    <h3 class="text-[24px] font-semibold text-dark {{ $card['icon'] == '' ? 'w-full ' . $textAlignment : '' }}" style="color: {{ $color ?? 'inherit' }}">
                                        {{ $card['title'] }}
                                    </h3>
                                @endif
                            </div>
                        @endif
                        @if(isset($card['description']) && $card['description'] != '')
                            <p class="text-[18px] mt-2 {{ $textAlignment }}" style="color: {{ $color ?? 'inherit' }}">
                                {!! $card['description'] !!}
                            </p>
                        @endif
                        @if(isset($card['url']))
                            <a href="{{ $card['url'] }}" class="flex items-center gap-2" target="{{ $card['url_target'] == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                                </div>
                                <h2 class="font-bold text-[18px] text-white" style="color: {{ $color ?? 'inherit' }}">{{ $card['buttonText'] }}</h2>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</section>