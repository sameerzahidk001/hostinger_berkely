@extends('admin.layout.app')
@section('title', 'Create Testimonial')
@push('style')
    <style>
        .mb {
            margin-bottom: 1.5rem;
        }
    </style>
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Testimonial</h2>
            <ol class="breadcrumb">
                <li><a href="{{route('admin.home')}}">Home</a></li>
                <li><a href="{{route('testimonial.index')}}">Testimonial</a></li>
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
                        <div class="ibox-tools">
                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                            <a class="close-link"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="ibox-content">
                        <form role="form" action="{{ route('testimonial.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 mb">
                                    <label for="">Alumni Name</label>
                                    <input class="form-control" placeholder="Add Alumni Name" type="text" name="name"
                                        value="{{ old('name') }}">
                                </div>
                                <!-- Image Input Group -->
                                <div class="col-lg-6 mb form-group">
                                    <label for="image_path">Select Image</label>
                                    <div class="input-group">
                                        <input type="text" id="image_path" name="image_path" class="form-control" readonly onclick="showFileModal()">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" onclick="showFileModal()">Browse</button>
                                        </span>
                                    </div>
                                </div>

                                <!-- Hidden file input -->
                                <input type="file" id="local_file_input" name="local_file_input" style="display: none;" accept="image/*" onchange="uploadLocalFile(this)">

                                <!-- Modal -->
                                <div id="fileModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="fileModalLabel">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                <h4 class="modal-title" id="fileModalLabel">Select Image</h4>
                                            </div>
                                            <div class="modal-body text-center">
                                                <button type="button" class="btn btn-primary btn-block" onclick="pickLocalFile()">Upload from Computer</button>
                                                <button type="button" class="btn btn-success btn-block" onclick="showFileManagerModal()">Choose from File Manager</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Placeholder -->
                                <div id="fileManagerModal" class="modal fade" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                <h4 class="modal-title" id="fileModalLabel">Choose Image</h4>
                                            </div>

                                            <div class="modal-body" style="max-height: 65vh; overflow-y: auto;" id="fileManagerModalBody">
                                                {{-- Your image grid with checkboxes --}}
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-primary" onclick="confirmImageSelection()">Select</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 mb">
                                    <label for="">Alumni City</label>
                                    <input class="form-control" placeholder="Add Alumni City" type="text" name="city"
                                        value="{{ old('city') }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Country</label>
                                    <select name="country" class="form-control">
                                        <option value="">-- Select Country --</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ old('country') == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-6 mb">
                                    <label for="">Date</label>
                                    <input class="form-control" type="date" name="date"
                                        value="{{ old('date', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                                </div>

                                <div class="col-lg-6 mb">
                                    <label for="">Courses</label>
                                    <select class="form-control" name="course_id">
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                                {{ $course->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Text</label>
                                    <textarea name="text" class="form-control text-editor"
                                        rows="2">{{ old('text') }}</textarea>
                                </div>
                                <div class="col-lg-12">
                                    <hr>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Youtube Video URL</label>
                                    <input type="url" name="asset_path" class="form-control"
                                        value="{{ old('asset_path') }}">
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
                reader.onload = function (e) {
                    document.getElementById('image_path').value = file.name;
                };
                reader.readAsDataURL(file);
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
                if (!selectedImageURLs.includes(url)) {
                    selectedImageURLs.push(url);
                }
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
                const joined = selectedImageURLs.join(',');
                $('#image_path').val(joined);
            } else {
                const url = selectedImageURLs.length > 0 ? selectedImageURLs[0] : '';
                $('#image_path').val(url);
            }

            $('#fileManagerModal').modal('hide');
        }
    </script>
@endpush