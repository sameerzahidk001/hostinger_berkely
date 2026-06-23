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

        .hero-info h3 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hero-info p {
            font-size: 16px;
            margin: 4px 0;
            opacity: 0.9;
        }

        .hero-highlight {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 12px;
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

        .instructor-about h2 {
            color: #00435a;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 3px solid #e6a60b;
            display: inline-block;
            padding-bottom: 6px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .about-item {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 20px;
            border-left: 6px solid #e6a60b;
            transition: all 0.3s ease;
        }

        .about-item:hover {
            transform: translateY(-6px);
            background: #fff;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .about-item strong {
            color: #bc1701;
            display: block;
            font-size: 14px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .about-item span {
            font-size: 16px;
            color: #00435a;
            font-weight: 600;
        }

        .bio-box {
            background: linear-gradient(90deg, rgba(230, 166, 11, 0.1), rgba(188, 23, 1, 0.08));
            padding: 25px 30px;
            border-radius: 14px;
            margin-top: 40px;
            border-left: 6px solid #e6a60b;
        }

        .bio-box h3 {
            font-size: 20px;
            font-weight: 600;
            color: #bc1701;
            margin-bottom: 10px;
        }

        .bio-box p {
            color: #222;
            font-size: 15px;
            line-height: 1.8;
            margin: 0;
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
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
                    <p class="hero-highlight">
                        {{ is_array(json_decode($instructor->education, true)) ? implode(', ', json_decode($instructor->education, true)) : $instructor->education }}
                    </p>
                    <p>{!! $instructor->experience ?? '<p>Professional Instructor</p>' !!}</p>
                </div>
            </div>
        </div>

        <!-- ABOUT SECTION -->
        <div class="instructor-about">
            <h2>Trainer Profile</h2>

            {{-- <div class="about-grid">
            <div class="about-item"><strong>Gender</strong><span>{{ $instructor->gender ?? 'N/A' }}</span></div>
            <div class="about-item"><strong>Nationality</strong><span>{{ $instructor->nationality ?? 'N/A' }}</span></div>
            <div class="about-item"><strong>Education</strong><span>
                {{ is_array(json_decode($instructor->education, true)) ? implode(', ', json_decode($instructor->education, true)) : $instructor->education }}
            </span></div>
        </div> --}}

            <div class="bio-box">
                <div>{!! $instructor->long_description ?? '<p>This instructor has not added a biography yet.</p>' !!}</div>
            </div>
        </div>

        <!-- COURSES SECTION -->
        <div class="courses-section">
            <h2>Courses by {{ $instructor->name }}</h2>

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