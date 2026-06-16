@php
    $grid = $columns == 1 ? 'lg:grid-cols-1' : 
        ($columns == 2 ? 'md:grid-cols-2' : 
        ($columns == 3 ? 'md:grid-cols-2 lg:grid-cols-3' : 
        ($columns == 4 ? 'md:grid-cols-2 lg:grid-cols-4' : 
        ($columns == 5 ? 'md:grid-cols-2 lg:grid-cols-5' : ''))));
@endphp
{{-- @dd($categories) --}}
<section id="section-{{ $id }}" class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 flex flex-col gap-16 w-full my-16 {{ $background != 'transparent' ? 'py-16' : '' }}" style="background-color: {{ $background ?? 'transparent' }};">
    <div class="flex flex-col flex-1 gap-y-10 justify-between {{ $layout == 'layout-1' ? 'md:flex-row gap-x-20' : '' }}">
        @if (isset($title) && isset($description))
            <div class="flex flex-1 flex-col gap-2">
                @if (isset($title))
                    <span class="font-canela text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[42px] min-[960px]:leading-[67px] {{ $layout == 'layout-2' ? 'text-center' : '' }}" style="color: {{ $color }}">{{ $title }}</span>
                @endif
                @if (isset($description))
                    <div class="text-[18px]" style="color: {{ $color }}">{!! $description !!}</div>
                @endif
            </div>
        @endif
        @if (!empty($categories))
            <div class="flex flex-1 flex-col gap-2">
                <div class="grid gap-8 {{ $grid }} w-full">
                    @foreach($categories as $category)
                        <div class="flex items-start gap-2 {{ $seperator == '1' ? 'border-b pb-4' : '' }}" style="border-color: {{ $color }}">
                            @if ($category['icon'] && !empty($category['icon']))
                                <img src="{{ $category['icon'] }}" class="w-6 h-6" alt="">
                            @endif
                            <a href="{{ $category['url'] }}" target="{{ $category['target'] ?? '_self' }}" class="text-[18px]" style="color: {{ $color }}">{{ $category['name'] }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>