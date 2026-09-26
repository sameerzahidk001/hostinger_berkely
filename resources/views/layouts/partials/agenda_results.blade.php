<div class="space-y-4 overflow-x-auto">
    <table class="agenda-results-table w-full text-sm text-left text-gray-700 rounded-lg">
        <colgroup>
            <col class="col-school">
            <col class="col-category">
            <col class="col-course">
            <col class="col-delivery">
            <col class="col-location">
            <col class="col-dates">
            <col class="col-actions">
        </colgroup>
        <thead class="text-xs uppercase bg-gray-100 text-gray-600">
            <tr>
                <th scope="col" class="px-2 py-3 font-semibold col-school" data-sort="school">School<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span></th>
                <th scope="col" class="px-2 py-3 font-semibold col-category" data-sort="category">
                    Category<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span></th>
                <th scope="col" class="px-3 py-3 font-semibold col-course" data-sort="course">Course<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-2 py-3 font-semibold col-delivery" data-sort="deliveryType">
                    Delivery<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-2 py-3 font-semibold col-location" data-sort="location">
                    Location<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-3 py-3 font-semibold col-dates" data-sort="dates">
                    Dates<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-2 py-3 font-semibold text-right col-actions">Action</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse($results as $course_agenda)
                <tr class="{{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                    <td class="px-2 py-3 text-gray-800 text-xs">
                        @php
                            $schoolName = $course_agenda->course->categories
                                ->first()
                                ?->schools->first()?->name;
                        @endphp
                        {{ $schoolName ?? 'N/A' }}
                    </td>
                    <td class="px-2 py-3 text-gray-800 text-xs">
                        @php
                            $categoryNames = $course_agenda->course->categories
                                ->pluck('name')
                                ->implode('<br>');
                        @endphp
                        {!! $categoryNames !!}
                    </td>
                    <td class="px-3 py-3">
                        <a class="text-[#000435] hover:underline font-semibold" target="_blank"
                            href="{{ route('course.details', ['course' => $course_agenda->course->slug]) }}">
                            {{ $course_agenda->course->title }}
                        </a>
                        @if ($course_agenda->subject)
                            <br>
                            {{ $course_agenda->subject }}
                        @endif
                        @if ($course_agenda->description)
                            <br>
                            {!! $course_agenda->description !!}
                        @endif
                    </td>
                    <td class="px-2 py-3 text-gray-800 text-xs">
                        {{ $course_agenda->delivery_type ? $course_agenda->delivery_type : 'Virtual & Classroom' }}
                    </td>
                    <td class="px-2 py-3 text-xs">
                        {{ $course_agenda->country ? $course_agenda->country->name : 'International' }}
                        @if($course_agenda->city)
                            <br><span>{{ $course_agenda->city }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-gray-600 agenda-dates-cell">
                        <div><strong>Start:</strong> {{ \Carbon\Carbon::parse($course_agenda->from)->format('d M Y') }}</div>
                        <div><strong>End:</strong> {{ \Carbon\Carbon::parse($course_agenda->to)->format('d M Y') }}</div>
                    </td>
                    <td class="px-2 py-3 text-right">
                        <div class="flex flex-col gap-2 min-w-[100px]">
                            <a href="{{ route('course.details', ['course' => $course_agenda->course->slug]) }}#eight"
                                class="border px-3 py-1 w-full border-[#000435] bg-[#000435] text-white transition-all delay-300 duration-300 content-center rounded uppercase text-center text-xs font-semibold">
                                Enroll
                            </a>
                            <a href="{{ route('course.details', ['course' => $course_agenda->course->slug]) }}#apply"
                                class="border px-3 py-1 w-full border-[#000435] bg-white text-[#000435] transition-all delay-300 duration-300 content-center rounded uppercase text-center text-xs font-semibold">
                                Inquire
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">No data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
