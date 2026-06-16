@php
    $buttonCount = 0;
    if (!empty($solidButtonUrl) && !empty($solidButtonText)) {
        $buttonCount++;
    }
    if (!empty($outlineButtonUrl) && !empty($outlineButtonText)) {
        $buttonCount++;
    }

    $buttonWidth = $buttonCount === 1 ? 'w-full md:w-[50%]' : 'w-full';

    $fixedTitle = $title ?? null;
    $fixedSubtitle = $subtitle ?? null;

    $fallbackBannerImage = asset('frontend/images/jpg/60.jpg');
    $useFallbackImage = empty($image) && (request()->is('courses') || stripos((string) $fixedTitle, 'executive') !== false);
    $resolvedImage = !empty($image) ? $image : ($useFallbackImage ? $fallbackBannerImage : '');
@endphp

<section class="flex flex-col-reverse justify-between relative min-h-[491px] md:max-h-[491px]" style="background-color: {{ $backgroundColor ?? 'transparent' }}" id="section-{{$id}}">
    <div class="flex text-white min-[1200px]:pl-[72px] md:pr-12 py-[30px] px-6 md:w-[50%] m-0 flex-1 flex-col">
        @if (isset($breadcrumb) && count($breadcrumb) > 0)
            <div class="items-center gap-1 hidden md:flex">
                @foreach ($breadcrumb as $index => $crumb)
                    @if ($index !== 0)
                        <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-4 h-4" alt="">
                    @endif
                    @if ($index < count($breadcrumb) - 1)
                        <a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                    @else
                        <span class="font-bold">{{ $crumb['title'] }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        @if (isset($subtitle) || isset($title))
            <div class="flex flex-col gap-1 mt-4 md:mt-10 mb-4 md:mb-10">
                @if (isset($subtitle))
                    <h2 class="text-[20px] leading-8 text-primary_orange">{{ $fixedSubtitle }}</h2>
                @endif
                @if (isset($title))
                    <h1 class="text-[42px] md:text-[48px] leading-[58px] font-canela" style="color: {{ $color }}">{{ $fixedTitle }}</h1>
                @endif
            </div>
        @endif
        @if (isset($description))
            <div class="" style="color: {{ $color }}">{!! $description !!}</div>
        @endif
        
        @if (!empty($solidButtonUrl) || !empty($outlineButtonUrl)) 
            <div class="flex gap-6 mt-4 md:mt-auto">
                @if (!empty($solidButtonUrl) && !empty($solidButtonText))
                    <a href="{{ $solidButtonUrl }}"
                        class="text-center border py-4 px-3 {{ $buttonWidth }} border-primary_orange bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300"
                        target="{{ $solidUrlTarget == '0' ? '_blank' : '' }}">
                        {{ $solidButtonText }}
                    </a>
                @endif
                
                @if (!empty($outlineButtonUrl) && !empty($outlineButtonText))
                    <a href="{{ $outlineButtonUrl }}"
                        class="text-center border py-4 px-3 {{ $buttonWidth }} border-primary_orange hover:bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-300 duration-300"
                        target="{{ $outlineUrlTarget == '0' ? '_blank' : '' }}">
                        {{ $outlineButtonText }}
                    </a>
                @endif
            </div>
        @endif
    </div>
    
    @if (!empty($resolvedImage))
        <div class="flex xl:px-0 md:top-0 md:absolute md:right-0 z-40 md:w-[50%] m-0 flex-1 flex-col gap-4 md:h-full">
            <img src="{{ $resolvedImage }}" alt="" class="object-cover h-full md:max-h-full md:w-full md:h-full">
        </div>
    @endif
</section>