      
<aside class="hidden md:w-1/3 lg:w-1/4 py-4 md:block ">
    <div class="sticky top-20 flex flex-col  divide-y divide-primary_orange bg-[#000435] text-white text-[18px] font-semibold capitalize">

        <a href="{{ url('/general-policy') }}" class="p-4 transition-all ease-in duration-200 delay-100 hover:bg-primary_orange {{ Request::is('general-policy', 'general-policies') ? 'bg-primary_orange text-white' : '' }}">
        General Policies
        </a>
        <a href="{{ url('/privacy-policy') }}" class="p-4 transition-all ease-in duration-200 delay-100 hover:bg-primary_orange {{ Request::is('privacy-policy', 'privacy-policies') ? 'bg-primary_orange text-white' : '' }}">
        Privacy Policy
        </a>
        <a href="{{ url('/terms-and-conditions') }}" class="p-4 transition-all ease-in duration-200 delay-100 hover:bg-primary_orange {{ Request::is('terms-and-conditions', 'term-and-condition') ? 'bg-primary_orange text-white' : '' }}">
        Terms and Conditions
        </a>
        <a href="{{ route('complaints-and-misconducts') }}" class="p-4 transition-all ease-in duration-200 delay-100 hover:bg-primary_orange {{ Request::is('complaints-and-misconducts') ? 'bg-primary_orange text-white' : '' }}">
        Complaints & Misconduct
        </a>

    </div>
</aside>