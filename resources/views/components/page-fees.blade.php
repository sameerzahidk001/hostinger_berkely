@props(['services', 'heading' => 'Fee Update in Page'])

@if($services && count($services))
<section class="w-full px-4 md:px-8 lg:px-[72px] py-12" id="page-fees">
    <h2 class="font-canela text-3xl md:text-4xl text-[#000435] mb-8">{{ $heading }}</h2>
    @foreach($services as $service)
        @continue(!($service->is_visible ?? true))
        <div class="mb-10">
            <h3 class="text-2xl font-semibold text-[#000435] mb-4">{{ $service->name }}</h3>
            @foreach(($service->categories ?? []) as $category)
                @continue(!($category->is_visible ?? true))
                <h4 class="text-xl text-[#000435] mb-3">{{ $category->name }}</h4>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 mb-6">
                    @foreach(($category->packages ?? []) as $package)
                        @continue(!($package->showonwebsite ?? true) || !($package->status ?? true))
                        <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm">
                            <div class="text-lg font-semibold mb-2">{{ $package->package_name }}</div>
                            <div class="text-2xl font-bold text-[#000435] mb-3">
                                {{ $package->currency }} {{ number_format((float) $package->price, 2) }}
                            </div>
                            @if($package->short_description)
                                <p class="text-sm text-gray-600 mb-3">{{ $package->short_description }}</p>
                            @endif
                            @if(is_array($package->package_includes) && count($package->package_includes))
                                <ul class="list-disc list-inside text-sm text-gray-700 mb-4">
                                    @foreach($package->package_includes as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <a href="{{ url('/cart') }}" class="inline-block bg-primary_orange text-white px-4 py-2 text-sm font-semibold">Enroll</a>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</section>
@endif
