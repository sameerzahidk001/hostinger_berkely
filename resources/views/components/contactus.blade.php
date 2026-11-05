<section id="section-{{ $id }}" class="page-section card-hidden px-6 min-[1200px]:px-[72px] md:px-12" style="background-color: {{ section_bg_color($background) }}">
    <div class="flex flex-col justify-center items-center">
        <div class="w-full text-center">
            <h2 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]" style="color: {{ $color }}">{{ $title }}</h2>
            <div class="w-20 h-[2px] mx-auto mt-2" style="background-color: {{ $color }}"></div>
            @if (!empty($iframe))
                <div class="mt-6">
                    {!! $iframe !!} 
                </div>
            @endif
        </div>
    </div>
</section>