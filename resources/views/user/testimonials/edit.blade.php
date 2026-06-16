@extends('user.layout.app')
@section('title', 'Testimonial')
@push('style')
    <style>
        .mb {
            margin-bottom: 1.5rem;
        }

        .text-danger {
            font-size: 0.9rem;
        }

        /* optional: bootstrap-style red border when invalid */
        .is-invalid {
            border-color: #dc3545;
        }
    </style>
@endpush

@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Testimonial</h2>
            <ol class="breadcrumb">
                <li><a href="#">Home</a></li>
                <li><a href="{{ route('user.testimonial.index') }}">Testimonial</a></li>
                <li class="active"><strong>Edit Testimonial</strong></li>
            </ol>
        </div>
        <div class="col-lg-2"></div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Edit Testimonial of {{ $testimonial->course->title ?? 'N/A' }}</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                            <a class="close-link"><i class="fa fa-times"></i></a>
                        </div>
                    </div>

                    <div class="ibox-content">
                        <form role="form" action="{{ route('user.testimonial.update', $testimonial->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!-- hidden course_id -->
                            <input type="hidden" name="course_id" value="{{ $testimonial->course->id ?? 'N/A' }}">
                            @error('course_id') <span class="text-danger">{{ $message }}</span> @enderror

                            <div class="row">
                                <!-- Alumni Name -->
                                <div class="col-lg-6 mb">
                                    <label for="name">Alumni Name</label>
                                    <input id="name" class="form-control @error('name') is-invalid @enderror"
                                        placeholder="Add Alumni Name" type="text" name="name"
                                        value="{{ old('name', $testimonial->name ?? '') }}" required>
                                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Image -->
                                <div class="col-lg-6 mb">
                                    <label class="mb-1" for="image">Image</label>
                                    <input id="image" class="form-control @error('image') is-invalid @enderror" type="file"
                                        name="image">
                                    @error('image') <span class="text-danger">{{ $message }}</span> @enderror
                                    @if($testimonial->image)
                                        <small class="help-block" style="display:block; margin-top:6px;">
                                            <a href="{{ asset($testimonial->image) }}" target="_blank" rel="noopener">Show
                                                Image</a>
                                        </small>
                                    @endif
                                </div>

                                <!-- Alumni City -->
                                <div class="col-lg-6 mb">
                                    <label for="city">Alumni City</label>
                                    <input id="city" class="form-control @error('city') is-invalid @enderror"
                                        placeholder="Add Alumni City" type="text" name="city"
                                        value="{{ old('city', $testimonial->city ?? '') }}" required>
                                    @error('city') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Country -->
                                <div class="col-lg-6 mb">
                                    <label class="mb-1" for="country">Country</label>
                                    @php
                                        $countries = App\Models\Country::get();
                                        $selectedCountry = old('country', $testimonial->country ?? $user->countryarray->name ?? $user->country ?? '');
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
                                    @error('country') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Date -->
                                <div class="col-lg-6 mb">
                                    <label for="date">Date</label>
                                    <input id="date" class="form-control @error('date') is-invalid @enderror" type="date"
                                        name="date" value="{{ old('date', $testimonial->date) }}" required>
                                    @error('date') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <!-- Text -->
                                <div class="col-lg-12 mb">
                                    <label class="mb-1" for="text">Text</label>
                                    <textarea id="text" name="text"
                                        class="form-control text-editor @error('text') is-invalid @enderror" rows="2"
                                        required>{{ old('text', $testimonial->text) }}</textarea>
                                    @error('text') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-lg-12">
                                    <hr>
                                </div>

                                <!-- Youtube / LinkedIn / Rating -->
                                <div class="row" style="width:100%; margin-left:0;">
                                    <div class="col-lg-4 mb">
                                        <label class="mb-1" for="asset_path">Youtube Video URL</label>
                                        <input id="asset_path" type="url" name="asset_path"
                                            class="form-control @error('asset_path') is-invalid @enderror"
                                            value="{{ old('asset_path', $testimonial->asset_path ?? '') }}">
                                        @if(!empty($testimonial->asset_path))
                                            <small class="help-block" style="display:block; margin-top:6px;">
                                                <a href="{{ $testimonial->asset_path }}" target="_blank" rel="noopener">View
                                                    video here</a>
                                            </small>
                                        @endif
                                        @error('asset_path') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-lg-4 mb">
                                        <label for="linkedin_url">LinkedIn Profile URL</label>
                                        <input id="linkedin_url" type="url"
                                            class="form-control @error('linkedin_url') is-invalid @enderror"
                                            name="linkedin_url"
                                            value="{{ old('linkedin_url', $testimonial->linkedin_url ?? '') }}">
                                        @if(!empty($testimonial->linkedin_url))
                                            <small class="help-block" style="display:block; margin-top:6px;">
                                                <a href="{{ $testimonial->linkedin_url }}" target="_blank" rel="noopener">View
                                                    profile</a>
                                            </small>
                                        @endif
                                        @error('linkedin_url') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-lg-4 mb">
                                        <label for="rating">Rating</label>
                                        <select id="rating" name="rating"
                                            class="form-control @error('rating') is-invalid @enderror" required>
                                            <option value="">Select Rating</option>
                                            @for ($i = 1; $i <= 5; $i++)
                                                <option value="{{ $i }}" {{ (old('rating', $testimonial->rating ?? '') == $i) ? 'selected' : '' }}>
                                                    {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                        @error('rating') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12" style="margin-top:0; text-align:right;">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fa fa-check"></i>&nbsp;Submit
                                    </button>
                                    <button class="btn btn-warning" type="reset">
                                        <i class="fa fa-paste"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!-- CKEditor (optional)
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.text-editor').forEach((el) => {
                ClassicEditor.create(el).catch(console.error);
            });
        });
    </script>
    -->
@endpush