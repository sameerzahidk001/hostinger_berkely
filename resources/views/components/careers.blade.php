<style>
    .career-cards{
        background-color: {{ $cardBackground }};
        color: {{ $cardColor }};
        border-color: {{ $cardBorderColor }};
    }
    .career-cards:hover{
        background-color: {{ $cardHoverBackground }};
        color: {{ $cardHoverColor }};
        border-color: {{ $cardHoverBorderColor }};
    }
</style>
<section
    class="w-full lg:px-[120px] px-4 md:px-8 my-6 {{ $background != 'transparent' ? 'py-8' : 'py-4' }}"
    style="background-color: {{ $background }}">

    @if (isset($title))
        <h2 class="font-heading mb-2 text-3xl font-bold lg:text-4xl text-center" style="color: {{ $color ?? 'inherit' }}">{{ $title }}</h2>
    @endif
    @if (isset($description))
        <div class="mb-4" style="color: {{ $color ?? 'inherit' }}">{!! $description !!}</div>
    @endif
    <div class="p-0">
        <!-- Job Listings -->
        <div class="space-y-4 md:space-y-5">
            @foreach ($cards as $card)
                <div
                    class="flex flex-col sm:flex-col lg:flex-row gap-y-4 items-center justify-between p-6 rounded-lg shadow-sm transition-all ease-in duration-200 delay-100 border career-cards gap-4">
                    <div>
                        {!! $card['content'] !!}
                        <div class="flex space-x-2 mt-2">
                            @foreach (explode(',', $card['tags']) as $tag)
                                <span class="px-3 py-1 bg-gray-100 text-sm text-gray-700 rounded-full">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-col flex-1">
                        <a href="{{ $card['url'] }}" target="{{ $card['urlTarget'] == '0' ? '_blank' : '' }}"
                            class="px-4 py-2 border bg-gray-100 text-gray-700 border-gray-300 rounded-full text-sm whitespace-nowrap">
                            {{ $card['urlText'] }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>