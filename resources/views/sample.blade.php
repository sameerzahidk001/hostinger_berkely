@extends('layouts.app')
@push('style')

@endpush

@section('content')

<section class="card-hidden px-6 min-[1200px]:px-[72px] md:px-12 mt-[52px]">
    <div class="flex flex-col lg:flex-row gap-16">
        <div class="flex-1 w-full">
            <div class="min-w-[300px] xl:min-w-[670px] overflow-hidden group w-full">
                <img src="{{ asset('frontend/images/pngs/Berkeley-Square-School-of-Arts-Certificate.png') }}"
                     alt="" class="xl:min-w-[670px] hover:scale-105 transition-all ease-in duration-200 h-[650px] w-full">
            </div>
        </div>
        <div class="flex-1 h-[650px] overflow-y-auto">
            <div class="flex flex-col gap-4">
                <div class="flex flex-row justify-between gap-4">
                    <div class="flex gap-3 flex-col">
                        <div class="flex gap-2 flex-col">
                            <a href="" class="text-[18px] font-bold">Professional Courses</a>
                            <p class="font-semibold">This is an age of professional certification. Almost every employer is looking for individuals having professional certification as part of their strive for excellence and compliance with best practices.</p>
                        </div>
                    </div>
                </div>
                <div class="w-full bg-primary h-[1px]"></div>
                <div class="flex flex-row justify-between gap-4">
                    <div class="flex gap-3 flex-col">
                        <div class="flex gap-2 flex-col">
                            <a href="" class="text-[18px] font-bold">Professional Courses</a>
                            <p class="font-semibold">This is an age of professional certification. Almost every employer is looking for individuals having professional certification as part of their strive for excellence and compliance with best practices.</p>
                        </div>
                    </div>
                </div>
                <div class="w-full bg-primary h-[1px]"></div>
                
            </div>
        </div>
    </div>
</section>

@endsection

@push('script')
@endpush