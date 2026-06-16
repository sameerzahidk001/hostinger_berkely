@foreach ($results as $instructor)
    <div class="instructor-card">
        <div class="instructor-left">
            <div class="instructor-image">
                <img src="{{ asset($instructor['image'] ?? '/images/profiles/user.png') }}" alt="{{ $instructor['name'] }}">
            </div>

            <div class="instructor-info">
                <h3>
                    {{ $instructor['name'] }}
                    @if ($instructor->linkedin)
                        <a href="{{ $instructor->linkedin }}" target="_blank" class="linkedin-link"
                            title="View LinkedIn Profile">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                <path
                                    d="M512 96L127.9 96C110.3 96 96 110.5 96 128.3L96 511.7C96 529.5 110.3 544 127.9 544L512 544C529.6 544 544 529.5 544 511.7L544 128.3C544 110.5 529.6 96 512 96zM231.4 480L165 480L165 266.2L231.5 266.2L231.5 480L231.4 480zM198.2 160C219.5 160 236.7 177.2 236.7 198.5C236.7 219.8 219.5 237 198.2 237C176.9 237 159.7 219.8 159.7 198.5C159.7 177.2 176.9 160 198.2 160zM480.3 480L413.9 480L413.9 376C413.9 351.2 413.4 319.3 379.4 319.3C344.8 319.3 339.5 346.3 339.5 374.2L339.5 480L273.1 480L273.1 266.2L336.8 266.2L336.8 295.4L337.7 295.4C346.6 278.6 368.3 260.9 400.6 260.9C467.8 260.9 480.3 305.2 480.3 362.8L480.3 480z" />
                            </svg>
                        </a>
                    @endif
                    <span
                        style="font-size: 14px; font-weight: 300;">{{ is_array(json_decode($instructor['education'], true)) ? implode(', ', json_decode($instructor['education'], true)) : $instructor['education'] }}</span>
                </h3>
                @if (!empty($instructor['city']) || !empty($instructor['countryarray']->name))
                    <p class="instructor-location">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="location-icon">
                            <path
                                d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z" />
                        </svg>
                        <span>{{ $instructor['city'] ?? '' }}{{ !empty($instructor['city']) && !empty($instructor['countryarray']->name) ? ', ' : '' }}{{ $instructor['countryarray']->name ?? '' }}</span>
                    </p>
                @endif


                <div><strong>About Trainer:</strong>
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