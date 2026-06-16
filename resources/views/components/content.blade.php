<section id="section-{{$id}}" class="card-hidden px-6 min-[1200px]:px-[72px] flex items-center w-full my-16" style="background-color: {{ $background }};">
    <div class="flex flex-col md:flex-row flex-1 gap-x-20 gap-y-10 justify-between {{ $background != 'transparent' ? 'py-16' : '' }}">
        <div class="flex flex-1 flex-col gap-2">
            @if (isset($description))
                <div style="color: {{ $color }}">{!! $description !!}</div>
            @endif
        </div>
    </div>
</section>