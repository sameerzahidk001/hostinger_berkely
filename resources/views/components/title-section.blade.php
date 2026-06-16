@php
    $textAlignment = $alignment == 'left' ? 'text-left' : 
        ($alignment == 'center' ? 'text-center' : 
        ($alignment == 'right' ? 'text-right' : ''));
    $rowAlignment = $alignment == 'left' ? 'justify-start' : 
        ($alignment == 'center' ? 'justify-center' : 
        ($alignment == 'right' ? 'justify-end' : ''));
@endphp
<section id="section-{{$id}}" class="card-hidden px-6 min-[1200px]:px-[72px] flex items-center mb-16 mt-16" 
    style="background: {{ $background }}; background-size: cover; background-position: center;">
    <div class="flex flex-col md:flex-row gap-4 {{ $background != 'transparent' ? 'mb-16 mt-16' : '' }}">
        <h1 class="flex-1 font-canela text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[56px] min-[960px]:leading-[67px] {{ $textAlignment }}" style="color: {{ $color }};">
            {{ $title }}
        </h1>
        <div class="flex flex-col gap-12 flex-1">
            <div class="text-[18px]" style="color: {{ $color }};">
                {!! $description !!}
            </div>
            <a href="{{ $link }}" class="flex items-center group gap-6" target="{{ $buttonUrlTarget == '0' ? '_blank' : '' }}">
                <div class="flex group-hover:bg-primary justify-center items-center rounded-full bg-secondary self-center min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="Arrow Right" />
                </div>
                <h2 class="font-bold text-[18px] max-w-[300px]" style="color: {{ $color }};">{{ $buttonText }}</h2>
            </a>
        </div>
    </div>
</section>