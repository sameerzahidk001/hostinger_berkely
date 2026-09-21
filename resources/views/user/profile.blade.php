@extends('user.layout.app')
@section('title', 'Profile')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Profile</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('user.home') }}">Home</a>
            </li>
            <li class="active">
                <strong>Profile</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-4" style="padding-top:20px;text-align:right;">
        @if(optional($user->roles->first())->name === 'instructor' && (int) ($user->is_on_web ?? 0) === 1)
            <a href="{{ url('/instructor/' . $user->id) }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                View page
            </a>
        @elseif(optional($user->roles->first())->name === 'instructor')
            <a href="{{ url('/instructor/' . $user->id) }}" target="_blank" rel="noopener noreferrer" class="btn btn-default" title="Public page (requires approved / on web)">
                View page
            </a>
        @endif
    </div>
</div>
<style>
    .profile-section-heading {
        font-size: 22px;
        font-weight: 700;
        color: #1ab394;
        margin: 28px 0 14px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e7eaec;
        clear: both;
    }
    .profile-section-heading:first-child {
        margin-top: 8px;
    }
</style>
<div class="wrapper wrapper-content">
    <div class="row animated fadeInRight">
        <div class="col-md-4">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Profile Detail</h5>
                </div>
                <div>
                    <div class="ibox-content no-padding border-left-right">
                        <center class="m-t-sm">
                            <img alt="Profile photo" class="img-responsive img-circle"
                                style="max-height: 200px; max-width: 200px; object-fit: cover;"
                                src="{{ user_avatar_url($user) }}" />
                        </center>
                    </div>
                    <div class="ibox-content profile-content">
                        <h4><strong>{{ $user->name }}</strong></h4>
                        <p><strong>Email: </strong> {{ $user->email ?? '-' }}</p>
                        <p><strong>Mobile Number: </strong> {{ $user->mobile_number ?? '-' }}</p>
                        <p><strong>Gender: </strong> {{ $user->gender ?? '-' }}</p>
                        <p><strong>Date Of Birth: </strong> {{ $user->date_of_birth ?? '-' }}</p>
                        <p><strong>Post Code: </strong> {{ $user->post_code ?? '-' }}</p>
                        <p><strong>Nationality: </strong> {{ $user->nationality ?? '-' }}</p>
                        <p><strong>City: </strong> {{ $user->city ?? '-' }}</p>
                        <p><strong>Country: </strong> {{ $user->country ?? '-' }}</p>

                        @if(optional($user->roles->first())->name === 'instructor')
                            @php
                                \App\Models\User::ensureInstructorExtraColumns();
                                $educationList = method_exists($user, 'educationList') ? $user->educationList() : [];
                                $expertiseList = method_exists($user, 'expertiseList') ? $user->expertiseList() : [];
                                $methodList = method_exists($user, 'teachingMethodologyList') ? $user->teachingMethodologyList() : [];
                                $methodLabels = array_values(array_intersect_key(\App\Models\User::teachingMethodologyOptions(), array_flip($methodList)));
                                $availLines = method_exists($user, 'availabilityDisplayLines') ? $user->availabilityDisplayLines() : [];
                            @endphp
                            <p><strong>Teaching &amp; Academic Experience: </strong> {!! $user->experience ?? '-' !!}</p>
                            @if($educationList !== [])
                                <p><strong>Academic Qualifications:</strong></p>
                                <ul>
                                    @foreach($educationList as $edu)
                                        <li>{{ $edu }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @php $proQualList = method_exists($user, 'professionalQualificationsList') ? $user->professionalQualificationsList() : []; @endphp
                            @if($proQualList !== [])
                                <p><strong>Professional Qualifications:</strong></p>
                                <ul>
                                    @foreach($proQualList as $q)
                                        <li>{{ $q }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if(method_exists($user, 'hasMapLocation') && $user->hasMapLocation())
                                <p><strong>Map location:</strong> Set (used for nearby trainer search)</p>
                            @endif
                            @if($expertiseList !== [])
                                <p><strong>Areas of Expertise:</strong> {{ implode(', ', $expertiseList) }}</p>
                            @endif
                            @if($methodLabels !== [])
                                <p><strong>Teaching Methodology:</strong> {{ implode(', ', $methodLabels) }}</p>
                            @endif
                            @if($availLines !== [])
                                <p><strong>Availability:</strong></p>
                                <ul>
                                    @foreach($availLines as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <p>
                                <strong>LinkedIn: </strong>
                                @if(!empty($user->linkedin))
                                    <a href="{{ $user->linkedin }}" target="_blank" class="text-primary" style="font-weight:600; text-decoration:none;">
                                        View LinkedIn â†’
                                    </a>
                                @else
                                    <span>-</span>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= Right Column (Edit Form) ================= --}}
        <div class="col-md-8">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Edit Profile</h5>
                    <div class="ibox-tools">
                        @if(optional($user->roles->first())->name === 'instructor')
                            <a href="{{ url('/instructor/' . $user->id) }}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-primary" style="margin-right:8px;">
                                View page
                            </a>
                        @endif
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <div>
                        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <h3 class="profile-section-heading">Basic information</h3>
                                </div>
                                {{-- General Fields --}}
                                <div class="col-md-6 mb-3">
                                    <label for="name">Full Name</label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ old('name', $user->name) }}" placeholder="Enter Your Name">
                                    @error('name') <p class="text-danger text-xs italic">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="image">Image</label>
                                    <input type="hidden" name="current_image" value="{{ old('current_image', $user->image) }}">
                                    <input type="file" name="image" id="image" class="form-control">
                                    @error('image') <p class="text-danger text-xs italic">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" class="form-control"
                                        value="{{ old('email', $user->email) }}" readonly>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="mobile_number">Mobile Number</label>
                                    <input type="tel" name="mobile_number" id="mobile_number" class="form-control"
                                        value="{{ old('mobile_number', $user->mobile_number) }}" placeholder="+9231*******">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="gender">Gender</label>
                                    <select class="form-control" name="gender" id="gender">
                                        <option value="Male" {{ old('gender', $user->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $user->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="date_of_birth">Date Of Birth</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
                                        value="{{ old('date_of_birth', $user->date_of_birth) }}" max="{{ date('Y-m-d') }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="nationality">Nationality</label>
                                    <input type="text" name="nationality" id="nationality" class="form-control"
                                        value="{{ old('nationality', $user->nationality) }}" placeholder="Enter Your Nationality">
                                </div>

                                <div class="col-md-12">
                                    <h3 class="profile-section-heading">Address</h3>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="address">Address</label>
                                    <input type="text" name="address" id="address" class="form-control"
                                        value="{{ old('address', $user->address) }}" placeholder="Residential Address">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="post_code">Post Code</label>
                                    <input type="text" name="post_code" id="post_code" class="form-control"
                                        value="{{ old('post_code', $user->post_code) }}" placeholder="Enter Post Code">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="city">City</label>
                                    <input type="text" name="city" id="city" class="form-control"
                                        value="{{ old('city', $user->city) }}" placeholder="Enter Your City">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="country">Country</label>
                                    <select class="form-control" name="country" id="country">
                                        @foreach($countries as $country)
                                            <option value="{{ $country->iso_code }}"
                                                {{ $country->iso_code == old('country', $user->country) ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Instructor Fields --}}
                                @if(optional($user->roles->first())->name === 'instructor')
                                    <div class="col-md-12" id="instructor-fields">
                                        @include('admin.user._instructor_profile_sections', ['user' => $user])

                                        <h3 class="profile-section-heading">LinkedIn</h3>
                                        <div class="form-group">
                                            <label for="linkedin">LinkedIn Profile URL</label>
                                            <input type="url" name="linkedin" id="linkedin" class="form-control"
                                                placeholder="https://www.linkedin.com/in/username"
                                                value="{{ old('linkedin', $user->linkedin) }}">
                                        </div>

                                        <h3 class="profile-section-heading">Availability &amp; methodology</h3>
                                        @include('admin.user._instructor_extra_fields', ['user' => $user])

                                        <h3 class="profile-section-heading">Map location</h3>
                                        @include('admin.user._instructor_map_location', ['user' => $user])
                                    </div>
                                @endif
                            </div>
                            <button class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Change Password --}}
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Change your password</h5>
                    <div class="ibox-tools">
                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                        <a class="close-link"><i class="fa fa-times"></i></a>
                    </div>
                </div>
                <div class="ibox-content">
                    <form method="POST" action="{{ route('user.profile.update.password') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password">Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="Enter Current Password">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="password">New Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Enter New Password">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="password_confirmation">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm New Password">
                            </div>
                        </div>
                        <button class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $('#education-wrapper').on('click', '.remove-education', function () {
        $(this).closest('.education-group').remove();
    });

    function addEducationInput() {
        $('#education-wrapper').append(`
            <div class="form-group mb-2 education-group">
                <input type="text" name="education[]" class="form-control" placeholder="e.g. MBA, MSc…">
                <button type="button" class="btn btn-danger btn-sm remove-education"
                    style="margin-left: 10px; float: right; margin-top: 10px;">Remove</button>
            </div>
        `);
    }

    $('#pro-qual-wrapper').on('click', '.remove-pro-qual', function () {
        $(this).closest('.pro-qual-group').remove();
    });
    $('#add-pro-qual-btn').on('click', function () {
        $('#pro-qual-wrapper').append(`
            <div class="form-group mb-2 pro-qual-group">
                <input type="text" name="professional_qualifications[]" class="form-control" placeholder="e.g. CFA, ACCA, PMP…">
                <button type="button" class="btn btn-danger btn-sm remove-pro-qual" style="margin-left:10px;float:right;margin-top:10px;">Remove</button>
            </div>
        `);
    });

    $('#expertise-wrapper').on('click', '.remove-expertise', function () {
        $(this).closest('.expertise-group').remove();
    });
    $('#add-expertise-btn').on('click', function () {
        $('#expertise-wrapper').append(`
            <div class="form-group mb-2 expertise-group">
                <input type="text" name="expertise[]" class="form-control" placeholder="Enter area of expertise">
                <button type="button" class="btn btn-danger btn-sm remove-expertise" style="margin-left:10px;float:right;margin-top:10px;">Remove</button>
            </div>
        `);
    });
    $(document).on('change', 'input[name="availability[frequency]"]', function () {
        var particular = $(this).val() === 'particular';
        $('#availability-days-wrap').toggle(particular);
        $('#availability-daily-times').toggle(!particular);
    });
    $(document).on('change', '.js-avail-day', function () {
        var day = $(this).data('day');
        $('.js-avail-day-times[data-day="' + day + '"]').toggle(this.checked);
    });

    function plainTextLength(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        return (tmp.textContent || tmp.innerText || '').replace(/\u00a0/g, ' ').trim().length;
    }
    function bindEditorCounter(editor, counterId, max) {
        var el = document.getElementById(counterId);
        if (!el) return;
        var update = function () {
            el.textContent = '(' + plainTextLength(editor.getData()) + ' / ' + max + ' Characters)';
        };
        editor.model.document.on('change:data', update);
        update();
    }

    $(document).ready(function () {
        var editorOpts = {
            toolbar: [
                'heading', '|', 'bold', 'italic', '|',
                'alignment', 'bulletedList', 'numberedList', '|',
                'link', 'blockQuote', '|',
                'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells', '|',
                'undo', 'redo', '|',
                'indent', 'outdent', '|'
            ],
        };

        var editors = [
            { id: 'short_description', counter: 'short_char_count', max: 500 },
            { id: 'experience', counter: 'experience_char_count', max: 2000 },
            { id: 'executive_experience', counter: 'executive_char_count', max: 2000 },
            { id: 'training_expertise', counter: 'training_char_count', max: 2000 },
            { id: 'corporate_training', counter: 'corporate_char_count', max: 2000 },
            { id: 'institutions', counter: 'institutions_char_count', max: 2000 },
        ];

        editors.forEach(function (cfg) {
            var el = document.getElementById(cfg.id);
            if (!el || typeof ClassicEditor === 'undefined') return;
            ClassicEditor.create(el, editorOpts)
                .then(function (editor) { bindEditorCounter(editor, cfg.counter, cfg.max); })
                .catch(function (error) { console.error('CKEditor initialization error:', error); });
        });
    });
</script>
@endpush
