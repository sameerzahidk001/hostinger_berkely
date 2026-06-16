<section id="section-{{$id}}" class="card-hidden show px-4 min-[1200px]:px-[72px] md:px-12 mt-3 mb-0 py-12 relative min-h-[230px] bg-[{{ $background }}]" id="our_clients">
    <div class="flex gap-3 items-center justify-center">
        <div class="bg-yellow w-[50px] h-[2px]"></div>
        <span class="font-semibold">{{ $title }}</span>
        <div class="bg-yellow w-[50px] h-[2px]"></div>
    </div>
    <p class="text-center my-4">
        {!! $description !!}
    </p>

    <div class="flex flex-col relative gap-6 mt-4 justify-between">
        <div id="scroll-container" class="flex flex-col overflow-x-scroll no-scrollbar gap-6">
            @php
                // Divide logos into 3 chunks for 3 rows
                $chunkedLogos = array_chunk($logos, ceil(count($logos) / 3));
            @endphp
            
            @foreach($chunkedLogos as $row)
                <ul class="px-4 flex gap-2">
                    @foreach($row as $logo)
                        <li>
                            <a href="{{ $logo['url'] ?? '#' }}"
                                target="{{ $logo['target'] == '1' ? '_blank' : '_self' }}" 
                                rel="{{ $logo['no_follow'] == '1' ? 'nofollow' : 'follow' }}"
                            >
                                <img src="{{ asset($logo['image']) }}" class="min-w-[170px] min-h-[80px] shrink-0 object-contain" alt="Client Logo">
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scrollContainer = document.getElementById('scroll-container');
        let scrollDirection = 'forward'; // Track scroll direction

        function autoScroll() {
            const maxScrollLeft = scrollContainer.scrollWidth - scrollContainer.clientWidth;

            if (scrollDirection === 'forward') {
                if (scrollContainer.scrollLeft >= maxScrollLeft) {
                    scrollDirection = 'backward';
                } else {
                    scrollContainer.scrollLeft += 1; // Slow forward scroll
                }
            } else {
                if (scrollContainer.scrollLeft <= 0) {
                    scrollDirection = 'forward';
                } else {
                    scrollContainer.scrollLeft -= 10; // Fast backward scroll
                }
            }
        }

        // Set the auto-scroll interval
        let autoScrollInterval = setInterval(autoScroll, 10);

        // Pause scrolling on mouse interaction
        scrollContainer.addEventListener('mouseenter', () => clearInterval(autoScrollInterval));
        scrollContainer.addEventListener('mouseleave', () => {
            autoScrollInterval = setInterval(autoScroll, 10);
        });
    });
</script>