<section class="flex items-center justify-center flex-col py-12 min-h-[174px]  px-4 md:px-8 lg:px-[120px]" id="section-{{$id}}" style="background-color: {{ $background }}">
    <div class="flex gap-3 items-center">
        <div class="w-[50px] h-[2px]" style="background-color: {{ $borderColor }}"></div>
        <span class="font-semibold" style="color: {{ $color }};">
            {{ $title }}
        </span>
        <div class="w-[50px] h-[2px]" style="background-color: {{ $borderColor }}"></div>
    </div>
    <p class="text-[17px] mt-3" style="color: {{ $color }};">{{ $subtitle }}</p>

    <div class="flex flex-1 w-full flex-col sm:flex-row gap-20  justify-center items-center  pt-10">
        @if(isset($image) && $image != '')
            <div class="flex items-center  flex-col">
                <img src="{{ $image }}"
                    alt="{{ $certificateName }}" class="w-[275px] h-[350px] transition-all hover:scale-110 ease-in duration-200 delay-100">
                @if(isset($certificateName) && $certificateName != '')
                    <div class="flex justify-center items-center  text-center flex-col gap-1 mt-[20px] max-w-[310px]">
                        <p class="text-[17px] font-semibold">
                            {{ $certificateName }}
                        </p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>