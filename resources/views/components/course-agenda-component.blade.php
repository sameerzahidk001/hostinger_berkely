<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    #agenda-search-form .select2-container { width: 100% !important; }
    #agenda-search-form .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
        background-color: #fff;
    }
    #agenda-search-form .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
        color: #111827;
    }
    #agenda-search-form .select2-container .select2-selection--single .select2-selection__placeholder {
        color: #9ca3af;
    }
    #agenda-search-form .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    #agenda-search-form .select2-dropdown {
        border-color: #d1d5db;
    }
    #agenda-search-form .select2-search__field {
        outline: none !important;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<section
    class="min-h-[174px] lg:px-[120px] px-4 md:px-8 w-full my-16 {{ $background != 'transparent' ? 'pb-16 pt-16' : '' }}"
    style="background-color: {{ $background }};">

    <div class="w-full max-w-[1100px] mx-auto">
    <!-- Filter Form -->
    <form id="agenda-search-form">
        <input type="hidden" name="sort_by" id="sort_by" value="from">
        <input type="hidden" name="sort_dir" id="sort_dir" value="asc">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 my-2">
            <div>
                <label class="" for="agenda_school">Schools</label>
                <select id="agenda_school" class="agenda-typefind w-full border border-gray-300 rounded px-3 py-2 text-sm" name="school" data-placeholder="All Schools">
                    <option value="">All Schools</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="" for="agenda_category">Categories</label>
                <select id="agenda_category" class="agenda-typefind w-full border border-gray-300 rounded px-3 py-2 text-sm" name="category" data-placeholder="All Categories">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="" for="agenda_course">Course</label>
                <select id="agenda_course" class="agenda-typefind w-full border border-gray-300 rounded px-3 py-2 text-sm" name="course" data-placeholder="All Courses">
                    <option value="">All Courses</option>
                    @php $courses = DB::table('courses')->get(); @endphp
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="" for="agenda_subject">Part / Subject</label>
                <select id="agenda_subject" class="agenda-typefind w-full border border-gray-300 rounded px-3 py-2 text-sm" name="subject" data-placeholder="All Subject">
                    <option value="">All Subject</option>
                    @foreach ($agenda_subjects as $agenda_subject)
                        <option value="{{ $agenda_subject }}">{{ $agenda_subject }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 my-2">
            <div>
                <label class="" for="agenda_class_type">Training Methodology</label>
                <select id="agenda_class_type" class="agenda-typefind w-full border border-gray-300 rounded px-3 py-2 text-sm" name="class_type" data-placeholder="Virtual & Classroom">
                    <option value="">Virtual & Classroom</option>
                    <option value="Virtual">Virtual</option>
                    <option value="In Person">In Person</option>
                </select>
            </div>

            <div>
                <label class="" for="agenda_country">Location</label>
                <select id="agenda_country" class="agenda-typefind w-full border border-gray-300 rounded px-3 py-2 text-sm" name="country" data-placeholder="All Countries / International">
                    <option value="0">All Countries / International</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="" for="agenda_city">City</label>
                <select id="agenda_city" class="agenda-typefind w-full border border-gray-300 rounded px-3 py-2 text-sm" name="city" data-placeholder="All Cities">
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
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex flex-col gap-2 min-w-[120px]">
                                                <a href="{{ route('course.details', ['course' => $course_agenda->course->slug]) }}#eight"
                                                    class="border px-4 py-1 w-full border-[#000435] bg-[#000435] text-white transition-all delay-300 duration-300 content-center rounded uppercase text-center text-xs font-semibold">
                                                    Enroll
                                                </a>
                                                <a href="{{ route('course.details', ['course' => $course_agenda->course->slug]) }}#apply"
                                                    class="border px-4 py-1 w-full border-[#000435] bg-white text-[#000435] transition-all delay-300 duration-300 content-center rounded uppercase text-center text-xs font-semibold">
                                                    Inquire
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        function containsMatcher(params, data) {
            if ($.trim(params.term || '') === '') {
                return data;
            }
            if (typeof data.text === 'undefined') {
                return null;
            }
            var haystack = String(data.text).toLowerCase();
            var tokens = String(params.term).toLowerCase().split(/\s+/).filter(Boolean);
            var matched = tokens.every(function (token) {
                return haystack.indexOf(token) !== -1;
            });
            return matched ? data : null;
        }

        function initAgendaTypeFind() {
            $('#agenda-search-form select.agenda-typefind').each(function () {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $el.data('placeholder') || 'Type to find…',
                    minimumResultsForSearch: 0,
                    dropdownParent: $(document.body),
                    matcher: containsMatcher
                });
            });
        }

        initAgendaTypeFind();

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
