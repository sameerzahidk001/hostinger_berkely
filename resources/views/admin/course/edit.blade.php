@extends('admin.layout.app')
@section('title', 'Edit Course')
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
            <h2>{{ $course->title }}</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                </li>
                <li>
                    <a>Course</a>
                </li>
                <li>
                    <a>Edit</a>
                </li>
                <li class="active">
                    <strong>{{ $course->title }}</strong>
                </li>
            </ol>
        </div>
        <div class="col-lg-2">

        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">
                    <div class="ibox-title">
                        <h5>Banner Section</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content" style="display:none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="banner_section">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6 mb">
                                    <label for="">Title</label>
                                    <input class="form-control" placeholder="Add Banner Title"
                                        type="text" name="label[banner_title]"
                                        value="{{ old('label[banner_title]', $course->dynamicLabel?->banner_title) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="banner_section" class="form-control">
                                        <option value="1" {{ $course->banner_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->banner_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Sub Title</label>
                                    <input class="form-control" placeholder="Add Banner Sub Title" type="text" name="label[banner_sub_title]"
                                        value="{{ old('label[banner_sub_title]', $course->dynamicLabel?->banner_sub_title) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Sub Title Placement</label>
                                    <select name="label[banner_sub_title_placement]" class="form-control">
                                        <option value="0" {{ $course->dynamicLabel?->banner_sub_title_placement == 0 ? 'selected' : '' }}>Below Title
                                        </option>
                                        <option value="1" {{ $course->dynamicLabel?->banner_sub_title_placement == 1 ? 'selected' : '' }}>Above Title
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Description (Short Description)</label>
                                    <textarea name="short_description" class="form-control short_description text-editor"
                                        rows="2"
                                        value="{{old('short_description')}}">{{ $course->short_description }}</textarea>
                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Banner Image</label>

                                    <div class="input-group">
                                        <input type="text" id="banner_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'banner_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'banner_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="banner_img_path" name="label[banner_img_path]"
                                        value="{{ old('label.banner_img_path', $course->dynamicLabel?->banner_image) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="banner_img_file" name="label[banner_img_file]"
                                        accept="image/*" style="display:none">

                                    <div id="banner_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->dynamicLabel?->banner_image)
                                            <img src="{{ asset($course->dynamicLabel->banner_image) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->banner_image)
                                        <a href="{{ asset($course->dynamicLabel->banner_image) }}" target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'banner_image_alt',
                                        'name' => 'label[image_alts][banner_image]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'banner_image'),
                                    ])
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Breadcrumb</label>
                                    <select name="label[banner_breadcrumb]" class="form-control">
                                        <option value="0" {{ $course->dynamicLabel?->banner_breadcrumb == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                        <option value="1" {{ $course->dynamicLabel?->banner_breadcrumb == 1 ? 'selected' : '' }}>Active
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Button 1 Text</label>
                                    <input class="form-control" placeholder="Add Button Text" type="text" name="label[banner_button_1_text]"
                                        value="{{ old('label[banner_button_1_text]', $course->dynamicLabel?->banner_button_1_text) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Button 1 URL</label>
                                    <input class="form-control" placeholder="Add Button URL" type="text" name="label[banner_button_1_url]"
                                        value="{{ old('label[banner_button_1_url]', $course->dynamicLabel?->banner_button_1_url) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Button 2 Text</label>
                                    <input class="form-control" placeholder="Add Button Text" type="text" name="label[banner_button_2_text]"
                                        value="{{ old('label[banner_button_2_text]', $course->dynamicLabel?->banner_button_2_text) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Button 2 URL</label>
                                    <input class="form-control" placeholder="Add Button URL" type="text" name="label[banner_button_2_url]"
                                        value="{{ old('label[banner_button_2_url]', $course->dynamicLabel?->banner_button_2_url) }}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>

                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">
                    <div class="ibox-title">
                        <h5>Overview Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content" style="display:none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="overview_section">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6 mb">
                                    <label for="">Label (Overview Section Heading)</label>
                                    <input class="form-control" placeholder="add label to overview section heading"
                                        type="text" name="label[overview]"
                                        value="{{ old('label[overview]', $course->dynamicLabel?->overview) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="overview_section" class="form-control">
                                        <option value="1" {{ $course->overview_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->overview_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Course Title</label>
                                    <input class="form-control" placeholder="Course Title" type="text" name="title"
                                        value="{{ old('title', $course->title) }}">
                                    @error('title')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Course Short Title</label>
                                    <input class="form-control" placeholder="Course Short Title" type="text"
                                        name="short_name" value="{{ old('short_name', $course->short_name) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Slug</label>
                                    <input type="text" class="form-control" name="slug"
                                        value="{{ old('slug', $course->slug) }}">
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Assigned Schools</label>
                                    <ul class="todo-list small-list ui-sortable" style="max-height:200px; overflow-y: scroll;">
                                        @foreach($schools as $key => $school)
                                            <li>
                                                <input type="checkbox" id="input-{{ $key }}" name="schools[]"
                                                    value="{{ $school->id }}" {{ $course->schools->contains('id', $school->id) ? 'checked' : '' }}>
                                                <label class="m-l-xs" for="input-{{ $key }}"> {{ $school->name }}</label>
                                            </li>
                                        @endforeach
                                    </ul>

                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Assigned Categories</label>
                                    <ul class="todo-list small-list ui-sortable" style="max-height:200px; overflow-y: scroll;">
                                        @foreach($categories as $key => $category)
                                            <li>
                                                <input type="checkbox" id="input-{{ $key }}" name="categories[]"
                                                    value="{{ $category->id }}" {{ $course->categories->contains('id', $category->id) ? 'checked' : '' }}>
                                                <label class="m-l-xs" for="input-{{ $key }}"> {{ $category->name }}</label>
                                            </li>
                                        @endforeach
                                    </ul>

                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Detailed Description</label>
                                    <textarea name="description" class="form-control description text-editor" rows="2"
                                        value="{{old('description')}}">{{ $course->description }}</textarea>
                                </div>


                                <div class="col-lg-12">
                                    <label for="">Label (Offered By)</label>
                                    <input class="form-control" placeholder="Offered by" type="text"
                                        name="label[offered_by]"
                                        value="{{ old('label[offered_by]', $course->dynamicLabel?->offered_by) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <!-- <label class="mb-1">Offered By</label> -->
                                    <textarea name="offered_by[institute]" class="form-control  text-editor" rows="2"
                                        value="{{old('offered_by[institute]')}}">{{ optional($course->offered_by)['institute'] }}</textarea>
                                </div>
                                <div class="col-lg-12">
                                    <label for="">Label (Head Office)</label>
                                    <input class="form-control" placeholder="Head Office" type="text"
                                        name="label[head_office]"
                                        value="{{ old('label[head_office]', $course->dynamicLabel?->head_office) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <!-- <label class="mb-1">Head Office</label> -->
                                    <textarea name="offered_by[head_office]" id="head_office"
                                        class="form-control text-editor" placeholder="Detailed Description"
                                        rows="2">{{ old('offered_by.head_office', optional($course->offered_by)['head_office']) }}</textarea>
                                </div>

                                <div class="col-lg-12">
                                    <label for="">Label (Members)</label>
                                    <input class="form-control" placeholder="Members" type="text" name="label[members]"
                                        value="{{ old('label[members]', $course->dynamicLabel?->members) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <!-- <label class="mb-1">Members</label> -->
                                    <textarea name="offered_by[members]" class="form-control text-editor" rows="2"
                                        value="{{old('offered_by[members]')}}">{{ optional($course->offered_by)['members'] }}</textarea>
                                </div>
                                <div class="col-lg-12">
                                    <label for="">Label (Founded In)</label>
                                    <input class="form-control" placeholder="Members" type="text" name="label[founded_in]"
                                        value="{{ old('label[founded_in]', $course->dynamicLabel?->founded_in) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <!-- <label class="mb-1">Founded In</label> -->
                                    <textarea name="offered_by[founded_in]" class="form-control text-editor" rows="2"
                                        value="{{old('offered_by[founded_in]')}}">{{ optional($course->offered_by)['founded_in'] }}</textarea>
                                </div>

                                <div class="col-lg-12">
                                    <label for="">Label (Vision & Mission)</label>
                                    <input class="form-control" placeholder="Members" type="text"
                                        name="label[vission_mission]"
                                        value="{{ old('label[vission_mission]', $course->dynamicLabel?->vission_mission) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <!-- <label class="mb-1">Vision & Mission</label> -->
                                    <textarea class="form-control text-editor" id="vision_and_mission"
                                        name="vision_and_mission"
                                        value="{{old('vision_and_mission')}}">{{ $course->vision_and_mission }}</textarea>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Overview Image</label>

                                    <!-- visible display (read-only) -->
                                    <div class="input-group">
                                        <input type="text" id="overview_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'overview_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'overview_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL field (from File-Manager) -->
                                    <input type="hidden" id="overview_img_path" name="overview_img_path"
                                        value="{{ old('overview_img_path', $course->overview_img) }}">

                                    <!-- actual file input (kept for real uploads) -->
                                    <input type="file" id="overview_img_file" name="overview_img" accept="image/*"
                                        style="display:none">

                                    <!-- tiny preview -->
                                    <div id="overview_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->overview_img)
                                            <img src="{{ asset($course->overview_img) }}" alt=""
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->overview_img)
                                        <a class="small" href="{{ asset($course->overview_img) }}" target="_blank">Current</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'overview_img_alt',
                                        'name' => 'image_alts[overview_img]',
                                        'value' => data_get($course->image_alts, 'overview_img'),
                                    ])
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Overview Video URL</label>
                                    <input type="url" class="form-control" id="overview_video_url" name="overview_video_url"
                                        placeholder="add overview video URL"
                                        value="{{ old('overview_video_url', $course->overview_video_url) }}">
                                    @if($course->overview_video_url)

                                        <a href="{{ asset($course->overview_video_url) }}" target="_blank">Show</a>
                                    @endif
                                </div>

                            </div>



                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>

                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">
                    <div class="ibox-title">
                        <h5>Eligibility Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content" style="display:none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="eligibility_section">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6 mb">
                                    <label for="">Label (Eligibility Section Heading)</label>
                                    <input class="form-control" placeholder="add label to eligibility section heading"
                                        type="text" name="label[eligibility]"
                                        value="{{ old('label[eligibility]', $course->dynamicLabel?->eligibility) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="eligibility_section" class="form-control">
                                        <option value="1" {{ $course->eligibility_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->eligibility_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Who is eligible to register for examination?</label>
                                    <textarea class="form-control text-editor" id="eligibility" name="eligibility"
                                        value="{{old('eligibility')}}">{{ $course->eligibility }}</textarea>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>

                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Who can do Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="who_can_do_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">
                                    <label for="">Label (Who can do)</label>
                                    <input class="form-control" placeholder="add label to Who can do" type="text"
                                        name="label[who_can_do]"
                                        value="{{ old('label[who_can_do]', $course->dynamicLabel?->who_can_do) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="who_can_do_section" class="form-control">
                                        <option value="1" {{ $course->who_can_do_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->who_can_do_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Label (Subheading 1)</label>
                                    <input class="form-control" placeholder="add subheading 1 i.e interested to learn"
                                        type="text" name="label[who_can_do_subh01]"
                                        value="{{ old('label[who_can_do_subh01]', $course->dynamicLabel?->who_can_do_subh01) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Label (Subheading 2)</label>
                                    <input class="form-control" placeholder="add subheading 1 i.e following designations"
                                        type="text" name="label[who_can_do_subh02]"
                                        value="{{ old('label[who_can_do_subh02]', $course->dynamicLabel?->who_can_do_subh02) }}">
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Label (Section Image)</label>

                                    <div class="input-group">
                                        <input type="text" id="who_can_do_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'who_can_do_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'who_can_do_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="who_can_do_img_path" name="label[who_can_do_img_path]"
                                        value="{{ old('label.who_can_do_img_path', $course->dynamicLabel?->who_can_do_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="who_can_do_img_file" name="label[who_can_do_img]"
                                        accept="image/*" style="display:none">

                                    <div id="who_can_do_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->dynamicLabel?->who_can_do_img)
                                            <img src="{{ asset($course->dynamicLabel->who_can_do_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->who_can_do_img)
                                        <a href="{{ asset($course->dynamicLabel->who_can_do_img) }}" target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'who_can_do_img_alt',
                                        'name' => 'label[image_alts][who_can_do_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'who_can_do_img'),
                                    ])
                                </div>

                                <div class="col-lg-6" style="margin:0;">
                                    <div style="display:flex; justify-content: space-between">
                                        <p>Who can do</p>
                                        <button type="button" class="btn-primary btn btn-xs"
                                            id="addColBtnInterestedToLearn"><i class="fa fa-plus"></i> Add</button>
                                    </div>
                                    <hr style="margin:0;">
                                </div>

                                <div class="col-lg-6" style="margin:0;">
                                    <div style="display:flex; justify-content: space-between">
                                        <p>Designations to pursue</p>
                                        <button type="button" class="btn-primary btn btn-xs"
                                            id="addColBtnDesignationToPursue"><i class="fa fa-plus"></i> Add</button>
                                    </div>
                                    <hr style="margin:0;">
                                </div>

                                <div id="InterestedToLearnContainer" class="col-lg-6" style="margin:0;">
                                    @foreach($course->who_can_do['interested_to_learn'] ?? [] as $index => $data)
                                        <div class="InterestedToLearnCol" style="position: relative; margin-bottom: 8px;">
                                            <label class="mb-1">Interested to learn</label>
                                            <input type="text" class="form-control"
                                                name="who_can_do[interested_to_learn][{{ $index }}]" value="{{ $data }}">
                                            <button type="button" class="btn btn-danger btn-xs"
                                                style="position: absolute; top: 30px; right: 5px;"
                                                onclick="removeInterestedToLearn(this)">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <!-- <div id="InterestedToLearnContainer" style="width:50%;">
                                                                                    @foreach($course->who_can_do['interested_to_learn'] ?? [] as $index => $data)
                                                                                        <div class="InterestedToLearnCol" style="position: relative; margin-bottom: 8px;">
                                                                                            <label class="mb-1">Interested to learn</label>
                                                                                            <input type="text" class="form-control" name="who_can_do[interested_to_learn][{{ $index }}]" value="{{ $data }}">
                                                                                            <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeInterestedToLearn(this)">
                                                                                                <i class="fa fa-times"></i>
                                                                                            </button>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div> -->
                                <div id="DesignationToPursueContainer" class="col-lg-6" style="margin:0;">
                                    @foreach(@$course->who_can_do['designation'] ?? [] as $index => $data)
                                        <div class="DesignationToPursueCol" style="position: relative; margin-bottom: 8px;">
                                            <label class="mb-1">Designation</label>
                                            <input type="text" class="form-control" name="who_can_do[designation][{{ $index }}]"
                                                value="{{ $data }}">
                                            <button type="button" class="btn btn-danger btn-xs"
                                                style="position: absolute; top: 30px; right: 5px;"
                                                onclick="removeDesignationToPursue(this)">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <!-- <div id="DesignationToPursueContainer" style="width:50%;">
                                                                                    @foreach(@$course->who_can_do['designation'] ?? [] as $index => $data)
                                                                                    <div class="DesignationToPursueCol" style="position: relative; margin-bottom: 8px;">
                                                                                        <label class="mb-1">Designation</label>
                                                                                        <input type="text" class="form-control" name="who_can_do[designation][{{ $index }}]" value="{{ $data }}">
                                                                                        <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeDesignationToPursue(this)">
                                                                                            <i class="fa fa-times"></i>
                                                                                        </button>
                                                                                    </div>
                                                                                    @endforeach
                                                                                </div> -->

                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Custom Videos Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="custom_videos_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">

                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="custom_videos_section" class="form-control">
                                        <option value="1" {{ $course->custom_videos_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->custom_videos_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Label (Heading)</label>
                                    <input name="label[custom_videos_heading]" class="form-control"
                                        placeholder="add label to section heading"
                                        value="{{ old('label[custom_videos_heading]', $course->dynamicLabel?->custom_videos_heading) }}">
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Description/Overview</label>
                                    <textarea type="text" class="form-control text-editor" name="custom_videos_desc"
                                        value="{{ old('custom_videos_desc', $course->custom_videos_desc) }}"
                                        row="2">{{ $course->custom_videos_desc }}</textarea>
                                </div>

                                <div class="col-lg-12 mb" style="text-align:right;">
                                    <button type="button" id="add_video_to_custom_Section" class="btn btn-primary btn-md"><i
                                            class="fa fa-check"></i>&nbsp; Add</button>
                                </div>
                                <div id="videos-row">
                                    @if(!empty($course->custom_videos)) <!-- Assuming you're fetching the course -->
                                        @foreach(json_decode($course->custom_videos, true) as $index => $video)
                                            <div class="video-item">
                                                <div class="col-lg-6 mb">
                                                    <label class="mb-1">Title</label>
                                                    <!-- Prepopulate the title -->
                                                    <input type="text" class="form-control"
                                                        name="custom_videos[{{ $index }}][title]" value="{{ $video['title'] }}"
                                                        required>
                                                </div>

                                                <div class="col-lg-5 mb">
                                                    <label class="mb-1">Video URL</label>
                                                    <!-- Input for video URL -->
                                                    <input type="url" class="form-control mb"
                                                        name="custom_videos[{{ $index }}][path]" value="{{ $video['path'] }}"
                                                        placeholder="Enter video URL" required>

                                                    <!-- If the video URL exists, show the video link -->
                                                    @if(!empty($video['path']))
                                                        <a href="{{ $video['path'] }}" target="_blank">View Video</a>
                                                    @endif
                                                </div>

                                                <div class="col-lg-1 mb">
                                                    <!-- Delete button -->
                                                    <button type="button"
                                                        class="del_video_from_custom_Section btn btn-danger btn-md"
                                                        style="margin-top:22px;">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Learning Methodology Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="learning_methodology_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">
                                    <label for="">Label (Learning Methodology)</label>
                                    <input class="form-control" placeholder="add label to learning methodology heading"
                                        type="text" name="label[learning_methodology]"
                                        value="{{ old('label[learning_methodology]', $course->dynamicLabel?->learning_methodology) }}">
                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="learning_methodology_section" class="form-control">
                                        <option value="1" {{ $course->learning_methodology_section == 1 ? 'selected' : '' }}>
                                            Active</option>
                                        <option value="0" {{ $course->learning_methodology_section == 0 ? 'selected' : '' }}>
                                            Disabled</option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Label (Learning Methodology Overview)</label>
                                    <textarea name="label[learning_methodology_overview]" class="form-control text-editor"
                                        placeholder="add Learning Methodology Overview"
                                        rows="2">{{ $course->dynamicLabel?->learning_methodology_overview }}</textarea>
                                </div>

                                <div class="col-lg-6">
                                    <label for="">Label (Lectures Box Heading)</label>
                                    <input class="form-control" placeholder="add label to lectures heading" type="text"
                                        name="label[lectures]"
                                        value="{{ old('label[lectures]', $course->dynamicLabel?->lectures) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Label (Lectures Box Image/video)</label>

                                    <div class="input-group">
                                        <input type="text" id="lectures_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'lectures_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'lectures_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="lectures_img_path" name="label[lectures_img_path]"
                                        value="{{ old('label.lectures_img_path', $course->dynamicLabel?->lectures_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="lectures_img_file" name="label[lectures_img]" accept="image/*"
                                        style="display:none">

                                    <div id="lectures_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->dynamicLabel?->lectures_img)
                                            <img src="{{ asset($course->dynamicLabel->lectures_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->lectures_img)
                                        <a href="{{ asset($course->dynamicLabel->lectures_img) }}" target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'lectures_img_alt',
                                        'name' => 'label[image_alts][lectures_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'lectures_img'),
                                    ])
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Label (Lectures Box Description)</label>
                                    <textarea name="label[lectures_des]" class="form-control text-editor"
                                        placeholder="add Lectures Box Description"
                                        rows="2">{{ $course->dynamicLabel?->lectures_des }}</textarea>
                                </div>

                                <div class="col-lg-6">
                                    <label for="">Label (Practice Session Box Heading)</label>
                                    <input class="form-control" placeholder="add label to lectures heading" type="text"
                                        name="label[practice_session]"
                                        value="{{ old('label[practice_session]', $course->dynamicLabel?->practice_session) }}">
                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Label (Practice Session Box Image/Video)</label>

                                    <div class="input-group">
                                        <input type="text" id="practice_session_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'practice_session_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'practice_session_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="practice_session_img_path"
                                        name="label[practice_session_img_path]"
                                        value="{{ old('label.practice_session_img_path', $course->dynamicLabel?->practice_session_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="practice_session_img_file" name="label[practice_session_img]"
                                        accept="image/*" style="display:none">

                                    <div id="practice_session_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->dynamicLabel?->practice_session_img)
                                            <img src="{{ asset($course->dynamicLabel->practice_session_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->practice_session_img)
                                        <a href="{{ asset($course->dynamicLabel->practice_session_img) }}"
                                            target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'practice_session_img_alt',
                                        'name' => 'label[image_alts][practice_session_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'practice_session_img'),
                                    ])
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Label (Practice Session Box Description)</label>
                                    <textarea name="label[practice_session_des]" class="form-control text-editor"
                                        placeholder="add Practice Session Box Description"
                                        rows="2">{{ $course->dynamicLabel?->practice_session_des }}</textarea>
                                </div>

                                <div class="col-lg-6">
                                    <label for="">Label (Mock Examination Box Heading)</label>
                                    <input class="form-control" placeholder="add Mock Examination Box Heading" type="text"
                                        name="label[mock_examination]"
                                        value="{{ old('label[mock_examination]', $course->dynamicLabel?->mock_examination) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Label (Mock Examination Box Image/Video)</label>

                                    <div class="input-group">
                                        <input type="text" id="mock_examination_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'mock_examination_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'mock_examination_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="mock_examination_img_path"
                                        name="label[mock_examination_img_path]"
                                        value="{{ old('label.mock_examination_img_path', $course->dynamicLabel?->mock_examination_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="mock_examination_img_file" name="label[mock_examination_img]"
                                        accept="image/*" style="display:none">

                                    <div id="mock_examination_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->dynamicLabel?->mock_examination_img)
                                            <img src="{{ asset($course->dynamicLabel->mock_examination_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->mock_examination_img)
                                        <a href="{{ asset($course->dynamicLabel->mock_examination_img) }}"
                                            target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'mock_examination_img_alt',
                                        'name' => 'label[image_alts][mock_examination_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'mock_examination_img'),
                                    ])
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Label (Mock Examination Box Description)</label>
                                    <textarea name="label[mock_examination_description]" class="form-control text-editor"
                                        placeholder="add Mock Examination Box Description"
                                        rows="2">{{ $course->dynamicLabel?->mock_examination_description }}</textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Performance Standard Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="performance_standard_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">
                                    <label for="">Label (Performance Standard Section)</label>
                                    <input class="form-control" placeholder="add label to Performance Standard Section"
                                        type="text" name="performance_standard_heading"
                                        value="{{ old('performance_standard_heading', $course->performance_standard_heading) }}">
                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="performance_standard_section" class="form-control">
                                        <option value="1" {{ $course->performance_standard_section == 1 ? 'selected' : '' }}>
                                            Active</option>
                                        <option value="0" {{ $course->performance_standard_section == 0 ? 'selected' : '' }}>
                                            Disabled</option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Performance Standard Description</label>
                                    <textarea name="performance_standard_description" class="form-control text-editor"
                                        placeholder="add Performance Standard Description"
                                        rows="2">{{ $course->performance_standard_description }}</textarea>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Career Path Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="career_path_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">
                                    <label for="">Label (Career Path Section)</label>
                                    <input class="form-control" placeholder="add label to Career Path Section" type="text"
                                        name="label[career_path_heading]"
                                        value="{{ old('label[career_path_heading]', $course->dynamicLabel?->career_path_heading) }}">
                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="career_path_section" class="form-control">
                                        <option value="1" {{ $course->career_path_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->career_path_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Career Path Description</label>
                                    <textarea name="career_path" class="form-control text-editor"
                                        placeholder="add Career Path Description"
                                        rows="2">{{ $course->career_path }}</textarea>
                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Label (Career Path Image)</label>

                                    <div class="input-group">
                                        <input type="text" id="career_path_section_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'career_path_section_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'career_path_section_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="career_path_section_img_path"
                                        name="label[career_path_section_img_path]"
                                        value="{{ old('label.career_path_section_img_path', $course->dynamicLabel?->career_path_section_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="career_path_section_img_file"
                                        name="label[career_path_section_img]" accept="image/*" style="display:none">

                                    <div id="career_path_section_img_preview" class="picker-preview"
                                        style="margin-top:6px;">
                                        @if($course->dynamicLabel?->career_path_section_img)
                                            <img src="{{ asset($course->dynamicLabel->career_path_section_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->career_path_section_img)
                                        <a href="{{ asset($course->dynamicLabel->career_path_section_img) }}"
                                            target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'career_path_section_img_alt',
                                        'name' => 'label[image_alts][career_path_section_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'career_path_section_img'),
                                    ])
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Exam Information Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="exam_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">

                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="exam_section" class="form-control">
                                        <option value="1" {{ $course->exam_section == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $course->exam_section == 0 ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Label (What Are The Exam Information?)</label>
                                    <input class="form-control" placeholder="add label Exam Information Heading" type="text"
                                        name="label[exam_information]"
                                        value="{{ old('label[exam_information]', $course->dynamicLabel?->exam_information) }}">
                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Label (Exam Section Image)</label>

                                    <div class="input-group">
                                        <input type="text" id="exam_information_section_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'exam_information_section_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'exam_information_section_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="exam_information_section_img_path"
                                        name="label[exam_information_section_img_path]"
                                        value="{{ old('label.exam_information_section_img_path', $course->dynamicLabel?->exam_information_section_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="exam_information_section_img_file"
                                        name="label[exam_information_section_img]" accept="image/*" style="display:none">

                                    <div id="exam_information_section_img_preview" class="picker-preview"
                                        style="margin-top:6px;">
                                        @if($course->dynamicLabel?->exam_information_section_img)
                                            <img src="{{ asset($course->dynamicLabel->exam_information_section_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->exam_information_section_img)
                                        <a href="{{ asset($course->dynamicLabel->exam_information_section_img) }}"
                                            target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'exam_information_section_img_alt',
                                        'name' => 'label[image_alts][exam_information_section_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'exam_information_section_img'),
                                    ])
                                </div>

                                <div class="col-lg-6 mb">
                                    <label for="">Label (Exam Section Video URL)</label>
                                    <input class="form-control" type="url" name="label[exam_information_section_video_url]"
                                        value="{{ old('label[exam_information_section_video_url]', $course->dynamicLabel?->exam_information_section_video_url) }}">
                                    @if($course->dynamicLabel?->exam_information_section_video_url)
                                        <a href="{{ asset($course->dynamicLabel->exam_information_section_video_url) }}"
                                            target="_blank">
                                            Current Video
                                        </a>
                                    @endif
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Custom Description</label>
                                    <textarea class="form-control text-editor" id="exam_info_custom_01"
                                        placeholder="Add Custom Description" name="exam_info_custom_01" row="4"
                                        value="{{ old('exam_info_custom_01', $course->exam_info_custom_01) }}">{{ $course->exam_info_custom_01 }}</textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label for="">Label (Exam Format & Duration Heading)</label>
                                    <input class="form-control" placeholder="add label Exam Format Heading" type="text"
                                        name="label[exam_format_duration]"
                                        value="{{ old('label[exam_format_duration]', $course->dynamicLabel?->exam_format_duration) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Exam Format & Duration Overview</label>
                                    <textarea class="form-control text-editor" id="course_exam_format_duration_overview"
                                        placeholder="Add Exam Format & Duration Overview"
                                        name="course_exam_format_duration_overview" row="4"
                                        value="{{ old('label[course_exam_format_duration_overview]', $course->course_exam_format_duration_overview) }}">{{ $course->course_exam_format_duration_overview }}</textarea>
                                </div>

                                <div class="col-lg-4 mb">
                                    <label for="">Label (Part/Module Heading)</label>
                                    <input class="form-control" placeholder="add label Part/Module Heading" type="text"
                                        name="label[exam_format_duration_01]"
                                        value="{{ old('label[exam_format_duration_01]', $course->dynamicLabel?->exam_format_duration_01) }}">
                                </div>
                                <div class="col-lg-4 mb">
                                    <label for="">Label (Exam Format Heading)</label>
                                    <input class="form-control" placeholder="add label Exam Format Heading" type="text"
                                        name="label[exam_format_duration_02]"
                                        value="{{ old('label[exam_format_duration_02]', $course->dynamicLabel?->exam_format_duration_02) }}">
                                </div>
                                <div class="col-lg-4 mb">
                                    <label for="">Label (Exam Duration)</label>
                                    <input class="form-control" placeholder="add label Exam Duration Heading" type="text"
                                        name="label[exam_format_duration_03]"
                                        value="{{ old('label[exam_format_duration_03]', $course->dynamicLabel?->exam_format_duration_03) }}">
                                </div>

                                <div class="col-lg-12">
                                    <label for="">Label (Exam Dates)</label>
                                    <input class="form-control" placeholder="add label Exam Dates" type="text"
                                        name="label[exam_dates]"
                                        value="{{ old('label[exam_dates]', $course->dynamicLabel?->exam_dates) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <label for="">Exam Dates Description</label>
                                    <textarea name="exam_dates" class="form-control text-editor"
                                        placeholder="add Exam Dates Description"
                                        rows="2">{{ $course->exam_dates }}</textarea>
                                </div>

                                <div class="col-lg-12">
                                    <label for="">Label (Exam Registration Deadline)</label>
                                    <input class="form-control" placeholder="add label Exam Registration Deadline"
                                        type="text" name="label[exam_dates_registration]"
                                        value="{{ old('label[exam_dates_registration]', $course->dynamicLabel?->exam_dates_registration) }}">
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Exam Registration Deadline</label>
                                    <textarea name="exam_reg_deadline" class="form-control text-editor"
                                        placeholder="add Exam Registration Deadline"
                                        rows="2">{{ $course->exam_reg_deadline }}</textarea>
                                </div>

                                <div class="col-lg-12">
                                    <label for="">Label (Passing Criteria)</label>
                                    <input class="form-control" placeholder="add label Passing Criteria" type="text"
                                        name="label[passing_criteria]"
                                        value="{{ old('label[passing_criteria]', $course->dynamicLabel?->passing_criteria) }}">
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Passing Criteria Description</label>
                                    <textarea name="exam_passing_criteria" class="form-control text-editor"
                                        placeholder="add Passing Criteria Description"
                                        rows="2">{{ $course->exam_passing_criteria }}</textarea>
                                </div>

                                <div class="col-lg-12">
                                    <label for="">Label (EXAM LOCATIONS)</label>
                                    <input class="form-control" placeholder="add label Exam Locations" type="text"
                                        name="label[exam_location]"
                                        value="{{ old('label[exam_location]', $course->dynamicLabel?->exam_location) }}">
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Exam Locations Description</label>
                                    <textarea name="exam_location_paragraph" class="form-control text-editor"
                                        placeholder="add Exam Locations Description"
                                        rows="2">{{ $course->exam_location_paragraph }}</textarea>
                                </div>


                                <div class="col-lg-6 mb">
                                    <label>Exam Locations/Cities</label>
                                </div>
                                <div class="col-lg-6 mb">
                                    <button type="button" class="btn-primary btn btn-xs" id="addColBtnExamLocations">
                                        <i class="fa fa-plus"></i> Add
                                    </button>
                                </div>


                                <div id="ExamLocationsContainer">
                                    @foreach($course->exam_location ?? [] as $index => $data)
                                        <div class="col-lg-4 ExamLocationsCol mb" style="position: relative;">
                                            <label class="mb-1">Exam Locations {{ $index + 1 }}</label>
                                            <input type="text" class="form-control" name="exam_location[]" value="{{ $data}}">
                                            <button type="button" class="btn btn-danger btn-xs"
                                                style="position: absolute; top: 30px; right: 15px;"
                                                onclick="removeExamLocation(this)">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>


                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Success Stories & Alumni Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="success_stories_section">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <label for="">Alumni Benefits Description</label>
                                    <textarea name="alumni_benefits_description" class="form-control text-editor"
                                        placeholder="add Alumni Benefits Description"
                                        rows="2">{{ $course->alumni_benefits_description }}</textarea>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Label (Success Stories Image)</label>

                                    <div class="input-group">
                                        <input type="text" id="learner_stories_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'learner_stories_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'learner_stories_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="learner_stories_img_path"
                                        name="label[learner_stories_img_path]"
                                        value="{{ old('label.learner_stories_img_path', $course->dynamicLabel?->learner_stories_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="learner_stories_img_file" name="label[learner_stories_img]"
                                        accept="image/*" style="display:none">

                                    <div id="learner_stories_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->dynamicLabel?->learner_stories_img)
                                            <img src="{{ asset($course->dynamicLabel->learner_stories_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->learner_stories_img)
                                        <a href="{{ asset($course->dynamicLabel->learner_stories_img) }}"
                                            target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'learner_stories_img_alt',
                                        'name' => 'label[image_alts][learner_stories_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'learner_stories_img'),
                                    ])
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Label ( Success Stories )</label>
                                    <input class="form-control" placeholder="add label to Success Stories" type="text"
                                        name="label[success_stories]"
                                        value="{{ old('label[success_stories]', $course->dynamicLabel?->success_stories) }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 mb">
                                    <label for="">Label ( Success Stories Link )</label>
                                    <input class="form-control" placeholder="add label to Success Stories Link"
                                        type="text" name="label[success_stories_link]"
                                        value="{{ old('label[success_stories_link]', $course->dynamicLabel?->success_stories_link) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label for="">Label ( Success Stories Link Text )</label>
                                    <input class="form-control" placeholder="add label to Success Stories Link Text"
                                        type="text" name="label[success_stories_link_text]"
                                        value="{{ old('label[success_stories_link_text]', $course->dynamicLabel?->success_stories_link_text) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <label for="">Label ( Alumni Benefits )</label>
                                        <textarea name="label[alumni_benefits]" class="form-control text-editor"
                                        placeholder="add label to Alumni Benefits"
                                        rows="2">{!! old('label[alumni_benefits]', $course->dynamicLabel?->alumni_benefits) !!}</textarea>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="success_stories_section" class="form-control">
                                        <option value="1" {{ $course->success_stories == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->success_stories == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>Custom Section 01</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="custom_section_01">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">

                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="custom_section_01" class="form-control">
                                        <option value="1" {{ $course->custom_section_01 == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->custom_section_01 == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>

                                <div class="col-lg-12">
                                    <label for="">Label (Custom Section 01)</label>
                                    <input class="form-control" placeholder="add label to Custom Section 01" type="text"
                                        name="label[custom_section_01]"
                                        value="{{ old('label[custom_section_01]', $course->dynamicLabel?->custom_section_01) }}">
                                </div>
                                <div class="col-lg-12 mb">
                                    <!-- <label for="">Description Text</label> -->
                                    <textarea name="custom_section_01_description" class="form-control text-editor"
                                        placeholder="add Description Text"
                                        rows="2">{{ $course->custom_section_01_description }}</textarea>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                    <div class="ibox-title">
                        <h5>What You Earn/Benifits Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display:none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="benefits_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">
                                    <label for="">Label (What You Earn)</label>
                                    <input class="form-control" placeholder="add label to What You Earn heading" type="text"
                                        name="label[what_you_earn]"
                                        value="{{ old('label[what_you_earn]', $course->dynamicLabel?->what_you_earn) }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="benefits_section" class="form-control">
                                        <option value="1" {{ $course->benefits_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->benefits_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label for="">Section Overview</label>
                                    <textarea class="form-control text-editor" id="label[what_you_earn_des]"
                                        name="label[what_you_earn_des]"
                                        value="{{old('label[what_you_earn_des]')}}">{{ $course->dynamicLabel?->what_you_earn_des }}</textarea>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Section Image/Certificate</label>

                                    <div class="input-group">
                                        <input type="text" id="what_you_earn_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'what_you_earn_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'what_you_earn_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL (kept inside your label[] array) -->
                                    <input type="hidden" id="what_you_earn_img_path" name="label[what_you_earn_img_path]"
                                        value="{{ old('label.what_you_earn_img_path', $course->dynamicLabel?->what_you_earn_img) }}">

                                    <!-- actual file upload, keep original key -->
                                    <input type="file" id="what_you_earn_img_file" name="label[what_you_earn_img]"
                                        accept="image/*" style="display:none">

                                    <div id="what_you_earn_img_preview" class="picker-preview" style="margin-top:6px;">
                                        @if($course->dynamicLabel?->what_you_earn_img)
                                            <img src="{{ asset($course->dynamicLabel->what_you_earn_img) }}"
                                                style="max-height:60px;border-radius:4px;">
                                        @endif
                                    </div>

                                    @if($course->dynamicLabel?->what_you_earn_img)
                                        <a href="{{ asset($course->dynamicLabel->what_you_earn_img) }}" target="_blank">Current
                                            Image</a>
                                    @endif
                                    @include('admin.partials.image-alt-input', [
                                        'id' => 'what_you_earn_img_alt',
                                        'name' => 'label[image_alts][what_you_earn_img]',
                                        'value' => data_get($course->dynamicLabel?->image_alts, 'what_you_earn_img'),
                                    ])
                                </div>

                                <div class="col-lg-12 mb" style="margin:0;text-align: right;">
                                    <button type="button" class="btn-primary btn btn-xs" id="addRowBtn"><i
                                            class="fa fa-plus"></i> Add</button>
                                </div>

                                <div id="benefitsContainer">
                                    @foreach($course->benifits ?? [] as $key => $benifit)
                                        <div class=" benefitRow">
                                            <div class="col-lg-12">
                                                <label class="mb-1">Title</label>
                                                <input type="text" name="benifits[{{$key}}][title]" class="form-control"
                                                    value="{{ $benifit['title'] }}">
                                            </div>
                                            <div class="col-lg-12 mb">
                                                <label class="mb-1">Description</label>
                                                <textarea style="position: relative;" class="form-control text-editor"
                                                    id="description"
                                                    name="benifits[{{$key}}][description]">{{ $benifit['description'] }}</textarea>
                                                <button style="position: absolute;right:16px;bottom:0" type="button"
                                                    class="btn-danger btn btn-xs deleteRowBtn"><i class="fa fa-trash"></i>
                                                    Delete</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>


                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">

                    <div class="ibox-title">
                        <h5>Contact us Section</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content" style="display: none;">
                        <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="course_module" value="contact_us_section">
                            @csrf
                            @method('PUT')
                            <div class="row">

                                <div class="col-lg-6 mb">

                                </div>

                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Status</label>
                                    <select name="contact_us_section" class="form-control">
                                        <option value="1" {{ $course->contact_us_section == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ $course->contact_us_section == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label for="">Section Text</label>
                                    <textarea name="contact_us_text" class="form-control text-editor"
                                        placeholder="add Contact us text details"
                                        rows="2">{{ $course->contact_us_text }}</textarea>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Registration Iframe</label>
                                    <input type="text" class="form-control" name="reg_iframe"
                                        value="{{ old('reg_iframe', $course->reg_iframe) }}">
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- GLOBAL: hidden file input used by whichever picker is active -->
    <input type="file" id="global_media_input" style="display:none" accept="image/*">

    <!-- Pick-source modal -->
    <div id="mediaPickSourceModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="mediaPickSourceModalLabel">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="mediaPickSourceModalLabel">Select Media</h4>
                </div>
                <div class="modal-body text-center">
                    <button type="button" class="btn btn-primary btn-block" onclick="MediaPicker.pickLocal()">Upload from
                        Computer</button>
                    <button type="button" class="btn btn-success btn-block" onclick="MediaPicker.openFileManager()">Choose
                        from File Manager</button>
                </div>
            </div>
        </div>
    </div>

    <!-- File-Manager modal -->
    <div id="mediaFileManagerModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="mediaFileManagerModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button class="close" type="button" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="mediaFileManagerModalLabel">Choose Media</h4>
                </div>
                <div class="modal-body" style="max-height: 65vh; overflow-y:auto;" id="mediaFileManagerBody">
                    <div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" onclick="MediaPicker.confirm()">Select</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!-- <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', (event) => {
                                                            document.querySelectorAll('.text-editor').forEach((editorElement) => {
                                                                ClassicEditor.create(editorElement)
                                                                    .catch(error => {
                                                                        console.error(error);
                                                                    });
                                                            });
                                                        });
                                                    </script> -->
    <!-- <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', (event) => {
                                                            document.querySelectorAll('.text-editor').forEach((editorElement) => {
                                                                ClassicEditor.create(editorElement, {
                                                                    link: {
                                                                        addTargetToExternalLinks: true, // Automatically add target="_blank" to external links
                                                                        decorators: {
                                                                            openInNewTab: {
                                                                                mode: 'manual',
                                                                                label: 'Open in a new tab',
                                                                                attributes: {
                                                                                    target: '_blank',
                                                                                    rel: 'noopener noreferrer' // Recommended for security reasons
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                })
                                                                .catch(error => {
                                                                    console.error(error);
                                                                });
                                                            });
                                                        });
                                                    </script> -->

    <script>
        $(document).ready(function () {
            // let index = 1;
            let index = $('.benefitRow').length;
            $('#addRowBtn').click(function () {
                let newRow = `<div class=" benefitRow">
                                                                    <div class="col-lg-12">
                                                                        <label class="mb-1">Title</label>
                                                                        <input type="text" name="benifits[${index}][title]" class="form-control" placeholder="Add Title to Benefit Section">
                                                                    </div>
                                                                    <div class="col-lg-12 mb">
                                                                        <label class="mb-1">Description</label>
                                                                        <textarea style="position: relative;" class="form-control text-editor" id="description" placeholder="Add Description to Benefit Section Title" name="benifits[${index}][description]"></textarea>
                                                                        <button style="position: absolute;right:16px;bottom:0" type="button" class="btn-danger btn btn-xs deleteRowBtn"><i class="fa fa-trash"></i> Delete</button>
                                                                    </div>
                                                                </div>`;
                $('#benefitsContainer').append(newRow);
                index++;
            });

            $(document).on('click', '.deleteRowBtn', function () {
                $(this).closest('.benefitRow').remove();
                // Update indexes
                index = 0;
                $('.benefitRow').each(function () {
                    $(this).find('input[name^="benefits"]').attr('name', `benefits[${index}][title]`);
                    $(this).find('textarea[name^="benefits"]').attr('name', `benefits[${index}][description]`);
                    index++;
                });
            });
        });
    </script>
    <script>
        $(document).ready(function () {

            // let index = 1;
            let index = $('.benefitRow').length;
            $('#addRowBtn').click(function () {
                let newRow = `<div class=" benefitRow">
                                                                <div class="col-lg-12">
                                                                    <label class="mb-1">Title</label>
                                                                    <input type="text" name="benifits[${index}][title]" class="form-control" placeholder="Add Title to Benefit Section">
                                                                </div>
                                                                <div class="col-lg-12 mb">
                                                                    <label class="mb-1">Description</label>
                                                                    <textarea style="position: relative;" class="form-control text-editor" id="description" placeholder="Add Description to Benefit Section Title" name="benifits[${index}][description]"></textarea>
                                                                    <button style="position: absolute;right:16px;bottom:0" type="button" class="btn-danger btn btn-xs deleteRowBtn"><i class="fa fa-trash"></i> Delete</button>
                                                                </div>
                                                            </div>`;
                $('#benefitsContainer').append(newRow);
                index++;
            });

            $(document).on('click', '.deleteRowBtn', function () {
                $(this).closest('.benefitRow').remove();
                // Update indexes
                index = 0;
                $('.benefitRow').each(function () {
                    $(this).find('input[name^="benefits"]').attr('name', `benefits[${index}][title]`);
                    $(this).find('textarea[name^="benefits"]').attr('name', `benefits[${index}][description]`);
                    index++;
                });
            });

            //Exam Location
            $('#addColBtnExamLocations').click(function () {
                let newRowExam = `<div class="col-lg-4 ExamLocationsCol  mb" style="position: relative;">
                                                                <label class="mb-1">Exam Locations</label>
                                                                <input type="text" class="form-control" name="exam_location[]" placeholder="Add Exam Locations">
                                                                <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 15px;" onclick="removeExamLocation(this)">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </div>`;
                $('#ExamLocationsContainer').append(newRowExam);
                indexExamLocation++;
            });

            window.removeExamLocation = function (button) {
                $(button).closest('.ExamLocationsCol ').remove();
            };

            // interested to learn dynamic inputs starts
            // let index2 = 1;
            let index2 = $('.InterestedToLearnCol').length;
            $('#addColBtnInterestedToLearn').click(function () {
                let newRow2 = `<div class="InterestedToLearnCol" style="position: relative; margin-bottom: 8px;">
                                                                <label class="mb-1">Interested to learn</label>
                                                                <input type="text" class="form-control" name="who_can_do[interested_to_learn][${index2}]" placeholder="Interested to learn">
                                                                <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeInterestedToLearn(this)">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </div>`;
                $('#InterestedToLearnContainer').append(newRow2);
                index2++;
            });

            window.removeInterestedToLearn = function (button) {
                $(button).closest('.InterestedToLearnCol').remove();
            };
            // interested to learn dynamic inputs ENDS

            // Designation topursue dynamic inputs STARTS
            // let index3 = 1;
            let index3 = $('.DesignationToPursueCol').length;
            $('#addColBtnDesignationToPursue').click(function () {
                let newRow3 = `<div class="DesignationToPursueCol" style="position: relative; margin-bottom: 8px;">
                                                                <label class="mb-1">Designation</label>
                                                                <input type="text" class="form-control" name="who_can_do[designation][${index3}]" placeholder="Designations to pursue course">
                                                                <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeDesignationToPursue(this)">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </div>`;
                $('#DesignationToPursueContainer').append(newRow3);
                index3++;
            });

            window.removeDesignationToPursue = function (button) {
                $(button).closest('.DesignationToPursueCol').remove();
            };
            // Designation to pursue dynamic inputs ENDS

        });
    </script>

    <script>
        $(document).ready(function () {
            // Add new video row
            $('#add_video_to_custom_Section').click(function () {
                // Get the current count of video items to ensure unique indexing
                var index = $('#videos-row .video-item').length;

                // Create new row with the correct index
                var newRow = `
                                                                <div class="video-item">
                                                                    <div class="col-lg-6 mb">
                                                                        <label class="mb-1">Title</label>
                                                                        <input type="text" class="form-control" name="custom_videos[${index}][title]" value="">
                                                                    </div>
                                                                    <div class="col-lg-5 mb">
                                                                        <label class="mb-1">Video URL</label>
                                                                        <input type="url" class="form-control" name="custom_videos[${index}][path]" placeholder="Enter video URL">
                                                                    </div>
                                                                    <div class="col-lg-1 mb">
                                                                        <button type="button" class="del_video_from_custom_Section btn btn-danger btn-md" style="margin-top:22px;"><i class="fa fa-trash"></i></button>
                                                                    </div>
                                                                </div>
                                                                `;
                // Append the new row to the container
                $('#videos-row').append(newRow);
            });

            // Delete video row
            $(document).on('click', '.del_video_from_custom_Section', function () {
                $(this).closest('.video-item').remove();

                // Re-index after a row is deleted to maintain proper indexing
                $('#videos-row .video-item').each(function (index) {
                    $(this).find('input[name^="custom_videos"]').each(function () {
                        var name = $(this).attr('name');
                        // Replace index in the name attribute to ensure correct data submission
                        var updatedName = name.replace(/\d+/, index);
                        $(this).attr('name', updatedName);
                    });
                });
            });
        });

        window.MediaPicker = {
            active: null,                 // { idBase, multiple, accept, fmType, pathField, fileField, displayField }
            selectedUrls: [],

            open(cfg) {
                // cfg = {idBase, multiple=false, accept='image/*', fmType='image'}
                this.active = Object.assign({ multiple: false, accept: 'image/*', fmType: 'image' }, cfg || {});
                this.selectedUrls = [];

                // adjust the global hidden file input to accept wanted types
                const g = document.getElementById('global_media_input');
                g.accept = this.active.accept;
                g.multiple = !!this.active.multiple;

                // show choose-source modal
                $('#mediaPickSourceModal').modal('show');
            },

            pickLocal() {
                $('#mediaPickSourceModal').modal('hide');
                const g = document.getElementById('global_media_input');

                const onChange = (e) => {
                    const files = Array.from(e.target.files || []);
                    if (!files.length) return;

                    const fileField = document.getElementById(this.active.idBase + '_file');
                    const pathField = document.getElementById(this.active.idBase + '_path');
                    const display = document.getElementById(this.active.idBase + '_display');

                    // Prefer local upload → clear FM path
                    if (pathField) pathField.value = '';

                    // Use DataTransfer to assign files programmatically
                    const dt = new DataTransfer();
                    files.forEach(f => dt.items.add(f));
                    fileField.files = dt.files;

                    if (display) display.value = files.map(f => f.name).join(', ');

                    this.renderPreview(files.map(f => URL.createObjectURL(f)));

                    g.removeEventListener('change', onChange);
                    g.value = ''; // reset
                };

                g.addEventListener('change', onChange, { once: true });
                g.click();
            },

            openFileManager() {
                $('#mediaPickSourceModal').modal('hide');

                $('#mediaFileManagerModal').modal('show');
                $('#mediaFileManagerBody').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>');

                $('#mediaFileManagerBody').load("{{ route('file-manager.index') }}?type=" + encodeURIComponent(this.active.fmType), () => {
                    const syncUI = () => {
                        // highlight selected thumbnails
                        $('#mediaFileManagerBody .file-thumbnail').each((_, el) => {
                            const url = $(el).data('url');
                            $(el).toggleClass('selected', this.selectedUrls.includes(url));
                        });
                        // check the checkboxes
                        $('#mediaFileManagerBody .image-selector').each((_, cb) => {
                            const url = cb.value;
                            cb.checked = this.selectedUrls.includes(url);
                        });
                    };

                    // Thumbnail click
                    $('#mediaFileManagerBody').off('click.thumb')
                        .on('click.thumb', '.file-thumbnail', (ev) => {
                            const url = $(ev.currentTarget).data('url');
                            if (this.active.multiple) {
                                const i = this.selectedUrls.indexOf(url);
                                if (i >= 0) this.selectedUrls.splice(i, 1); else this.selectedUrls.push(url);
                            } else {
                                this.selectedUrls = [url];
                            }
                            syncUI();
                        });

                    // Checkbox change
                    $('#mediaFileManagerBody').off('change.cb')
                        .on('change.cb', '.image-selector', (ev) => {
                            const url = ev.currentTarget.value;
                            if (this.active.multiple) {
                                if (ev.currentTarget.checked) {
                                    if (!this.selectedUrls.includes(url)) this.selectedUrls.push(url);
                                } else {
                                    this.selectedUrls = this.selectedUrls.filter(u => u !== url);
                                }
                            } else {
                                this.selectedUrls = [url];
                                // uncheck other boxes
                                $('#mediaFileManagerBody .image-selector').not(ev.currentTarget).prop('checked', false);
                            }
                            syncUI();
                        });

                    // Initial sync (in case you want to preselect)
                    syncUI();
                });
            },

            confirm() {
                // put selection into *_path (string or CSV) and clear *_file
                const pathField = document.getElementById(this.active.idBase + '_path');
                const fileField = document.getElementById(this.active.idBase + '_file');
                const display = document.getElementById(this.active.idBase + '_display');

                if (pathField) pathField.value = this.active.multiple ? this.selectedUrls.join(',') : (this.selectedUrls[0] || '');
                if (fileField) fileField.value = ''; // clear any previous local selection
                if (display) display.value = pathField.value;

                this.renderPreview(this.selectedUrls);

                $('#mediaFileManagerModal').modal('hide');
            },

            renderPreview(urls) {
                const wrap = document.getElementById(this.active.idBase + '_preview');
                if (!wrap) return;
                wrap.innerHTML = '';
                (urls || []).forEach(u => {
                    const img = document.createElement('img');
                    img.src = u;
                    img.className = 'img img-bordered picker-preview';
                    img.style.maxHeight = '60px';
                    img.style.marginRight = '6px';
                    wrap.appendChild(img);
                });
            }
        };
    </script>

@endpush