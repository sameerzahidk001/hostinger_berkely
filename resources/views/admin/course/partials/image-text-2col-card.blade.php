@php
    $imageTextCard = course_image_text_2col_card($course);
@endphp
<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0px;">

                <div class="ibox-title">
                    <h5>Image + Text 2 Column Card</h5>
                    <div class="ibox-tools">

                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>

                    </div>
                </div>

                <div class="ibox-content">
                    <form role="form" action="{{ route('course.update', $course->id) }}" method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="course_module" value="image_text_2col_card">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-lg-6 mb">
                                <label class="mb-1">Title</label>
                                <input name="image_text_2col_title" class="form-control"
                                    placeholder="Card title"
                                    value="{{ old('image_text_2col_title', $imageTextCard['title'] ?? '') }}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label class="mb-1">Status</label>
                                <select name="image_text_2col_status" class="form-control">
                                    <option value="1" {{ (int) old('image_text_2col_status', $imageTextCard['status'] ?? 0) === 1 ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="0" {{ (int) old('image_text_2col_status', $imageTextCard['status'] ?? 0) === 0 ? 'selected' : '' }}>Disabled
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-12 mb">
                                <label class="mb-1">Description/Overview</label>
                                <textarea class="form-control text-editor" name="image_text_2col_description"
                                    rows="2">{{ old('image_text_2col_description', $imageTextCard['description'] ?? '') }}</textarea>
                            </div>
                            <div class="col-lg-6 mb">
                                <label class="mb-1">Image</label>
                                <div class="input-group">
                                    <input type="text" id="image_text_2col_display" class="form-control"
                                        placeholder="Choose or upload..." readonly
                                        onclick="MediaPicker.open({ idBase:'image_text_2col', multiple:false, accept:'image/*', fmType:'image' })">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button"
                                            onclick="MediaPicker.open({ idBase:'image_text_2col', multiple:false, accept:'image/*', fmType:'image' })">
                                            Browse
                                        </button>
                                    </span>
                                </div>
                                <input type="hidden" id="image_text_2col_path" name="image_text_2col_path"
                                    value="{{ old('image_text_2col_path', $imageTextCard['image'] ?? '') }}">
                                <input type="file" id="image_text_2col_file" name="image_text_2col_image" accept="image/*"
                                    style="display:none">
                                <div id="image_text_2col_preview" class="picker-preview" style="margin-top:6px;">
                                    @if(!empty($imageTextCard['image']))
                                        <img src="{{ asset(ltrim($imageTextCard['image'], '/')) }}" alt=""
                                            style="max-height:60px;border-radius:4px;">
                                    @endif
                                </div>
                                @include('admin.partials.image-alt-input', [
                                    'id' => 'image_text_2col_alt',
                                    'name' => 'image_alts[image_text_2col]',
                                    'value' => form_image_alt_value($course, 'image_text_2col', 'image_alts.image_text_2col'),
                                ])
                            </div>
                            <div class="col-lg-6 mb">
                                <label class="mb-1">Image Placement</label>
                                <select name="image_text_2col_image_placement" class="form-control">
                                    <option value="left" {{ old('image_text_2col_image_placement', $imageTextCard['image_placement'] ?? 'left') === 'left' ? 'selected' : '' }}>Left</option>
                                    <option value="right" {{ old('image_text_2col_image_placement', $imageTextCard['image_placement'] ?? 'left') === 'right' ? 'selected' : '' }}>Right</option>
                                </select>
                            </div>
                            <div class="col-lg-4 mb">
                                <label class="mb-1">Layout</label>
                                <select name="image_text_2col_layout" class="form-control">
                                    <option value="layout-1" {{ old('image_text_2col_layout', $imageTextCard['layout'] ?? 'layout-1') === 'layout-1' ? 'selected' : '' }}>Layout 1</option>
                                    <option value="layout-2" {{ old('image_text_2col_layout', $imageTextCard['layout'] ?? 'layout-1') === 'layout-2' ? 'selected' : '' }}>Layout 2</option>
                                </select>
                            </div>
                            <div class="col-lg-4 mb">
                                <label class="mb-1">Button URL</label>
                                <input type="url" name="image_text_2col_url" class="form-control"
                                    value="{{ old('image_text_2col_url', $imageTextCard['url'] ?? '') }}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label class="mb-1">Button Text</label>
                                <input name="image_text_2col_url_text" class="form-control"
                                    value="{{ old('image_text_2col_url_text', $imageTextCard['url_text'] ?? 'Learn More') }}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label class="mb-1">URL Target</label>
                                <select name="image_text_2col_url_target" class="form-control">
                                    <option value="0" {{ (string) old('image_text_2col_url_target', $imageTextCard['url_target'] ?? '0') === '0' ? 'selected' : '' }}>Open in new tab</option>
                                    <option value="1" {{ (string) old('image_text_2col_url_target', $imageTextCard['url_target'] ?? '0') === '1' ? 'selected' : '' }}>Open in same tab</option>
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
