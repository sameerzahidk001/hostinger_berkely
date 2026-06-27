<style>
    @media (min-width: 1280px) {
        .content-bg {
            background-color: {{ $contentBackground }};
        }
    }
</style>
@if ($layout === 'layout-1')
    <section id="section-{{$id}}" class="card-hidden px-6 min-[1200px]:px-[72px] my-16 {{ $backgroundColor != 'transparent' ? 'py-16' : '' }} md:px-12" style="background-color: {{ $backgroundColor }}">
        <div class="flex flex-col {{ $direction === 'left' ? 'lg:flex-row' : 'lg:flex-row-reverse' }} items-center gap-x-24 gap-y-10">
            <div class="flex-1">
                <img src="{{ $image }}" alt="{{ image_alt($altText ?? null, $title ?? 'Media image') }}"
                    class="w-full min-h-[300px] xl:min-h-[500px] xl:min-w-[500px] object-cover">
            </div>
            <div class="relative flex-1 flex flex-col gap-1 {{ $contentBackground != 'transparent' ? 'xl:px-8 xl:py-4 min-[1340px]:py-8 my-2' : '' }} content-bg">
                @if(isset($title) || (isset($icon) && $icon != ''))
                    <div class="flex gap-2 flex-col">
                        <div class="flex flex-col sm:flex-row items-center space-x-4">
                            @if(isset($icon) && $icon != '')
                                <img src="{{ media_url($icon) }}" alt="{{ $title }} Icon"
                                    class="w-36 h-36 object-contain" />
                            @endif
                            @if(isset($title))
                                <div>
                                    <h2 class="font-canela text-[24px] sm:text-[28px] md:text-[32px] lg:text-[36px] leading-normal" style="color: {{ $color ?? 'inherit' }};">
                                        {{ $title }}</h2>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if(isset($description))
                    <div class="flex flex-col">
                        <div class="text-[18px] my-6" style="color: {{ $color ?? 'inherit' }};">{!! $description !!}</div>
                    </div>
                @endif
                @if(isset($link))
                    <a href="{{ $link }}" class="flex flex-1 items-center group gap-2 mt-2"
                        target="{{ $urlTarget == '0' ? '_blank' : '' }}">
                        <div
                            class="flex group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                            <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4"
                                alt="Arrow Right" />
                        </div>
                        <h2 class="font-bold text-[18px] max-w-[420px]" style="color: {{ $color ?? 'inherit' }};">{{ $buttonText }}</h2>
                    </a>
                @endif
            </div>
        </div>
    </section>
@elseif ($layout === 'layout-2')
    <section class="lg:px-[48px] my-16 {{ $backgroundColor != 'transparent' ? 'py-16' : '' }}" style="background-color: {{ $backgroundColor }}">
        <div class="flex flex-col gap-10 xl:gap-0 {{ $direction === 'left' ? 'lg:flex-row' : 'lg:flex-row-reverse' }} items-stretch gap-y-10 ">
            <div class="flex-1 w-full basis-full">
                <img src="{{ $image }}" alt="{{ image_alt($altText ?? null, $title ?? 'Media image') }}"
                    class="w-full h-full object-cover">
            </div>
            <div class="relative flex justify-center items-center px-8 lg:px-0 py-4 w-full basis-full ">
                <div
                    class="flex-1 w-full xl:py-4 min-[1340px]:py-8 my-2 xl:absolute {{ $direction === 'left' ? 'xl:-left-20' : 'xl:-right-20' }} xl:px-8 gap-2 flex flex-col {{ $contentBackground != 'transparent' ? 'content-bg' : 'xl:bg-white' }}">
                    @if(isset($title))
                        <h2 class="font-roboto text-[32px] leading-[40px] md:text-[48px] md:leading-[58px] min-[960px]:text-[32px] min-[960px]:leading-[40px] tracking-wide max-w-[450px]"
                            style="color: {{ $color ?? 'inherit' }};">
                                {{ $title }}
                        </h2>
                    @endif
                    <div class="flex flex-col gap-2">
                        @if(isset($description))
                            <div class="text-[18px]" style="color: {{ $color ?? 'inherit' }};">{!! $description !!}</div>
                        @endif

                        @if(isset($link))
                            <a href="{{ $link }}" class="flex items-center gap-2 mt-2" target="{{ $urlTarget == '0' ? '_blank' : '' }}">
                                <div
                                    class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary  min-h-10 min-w-10 max-h-10 max-w-10">
                                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}"
                                        class="w-[28px] h-4" alt="Arrow Right">
                                </div>
                                <h2 class="font-bold text-[16px] hover:underline" style="color: {{ $color ?? 'inherit' }};">{{ $buttonText }}
                                </h2>
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </section>
@endif