<div class="space-y-4 overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-700 rounded-lg">
        <thead class="text-xs uppercase bg-gray-100 text-gray-600">
            <tr>
                <th scope="col" class="px-4 py-3 font-semibold" data-sort="school">School<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span></th>
                <th scope="col" class="px-4 py-3 font-semibold" data-sort="category">
                    Category<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span></th>
                <th scope="col" class="px-4 py-3 font-semibold" data-sort="course">Course<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-4 py-3 font-semibold" data-sort="deliveryType">
                    Delivery Type<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-4 py-3 font-semibold" data-sort="location">
                    Location<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-4 py-3 font-semibold text-right" data-sort="dates">
                    Dates<span
                        class="inline-block ml-1 sort-icon float-right cursor-pointer">⇅</span>
                </th>
                <th scope="col" class="px-4 py-3 font-semibold text-right">Inquiry</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @foreach ($results as $course_agenda)
                <tr class="{{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                    <td class="px-4 py-3 text-gray-800">
                        @php
                            $schoolName = $course_agenda->course->categories
                                ->first()
                                ?->schools->first()?->name;
                        @endphp
                        {{ $schoolName ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-gray-800">
                        @php
                            $categoryNames = $course_agenda->course->categories
                                ->pluck('name')
                                ->implode('<br>');
                        @endphp
                        {!! $categoryNames !!}
                    </td>
                    <td class="px-4 py-3">
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
                    <td class="px-4 py-3 text-gray-800">
                        {{ $course_agenda->delivery_type ? $course_agenda->delivery_type : 'Virtual & Classroom' }}
                    </td>
                    <td class="px-4 py-3">
                        {{ $course_agenda->country ? $course_agenda->country->name : 'International' }}<br>
                        <span class="">{{ $course_agenda->city }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-600">
                        <strong>Start Date:</strong>
                        {{ \Carbon\Carbon::parse($course_agenda->from)->format('d M Y') }} <br>
                        <strong>End Date:</strong>
                        {{ \Carbon\Carbon::parse($course_agenda->to)->format('d M Y') }}
                    </td>
                    @if ($course_agenda->inquiry)
                        <td>
                            <button type="button"
                                class="border px-4 py-1 w-full border-[#000435] bg-[#000435] text-white transition-all delay-300 duration-300 content-center rounded uppercase"
                                data-form="{!! htmlentities($course_agenda->inquiry, ENT_QUOTES) !!}">
                                Enroll
                            </button>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>