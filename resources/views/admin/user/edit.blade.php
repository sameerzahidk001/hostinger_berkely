@extends('admin.layout.app')
@section('title', 'Edit User')
@push('style')
    <style>
        .page-heading {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .display-flex {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .col {
            flex: 1;
        }

        .card {
            border: 1px solid #efefef;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            background-color: #fff;
        }

        .card.radio-checked {
            transition: border-color 0.3s ease-in-out;
            cursor: pointer;
        }

        .radio-checked:has(input[type="radio"]:checked) {
            border-color: #304FFE !important;
        }
    </style>
@endpush

@section('content')

    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col">
            <h2>Edit {{$user->roles[0]->name}}</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                </li>
                <li>
                    <a href="{{ route('users') }}">Users</a>
                </li>
                <li class="active">
                    <strong>Edit</strong>
                </li>
            </ol>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Edit {{$user->roles[0]->name}} Details</h5>
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
                        <form role="form" action="{{ route('users.update', $user->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="current_image" value="{{ old('current_image', $user->image) }}">
                            <div class="row">

                                <!-- Name -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" name="name" id="name" class="form-control"
                                            value="{{ old('name', $user->name) }}" required>
                                        @error('name')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" id="email" class="form-control"
                                            value="{{ old('email', $user->email) }}" ReadOnly>
                                        @error('email')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6" style="margin-bottom: 15px;">
                                    <label for="image_path">Select Image</label>
                                    <div class="input-group">
                                        <input type="text" id="image_path" name="image_path" class="form-control" readonly
                                            onclick="showFileModal()"
                                            value="{{ old('image_path', $user->image) }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="showFileModal()">Browse</button>
                                        </span>
                                    </div>

                                    @error('image_path') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                    @error('local_file_input') <span class="help-block text-danger">{{ $message }}</span> @enderror

                                    @if(!empty($user->image))
                                        <small id="imageUrlText" class="help-block" style="display:block; margin-top:0px;">
                                            @if($user->image)
                                                <img src="{{ user_avatar_url($user) }}" alt="" width="30" height="30">
                                            @else
                                                <span class="text-muted">No image selected</span>
                                            @endif
                                        </small>
                                    @endif

                                    <!-- Hidden file input -->
                                    <input type="file" id="local_file_input" name="local_file_input" style="display: none;"
                                        accept="image/*" onchange="uploadLocalFile(this)">

                                    <!-- File Source Modal -->
                                    <div id="fileModal" class="modal fade" tabindex="-1" role="dialog"
                                        aria-labelledby="fileModalLabel">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    <h4 class="modal-title" id="fileModalLabel">Select Image</h4>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <button type="button" class="btn btn-primary btn-block"
                                                        onclick="pickLocalFile()">Upload from Computer</button>
                                                    <button type="button" class="btn btn-success btn-block"
                                                        onclick="showFileManagerModal()">Choose from File Manager</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- File Manager Modal -->
                                    <div id="fileManagerModal" class="modal fade" tabindex="-1" role="dialog">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    <h4 class="modal-title">Choose Image</h4>
                                                </div>

                                                <div class="modal-body" style="max-height: 65vh; overflow-y: auto;"
                                                    id="fileManagerModalBody">
                                                    {{-- Your image grid with checkboxes --}}
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary"
                                                        onclick="confirmImageSelection()">Select</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6" style="margin-bottom: 15px;">
                                    <label for="mobile_number">Mobile Number</label>
                                    <input type="tel" name="mobile_number" id="mobile_number" class="form-control"
                                        value="{{ old('mobile_number', $user->mobile_number) }}" placeholder="+9231*******">
                                    @error('mobile_number')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-4" style="margin-bottom: 15px;">
                                    <label for="gender">Gender</label>
                                    <select class="form-control" name="gender" id="gender">
                                        <option value="Male" {{ old('gender', $user->gender) == 'Male' ? 'selected' : '' }}>
                                            Male</option>
                                        <option value="Female" {{ old('gender', $user->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                    @error('gender')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-4" style="margin-bottom: 15px;">
                                    <label for="date_of_birth">Date Of Birth</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control"
                                        value="{{ old('date_of_birth', $user->date_of_birth) }}" max="{{ date('Y-m-d') }}">
                                    @error('date_of_birth')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-4" style="margin-bottom: 15px;">
                                    <label for="nationality">Nationality</label>
                                    <input type="text" name="nationality" id="nationality" class="form-control"
                                        value="{{ old('nationality', $user->nationality) }}"
                                        placeholder="Enter Your Nationality">
                                    @error('nationality')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-12" style="margin-bottom: 15px;">
                                    <label for="city">Address</label>
                                    <input type="text" name="address" id="address" class="form-control"
                                        value="{{ old('address', $user->address) }}" placeholder="Residentail Address">
                                    @error('address')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="col-md-4" style="margin-bottom: 15px;">
                                    <label for="post_code">Post Code</label>
                                    <input type="text" name="post_code" id="post_code" class="form-control"
                                        value="{{ old('post_code', $user->post_code) }}" placeholder="Enter Post Code">
                                    @error('post_code')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-4" style="margin-bottom: 15px;">
                                    <label for="city">City</label>
                                    <input type="text" name="city" id="city" class="form-control"
                                        value="{{ old('city', $user->city) }}" placeholder="Enter Your City Name">
                                    @error('city')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-4" style="margin-bottom: 15px;">
                                    <label for="country">Country</label>
                                    <select class="form-control" name="country" id="country">
                                        @foreach($countries as $country)
                                            <option value="{{ $country->iso_code }}" {{ $country->iso_code == old('country', $user->country) ? 'selected' : '' }}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('country')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Account Status -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="approved">Status</label>
                                        <select name="approved" id="approved" class="form-control">
                                            <option value="1" {{ old('approved', '1') == 1 ? 'selected' : '' }}>Active
                                            </option>
                                            <option value="0" {{ old('approved', '1') == 0 ? 'selected' : '' }}>Disabled
                                            </option>
                                        </select>
                                        @error('approved')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
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
                                                placeholder="https://www.linkedin.com/in/username" value="{{ old('linkedin', $user->linkedin) }}">
                                            @error('linkedin')
                                                <p class="text-danger text-xs italic">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Education (Multiple input fields) -->
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
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                                onclick="addEducationInput()">Add More</button>
                                            @error('education.*')
                                                <p class="text-danger text-xs italic">{{ $message }}</p>
                                            @enderror
                                        </div>

                                    </div>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                    <button class="btn btn-primary" type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Change {{$user->roles[0]->name}} Password</h5>
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
                        <form role="form" action="{{ route('users.update', $user->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" name="password" id="password" class="form-control">
                                        @error('password')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                    <button class="btn btn-primary" type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
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
    <script>
        function showFileModal() {
            $('#fileModal').modal('show');
        }

        function pickLocalFile() {
            $('#fileModal').modal('hide');
            $('#local_file_input').click();
        }

        function uploadLocalFile(input) {
            if (!input.files[0]) {
                return;
            }
        }

        function showFileManagerModal() {
            $('#fileModal').modal('hide');
            $('#fileModal').on('hidden.bs.modal', function () {
                $('#fileManagerModal').modal('show');
                $('#fileManagerModalBody').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>');
                $('#fileManagerModalBody').load("{{ route('file-manager.index', ['type' => 'image']) }}");
                $(this).off('hidden.bs.modal');
            });
        }

        let selectedImageURLs = [];

        function selectImage(url) {
            const isMultiple = $('#image_path').prop('multiple');
            if (isMultiple) {
                if (!selectedImageURLs.includes(url)) selectedImageURLs.push(url);
            } else {
                selectedImageURLs = [url];
            }

            $('.file-thumbnail').removeClass('selected');
            selectedImageURLs.forEach(function (selected) {
                $(`.file-thumbnail[data-url="${selected}"]`).addClass('selected');
            });
        }

        function confirmImageSelection() {
            const isMultiple = $('#image_path').prop('multiple');
            if (isMultiple) {
                $('#image_path').val(selectedImageURLs.join(','));
            } else {
                $('#image_path').val(selectedImageURLs.length > 0 ? selectedImageURLs[0] : '');
            }
            $('#fileManagerModal').modal('hide');
        }

        // Remove education input
        $('#education-wrapper').on('click', '.remove-education', function () {
            $(this).closest('.education-group').remove();
        });

        function addEducationInput() {
            $('#education-wrapper').append(`
                    <div class="form-group mb-2 education-group">
                        <input type="text" name="education[]" class="form-control" placeholder="Enter education">
                        <button type="button" class="btn btn-danger btn-sm remove-education" style="margin-left: 10px; float: right; margin-top: 10px;">Remove</button>
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