<style>
    .instructor-section {
        background-color: {{ section_bg_color($background) }};
    }

    .instructor-section h2 {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        text-align: center;
        font-size: 36px;
        margin-bottom: 20px;
        color: {{ $color ?? '#222' }};
    }

    .instructor-section .description {
        margin: 0 auto 40px;
        color: {{ $color ?? '#555' }};
        line-height: 1.6;
    }

    .instructor-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
    }

    .instructor-card {
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        flex-direction: column;
        gap: 12px;
        background-color: {{ card_bg_color($cardBackground, false, '#ffffff') }};
        color: {{ section_color($cardColor, '#222222') }};
        border: 1px solid #e0e0e0;
        border-radius: 16px;
        padding: 20px 24px;
        transition: all 0.3s ease;
        min-width: 0;
        height: auto;
    }

    .instructor-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .instructor-left {
        display: flex;
        align-items: flex-start;
        flex: 1;
        min-width: 250px;
        width: 100%;
        gap: 16px;
    }

    .instructor-image {
        flex-shrink: 0;
        width: 148px;
        height: 148px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #ddd;
        background: #f3f4f6;
    }

    .instructor-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .instructor-info {
        margin-left: 0;
        flex: 1;
        min-width: 0;
    }

    .instructor-info h3 {
        font-size: 20px;
        font-weight: 600;
        color: #222;

        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .instructor-info p {
        margin-bottom: 8px;
        font-size: 14px;
        color: #555;
    }

    .instructor-info strong {
        color: #000;
        font-weight: 600;
    }

    /* LinkedIn link style */
    .linkedin-link svg {
        width: 28px;
        height: 28px;
        fill: #0A66C2;
        transition: fill 0.3s ease;
    }

    .linkedin-link:hover svg {
        fill: #084d99;
    }

    .instructor-action {
        margin-top: 4px;
    }

    .instructor-btn {
        display: inline-block;
        background: linear-gradient(135deg, #bc8a13, #876213);
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .instructor-btn:hover {
        background: linear-gradient(135deg, #bc8a13, #876213);
        box-shadow: 0 4px 12px rgba(188, 138, 19, 0.5);
        transform: translateY(-2px);
    }

    .instructor-location {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: #555;
        margin-top: 4px;
        line-height: 1;
    }

    .location-icon {
        width: 15px;
        height: 15px;
    }

    .instructor-title {
        width: 100%;
        overflow: hidden;
    }

    .name-row {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: nowrap !important;
        gap: 8px;
        overflow: hidden;
    }

    .name-row h3 {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .name-row a {
        flex-shrink: 0;
    }

    /* Responsive Design */
    @media (max-width: 900px) {
        .instructor-list {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .instructor-card {
            flex-direction: column;
            text-align: center;
            padding: 20px;
            align-items: center;
        }

        .instructor-left {
            flex-direction: column;
            align-items: center;
        }

        .instructor-image {
            width: 160px;
            height: 160px;
            border-radius: 8px;
        }

        .instructor-info {
            margin-left: 0;
            margin-top: 12px;
            text-align: center;
        }

        .name-row {
            justify-content: center;
        }

        .instructor-location {
            justify-content: center;
        }

        .instructor-action {
            text-align: center;
            margin-top: 8px;
            width: 100%;
        }

        .instructor-btn {
            width: auto;
        }
    }

    @media (max-width: 768px) {
        .name-row {
            display: flex;
            flex-direction: column;
            /* ✅ stack items vertically */
            align-items: center;
            /* center name + icon */
            text-align: center;
            gap: 4px;
        }

        .name-row h3,
        .name-row a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .name-row p {
            flex: 1 1 100%;
            margin: 4px 0 0;
            text-align: center;
            white-space: normal;
            word-break: break-word;
        }
    }
</style>

<section class="min-h-[174px] lg:px-[120px] px-4 md:px-8 w-full my-16 instructor-section">
    @if (isset($title))
        <h2>{{ $title }}</h2>
    @endif

    @if (isset($description))
        <div class="description cms-html">{!! render_cms_html($description) !!}</div>
    @endif
    <h3 class="text-lg font-semibold text-[#000435] mb-3">Filter by:</h3>
    <form id="instructors-search-form">
        @php
            $facultyCourses = DB::table('courses')->orderBy('title')->get(['id', 'title']);
            $specialisationOptions = collect($instructors ?? [])
                ->flatMap(function ($instructor) {
                    return method_exists($instructor, 'expertiseList') ? $instructor->expertiseList() : [];
                })
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique(fn ($v) => mb_strtolower($v))
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            $educationOptions = collect($instructors ?? [])
                ->flatMap(function ($instructor) {
                    return method_exists($instructor, 'educationList') ? $instructor->educationList() : [];
                })
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique(fn ($v) => mb_strtolower($v))
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            $professionalQualOptions = collect($instructors ?? [])
                ->flatMap(function ($instructor) {
                    return method_exists($instructor, 'professionalQualificationsList')
                        ? $instructor->professionalQualificationsList()
                        : [];
                })
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique(fn ($v) => mb_strtolower($v))
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        @endphp
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-semibold mb-1" for="name">Name</label>
                <input type="text" name="name" id="name" placeholder="Search by name…"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-[#f8961f] focus:ring-[#f8961f]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="city">City</label>
                <input type="text" name="city" id="city" placeholder="Search by city…"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-[#f8961f] focus:ring-[#f8961f]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="country">Country</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm js-faculty-select" name="country" id="country" data-placeholder="All Countries">
                    <option value="">All Countries</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->iso_code }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="education">Academic Qualifications</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm js-faculty-typefind" name="education" id="education" data-placeholder="Type to find academic qualifications…">
                    <option value=""></option>
                    @foreach ($educationOptions as $edu)
                        <option value="{{ $edu }}">{{ $edu }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="professional_qualification">Professional Qualifications</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm js-faculty-typefind" name="professional_qualification" id="professional_qualification" data-placeholder="Type to find professional qualifications…">
                    <option value=""></option>
                    @foreach ($professionalQualOptions as $pq)
                        <option value="{{ $pq }}">{{ $pq }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="specialisation">Professional &amp; Academic Specialisations</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm js-faculty-typefind" name="specialisation" id="specialisation" data-placeholder="Type to find specialisation…">
                    <option value=""></option>
                    @foreach ($specialisationOptions as $spec)
                        <option value="{{ $spec }}">{{ $spec }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="course">Course</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm js-faculty-typefind" name="course" id="course" data-placeholder="Type to find course…">
                    <option value=""></option>
                    @foreach ($facultyCourses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="avail_day">Available day</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm js-faculty-typefind" name="avail_day" id="avail_day" data-placeholder="Any day">
                    <option value=""></option>
                    @foreach (\App\Models\User::teachingAvailabilityDays() as $dayCode => $dayLabel)
                        <option value="{{ $dayCode }}">{{ $dayLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="avail_period">Available period</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm js-faculty-typefind" name="avail_period" id="avail_period" data-placeholder="Any period">
                    <option value=""></option>
                    @foreach (\App\Models\User::teachingAvailabilitySlots() as $slotCode => $slotLabel)
                        <option value="{{ $slotCode }}">{{ $slotLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" for="distance_km">Within distance</label>
                <select class="w-full border border-gray-300 rounded px-3 py-2 text-sm" name="distance_km" id="distance_km">
                    <option value="" selected>Any distance</option>
                    <option value="5">5 km</option>
                    <option value="10">10 km</option>
                    <option value="25">25 km</option>
                    <option value="50">50 km</option>
                    <option value="100">100 km</option>
                </select>
            </div>
        </div>
        <div class="flex flex-wrap gap-3 items-end mb-4">
            <button type="button" id="use-my-location"
                class="border px-3 py-2 border-[#000435] text-[#000435] rounded text-sm">
                Use my location
            </button>
            <p id="nearby-status" class="text-sm text-gray-600 mb-0">Optional: click “Use my location” to find instructors near you by distance.</p>
        </div>
        <input type="hidden" name="lat" id="search_lat" value="">
        <input type="hidden" name="lng" id="search_lng" value="">
        <div class="flex gap-2 items-end mb-8 max-w-md">
            <button
                class="border px-4 py-2 w-full border-[#000435] bg-[#000435] transition-all delay-300 duration-300 content-center rounded uppercase text-white"
                type="submit">Search</button>
            <button type="button" id="reset"
                class="hidden group-hover:bg-primary bg-secondary px-4 py-2 w-full transition-all delay-300 duration-300 content-center rounded uppercase text-white">
                Reset
            </button>
        </div>
    </form>

    <div class="instructor-list" id="instructors-container">
        @foreach ($instructors as $instructor)
            <div class="instructor-card">
                <div class="instructor-left">
                    <div class="instructor-image">
                        <img src="{{ asset($instructor['image'] ?? '/images/profiles/user.png') }}"
                            alt="{{ $instructor['name'] }}">
                    </div>
                    <div class="instructor-info">
                        <div class="instructor-title">
                            <div class="name-row">
                                <h3>{{ $instructor['name'] }}</h3>
                                @if ($instructor->linkedin)
                                    <a href="{{ $instructor->linkedin }}" target="_blank" class="linkedin-link"
                                        title="View LinkedIn Profile">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                            <path
                                                d="M512 96L127.9 96C110.3 96 96 110.5 96 128.3L96 511.7C96 529.5 110.3 544 127.9 544L512 544C529.6 544 544 529.5 544 511.7L544 128.3C544 110.5 529.6 96 512 96zM231.4 480L165 480L165 266.2L231.5 266.2L231.5 480L231.4 480zM198.2 160C219.5 160 236.7 177.2 236.7 198.5C236.7 219.8 219.5 237 198.2 237C176.9 237 159.7 219.8 159.7 198.5C159.7 177.2 176.9 160 198.2 160zM480.3 480L413.9 480L413.9 376C413.9 351.2 413.4 319.3 379.4 319.3C344.8 319.3 339.5 346.3 339.5 374.2L339.5 480L273.1 480L273.1 266.2L336.8 266.2L336.8 295.4L337.7 295.4C346.6 278.6 368.3 260.9 400.6 260.9C467.8 260.9 480.3 305.2 480.3 362.8L480.3 480z" />
                                        </svg>
                                    </a>
                                @endif


                            </div>
                        </div>

                        @if (!empty($instructor['city']) || !empty($instructor['countryarray']->name))
                            <p class="instructor-location">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="location-icon">
                                    <path
                                        d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z" />
                                </svg>
                                <span>{{ $instructor['city'] ?? '' }}{{ !empty($instructor['city']) && !empty($instructor['countryarray']->name) ? ', ' : '' }}{{ $instructor['countryarray']->name ?? '' }}</span>
                            </p>
                        @endif


                        <div><strong>About Instructor:</strong>
                            {!! $instructor->short_description ?? '<p>This instructor has not added a biography yet.</p>' !!}
                        </div>
                    </div>
                </div>

                <!-- View Details Button -->
                <div class="instructor-action">
                    <a href="{{ url('/instructor/' . $instructor['id']) }}" target="_blank" rel="noopener noreferrer"
                        class="instructor-btn">
                        View Profile
                    </a>
                </div>

            </div>
        @endforeach
    </div>
</section>

@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    #instructors-search-form .select2-container { width: 100% !important; }
    #instructors-search-form .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
    }
    #instructors-search-form .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
        color: #111827;
    }
    #instructors-search-form .select2-container .select2-selection--single .select2-selection__placeholder {
        color: #9ca3af;
    }
    #instructors-search-form .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    #instructors-search-form .select2-dropdown {
        border-color: #d1d5db;
    }
    #instructors-search-form .select2-search__field {
        outline: none !important;
    }
</style>
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function ($) {
    function initFacultyTypeFind() {
        $('#specialisation, #course, #avail_day, #avail_period, #education, #professional_qualification').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                width: '100%',
                allowClear: true,
                placeholder: $el.data('placeholder') || 'Type to find…',
                minimumResultsForSearch: 0,
                dropdownParent: $(document.body)
            });
        });

        $('#country').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                width: '100%',
                allowClear: true,
                placeholder: $el.data('placeholder') || 'All Countries',
                minimumResultsForSearch: 0,
                dropdownParent: $(document.body)
            });
        });
    }

    $(function () {
        initFacultyTypeFind();

        $('#use-my-location').on('click', function () {
            var $btn = $(this);
            var $status = $('#nearby-status');
            if (!navigator.geolocation) {
                $status.text('Geolocation is not supported in this browser.');
                return;
            }
            $btn.prop('disabled', true).text('Locating…');
            navigator.geolocation.getCurrentPosition(function (pos) {
                $('#search_lat').val(pos.coords.latitude.toFixed(7));
                $('#search_lng').val(pos.coords.longitude.toFixed(7));
                $status.text('Your location is set. Search to find nearby trainers.');
                $btn.prop('disabled', false).text('Use my location');
                $('#instructors-search-form').trigger('submit');
            }, function (err) {
                $status.text('Could not get your location: ' + (err.message || 'permission denied'));
                $btn.prop('disabled', false).text('Use my location');
            }, { enableHighAccuracy: true, timeout: 15000 });
        });

        $('#instructors-search-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalBtnText = $submitBtn.html();
            var distance = $('#distance_km').val();
            var lat = $('#search_lat').val();
            var lng = $('#search_lng').val();

            if (distance && (!lat || !lng)) {
                $('#nearby-status').text('Click “Use my location” first to search within a distance.');
                return;
            }

            $submitBtn.prop('disabled', true).html(
                'Searching... <span class="animate-spin inline-block ml-1">&#9696;</span>');

            $.ajax({
                url: @json(route('faculty_search')),
                method: 'GET',
                data: $form.serialize(),
                success: function (response) {
                    if (response.html) {
                        $('#instructors-container').html(response.html);
                    } else {
                        $('#instructors-container').html(
                            '<div class="col-span-full text-center text-gray-500 py-10">No data found.</div>'
                        );
                    }
                    $('#reset').removeClass('hidden');
                },
                error: function (xhr) {
                    console.error('Error:', (xhr.responseJSON && xhr.responseJSON.message) ||
                        'Something went wrong.');
                    $('#instructors-container').html(
                        '<p class="col-span-full text-center text-gray-500 py-10">Failed to load faculty.</p>'
                    );
                },
                complete: function () {
                    $submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        $('#reset').on('click', function () {
            location.reload();
        });
    });
})(jQuery);
</script>
@endpush