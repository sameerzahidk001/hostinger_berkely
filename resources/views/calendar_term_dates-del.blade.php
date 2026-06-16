@extends('layouts.app')
@push('style')

@endpush

@section('content')
<section class="flex flex-col  mt-[0px] md:mt-[0px] lg:mt-0  relative min-h-[271px] bg-[#000435]">

    <div
        class="flex m-0 py-6 z-20  flex-1 flex-col justify-center gap-4 lg:px-[121px] px-8 hover:bg-[#000435] md:px-10">

        <h1 class="font-canela text-[44px] font-bold text-white">Calendar, Day Timings & Term Dates</h1>
        <!-- <span class="max-w-[740px] text-white">KEY STAGE 4: STUDY GCSE ONLINE COURSES -->
        </span>

    </div>
</section>

<section class="min-[1200px]:px-[48px]">
    <div class="flex gap-4 min-h-screen my-10">
        <!-- Sidebar -->
        <div class="w-1/3 h-screen bg-[#000435] self-start  sticky top-20 rounded-lg">

            <ol class="space-y-8 pt-4 list-decimal list-inside text-white 
            text-[18px]
            sm:text-[22px] font-semibold font-canela">
                <li
                    class="px-8 hover:bg-[hsl(235,100%,20%)] transition-all ease-linear duration-500 delay-75 hover:bg-opacity-90 py-2">
                    <a href="#section1" class="">
                        Calender, Date Timings
                    </a>
                </li>
                <li
                    class="px-8 hover:bg-[hsl(235,100%,20%)] transition-all ease-linear duration-500 delay-75 hover:bg-opacity-90 py-2">
                    <a href="#section2" class="">Safeguarding</a>
                </li>
                <li
                    class="px-8 hover:bg-[hsl(235,100%,20%)] transition-all ease-linear duration-500 delay-75 hover:bg-opacity-90 py-2">
                    <a href="#section3" class="">School
                        Policies</a>
                </li>
                <li
                    class="px-8 hover:bg-[hsl(235,100%,20%)] transition-all ease-linear duration-500 delay-75 hover:bg-opacity-90 py-2">
                    <a href="#section4" class="">Attendance
                        Policies</a>
                </li>
                <li
                    class="px-8 hover:bg-[hsl(235,100%,20%)] transition-all ease-linear duration-500 delay-75 hover:bg-opacity-90 py-2">
                    <a href="#section5" class="">Complaints &
                        Misconduct</a>
                </li>
            </ol>

        </div>

        <!-- Content Area -->
        <div class="w-5/6 px-3  overflow-y-auto">
            <section class="font-mono flex flex-col gap-4">
                <!-- <span class="text-[18px] font-semibold">School Time Table</span> -->
                <h1 id="section5" class="text-2xl font-semibold font-canela mb-2">School Time Table</h1>
                <div class="w-[80px] h-[2px] bg-yellow"></div>
                <div class="w-full mb-8 overflow-hidden rounded-lg shadow-lg">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr
                                    class="text-md font-semibold tracking-wide text-left text-gray-900 bg-gray-100 uppercase border-b border-gray-600">
                                    <th class="px-4 py-3">Monday - Friday</th>
                                    <th class="px-4 py-3">Time - Monday to Friday</th>

                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr class="text-gray-700">

                                    <td class="px-4 py-3 text-ms font-semibold border">School is open from</td>
                                    <td class="px-4 py-3 text-ms font-semibold border">8:15 am, with students encouraged to be on-site by 8.30 am</td>

                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Form Time</td>
                                    <td class="px-4 py-3 text-md font-semibold border">8:40 - 9:05</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Period 1</td>
                                    <td class="px-4 py-3 text-md font-semibold border">9:05 – 10:05</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Period 2</td>
                                    <td class="px-4 py-3 text-md font-semibold border">10:05 – 11:05</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Break</td>
                                    <td class="px-4 py-3 text-md font-semibold border">11:05 - 11:20</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Period 3 </td>
                                    <td class="px-4 py-3 text-md font-semibold border">11:20 - 12:20</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Lunchtime </td>
                                    <td class="px-4 py-3 text-md font-semibold border">12:20 – 1:05</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Period 4</td>
                                    <td class="px-4 py-3 text-md font-semibold border">1:05 – 2:05</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">Period 5</td>
                                    <td class="px-4 py-3 text-md font-semibold border">2:05 – 3:05</td>
                                </tr>
                                <tr class="text-gray-700">
                                    <td class="px-4 py-3 text-md font-semibold border">After School Activities</td>
                                    <td class="px-4 py-3 text-md font-semibold border">3:05- 5:00</td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </div>
</section>

@endsection

@push('script')
    <script>
        // Smooth scroll to anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();

                const offsetTop = document.querySelector(this.getAttribute('href')).offsetTop;
                window.scrollTo({
                    top: offsetTop - 80, // Adjusted scroll position
                    behavior: 'smooth'
                });
            });
        });
    </script>
@endpush