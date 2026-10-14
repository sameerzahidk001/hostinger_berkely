@props(['services', 'heading' => 'Fee Update in Page'])

@if($services && count($services))
<section id="page-fees" class="flex flex-col items-center bg-[#000435] py-12 px-6 sm:px-12 lg:px-16">
    <div class="flex flex-col items-center gap-2 mb-10">
        <div class="flex gap-3 items-center">
            <div class="bg-yellow w-12 h-[2px]"></div>
            <span class="text-[22px] sm:text-[28px] md:text-[34px] text-white font-canela tracking-wide">
                {{ $heading }}
            </span>
            <div class="bg-yellow w-12 h-[2px]"></div>
        </div>
    </div>

    <div class="w-full max-w-[1400px]">
        @foreach($services as $service)
            @continue(!($service->is_visible ?? true))
            <div class="mb-12">
                <h3 class="text-white text-2xl font-semibold mb-6 text-center md:text-left">{{ $service->name }}</h3>

                @foreach(($service->categories ?? []) as $category)
                    @continue(!($category->is_visible ?? true))
                    <h4 class="text-yellow text-lg font-semibold mb-4 text-center md:text-left">{{ $category->name }}</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 w-full mb-8">
                        @foreach(($category->packages ?? []) as $package)
                            @continue(!($package->showonwebsite ?? true) || !($package->status ?? true))
                            @php($price = format_package_price($package->price, $package->currency))
                            <div class="relative group bg-white flex flex-col p-6 text-center rounded-2xl shadow-lg border border-gray-100
                                hover:shadow-2xl hover:border-yellow transition duration-300 ease-in-out w-full max-w-sm mx-auto">
                                <h3 class="mb-3 text-2xl font-bold text-[#000435] min-h-[50px] leading-tight break-words">
                                    {{ $package->package_name }}
                                </h3>

                                <div class="flex justify-center items-baseline mb-2">
                                    <span class="text-2xl font-bold text-[#000435] leading-tight">
                                        {{ $price['display'] }}
                                    </span>
                                </div>

                                @if($package->short_description)
                                    <p class="text-gray-600 mb-4 leading-relaxed text-sm sm:text-base">
                                        {{ $package->short_description }}
                                    </p>
                                @endif

                                @if($package->key_point)
                                    <div class="mb-4">
                                        @foreach(explode(',', $package->key_point) as $point)
                                            @if(trim($point) !== '')
                                                <span class="bg-yellow text-[#000435] text-xs sm:text-sm font-bold py-1 px-3 rounded-full shadow-md">{{ trim($point) }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <div class="w-full h-[2px] bg-yellow mb-3"></div>

                                @if(is_array($package->package_includes) && count($package->package_includes))
                                    <ul class="mb-6 space-y-1 text-left px-2">
                                        @foreach($package->package_includes as $item)
                                            <li class="flex items-start gap-2">
                                                <img src="{{ asset('frontend/images/svgs/tick.svg') }}" class="w-4 h-4 mt-0.5 shrink-0" alt="">
                                                <span class="text-gray-700 text-sm sm:text-base leading-tight min-w-0 flex-1 break-words">{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <form method="POST" action="{{ route('cart.create') }}" class="mt-auto">
                                    @csrf
                                    <input type="hidden" name="package_type" value="page">
                                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                                    <button type="submit"
                                        class="w-full py-3 bg-[#000435] text-white font-semibold rounded-xl
                                        hover:bg-yellow hover:text-[#000435] transition duration-300 transform hover:scale-105">
                                        Enroll
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</section>
@endif
