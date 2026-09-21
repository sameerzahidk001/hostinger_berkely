@extends('layouts.app')

@section('content')
    <style>
        .instructor-profile {
            width: 100%;
            min-height: 100vh;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
            margin-bottom: 40px;
            padding-top: 28px;
        }

        /* ======= HERO SECTION ======= */
        .instructor-hero {
            width: 100%;
            background: linear-gradient(135deg, #00435a, #bc1701, #e6a60b);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
            color: #fff;
            padding: 80px 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-top: 12px;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .hero-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 50px;
            flex-wrap: wrap;
            max-width: 1200px;
            width: 100%;
        }

        .profile-img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid #fff;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            flex-shrink: 0;
        }

        .profile-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-info {
            flex: 1;
            min-width: 300px;
        }

        .hero-kicker {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #fff;
            margin: 0 0 8px;
            opacity: 0.95;
        }

        .hero-info h3 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 14px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hero-meta {
            font-size: 16px;
            margin: 6px 0;
            opacity: 0.95;
            line-height: 1.5;
        }

        .hero-meta strong {
            font-weight: 700;
        }

        .hero-professional {
            font-size: 16px;
            line-height: 1.55;
            opacity: 0.96;
            margin-top: 8px;
            max-width: 720px;
        }

        .hero-professional p {
            margin: 0 0 8px;
            color: #fff;
        }

        .hero-professional p:last-child {
            margin-bottom: 0;
        }

        /* ======= LinkedIn link professional style ======= */
        .linkedin-link svg {
            width: 32px;
            height: 32px;
            fill: #0A66C2;
            transition: fill 0.3s ease;
        }

        .linkedin-link:hover svg {
            fill: #084d99;
        }

        /* ======= ABOUT SECTION ======= */
        .instructor-about {
            background-color: #ffffff;
            color: #222;
            width: 100%;
            max-width: 1200px;
            margin-top: -40px;
            border-radius: 20px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.1);
            padding: 50px 60px;
            position: relative;
            z-index: 10;
        }

        .profile-section {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 20px 24px;
            border-left: 6px solid #e6a60b;
            margin-bottom: 20px;
        }

        .profile-section:last-child {
            margin-bottom: 0;
        }

        .profile-section h3 {
            color: #bc1701;
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-section .section-body {
            font-size: 16px;
            color: #00435a;
            font-weight: 500;
            line-height: 1.7;
        }

        .profile-section .section-body p {
            margin: 0 0 0.75em;
            color: #222;
            font-weight: 400;
        }

        .profile-section .section-body p:last-child {
            margin-bottom: 0;
        }

        .profile-section ul {
            margin: 0;
            padding-left: 1.2em;
            color: #00435a;
        }

        /* ======= COURSES SECTION ======= */
        .courses-section {
            width: 100%;
            max-width: 1200px;
            margin-top: 70px;
            text-align: center;
            color: #222;
        }

        .courses-section h2 {
            color: #00435a;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .courses-section h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, #e6a60b, #bc1701);
            border-radius: 2px;
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
            margin-top: 20px;
            text-align: left;
        }

        @media (max-width: 992px) {
            .course-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .course-grid {
                grid-template-columns: 1fr;
            }
        }

        .course-card {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #e6a60b;
            padding: 25px;
            text-align: left;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border-top-color: #bc1701;
        }

        .course-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: #00435a;
            margin-bottom: 10px;
        }

        .course-card p {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
        }

        .course-card a {
            color: #bc1701;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        .course-card a:hover {
            color: #e6a60b;
        }

        .profile-location {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
            font-size: 15px;
            text-align: center;
        }

        .profile-location-icon {
            width: 20px;
            height: 20px;
            fill: #fff;
        }

        .profile-block {
            display: flex;
            flex-direction: column;
            align-items: center;
        }


        /* ======= RESPONSIVE ======= */
        @media (max-width: 768px) {
            .hero-container {
                flex-direction: column;
                text-align: center;
            }

            .instructor-about {
                padding: 30px 20px;
            }
        }
    </style>

    <section class="instructor-profile">
        <!-- HERO SECTION -->
        <div class="instructor-hero">
            <div class="hero-container">
                <div class="profile-block">
                    <div class="profile-img">
                        <img src="{{ asset($instructor->image ?? '/images/profiles/user.png') }}"
                            alt="{{ $instructor->name }}">
                    </div>
                    @if (!empty($instructor->city) || !empty($instructor->countryarray->name))
                        <p class="profile-location">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="profile-location-icon">
                                <path
                                    d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z" />
                            </svg>
                            <span>{{ $instructor->city ?? '' }}{{ !empty($instructor->city) && !empty($instructor->countryarray->name) ? ', ' : '' }}{{ $instructor->countryarray->name ?? '' }}</span>
                        </p>
                    @endif
                </div>

                <div class="hero-info">
                    @php
                        \App\Models\User::ensureInstructorExtraColumns();
                        $educationList = method_exists($instructor, 'educationList') ? $instructor->educationList() : [];
                        $proQualList = method_exists($instructor, 'professionalQualificationsList') ? $instructor->professionalQualificationsList() : [];
                        $expertiseList = method_exists($instructor, 'expertiseList') ? $instructor->expertiseList() : [];
                        $methodList = method_exists($instructor, 'teachingMethodologyList') ? $instructor->teachingMethodologyList() : [];
                        $methodLabels = array_values(array_intersect_key(\App\Models\User::teachingMethodologyOptions(), array_flip($methodList)));
                        $recognitionLabels = method_exists($instructor, 'teachingRecognitionLabels') ? $instructor->teachingRecognitionLabels() : [];
                        $hasAvailGrid = method_exists($instructor, 'hasAvailabilityGrid') && $instructor->hasAvailabilityGrid();
                        $hasProfessional = \App\Models\User::hasRichTextContent($instructor->short_description ?? null);
                        $hasExecutive = \App\Models\User::hasRichTextContent($instructor->executive_experience ?? null);
                        $hasTeaching = \App\Models\User::hasRichTextContent($instructor->experience ?? null);
                        $hasTraining = \App\Models\User::hasRichTextContent($instructor->training_expertise ?? null);
                        $hasCorporate = \App\Models\User::hasRichTextContent($instructor->corporate_training ?? null);
                        $hasInstitutions = \App\Models\User::hasRichTextContent($instructor->institutions ?? null);
                    @endphp
                    <p class="hero-kicker">Instructor's Profile</p>
                    <h3>{{ $instructor->name }}
                        @if ($instructor->linkedin)
                            <a href="{{ $instructor->linkedin ?? '' }}" target="_blank" class="linkedin-link"
                                title="View LinkedIn Profile">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path
                                        d="M512 96L127.9 96C110.3 96 96 110.5 96 128.3L96 511.7C96 529.5 110.3 544 127.9 544L512 544C529.6 544 544 529.5 544 511.7L544 128.3C544 110.5 529.6 96 512 96zM231.4 480L165 480L165 266.2L231.5 266.2L231.5 480L231.4 480zM198.2 160C219.5 160 236.7 177.2 236.7 198.5C236.7 219.8 219.5 237 198.2 237C176.9 237 159.7 219.8 159.7 198.5C159.7 177.2 176.9 160 198.2 160zM480.3 480L413.9 480L413.9 376C413.9 351.2 413.4 319.3 379.4 319.3C344.8 319.3 339.5 346.3 339.5 374.2L339.5 480L273.1 480L273.1 266.2L336.8 266.2L336.8 295.4L337.7 295.4C346.6 278.6 368.3 260.9 400.6 260.9C467.8 260.9 480.3 305.2 480.3 362.8L480.3 480z" />
                                </svg>
                            </a>
                        @endif
                    </h3>
                    @if($hasProfessional)
                        <div class="hero-professional">{!! $instructor->short_description !!}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- DETAILS SECTION -->
        <div class="instructor-about">
            @if($educationList !== [])
                <div class="profile-section">
                    <h3>Academic Qualifications</h3>
                    <div class="section-body">
                        <ul>
                            @foreach($educationList as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if($proQualList !== [])
                <div class="profile-section">
                    <h3>Professional Qualifications &amp; Certifications</h3>
                    <div class="section-body">
                        <ul>
                            @foreach($proQualList as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if($hasExecutive)
                <div class="profile-section">
                    <h3>Executive &amp; Industry Experience</h3>
                    <div class="section-body">{!! $instructor->executive_experience !!}</div>
                </div>
            @endif

            @if($hasTeaching)
                <div class="profile-section">
                    <h3>Teaching &amp; Academic Experience</h3>
                    <div class="section-body">{!! $instructor->experience !!}</div>
                </div>
            @endif

            @if($recognitionLabels !== [])
                <div class="profile-section">
                    <h3>Teaching Recognition and Certificates</h3>
                    <div class="section-body">
                        <ul>
                            @foreach($recognitionLabels as $label)
                                <li>{{ $label }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if($hasTraining)
                <div class="profile-section">
                    <h3>Professional Training Expertise</h3>
                    <div class="section-body">{!! $instructor->training_expertise !!}</div>
                </div>
            @endif

            @if($hasCorporate)
                <div class="profile-section">
                    <h3>Corporate &amp; Executive Training Experience</h3>
                    <div class="section-body">{!! $instructor->corporate_training !!}</div>
                </div>
            @endif

            @if($expertiseList !== [])
                <div class="profile-section">
                    <h3>Professional &amp; Academic Specialisations</h3>
                    <div class="section-body">
                        <ul>
                            @foreach($expertiseList as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if($hasInstitutions)
                <div class="profile-section">
                    <h3>Institutions &amp; Organisations</h3>
                    <div class="section-body">{!! $instructor->institutions !!}</div>
                </div>
            @endif

            @if($methodLabels !== [])
                <div class="profile-section">
                    <h3>Teaching Methodology</h3>
                    <div class="section-body">
                        <ul>
                            @foreach($methodLabels as $label)
                                <li>{{ $label }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if($hasAvailGrid)
                <div class="profile-section">
                    <h3>Teaching Availability</h3>
                    <div class="section-body">
                        @include('admin.user._availability_grid_display', ['user' => $instructor])
                    </div>
                </div>
            @endif

            @if(! $hasProfessional && $educationList === [] && $proQualList === [] && ! $hasExecutive && ! $hasTeaching && $recognitionLabels === [] && ! $hasTraining && ! $hasCorporate && $expertiseList === [] && ! $hasInstitutions && $methodLabels === [] && ! $hasAvailGrid)
                <div class="profile-section">
                    <h3>Professional Profile</h3>
                    <div class="section-body"><p>This instructor has not added profile details yet.</p></div>
                </div>
            @endif
        </div>

        <!-- COURSES SECTION -->
        <div class="courses-section">
            <h2>Courses by the instructor</h2>

            @if ($instructor->courses && count($instructor->courses) > 0)
                <div class="course-grid">
                    @foreach ($instructor->courses as $course)
                        <div class="course-card">
                            <h3>{{ $course->title }}</h3>
                            <p>{{ Str::limit(strip_tags($course->short_description ?: $course->description ?? 'No description available.'), 90) }}</p>
                            <a href="{{ route('course.details', ['course' => $course->slug]) }}">View Course →</a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="no-courses" style="color:#555; margin-top:15px;">No courses available for this instructor.</p>
            @endif
        </div>
    </section>
@endsection