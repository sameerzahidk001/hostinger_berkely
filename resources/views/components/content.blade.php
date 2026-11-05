<section id="section-{{$id}}" class="page-section card-hidden px-6 min-[1200px]:px-[72px] flex items-center w-full" style="background-color: {{ section_bg_color($background) }};">
    <div class="flex flex-col md:flex-row flex-1 gap-x-20 gap-y-10 justify-between">
        <div class="flex flex-1 flex-col gap-2">
            @if (isset($description))
                <div class="cms-html" style="color: {{ $color }}">{!! render_cms_html($description) !!}</div>
            @endif
        </div>
    </div>
</section>