@php
    $grid = $columns == 2 ? 'md:grid-cols-2' : 
        ($columns == 3 ? 'md:grid-cols-2 lg:grid-cols-3' : 
        ($columns == 4 ? 'md:grid-cols-2 lg:grid-cols-4' : ''));
@endphp
<section id="section-{{$id}}" class="card-hidden px-6 min-[1200px]:px-[72px] my-16 lg:pt-0 md:px-12 flex flex-col gap-16 w-full" >
    <div class="flex m-0 z-20 flex-1 flex-col justify-center gap-4 min-[1200px]:px-[72px] md:px-12 px-6">
        <h2 class="text-[18px] font-semibold font-canela" style="color:{{ $color }}">{{ $title }} Courses ({{ count($courses ?? []) }})</h2>
        <!-- <div class="w-[100%] h-[1px] bg-gray-200"></div> -->
        <div class="w-[100%] h-[2px]" style="background-color: {{ $background ?? 'transparent' }}"></div>
    </div>
    <div class="flex py-0 z-20 flex-1 gap-4 min-[1200px]:px-[72px] md:px-12 px-6  ">
        <div class="grid w-full {{ $grid }} gap-3 place-content-between pb-2">
            @foreach($courses as $key => $course)
            <div class="flex flex-col row-span-1 w-full border rounded overflow-hidden">
                
                <a href="{{ route('course.details', $course['slug']) }}" class="flex flex-1 flex-col px-[20px] py-[20px]">
                    
                    <div class="flex flex-col gap-1">
                        <h2 class="text-[18px] font-semibold  sm:min-h-auto sm:max-h-auto md:min-h-[53px] md:max-h-[53px] overflow-hidden" style="color:{{ $color }}">{{ $course['title'] }}</h2>
                            <div class="w-[80px] h-[3px] mt-2" style="background-color: {{ $background ?? 'transparent' }}"></div>
                    </div>
                    
                </a>
            </div>
            
            @endforeach
            
        </div>

    </div>

</section>