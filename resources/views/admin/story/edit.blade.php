@extends('admin.layout.app')
@section('title', 'Edit Testimonial')

@push('style')
<style>
    .mb {
        margin-bottom: 1.5rem;
    }

    .thumb-preview {
        display: inline-block;
        border: 1px solid #e7eaec;
        border-radius: 4px;
        padding: 6px;
        background: #fff;
        margin-top: 6px;
        max-width: 160px
    }

    .thumb-preview img {
        max-width: 100%;
        height: auto;
        display: block
    }
</style>
@endpush

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Testimonial</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('testimonial.index') }}">Testimonial</a></li>
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
                    <h5>Edit Testimonial</h5>
                    <div class="ibox-tools">
                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                        <a class="close-link"><i class="fa fa-times"></i></a>
                    </div>
                </div>

                <div class="ibox-content">
                    <form role="form" action="{{ route('testimonial.update', $testimonial->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-lg-6 mb">
                                <label>Alumni Name</label>
                                <input class="form-control" placeholder="Add Alumni Name" type="text" name="name"
                                    value="{{ old('name', $testimonial->name) }}">
                                @error('name') <span class="help-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Image Input Group -->
                            <div class="col-lg-6 mb form-group">
                                <label for="image_path">Select Image</label>
                                <div class="input-group">
                                    <input type="text" id="image_path" name="image_path" class="form-control" readonly
                                        onclick="showFileModal()"
                                        value="{{ old('image_path', $testimonial->image_path) }}">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button"
                                            onclick="showFileModal()">Browse</button>
                                    </span>
                                </div>

                                @error('image_path') <span class="help-block text-danger">{{ $message }}</span> @enderror
                                @error('local_file_input') <span class="help-block text-danger">{{ $message }}</span> @enderror

                                @if(!empty($testimonial->image))
                                <small id="imageUrlText" class="help-block" style="display:block; margin-top:0px;">
                                    @if($testimonial->image)
                                    View image <a href="{{ old('image_path', $testimonial->image) }}" target="_blank"
                                        rel="noopener">here</a>
                                    @else
                                    <span class="text-muted">No image selected</span>
                                    @endif
                                </small>
                                @endif
                            </div>

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

                            <div class="col-lg-6 mb">
                                <label>Alumni City</label>
                                <input class="form-control" placeholder="Add Alumni City" type="text" name="city"
                                    value="{{ old('city', $testimonial->city) }}">
                                @error('city') <span class="help-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-6 mb">
                                <label class="mb-1">Country</label>
                                @php
                                $selectedCountry = old('country', $testimonial->country ?? $testimonial->country_id ?? null);
                                @endphp
                                <select name="country" class="form-control">
                                    <option value="">-- Select Country --</option>
                                    @foreach($countries as $country)
                                    <option value="{{ $country->name }}" {{ ($selectedCountry == $country->name) ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('country') <span class="help-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-6 mb">
                                <label>Date</label>
                                <input class="form-control" type="date" name="date"
                                    value="{{ old('date', $testimonial->date ? \Carbon\Carbon::parse($testimonial->date)->format('Y-m-d') : '') }}">
                                @error('date') <span class="help-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-6 mb">
                                <label>Courses</label>
                                <select class="form-control" name="course_id">
                                    @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id', $testimonial->course_id) == $course->id ? 'selected' : '' }}>
                                        {{ $course->title }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('course_id') <span class="help-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12 mb">
                                <label class="mb-1">Text</label>
                                <textarea name="text" class="form-control text-editor"
                                    rows="2">{{ old('text', $testimonial->text) }}</textarea>
                                @error('text') <span class="help-block text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12">
                                <hr>
                            </div>

                            <div class="row">
                                <div class="col-lg-4 mb">
                                    <label class="mb-1">Youtube Video URL</label>
                                    <input type="url" name="asset_path" class="form-control"
                                        value="{{ old('asset_path', $testimonial->asset_path) }}">
                                    @if(!empty($testimonial->asset_path))
                                    <small id="imageUrlText" class="help-block" style="display:block; margin-top:0px;">
                                        <a href="{{ $testimonial->asset_path }}" target="_blank" rel="noopener">View video here</a>
                                    </small>
                                    @endif
                                    @error('asset_path')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-4 mb">
                                    <label for="linkedin_url">LinkedIn Profile URL</label>
                                    <input type="url" class="form-control" name="linkedin_url" id="linkedin_url"
                                        value="{{ old('linkedin_url', $testimonial->linkedin_url ?? '') }}">
                                    @if(!empty($testimonial->linkedin_url))
                                    <small id="linkedinUrlText" class="help-block" style="display:block; margin-top:0px;">
                                        <a href="{{ $testimonial->linkedin_url }}" target="_blank" rel="noopener">View profile</a>
                                    </small>
                                    @endif
                                    @error('linkedin_url')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-lg-4 mb">
                                    <label for="rating">Rating</label>
                                    <select name="rating" id="rating" class="form-control">
                                        <option value="">Select Rating</option>
                                        @for ($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" {{ (old('rating', $testimonial->rating ?? '') == $i) ? 'selected' : '' }}>
                                            {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                            </option>
                                            @endfor
                                    </select>
                                    @error('rating')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>


                        </div>

                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 0; text-align:right;">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa fa-check"></i>&nbsp;Update
                                </button>
                                <a href="{{ route('testimonial.index') }}" class="btn btn-default">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.ibox-content -->
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
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('image_path').value = file.name;
            };
            reader.readAsDataURL(file);
        }
    }

    function showFileManagerModal() {
        $('#fileModal').modal('hide');
        $('#fileModal').on('hidden.bs.modal', function() {
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
        selectedImageURLs.forEach(function(selected) {
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
</script>
@endpush