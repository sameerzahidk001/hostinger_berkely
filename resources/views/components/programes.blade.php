@php
$grid = $columns == 1 ? 'lg:grid-cols-1' :
($columns == 2 ? 'md:grid-cols-2' :
($columns == 3 ? 'md:grid-cols-2 lg:grid-cols-3' :
($columns == 4 ? 'md:grid-cols-2 lg:grid-cols-4' : '')));
@endphp

<section class="flex flex-col card-hidden my-16" id="section-{{$id}}">
    <div class="flex flex-col items-center px-2 sm:px-4 md:px-10 lg:px-16 lg:py-10 gap-12 {{ $background ? 'py-16' : '' }}" style="background-color: {{ section_bg_color($background) }}">
        <h2 class="text-[32px] font-canela leading-[38px] md:text-[40px] md:leading-[40px]" style="color: {{ $color }}">{{ $title }}</h2>

        <div class="gap-4 grid {{ $grid }}">
            @foreach($categories as $index => $data)
            <div class="p-8 self-start flex flex-col h-full" style="background-color: {{ card_bg_color($cardBackground, false, '#ffffff') }}">
                <h3 class="text-[20px] font-canela font-bold capitalize" style="color: #f8961f;">
                    {{ $data->name }}
                </h3>
                <div class="w-[80px] h-[3px] mt-3" style="background-color: #000;"></div>
                <p class="mt-6 text-[14px] font-normal font-ghothic mb-10 min-h-[136px] leading-[20px]" style="color: {{ $color }}">
                    {{ $data->description }}
                </p>
                @if ($data->courses->isNotEmpty())
                <div class="tab w-full overflow-hidden border-b-2 mt-auto" style="border-color: {{ $color }}">
                    <input class="absolute opacity-0" id="tab-single-{{$index}}" type="radio" name="tabs2">
                    <label class="course-tab-label flex items-center justify-between text-[15px] font-ghothic font-semibold leading-normal cursor-pointer capitalize" for="tab-single-{{$index}}" style="color: {{ $color }}">
                        <span>Programs</span>
                        <svg class="course-tab-caret" width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M7 10l5 6 5-6z"></path>
                        </svg>
                    </label>
                    <div class="tab-content overflow-hidden flex flex-col my-3 gap-4 leading-normal">
                        @foreach($data->courses as $key => $course)
                        <a href="{{ route('course.details', ['course' => $course->slug]) }}" target="{{ $url_target == 0 ? '_blank' : '' }}" class="flex group gap-2 items-center">
                            <span class="text-[14px] font-ghothic group-hover:underline underline-offset-2 font-semibold leading-[20px]" style="color: {{ $color }}">{{ $course->title }}</span>
                            <img src="{{ asset('frontend/images/svgs/caret-r-o.svg') }}" class="w-6 h-6" alt="">
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @if ($pagination != 1 && $categories->hasPages())
        <div class="mt-8 flex justify-center items-center space-x-2">
            {{-- Previous Page Button --}}
            @if (!$categories->onFirstPage())
            <a href="{{ $categories->previousPageUrl() }}" class="px-4 py-2 bg-primary_orange text-white rounded hover:bg-opacity-80 transition">
                Previous
            </a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($categories->getUrlRange(1, $categories->lastPage()) as $page => $url)
            @if ($page == $categories->currentPage())
            <span class="px-4 py-2 bg-primary_orange text-white rounded">{{ $page }}</span>
            @else
            <a href="{{ $url }}" class="px-4 py-2 bg-gray-200 text-black rounded hover:bg-gray-300 transition">
                {{ $page }}
            </a>
            @endif
            @endforeach

            {{-- Next Page Button --}}
            @if ($categories->hasMorePages())
            <a href="{{ $categories->nextPageUrl() }}" class="px-4 py-2 bg-primary_orange text-white rounded hover:bg-opacity-80 transition">
                Next
            </a>
            @endif
        </div>
        @endif
    </div>
</section>

@push('script')
<script>
    var myRadios = document.getElementsByName('tabs2');
    var setCheck;
    var x = 0;
    for (x = 0; x < myRadios.length; x++) {
        myRadios[x].onclick = function() {
            if (setCheck != this) {
                setCheck = this;
            } else {
                this.checked = false;
                setCheck = null;
            }
        };
    }
</script>
@endpush