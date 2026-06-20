@extends('user.layout.app')
@section('title', 'Profile')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
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
    <div class="col-lg-2"></div>
</div>
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
                        <p><strong>Address: </strong> {{ $user->address ?? '-' }}</p>
                        <p><strong>Post Code: </strong> {{ $user->post_code ?? '-' }}</p>
                        <p><strong>Nationality: </strong> {{ $user->nationality ?? '-' }}</p>
                        <p><strong>City: </strong> {{ $user->city ?? '-' }}</p>
                        <p><strong>Country: </strong> {{ $user->country ?? '-' }}</p>

                        @if($user->roles[0]->name == 'instructor')
                            <div><strong>Short Profile Description: </strong> {!! $user->short_description ?? '-' !!}</div>
                            <p><strong>Experience: </strong> {!! $user->experience ?? '-' !!}</p>
                            <p><strong>Education:</strong></p>
                            <ul>
                                @php $education = json_decode($user->education ?? '[]', true); @endphp
                                @forelse($education as $edu)
                                    <li>{{ $edu }}</li>
                                @empty
                                    <li>-</li>
                                @endforelse
                            </ul>

                            {{-- LinkedIn Display --}}
                            <p>
                                <strong>LinkedIn: </strong>
                                @if(!empty($user->linkedin))
                                    <a href="{{ $user->linkedin }}" target="_blank" class="text-primary" style="font-weight:600; text-decoration:none;">
                                        View Profile →
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
                                {{-- General Fields --}}
                                <div class="col-md-6 mb-3">
                                    <label for="name">Full Name</label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ old('name', $user->name) }}" placeholder="Enter Your Name">
                                    @error('name') <p class="text-danger text-xs italic">{{ $message }}</p> @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="image">Image</label>
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
                                @if($user->roles[0]->name == 'instructor')
                                    <div class="col-md-12" id="instructor-fields">
                                        <div class="form-group">
                                            <label for="short_description">Short Description</label>
                                            <textarea name="short_description" id="short_description" class="form-control" rows="3"
                                                placeholder="Define yourself shortly...">{!! old('short_description', $user->short_description) !!}</textarea>
                                            @error('short_description')
                                                <p class="text-danger text-xs italic">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="long_description">Long Description</label>
                                            <textarea name="long_description" id="long_description" class="form-control" rows="3"
                                                placeholder="Define yourself shortly...">{!! old('long_description', $user->long_description) !!}</textarea>
                                            @error('long_description')
                                                <p class="text-danger text-xs italic">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="experience">Experience</label>
                                            <textarea name="experience" id="experience" class="form-control" rows="3"
                                                placeholder="Describe experience...">{!! old('experience', $user->experience)  !!}</textarea>
                                            @error('experience')
                                                <p class="text-danger text-xs italic">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="linkedin">LinkedIn Profile URL</label>
                                            <input type="url" name="linkedin" id="linkedin" class="form-control"
                                                placeholder="https://www.linkedin.com/in/username"
                                                value="{{ old('linkedin', $user->linkedin) }}">
                                            @error('linkedin')
                                                <p class="text-danger text-xs italic">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="education[]">Education</label>
                                            <div id="education-wrapper">
                                                @foreach(old('education', json_decode($user->education ?? '[]', true) ?? []) as $edu)
                                                    <div class="form-group mb-2 education-group">
                                                        <input type="text" name="education[]" class="form-control"
                                                            placeholder="Enter education" value="{{ $edu }}">
                                                        @if(!$loop->first)
                                                            <button type="button" class="btn btn-danger btn-sm remove-education"
                                                                style="margin-left: 10px; float: right; margin-top: 10px;">Remove</button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addEducationInput()">Add More</button>
                                        </div>
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
                <input type="text" name="education[]" class="form-control" placeholder="Enter education">
                <button type="button" class="btn btn-danger btn-sm remove-education"
                    style="margin-left: 10px; float: right; margin-top: 10px;">Remove</button>
            </div>
        `);
    }

    $(document).ready(function () {
        let shortEditorElement = document.getElementById('short_description');
        let longEditorElement = document.getElementById('long_description');
        let experienceElement = document.getElementById('experience');

        if (shortEditorElement) {
            ClassicEditor.create(shortEditorElement, {
                toolbar: [
                    'heading', '|', 'bold', 'italic', '|',
                    'alignment', 'bulletedList', 'numberedList', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells', '|',
                    'undo', 'redo', '|',
                    'indent', 'outdent', '|'
                ],
            })
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                console.error('CKEditor initialization error:', error);
            });
        }

        if (longEditorElement) {
            ClassicEditor.create(longEditorElement, {
                toolbar: [
                    'heading', '|', 'bold', 'italic', '|',
                    'alignment', 'bulletedList', 'numberedList', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells', '|',
                    'undo', 'redo', '|',
                    'indent', 'outdent', '|'
                ],
            })
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                console.error('CKEditor initialization error:', error);
            });
        }

        if (experienceElement) {
            ClassicEditor.create(experienceElement, {
                toolbar: [
                    'heading', '|', 'bold', 'italic', '|',
                    'alignment', 'bulletedList', 'numberedList', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', 'tableColumn', 'tableRow', 'mergeTableCells', '|',
                    'undo', 'redo', '|',
                    'indent', 'outdent', '|'
                ],
            })
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                console.error('CKEditor initialization error:', error);
            });
        }
    });
</script>
@endpush