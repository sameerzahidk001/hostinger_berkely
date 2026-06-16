<style>
    .filter-tabs {
        color:
            {{ $filterColor }}
        ;
        border: 2px solid
            {{ $filterBorderColor }}
        ;
        background-color:
            {{ $filterBackground }}
        ;
    }

    .filter-tabs:hover,
    .filter-tabs.active {
        color:
            {{ $activeFilterColor }}
        ;
        border: 2px solid
            {{ $activeFilterBorderColor }}
        ;
        background-color:
            {{ $activeFilterBackground }}
        ;
    }

    .tab-btn {
        color:
            {{ $tabColor }}
        ;
        border: 2px solid
            {{ $tabBorderColor }}
        ;
        background-color:
            {{ $tabBackground }}
        ;
    }

    .tab-btn:hover,
    .tab-btn.active {
        color:
            {{ $activeTabColor }}
        ;
        border: 2px solid
            {{ $activeTabBorderColor }}
        ;
        background-color:
            {{ $activeTabBackground }}
        ;
    }

    .custom-tab-content {
        color:
            {{ $contentColor }}
        ;
        border: 2px solid
            {{ $contentBorderColor }}
        ;
        background-color:
            {{ $contentBackground }}
        ;
    }
</style>

<section class="flex flex-col text-black card-hidden my-16 {{ $background != 'transparent' ? 'pb-16 pt-16' : '' }}"
    style="background-color: {{ $background }};">
    <div class="flex flex-col items-center px-2 sm:px-4 md:px-10 lg:px-32 gap-8">
        <div class="b-custom">
            <h3 class="text-[24px] font-semibold pb-2" style="color: {{ $color }}">{{ $title }}</h3>
            <div class="text-[18px] pb-2" style="color: {{ $color }}">{!! $description !!}</div>

            <!-- School Filters -->
            @if(isset($schools) && count($schools) > 0)
                <div class="gap-2 grid grid-cols-1 md:grid-cols-3 w-full text-[16px] pb-6 cursor-pointer leading-normal">
                    @foreach($schools as $index => $school)
                        <div class="school-item p-3 self-start flex flex-col justify-center items-center min-h-[72px] filter-tabs {{ $index == 0 ? 'active' : '' }}"
                            data-school-id="{{ $school->id }}">
                            <span class="school-info text-center">{{ $school->name }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Categories container -->
                <div id="school-categories-container" class="w-full text-center text-gray-500">
                    <p>Loading categories...</p>
                </div>
            @else
                <p class="text-center text-gray-500">No schools available.</p>
            @endif
        </div>
    </div>
</section>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        function fetchCategories(schoolId, urlTarget) {
            $('#school-categories-container').html('<p>Loading categories...</p>');

            $.ajax({
                url: '{{ route("school-categories-ajax") }}',
                method: 'GET',
                data: {
                    school_id: schoolId,
                    urlTarget: urlTarget,
                },
                dataType: 'json',
                success: function (response) {
                    if (response.html) {
                        $('#school-categories-container').html(response.html);
                        initializeTabs();
                    } else {
                        $('#school-categories-container').html('<p class="text-gray-500 text-center">No categories found for this school.</p>');
                    }
                },
                error: function (xhr) {
                    console.error('Error:', xhr.responseJSON?.message || 'Something went wrong.');
                    $('#school-categories-container').html('<p class="text-red-500 text-center">Failed to load categories.</p>');
                }
            });
        }

        // Load first school categories automatically
        var firstSchool = $('.school-item').first();
        if (firstSchool.length) {
            fetchCategories(firstSchool.data('school-id'), '{{ $urlTarget }}');
        }

        // School click handler
        $(document).on('click', '.school-item', function (e) {
            e.preventDefault();
            $('.school-item').removeClass('active');
            $(this).addClass('active');
            fetchCategories($(this).data('school-id'), '{{ $urlTarget }}');
        });

        // Initialize category tabs
        function initializeTabs() {
            const tabTitles = $('.custom-tab-title');
            const tabPanes = $('.custom-tab-pane');

            tabTitles.off('click').on('click', function () {
                const targetId = $(this).data('target');
                tabPanes.addClass('hidden').removeClass('block');
                tabTitles.removeClass('active');
                $('#' + targetId).removeClass('hidden').addClass('block');
                $(this).addClass('active');
            });

            if (tabTitles.length > 0) {
                tabTitles.first().click();
            }
        }
    });
</script>