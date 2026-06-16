<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<section
    class="min-h-[174px] lg:px-[120px] px-4 md:px-8 w-full my-16 {{ $background != 'transparent' ? 'pb-16 pt-16' : '' }}"
    style="background-color: {{ $background }};">

    <!-- Filter Form -->
    <form id="agenda-search-form">
        <input type="hidden" name="sort_by" id="sort_by" value="from">
        <input type="hidden" name="sort_dir" id="sort_dir" value="asc">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 my-2">
            <div>
                <label class="" for="school">Shools</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="school">
                    <option value="">All Schools</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="" for="category">Categories</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="category">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="" for="course">Course</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="course">
                    <option value="">All Courses</option>
                    @php $courses = DB::table('courses')->get(); @endphp
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="" for="subject">Part / Subject</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="subject">
                    <option value="">All Subject</option>
                    @foreach ($agenda_subjects as $agenda_subject)
                        <option value="{{ $agenda_subject }}">{{ $agenda_subject }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 my-2">
            <div>
                <label class="" for="class_type">Training Methodology</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="class_type">
                    <option value="">Virtual & Classroom</option>
                    <option value="Virtual">Virtual</option>
                    <option value="In Person">In Person</option>
                </select>
            </div>

            <div>
                <label class="" for="country">Location</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="country">
                    <option value="0">All Countries / International</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="" for="city">City</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="city">
                    <option value="">All Cities</option>
                    @foreach ($agenda_cities as $agenda_city)
                        <option value="{{ $agenda_city }}">{{ $agenda_city }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="" for="dateRange">Date Range</label>
                <input type="text" id="dateRange" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                    name="date_range" placeholder="Select Dates">
            </div>
            <div class="flex gap-2 items-end">

                <input type="text" name="keyword" id="keyword" placeholder="Search by keyword..."
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-[#f8961f] focus:ring-[#f8961f]">

            </div>


            <div class="flex gap-2">
                <button
                    class="border px-4 py-1 w-full border-[#000435] bg-[#000435] transition-all delay-300 duration-300 content-center rounded uppercase text-white"
                    type="submit">Search</button>
                <button type="button" id="reset"
                    class="hidden group-hover:bg-primary bg-secondary px-4 py-1 w-full transition-all delay-300 duration-300 content-center rounded uppercase text-white">
                    Reset
                </button>
            </div>

        </div>
    </form>

    <!-- Results Section -->
    <div class="flex flex-col items-center gap-8 mt-8">
        <div class="b-custom w-full">
            <h3 class="text-[24px] font-semibold pb-2" style="color: {{ $color }}">{{ $title }}</h3>
            <div class="text-[18px] pb-2" style="color: {{ $color }}">{!! $description !!}</div>
            <div class="position-relative">
                <div class="grid gap-6" id="agenda-container">
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
                                @foreach ($course_agendas as $course_agenda)
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
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Inquiry Modal -->
<div id="inquiry-modal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex justify-center items-center">
    <div class="bg-white rounded-lg w-full max-w-md mx-auto relative">
        <button id="close-inquiry" class="absolute top-0 right-2 text-gray-500 hover:text-red-500 text-2xl text-white"
            style="margin-top: -25px;">&times;</button>
        <div id="inquiry-form-container"></div>
    </div>
</div>

<!-- Ensure jQuery is included -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        flatpickr("#dateRange", {
            mode: "range",
            dateFormat: "Y-m-d",
        });

        $('#agenda-search-form').on('submit', function(e) {
            e.preventDefault();

            let $form = $(this);
            let $submitBtn = $form.find('button[type="submit"]');
            let originalBtnText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html(
                'Searching... <span class="animate-spin inline-block ml-1">&#9696;</span>');

            let formData = $form.serialize();

            $.ajax({
                url: '{{ route('agenda_search') }}',
                method: 'GET',
                data: formData,
                success: function(response) {
                    if (response.html) {
                        $('#agenda-container').html(response.html);
                        bindSortEvents();
                        bindInquiryModal();
                    } else {
                        $('#agenda-container').html(
                            '<div class="col-span-full text-center text-gray-500 py-10">No data found.</div>'
                        );
                    }
                    $('#reset').removeClass('hidden');
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseJSON?.message ||
                        'Something went wrong.');
                    $('#agenda-container').html(
                        '<p class="col-span-full text-center text-gray-500 py-10">Failed to load agenda.</p>'
                    );
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        $('#reset').on('click', function() {
            location.reload();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        bindSortEvents();
        bindInquiryModal();
    });

    $('#close-inquiry, #inquiry-modal').on('click', function(e) {
        if (e.target.id === 'close-inquiry' || e.target.id === 'inquiry-modal') {
            $('#inquiry-modal').addClass('hidden').removeClass('flex');
            $('#inquiry-form-container').html('');
        }
    });

    function bindSortEvents() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', function() {
                const column = th.getAttribute('data-sort');
                const currentSortBy = $('#sort_by').val();
                const currentSortDir = $('#sort_dir').val();
                let sortDirection = currentSortBy === column && currentSortDir === 'asc' ? 'desc' :
                    'asc';

                document.querySelectorAll('th[data-sort] .sort-icon').forEach(icon => {
                    icon.innerHTML = '⇅';
                });

                const currentIcon = th.querySelector('.sort-icon');
                if (currentIcon) {
                    currentIcon.innerHTML = '<span class="animate-spin inline-block">&#9696;</span>';
                }

                $('#sort_by').val(column);
                $('#sort_dir').val(sortDirection);

                let formData = $('#agenda-search-form').serialize();

                $.ajax({
                    url: '{{ route('agenda_search') }}',
                    method: 'GET',
                    data: formData,
                    success: function(response) {
                        if (response.html) {
                            $('#agenda-container').html(response.html);
                            bindSortEvents();
                            bindInquiryModal();

                            if (currentIcon) {
                                currentIcon.innerHTML = sortDirection === 'asc' ? '↑' : '↓';
                            }
                        } else {
                            $('#agenda-container').html(
                                '<div class="col-span-full text-center text-gray-500 py-10">No data found.</div>'
                            );
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr.responseJSON?.message ||
                            'Something went wrong.');
                        $('#agenda-container').html(
                            '<p class="col-span-full text-center text-gray-500 py-10">Failed to load agenda.</p>'
                        );
                    }
                });
            });
        });
    }

    function bindInquiryModal() {
        $('.open-inquiry-btn, button[data-form]').on('click', function() {
            const formHTML = $(this).data('form');
            $('#inquiry-form-container').html(formHTML);
            $('#inquiry-modal').removeClass('hidden').addClass('flex');
        });
    }
</script>