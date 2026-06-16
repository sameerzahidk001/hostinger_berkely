<section class="card-hidden px-6 min-[1200px]:px-[72px] my-16 show">
        
    <div class="flex flex-col lg:flex-row items-center gap-x-24 gap-y-10">

        <div class="flex-1">
            <img src="{{ asset('frontend/images/jpg/sheikh.jpg') }}" alt="" class="w-full  object-cover">
        </div>

        <div class="relative flex-1 gap-2 flex flex-col">
            <h1 class="font-canela text-primary
            text-[32px] leading-[40px]
            md:text-[48px] md:leading-[58px]
            min-[960px]:text-[56px] min-[960px]:leading-[60px] tracking-wide">
            Learner Stories
            </h1>
            <div class="flex flex-col my-6">
                <p class="text-[18px] ">The Executive Training Centre isn't just about advancing careers; it's about cultivating leadership excellence and driving meaningful change. Our students confidently say that their experience here has been instrumental in propelling career to new heights."
                </p>

            </div>
            <a href="{{ route('learner-stories') }}" class="flex flex-1 items-center group  gap-2 ">
                <div class="flex  group-hover:bg-secondary justify-center items-center rounded-full bg-primary self-center min-h-10 min-w-10">
                    <img src="{{ asset('frontend/images/svgs/arrow-right.svg') }}" class="w-[28px] h-4" alt="">
                </div>
                <h2 class="font-bold text-[18px] max-w-[420px] text-primary">Read about our student's success stories as testimonials</h2>
            </a>
        </div>

    </div>
</section>