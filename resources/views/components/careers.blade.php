<style>
    .career-cards {
        background-color: {{ section_color($cardBackground, '#ffffff') }};
        color: {{ section_color($cardColor, 'inherit') }};
        border-color: {{ section_color($cardBorderColor, '#e5e7eb') }};
    }

    .career-cards:hover {
        background-color: {{ section_color($cardHoverBackground, '#f9fafb') }};
        color: {{ section_color($cardHoverColor, 'inherit') }};
        border-color: {{ section_color($cardHoverBorderColor, '#e5e7eb') }};
    }

    .careers-section {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
        margin-top: 0;
        margin-bottom: 0;
    }

    .careers-section + .careers-section {
        padding-top: 0 !important;
        margin-top: 0 !important;
    }

    .career-cards :is(p, h1, h2, h3, h4, h5, h6, ul, ol) {
        margin: 0 0 0.5rem 0;
    }

    .career-cards :is(p, h1, h2, h3, h4, h5, h6, ul, ol):last-child {
        margin-bottom: 0;
    }

    .career-cards :empty {
        display: none;
    }
</style>
<section
    id="section-{{ $id }}"
    class="careers-section w-full lg:px-[120px] px-4 md:px-8"
    style="background-color: {{ $background }}">

    @if (trim(strip_tags((string) ($title ?? ''))) !== '')
        <h2 class="font-heading mb-2 text-3xl font-bold lg:text-4xl text-center" style="color: {{ $color ?? 'inherit' }}">{{ $title }}</h2>
    @endif
    @if (trim(strip_tags((string) ($description ?? ''))) !== '')
        <div class="mb-3" style="color: {{ $color ?? 'inherit' }}">{!! $description !!}</div>
    @endif

    <div class="flex flex-col gap-3">
        @foreach ($cards as $card)
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-4 md:p-5 rounded-lg shadow-sm border career-cards">
                <div class="flex-1 min-w-0">
                    <div class="career-card-content">{!! $card['content'] !!}</div>
                    @if (trim((string) ($card['tags'] ?? '')) !== '')
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach (array_filter(array_map('trim', explode(',', $card['tags']))) as $tag)
                                <span class="px-3 py-1 bg-gray-100 text-sm text-gray-700 rounded-full">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if (trim((string) ($card['urlText'] ?? '')) !== '')
                    <div class="shrink-0 lg:ml-4">
                        <a href="{{ $card['url'] }}" target="{{ $card['urlTarget'] == '0' ? '_blank' : '' }}"
                            class="inline-block px-4 py-2 border bg-gray-100 text-gray-700 border-gray-300 rounded-full text-sm whitespace-nowrap">
                            {{ $card['urlText'] }}
                        </a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>
