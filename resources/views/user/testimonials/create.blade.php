@extends('user.layout.app')
@section('title', 'Create Testimonial')
@push('style')
    <style>
        .mb {
            margin-bottom: 1.5rem;
        }

        .text-danger {
            font-size: 0.9rem;
        }
    </style>
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Testimonial</h2>
            <ol class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li href="{{route('user.testimonial.index')}}"><a>Testimonial</a></li>
                <li class="active"><strong>Create Testimonial</strong></li>
            </ol>
        </div>
        <div class="col-lg-2"></div>
    </div>
    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Create Testimonial</h5>
                    </div>
                    <div class="ibox-content">
                        <form role="form" action="{{ route('user.testimonial.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @php
                                use App\Models\User;

                                $user = Auth::check()
                                    ? User::with('countryarray')->find(Auth::id())
                                    : null;
                            @endphp

                            <div class="row">
                                <!-- Name -->
                                <div class="col-lg-6 mb">
                                    <label for="">Alumni Name</label>
                                    <input class="form-control @error('name') is-invalid @enderror"
                                        placeholder="Add Alumni Name" type="text" name="name"
                                        value="{{ old('name', $user->name ?? '') }}" required>
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Image -->
                                <div class="col-lg-6 mb"> <label class="mb-1">Image</label>
                                    <input class="form-control" type="file" name="image">
                                    <a href="{{ $user?->image ? asset($user->image) : '#' }}" target="_blank">
                                        {{ $user?->image ? 'Show Image' : 'No Image Available' }}
                                    </a>
                                </div>

                                <!-- City -->
                                <div class="col-lg-6 mb">
                                    <label for="">Alumni City</label>
                                    <input class="form-control @error('city') is-invalid @enderror"
                                        placeholder="Add Alumni City" type="text" name="city"
                                        value="{{ old('city', $user->city ?? '') }}" required>
                                    @error('city')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Country -->
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Country</label>
                                    @php
                                        $countries = App\Models\Country::get();
                                        $selectedCountry = old('country', $user->countryarray->name ?? $user->country ?? '');
                                    @endphp
                                    <select name="country" class="form-control @error('country') is-invalid @enderror"
                                        required>
                                        <option value="">-- Select Country --</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->name }}" {{ $selectedCountry == $country->name ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Date -->
                                <div class="col-lg-6 mb">
                                    <label for="">Date</label>
                                    <input class="form-control @error('date') is-invalid @enderror" type="date" name="date"
                                        value="{{ old('date') }}">
                                    @error('date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Courses -->
                                <div class="col-lg-6 mb">
                                    <label for="">Courses</label>
                                    <select class="form-control @error('course_id') is-invalid @enderror" name="course_id"
                                        required>
                                        <option value="">-- Select Course --</option>
                                        @php
                                            use App\Models\Payment;
                                            $Payment = Payment::where('user_id', Auth::id())->get();
                                            $paidCourses = $courses->filter(function ($course) use ($Payment) {
                                                return $Payment->contains('course_id', $course->id);
                                            });
                                        @endphp
                                        @foreach($paidCourses as $course)
                                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                                {{ $course->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('course_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Text -->
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Text</label>
                                    <textarea name="text"
                                        class="form-control text-editor @error('text') is-invalid @enderror" rows="2"
                                        required>{{ old('text') }}</textarea>
                                    @error('text')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-12">
                                    <hr>
                                </div>

                                <!-- Youtube URL -->
                                <div class="col-lg-4 mb">
                                    <label class="mb-1">Youtube Video URL</label>
                                    <input type="url" name="asset_path"
                                        class="form-control @error('asset_path') is-invalid @enderror"
                                        value="{{ old('asset_path') }}">
                                    @error('asset_path')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- LinkedIn -->
                                <div class="col-lg-4 mb">
                                    <label class="mb-1">LinkedIn Profile URL</label>
                                    <input type="url" name="linkedin_url"
                                        class="form-control @error('linkedin_url') is-invalid @enderror"
                                        value="{{ old('linkedin_url') }}" required>
                                    @error('linkedin_url')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Rating -->
                                <div class="col-lg-4 mb">
                                    <label class="mb-1">Rating</label>
                                    <select name="rating" class="form-control @error('rating') is-invalid @enderror"
                                        required>
                                        <option value="">-- Select Rating --</option>
                                        @for ($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" {{ old('rating') == $i ? 'selected' : '' }}>
                                                {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                            </option>
                                        @endfor
                                    </select>
                                    @error('rating')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 0; text-align:right;">
                                    <button class="btn btn-primary" type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Submit</button>
                                    <button class="btn btn-warning" type="reset"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection