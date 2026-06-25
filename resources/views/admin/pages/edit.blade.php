@extends('admin.layout.app')
@section('title', 'Page Builder')

@push('style')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.css">
    <style>
        .bg-light {
            background: #fefefe;
        }

        .border-0 {
            border: 0;
        }

        .p-0 {
            padding: 0;
        }

        #meta_keywords_tagsinput, #meta_additional_keywords_tagsinput {
            width: 100% !important;
            height: auto !important;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-height: auto !important;
        }

        #meta_keywords_addTag, #meta_additional_keywords_tagsinput {
            flex: 0 0 auto;
        }

        .tag,
        #meta_keywords_tag, #meta_additional_keywords_tagsinput {
            margin: 0px !important;
            width: auto !important;
        }

        .tags-field .tagsinput {
            width: 100% !important;
            height: auto !important;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-height: auto !important;
        }

        .tags-field input {
            margin: 0px !important;
            width: auto !important;
        }

        .tags_clear {
            display: none !important;
        }

        .image-item {
            height: 240px;
            /* Set this based on your tallest expected item */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 20px;
            text-align: center;
        }

        .image-item img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ccc;
            background-color: #fff;
            padding: 2px;
            box-sizing: border-box;
        }

        .image-item p {
            margin-top: 8px;
            font-size: 12px;
            line-height: 1.4;
            max-width: 150px;
            word-wrap: break-word;
        }
    </style>
@endpush
@section('content')
    <!-- Image Library Modal -->
    <div class="modal fade" id="imageLibraryModal" tabindex="-1" role="dialog" aria-labelledby="imageLibraryLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                    <h4 class="modal-title">Select an Image from Library</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <input type="text" id="imageSearch" class="form-control" placeholder="Search image by name..."
                                style="margin-bottom: 20px;">
                        </div>
                    </div>
                    <div class="row" id="image-library-container">
                        @php
                            $imageFiles = collect(\File::allFiles(public_path()))
                                ->filter(fn($file) => in_array($file->getExtension(), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']))
                                ->sortByDesc(fn($file) => $file->getMTime());
                        @endphp
                        @foreach($imageFiles as $file)
                            @php
                                $filePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());
                                $fileUrl = asset($filePath);
                                $originalName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                                $cleanName = preg_replace('/-[a-zA-Z0-9]{10,}$/', '', $originalName);
                                $readableName = ucwords(str_replace('-', ' ', $cleanName));
                            @endphp
                            <div class="col-xs-6 col-sm-4 col-md-3 text-center h-100 image-item"
                                data-name="{{ strtolower($readableName) }}">
                                <img src="{{ $fileUrl }}" class="image-select"
                                    style="cursor: pointer; width: 150px; height: 150px; object-fit: contain;"
                                    data-url="{{ $fileUrl }}">
                                <p class="small">{{ $readableName }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <form action="{{ route('pages.update.post', $page->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="sections_submitted" value="1">
        @if ($page->category_id)
            <input type="hidden" name="parent_id" value="">
        @endif
        <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                        <div class="ibox-content">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label for="">Page Name</label>
                                    <input class="form-control" placeholder="Add Page Name" type="text" name="page_name"
                                        value="{{ old('page_name', $page->page_name) }}">
                                    @error('page_name')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="col-lg-4">
                                    <label class="mb-1">Page URL <small class="italic">(dummy-page)</small></label>
                                    <input class="form-control" placeholder="Add Page URL" type="text" name="url"
                                        id="page_url" value="{{ old('url', $page->url) }}">
                                    @if ($page->category_id)
                                        <small class="text-muted">Category pages use a unique slug. Public URL: {{ (\App\Models\SiteSettings::value('category_perma') ?? 'category') . '/' . $page->url }}</small>
                                    @endif
                                    @error('url')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="col-lg-4">
                                    <label class="mb-1">Parent Page</label>
                                    <select class="form-control" name="parent_id" id="parent_id" {{ $page->category_id ? 'disabled' : '' }}>
                                        <option value="">- Select Parent Page -</option>
                                        @foreach($allpages as $allpage)
                                            <option value="{{ $allpage->id }}" {{ $page->parent_id == $allpage->id ? 'selected' : '' }}>
                                                {{ $allpage->page_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4">
                                    <label class="mb-1">Categories</label>
                                    <select class="form-control" name="category_id" id="categorySelect">
                                        <option value="">- Select Category Page -</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" data-url="{{ $category->slug }}" {{ $page->category_id == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4">
                                    <label class="mb-1">Status</label>
                                    @if(!pages_status_enabled())
                                        <div class="alert alert-warning" style="padding:8px 12px;margin-bottom:8px;">
                                            Active/Disabled will not save until <code>database/sql/add-pages-status-column.sql</code> is run on the server.
                                        </div>
                                    @endif
                                    <select class="form-control" name="status" required>
                                        <option value="1" @selected((string) old('status', $page->status ?? 1) === '1')>Active</option>
                                        <option value="0" @selected((string) old('status', $page->status ?? 1) === '0')>Disabled</option>
                                    </select>
                                    @error('status')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 px-1">
                <div class="">
                    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                    <div class="ibox-title">
                                        <h5>Add Sections</h5>
                                    </div>
                                    <div class="ibox-content">
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('heroBanner')">Hero Banner</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('banner')">Banner Section</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('category')">Category</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('school-category')">School Category</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('cards')">Cards</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('titleSection')">Title Section</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('mediaSection')">Media Section</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('clients')">Clients</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('gridCards')">Grid Cards</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('list')">List Section</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('separator')">Separator Section</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('certificate')">Certificate Section</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('contactus')">Contact Us Section</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('programmes')">Programmes</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('filterCourses')">Filter Courses</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('course-agendas')">Training Calendar</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('content')">Content</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('testimonials')">Testimonials</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('career')">Career</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('search-bar')">Search Bar</button>
                                        <button type="button" class="btn btn-light" style="width: 100%;"
                                            onclick="editSections('instructors')">Instructors</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-9 px-1">
                <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                <div class="ibox-title">
                                    <h5>Page Sections</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="" id="builder-container">
                    @if (old('sections', $page->sections))
                        @for ($i = 0; $i < count(old('sections', $page->sections)); $i++)
                            @php
                                $sectionData = $page->sections[$i]->data ?? [];
                                if (is_string($sectionData)) {
                                    $sectionData = json_decode($sectionData, true) ?? [];
                                }
                                $section = old('sections.' . $i, $sectionData);
                                if (empty($section['section_type'])) {
                                    $section['section_type'] = $page->sections[$i]->section_type ?? '';
                                }
                                $sectionType = ucwords(str_replace('-', ' ', $section['section_type'] ?? ''));
                            @endphp

                            @if ($section['section_type'] === "hero-banner")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="hero-banner">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_subtitle" class="form-label">Sub-title:</label>
                                                    <input type="text" id="sections_{{ $i }}_subtitle"
                                                        name="sections[{{ $i }}][subtitle]" value="{{ $section['subtitle'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="control-label block">Background Image:</label>

                                                    <div>
                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                            onclick="triggerFileInput({{ $i }}, 'image')">
                                                            <i class="fa fa-upload"></i>
                                                        </button>
                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                            onclick="openLibraryModal({{ $i }}, 'image')">
                                                            <i class="fa fa-image"></i>
                                                        </button>
                                                    </div>

                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="upload"
                                                        id="sections_{{ $i }}_radio_upload_image" {{ ($section['image_source'] ?? '') === 'upload' ? 'checked' : '' }} hidden>
                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="library"
                                                        id="sections_{{ $i }}_radio_library_image" {{ ($section['image_source'] ?? '') === 'library' ? 'checked' : '' }} hidden>

                                                    <input type="file" id="sections_{{ $i }}_image" name="sections[{{ $i }}][image]"
                                                        class="hidden" onchange="handleFileChange(this, {{ $i }}, 'image')">

                                                    <input type="hidden" id="sections_{{ $i }}_image_url"
                                                        name="sections[{{ $i }}][image]"
                                                        value="{{ $section['image'] ?? '' }}">

                                                    @include('admin.partials.image-alt-input', [
                                                        'id' => 'sections_' . $i . '_image_alt',
                                                        'name' => 'sections[' . $i . '][image_alt]',
                                                        'value' => old('sections.' . $i . '.image_alt', $section['image_alt'] ?? ''),
                                                    ])

                                                    @if (!empty($section['image_source']) && $section['image_source'] !== 'remove')
                                                        <div class="clearfix image-preview-block">
                                                            <small class="pull-left">
                                                                <a href="{{ $section['image_source'] === 'upload' ? asset('images/library/' . $section['image']) : $section['image'] }}"
                                                                    target="_blank" class="view-anchor" class="view-anchor">View</a>
                                                            </small>
                                                            <small class="pull-right">
                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                    <input type="radio" name="sections[{{ $i }}][image_source]"
                                                                        value="remove" hidden {{ ($section['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                    <i class="fa fa-trash"></i> Remove
                                                                </label>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_video" class="form-label">YouTube Video URL:</label>
                                                    <input type="url" id="sections_{{ $i }}_video" name="sections[{{ $i }}][video]"
                                                        class="form-control" placeholder="Enter YouTube Video URL"
                                                        value="{{ $section['video'] ?? '' }}">

                                                    @if (!empty($section['video']))
                                                        <div class="mt-2">
                                                            <small>Current Video:</small>
                                                            <iframe width="100%" height="200"
                                                                src="https://www.youtube.com/embed/{{ getYouTubeVideoID($section['video']) }}"
                                                                frameborder="0" allowfullscreen></iframe>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea id="sections_{{ $i }}_description" name="sections[{{ $i }}][description]"
                                                        class="form-control editor">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "filter-courses")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="filter-courses">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_filter_color" class="form-label">Filter Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_filter_color"
                                                        name="sections[{{ $i }}][filter_color]"
                                                        value="{{ $section['filter_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_filter_background" class="form-label">Filter
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_filter_background"
                                                        name="sections[{{ $i }}][filter_background]"
                                                        value="{{ $section['filter_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_filter_border_color" class="form-label">Filter Border
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_filter_border_color"
                                                        name="sections[{{ $i }}][filter_border_color]"
                                                        value="{{ $section['filter_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_filter_color" class="form-label">Active Filter
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_filter_color"
                                                        name="sections[{{ $i }}][active_filter_color]"
                                                        value="{{ $section['active_filter_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_filter_background" class="form-label">Active
                                                        Filter Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_filter_background"
                                                        name="sections[{{ $i }}][active_filter_background]"
                                                        value="{{ $section['active_filter_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_filter_border_color" class="form-label">Active
                                                        Filter Border Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_filter_border_color"
                                                        name="sections[{{ $i }}][active_filter_border_color]"
                                                        value="{{ $section['active_filter_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_tab_color" class="form-label">Tab Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_tab_color"
                                                        name="sections[{{ $i }}][tab_color]" value="{{ $section['tab_color'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_tab_background" class="form-label">Tab
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_tab_background"
                                                        name="sections[{{ $i }}][tab_background]"
                                                        value="{{ $section['tab_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_tab_border_color" class="form-label">Tab Border
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_tab_border_color"
                                                        name="sections[{{ $i }}][tab_border_color]"
                                                        value="{{ $section['tab_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_tab_color" class="form-label">Active Tab
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_tab_color"
                                                        name="sections[{{ $i }}][active_tab_color]"
                                                        value="{{ $section['active_tab_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_tab_background" class="form-label">Active Tab
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_tab_background"
                                                        name="sections[{{ $i }}][active_tab_background]"
                                                        value="{{ $section['active_tab_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_tab_border_color" class="form-label">Active Tab
                                                        Border Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_tab_border_color"
                                                        name="sections[{{ $i }}][active_tab_border_color]"
                                                        value="{{ $section['active_tab_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_content_color" class="form-label">Content
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_content_color"
                                                        name="sections[{{ $i }}][content_color]"
                                                        value="{{ $section['content_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_content_background" class="form-label">Content
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_content_background"
                                                        name="sections[{{ $i }}][content_background]"
                                                        value="{{ $section['content_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_content_border_color" class="form-label">Content
                                                        Border Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_content_border_color"
                                                        name="sections[{{ $i }}][content_border_color]"
                                                        value="{{ $section['content_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <!-- Order By Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_orderby" class="form-label">Order By:</label>
                                                    <select id="sections_{{ $i }}_orderby" name="sections[{{ $i }}][orderby]"
                                                        class="form-control">
                                                        <option value="id,asc" {{ isset($section['orderby']) && $section['orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending order by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['orderby']) && $section['orderby'] == 'id,desc' ? 'selected' : '' }}>Descending order by Id
                                                        </option>
                                                        <option value="name,asc" {{ isset($section['orderby']) && $section['orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending order by Name
                                                        </option>
                                                        <option value="name,desc" {{ isset($section['orderby']) && $section['orderby'] == 'name,desc' ? 'selected' : '' }}>Descending order by
                                                            Name</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_url_target" class="form-label">URL Target:</label>
                                                    <select id="sections_{{ $i }}_url_target" name="sections[{{ $i }}][url_target]"
                                                        class="form-control">
                                                        <option value="0" {{ isset($section['url_target']) && $section['url_target'] == '0' ? 'selected' : '' }}>Open in new tab</option>
                                                        <option value="1" {{ isset($section['url_target']) && $section['url_target'] == '1' ? 'selected' : '' }}>Open in same tab</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "course-agendas")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="course-agendas">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_filter_color" class="form-label">Filter Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_filter_color"
                                                        name="sections[{{ $i }}][filter_color]"
                                                        value="{{ $section['filter_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_filter_background" class="form-label">Filter
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_filter_background"
                                                        name="sections[{{ $i }}][filter_background]"
                                                        value="{{ $section['filter_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_filter_border_color" class="form-label">Filter Border
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_filter_border_color"
                                                        name="sections[{{ $i }}][filter_border_color]"
                                                        value="{{ $section['filter_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_filter_color" class="form-label">Active Filter
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_filter_color"
                                                        name="sections[{{ $i }}][active_filter_color]"
                                                        value="{{ $section['active_filter_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_filter_background" class="form-label">Active
                                                        Filter Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_filter_background"
                                                        name="sections[{{ $i }}][active_filter_background]"
                                                        value="{{ $section['active_filter_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_active_filter_border_color" class="form-label">Active
                                                        Filter Border Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_active_filter_border_color"
                                                        name="sections[{{ $i }}][active_filter_border_color]"
                                                        value="{{ $section['active_filter_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_color" class="form-label">Card Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_color"
                                                        name="sections[{{ $i }}][card_color]" value="{{ $section['card_color'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_background" class="form-label">Card
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_background"
                                                        name="sections[{{ $i }}][card_background]"
                                                        value="{{ $section['card_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_border_color" class="form-label">Card Border
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_border_color"
                                                        name="sections[{{ $i }}][card_border_color]"
                                                        value="{{ $section['card_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <!-- Order By Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_orderby" class="form-label">Order By:</label>
                                                    <select id="sections_{{ $i }}_orderby" name="sections[{{ $i }}][orderby]"
                                                        class="form-control">
                                                        <option value="id,asc" {{ isset($section['orderby']) && $section['orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending order by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['orderby']) && $section['orderby'] == 'id,desc' ? 'selected' : '' }}>Descending order by Id
                                                        </option>
                                                        <option value="name,asc" {{ isset($section['orderby']) && $section['orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending order by Name
                                                        </option>
                                                        <option value="name,desc" {{ isset($section['orderby']) && $section['orderby'] == 'name,desc' ? 'selected' : '' }}>Descending order by
                                                            Name</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "testimonials")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="testimonials">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_color" class="form-label">Card Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_color"
                                                        name="sections[{{ $i }}][card_color]" value="{{ $section['card_color'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_background" class="form-label">Card
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_background"
                                                        name="sections[{{ $i }}][card_background]"
                                                        value="{{ $section['card_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_border_color" class="form-label">Card Border
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_border_color"
                                                        name="sections[{{ $i }}][card_border_color]"
                                                        value="{{ $section['card_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_sidebar_color" class="form-label">Sidebar
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_sidebar_color"
                                                        name="sections[{{ $i }}][sidebar_color]"
                                                        value="{{ $section['sidebar_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_sidebar_background" class="form-label">Sidebar
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_sidebar_background"
                                                        name="sections[{{ $i }}][sidebar_background]"
                                                        value="{{ $section['sidebar_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_sidebar_border_color" class="form-label">Sidebar
                                                        Border Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_sidebar_border_color"
                                                        name="sections[{{ $i }}][sidebar_border_color]"
                                                        value="{{ $section['sidebar_border_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_columns" class="form-label">Columns:</label>
                                                    <select id="sections_{{ $i }}_columns" name="sections[{{ $i }}][columns]"
                                                        class="form-control">
                                                        <option value="2" {{ isset($section['columns']) && $section['columns'] == '2' ? 'selected' : '' }}>2</option>
                                                        <option value="3" {{ isset($section['columns']) && $section['columns'] == '3' ? 'selected' : '' }}>3</option>
                                                        <option value="4" {{ isset($section['columns']) && $section['columns'] == '4' ? 'selected' : '' }}>4</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_pagination_num" class="form-label">Pagination
                                                        Number:</label>
                                                    <input type="number" id="sections_{{ $i }}_pagination_num"
                                                        name="sections[{{ $i }}][pagination_num]" class="form-control"
                                                        value="{{ isset($section['pagination_num']) ? $section['pagination_num'] : '6' }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_category_orderby" class="form-label">Category Order
                                                        By:</label>
                                                    <select id="sections_{{ $i }}_category_orderby"
                                                        name="sections[{{ $i }}][category_orderby]" class="form-control">
                                                        <option value="id,asc" {{ isset($section['category_orderby']) && $section['category_orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending order
                                                            by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['category_orderby']) && $section['category_orderby'] == 'id,desc' ? 'selected' : '' }}>Descending
                                                            order by Id
                                                        </option>
                                                        <option value="name,asc" {{ isset($section['category_orderby']) && $section['category_orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending
                                                            order by Name
                                                        </option>
                                                        <option value="name,desc" {{ isset($section['category_orderby']) && $section['category_orderby'] == 'name,desc' ? 'selected' : '' }}>Descending
                                                            order by
                                                            Name</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_testimonials_orderby" class="form-label">Testimonials
                                                        Order By:</label>
                                                    <select id="sections_{{ $i }}_testimonials_orderby"
                                                        name="sections[{{ $i }}][testimonials_orderby]" class="form-control">
                                                        <option value="id,asc" {{ isset($section['testimonials_orderby']) && $section['testimonials_orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending
                                                            order by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['testimonials_orderby']) && $section['testimonials_orderby'] == 'id,desc' ? 'selected' : '' }}>Descending
                                                            order by Id
                                                        </option>
                                                        <option value="name,asc" {{ isset($section['testimonials_orderby']) && $section['testimonials_orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending
                                                            order by Name
                                                        </option>
                                                        <option value="name,desc" {{ isset($section['testimonials_orderby']) && $section['testimonials_orderby'] == 'name,desc' ? 'selected' : '' }}>
                                                            Descending order by
                                                            Name</option>
                                                        <option value="priority,asc" {{ isset($section['category_orderby']) && $section['category_orderby'] == 'priority,desc' ? 'selected' : '' }}>Ascending
                                                            order by Priority Set</option>
                                                        <option value="priority,desc" {{ isset($section['category_orderby']) && $section['category_orderby'] == 'priority,desc' ? 'selected' : '' }}>
                                                            Descending order by Priority Set</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_url_target" class="form-label">URL Target:</label>
                                                    <select id="sections_{{ $i }}_url_target" name="sections[{{ $i }}][url_target]"
                                                        class="form-control">
                                                        <option value="0" {{ isset($section['url_target']) && $section['url_target'] == '0' ? 'selected' : '' }}>Open in new tab</option>
                                                        <option value="1" {{ isset($section['url_target']) && $section['url_target'] == '1' ? 'selected' : '' }}>Open in same tab</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label class="checkbox-inline">
                                                            <input type="hidden" name="sections[{{ $i }}][add_sorting]" value="0">
                                                            <input type="checkbox" name="sections[{{ $i }}][add_sorting]" value="1" {{ isset($section['add_sorting']) && $section['add_sorting'] == '1' ? 'checked' : '' }}> Add sorting
                                                        </label>
                                                        <label class="checkbox-inline">
                                                            <input type="hidden" name="sections[{{ $i }}][add_search]" value="0">
                                                            <input type="checkbox" name="sections[{{ $i }}][add_search]" value="1" {{ isset($section['add_search']) && $section['add_search'] == '1' ? 'checked' : '' }}> Add search box
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "banner")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" name="sections[{{ $i }}][section_type]" value="banner">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_background_color"
                                                        name="sections[{{ $i }}][background_color]"
                                                        value="{{ $section['background_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="control-label block">Background Image:</label>

                                                    <div>
                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                            onclick="triggerFileInput({{ $i }}, 'image')">
                                                            <i class="fa fa-upload"></i>
                                                        </button>
                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                            onclick="openLibraryModal({{ $i }}, 'image')">
                                                            <i class="fa fa-image"></i>
                                                        </button>
                                                    </div>

                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="upload"
                                                        id="sections_{{ $i }}_radio_upload_image" {{ ($section['image_source'] ?? '') === 'upload' ? 'checked' : '' }} hidden>
                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="library"
                                                        id="sections_{{ $i }}_radio_library_image" {{ ($section['image_source'] ?? '') === 'library' ? 'checked' : '' }} hidden>

                                                    <input type="file" id="sections_{{ $i }}_image" name="sections[{{ $i }}][image]"
                                                        class="hidden" onchange="handleFileChange(this, {{ $i }}, 'image')">

                                                    <input type="hidden" id="sections_{{ $i }}_image_url"
                                                        name="sections[{{ $i }}][image]"
                                                        value="{{ $section['image'] ?? '' }}">

                                                    @include('admin.partials.image-alt-input', [
                                                        'id' => 'sections_' . $i . '_image_alt',
                                                        'name' => 'sections[' . $i . '][image_alt]',
                                                        'value' => old('sections.' . $i . '.image_alt', $section['image_alt'] ?? ''),
                                                    ])

                                                    @if (!empty($section['image_source']) && $section['image_source'] !== 'remove')
                                                        <div class="clearfix image-preview-block">
                                                            <small class="pull-left">
                                                                <a href="{{ $section['image_source'] === 'upload' ? asset('images/library/' . $section['image']) : $section['image'] }}"
                                                                    target="_blank" class="view-anchor" class="view-anchor">View</a>
                                                            </small>
                                                            <small class="pull-right">
                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                    <input type="radio" name="sections[{{ $i }}][image_source]"
                                                                        value="remove" hidden {{ ($section['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                    <i class="fa fa-trash"></i> Remove
                                                                </label>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_subtitle" class="form-label">SubTitle:</label>
                                                    <input type="text" id="sections_{{ $i }}_subtitle"
                                                        name="sections[{{ $i }}][subtitle]" value="{{ $section['subtitle'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea type="text" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]"
                                                        value="{{ $section['description'] ?? '' }}"
                                                        class="form-control editor">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_solid_button_url" class="form-label">Button
                                                        URL:</label>
                                                    <input type="url" id="sections_{{ $i }}_solid_button_url"
                                                        name="sections[{{ $i }}][solid_button_url]"
                                                        value="{{ $section['solid_button_url'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_solid_url_target" class="form-label">URL
                                                        Target:</label>
                                                    <select id="sections_{{ $i }}_solid_url_target"
                                                        name="sections[{{ $i }}][solid_url_target]" class="form-control">
                                                        <option value="0" {{ isset($section['solid_url_target']) && $section['solid_url_target'] == '0' ? 'selected' : '' }}>Open in new tab
                                                        </option>
                                                        <option value="1" {{ isset($section['solid_url_target']) && $section['solid_url_target'] == '1' ? 'selected' : '' }}>Open in same tab
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_solid_button_text" class="form-label">Button
                                                        Text:</label>
                                                    <input type="text" id="sections_{{ $i }}_solid_button_text"
                                                        name="sections[{{ $i }}][solid_button_text]"
                                                        value="{{ $section['solid_button_text'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_outline_button_url" class="form-label">Button
                                                        URL:</label>
                                                    <input type="url" id="sections_{{ $i }}_outline_button_url"
                                                        name="sections[{{ $i }}][outline_button_url]"
                                                        value="{{ $section['outline_button_url'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_outline_url_target" class="form-label">URL
                                                        Target:</label>
                                                    <select id="sections_{{ $i }}_outline_url_target"
                                                        name="sections[{{ $i }}][outline_url_target]" class="form-control">
                                                        <option value="0" {{ isset($section['outline_url_target']) && $section['outline_url_target'] == '0' ? 'selected' : '' }}>Open in new tab
                                                        </option>
                                                        <option value="1" {{ isset($section['outline_url_target']) && $section['outline_url_target'] == '1' ? 'selected' : '' }}>Open in same tab
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_outline_button_text" class="form-label">Button
                                                        Text:</label>
                                                    <input type="text" id="sections_{{ $i }}_outline_button_text"
                                                        name="sections[{{ $i }}][outline_button_text]"
                                                        value="{{ $section['outline_button_text'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}__breadcrumbs" class="form-label">BreadCrumbs:</label>
                                                    <input type="checkbox" id="sections_{{ $i }}__breadcrumbs"
                                                        name="sections[{{ $i }}][breadcrumbs]" {{ isset($section['breadcrumbs']) && $section['breadcrumbs'] == 'on' ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "category")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="category">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background_color" class="form-label">Border
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background_color"
                                                            name="sections[{{ $i }}][background_color]" class="form-control"
                                                            style="flex: 1;" value="{{ $section['background_color'] ?? '' }}" {{ $section['background_color'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_color_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background_color')"
                                                                {{ $section['background_color'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <!-- Columns Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_columns" class="form-label">Columns:</label>
                                                    <select id="sections_{{ $i }}_columns" name="sections[{{ $i }}][columns]"
                                                        class="form-control">
                                                        <option value="2" {{ isset($section['columns']) && $section['columns'] == '2' ? 'selected' : '' }}>2</option>
                                                        <option value="3" {{ isset($section['columns']) && $section['columns'] == '3' ? 'selected' : '' }}>3</option>
                                                        <option value="4" {{ isset($section['columns']) && $section['columns'] == '4' ? 'selected' : '' }}>4</option>
                                                    </select>
                                                </div>

                                                <!-- Order By Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_orderby" class="form-label">Order By:</label>
                                                    <select id="sections_{{ $i }}_orderby" name="sections[{{ $i }}][orderby]"
                                                        class="form-control">
                                                        <option value="id,asc" {{ isset($section['orderby']) && $section['orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending order by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['orderby']) && $section['orderby'] == 'id,desc' ? 'selected' : '' }}>Descending order by Id
                                                        </option>
                                                        <option value="title,asc" {{ isset($section['orderby']) && $section['orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending order by Name
                                                        </option>
                                                        <option value="title,desc" {{ isset($section['orderby']) && $section['orderby'] == 'name,desc' ? 'selected' : '' }}>Descending order by
                                                            Name</option>
                                                    </select>
                                                </div>

                                                <!-- Pagination Number Input -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_pagination_num" class="form-label">Pagination
                                                        Number:</label>
                                                    <input type="number" id="sections_{{ $i }}_pagination_num"
                                                        name="sections[{{ $i }}][pagination_num]" class="form-control"
                                                        value="{{ isset($section['pagination_num']) ? $section['pagination_num'] : '6' }}"
                                                        {{ isset($section['pagination']) ? 'disabled' : '' }}>
                                                    <label for="sections_{{ $i }}_pagination" class="form-check form-label">
                                                        <input type="checkbox" id="sections_{{ $i }}_pagination"
                                                            name="sections[{{ $i }}][pagination]" value="1"
                                                            onchange="toggleDisable(this, 'sections_{{ $i }}_pagination_num')" {{ isset($section['pagination']) ? 'checked' : '' }}> Pagination
                                                    </label>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_category" class="form-label">Select Category:</label>
                                                    <select name="sections[{{ $i }}][category]" id="sections_{{ $i }}_category"
                                                        class="form-control">
                                                        @foreach($categories as $category)
                                                            <option value="{{ $category->id }}" {{ isset($section['category']) && $section['category'] == $category->id ? 'selected' : '' }}>
                                                                {{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "school-category")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="school-category">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background_color" class="form-label">Border
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background_color"
                                                            name="sections[{{ $i }}][background_color]" class="form-control"
                                                            style="flex: 1;" value="{{ $section['background_color'] ?? '' }}" {{ $section['background_color'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_color_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background_color')"
                                                                {{ $section['background_color'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <!-- Columns Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_columns" class="form-label">Columns:</label>
                                                    <select id="sections_{{ $i }}_columns" name="sections[{{ $i }}][columns]"
                                                        class="form-control">
                                                        <option value="2" {{ isset($section['columns']) && $section['columns'] == '2' ? 'selected' : '' }}>2</option>
                                                        <option value="3" {{ isset($section['columns']) && $section['columns'] == '3' ? 'selected' : '' }}>3</option>
                                                        <option value="4" {{ isset($section['columns']) && $section['columns'] == '4' ? 'selected' : '' }}>4</option>
                                                    </select>
                                                </div>

                                                <!-- Order By Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_orderby" class="form-label">Order By:</label>
                                                    <select id="sections_{{ $i }}_orderby" name="sections[{{ $i }}][orderby]"
                                                        class="form-control">
                                                        <option value="id,asc" {{ isset($section['orderby']) && $section['orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending order by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['orderby']) && $section['orderby'] == 'id,desc' ? 'selected' : '' }}>Descending order by Id
                                                        </option>
                                                        <option value="title,asc" {{ isset($section['orderby']) && $section['orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending order by Name
                                                        </option>
                                                        <option value="title,desc" {{ isset($section['orderby']) && $section['orderby'] == 'name,desc' ? 'selected' : '' }}>Descending order by
                                                            Name</option>
                                                    </select>
                                                </div>

                                                <!-- Pagination Number Input -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_pagination_num" class="form-label">Pagination
                                                        Number:</label>
                                                    <input type="number" id="sections_{{ $i }}_pagination_num"
                                                        name="sections[{{ $i }}][pagination_num]" class="form-control"
                                                        value="{{ isset($section['pagination_num']) ? $section['pagination_num'] : '6' }}"
                                                        {{ isset($section['pagination']) ? 'disabled' : '' }}>
                                                    <label for="sections_{{ $i }}_pagination" class="form-check form-label">
                                                        <input type="checkbox" id="sections_{{ $i }}_pagination"
                                                            name="sections[{{ $i }}][pagination]" value="1"
                                                            onchange="toggleDisable(this, 'sections_{{ $i }}_pagination_num')" {{ isset($section['pagination']) ? 'checked' : '' }}> Pagination
                                                    </label>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_school" class="form-label">Select School:</label>
                                                    <select name="sections[{{ $i }}][school]" id="sections_{{ $i }}_school"
                                                        class="form-control">
                                                        @foreach($schools as $school)
                                                            <option value="{{ $school->id }}" {{ isset($section['school']) && $section['school'] == $school->id ? 'selected' : '' }}>
                                                                {{ $school->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_category" class="form-label">Select Category:</label>
                                                    <select name="sections[{{ $i }}][category]" id="sections_{{ $i }}_category"
                                                        class="form-control">
                                                        @foreach($categories as $category)
                                                            <option value="{{ $category->id }}" {{ isset($section['category']) && $section['category'] == $category->id ? 'selected' : '' }}>
                                                                {{ $category->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "programmes")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="programmes">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_border_color" class="form-label">Border Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_border_color"
                                                            name="sections[{{ $i }}][border_color]" class="form-control"
                                                            style="flex: 1;" value="{{ $section['border_color'] ?? '' }}" {{ $section['border_color'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_border_color_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_border_color')" {{ $section['border_color'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_card_background" class="form-label">Card
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_background"
                                                        name="sections[{{ $i }}][card_background]"
                                                        value="{{ $section['card_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <!-- Columns Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_columns" class="form-label">Columns:</label>
                                                    <select id="sections_{{ $i }}_columns" name="sections[{{ $i }}][columns]"
                                                        class="form-control">
                                                        <option value="2" {{ isset($section['columns']) && $section['columns'] == '2' ? 'selected' : '' }}>2</option>
                                                        <option value="3" {{ isset($section['columns']) && $section['columns'] == '3' ? 'selected' : '' }}>3</option>
                                                        <option value="4" {{ isset($section['columns']) && $section['columns'] == '4' ? 'selected' : '' }}>4</option>
                                                    </select>
                                                </div>

                                                <!-- Order By Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_orderby" class="form-label">Order By:</label>
                                                    <select id="sections_{{ $i }}_orderby" name="sections[{{ $i }}][orderby]"
                                                        class="form-control">
                                                        <option value="id,asc" {{ isset($section['orderby']) && $section['orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending order by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['orderby']) && $section['orderby'] == 'id,desc' ? 'selected' : '' }}>Descending order by Id
                                                        </option>
                                                        <option value="name,asc" {{ isset($section['orderby']) && $section['orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending order by Name
                                                        </option>
                                                        <option value="name,desc" {{ isset($section['orderby']) && $section['orderby'] == 'name,desc' ? 'selected' : '' }}>Descending order by
                                                            Name</option>
                                                    </select>
                                                </div>

                                                <!-- Pagination Number Input -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_pagination_num" class="form-label">Pagination
                                                        Number:</label>
                                                    <input type="number" id="sections_{{ $i }}_pagination_num"
                                                        name="sections[{{ $i }}][pagination_num]" class="form-control"
                                                        value="{{ isset($section['pagination_num']) ? $section['pagination_num'] : '6' }}"
                                                        {{ isset($section['pagination']) ? 'disabled' : '' }}>
                                                    <label for="sections_{{ $i }}_pagination" class="form-check form-label">
                                                        <input type="checkbox" id="sections_{{ $i }}_pagination"
                                                            name="sections[{{ $i }}][pagination]" value="1"
                                                            onchange="toggleDisable(this, 'sections_{{ $i }}_pagination_num')" {{ isset($section['pagination']) ? 'checked' : '' }}> Pagination
                                                    </label>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_url_target" class="form-label">URL Target:</label>
                                                    <select id="sections_{{ $i }}_url_target" name="sections[{{ $i }}][url_target]"
                                                        class="form-control">
                                                        <option value="0" {{ isset($section['url_target']) && $section['url_target'] == '0' ? 'selected' : '' }}>Open in new tab</option>
                                                        <option value="1" {{ isset($section['url_target']) && $section['url_target'] == '1' ? 'selected' : '' }}>Open in same tab</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "title-section")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="title-section">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_url" class="form-label">URL:</label>
                                                    <input type="url" id="sections_{{ $i }}_url" name="sections[{{ $i }}][url]"
                                                        value="{{ $section['url'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_url_target" class="form-label">URL Target:</label>
                                                    <select id="sections_{{ $i }}_url_target" name="sections[{{ $i }}][url_target]"
                                                        class="form-control">
                                                        <option value="0" {{ isset($section['url_target']) && $section['url_target'] == '0' ? 'selected' : '' }}>Open in new tab</option>
                                                        <option value="1" {{ isset($section['url_target']) && $section['url_target'] == '1' ? 'selected' : '' }}>Open in same tab</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_url_text" class="form-label">URL Text:</label>
                                                    <input type="text" id="sections_{{ $i }}_url_text"
                                                        name="sections[{{ $i }}][url_text]" value="{{ $section['url_text'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_alignment" class="form-label">Text Alignment:</label>
                                                    <select id="sections_{{ $i }}_alignment" name="sections[{{ $i }}][alignment]"
                                                        class="form-control">
                                                        <option value="left">Left</option>
                                                        <option value="center">Center</option>
                                                        <option value="right">Right</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "media-section")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="media-section">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_background_color" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background_color"
                                                            name="sections[{{ $i }}][background_color]" class="form-control"
                                                            style="flex: 1;" value="{{ $section['background_color'] ?? '' }}" {{ $section['background_color'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background_color')"
                                                                {{ $section['background_color'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_content_background" class="form-label">Content
                                                        Background:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_content_background"
                                                            name="sections[{{ $i }}][content_background]" class="form-control"
                                                            style="flex: 1;" value="{{ $section['content_background'] ?? '' }}" {{ $section['content_background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_content_background')"
                                                                {{ $section['content_background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_layout" class="form-label">Layout:</label>
                                                    <select name="sections[{{ $i }}][layout]" id="sections_{{ $i }}_layout"
                                                        class="form-control">
                                                        <option value="layout-1" {{ (isset($section['layout']) && $section['layout'] == 'layout-1') ? 'selected' : '' }}>Layout 1</option>
                                                        <option value="layout-2" {{ (isset($section['layout']) && $section['layout'] == 'layout-2') ? 'selected' : '' }}>Layout 2</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="control-label block">Icon Image:</label>

                                                    <div>
                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                            onclick="triggerFileInput({{ $i }}, 'icon')">
                                                            <i class="fa fa-upload"></i>
                                                        </button>
                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                            onclick="openLibraryModal({{ $i }}, 'icon')">
                                                            <i class="fa fa-image"></i>
                                                        </button>
                                                    </div>

                                                    <input type="radio" name="sections[{{ $i }}][icon_source]" value="upload"
                                                        id="sections_{{ $i }}_radio_upload_icon" {{ ($section['icon_source'] ?? '') === 'upload' ? 'checked' : '' }} hidden>
                                                    <input type="radio" name="sections[{{ $i }}][icon_source]" value="library"
                                                        id="sections_{{ $i }}_radio_library_icon" {{ ($section['icon_source'] ?? '') === 'library' ? 'checked' : '' }} hidden>

                                                    <input type="file" id="sections_{{ $i }}_icon" name="sections[{{ $i }}][icon]"
                                                        class="hidden" onchange="handleFileChange(this, {{ $i }}, 'icon')">

                                                    <input type="hidden" id="sections_{{ $i }}_icon_url" name="sections[{{ $i }}][icon]"
                                                        value="{{ ($section['icon_source'] ?? '') === 'library' ? ($section['icon'] ?? '') : '' }}">

                                                    @if (!empty($section['icon_source']) && $section['icon_source'] !== 'remove')
                                                        <div class="clearfix image-preview-block">
                                                            <small class="pull-left">
                                                                <a href="{{ $section['icon_source'] === 'upload' ? asset('images/library/' . $section['icon']) : $section['icon'] }}"
                                                                    target="_blank" class="view-anchor">View</a>
                                                            </small>
                                                            <small class="pull-right">
                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                    <input type="radio" name="sections[{{ $i }}][icon_source]"
                                                                        value="remove" hidden {{ ($section['icon_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                    <i class="fa fa-trash"></i> Remove
                                                                </label>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="control-label block">Image:</label>

                                                    <div>
                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                            onclick="triggerFileInput({{ $i }}, 'image')">
                                                            <i class="fa fa-upload"></i>
                                                        </button>
                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                            onclick="openLibraryModal({{ $i }}, 'image')">
                                                            <i class="fa fa-image"></i>
                                                        </button>
                                                    </div>

                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="upload"
                                                        id="sections_{{ $i }}_radio_upload_image" {{ ($section['image_source'] ?? '') === 'upload' ? 'checked' : '' }} hidden>
                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="library"
                                                        id="sections_{{ $i }}_radio_library_image" {{ ($section['image_source'] ?? '') === 'library' ? 'checked' : '' }} hidden>

                                                    <input type="file" id="sections_{{ $i }}_image" name="sections[{{ $i }}][image]"
                                                        class="hidden" onchange="handleFileChange(this, {{ $i }}, 'image')">

                                                    <input type="hidden" id="sections_{{ $i }}_image_url"
                                                        name="sections[{{ $i }}][image]"
                                                        value="{{ $section['image'] ?? '' }}">

                                                    @include('admin.partials.image-alt-input', [
                                                        'id' => 'sections_' . $i . '_image_alt',
                                                        'name' => 'sections[' . $i . '][image_alt]',
                                                        'value' => old('sections.' . $i . '.image_alt', $section['image_alt'] ?? ''),
                                                    ])

                                                    @if (!empty($section['image_source']) && $section['image_source'] !== 'remove')
                                                        <div class="clearfix image-preview-block">
                                                            <small class="pull-left">
                                                                <a href="{{ $section['image_source'] === 'upload' ? asset('images/library/' . $section['image']) : $section['image'] }}"
                                                                    target="_blank" class="view-anchor">View</a>
                                                            </small>
                                                            <small class="pull-right">
                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                    <input type="radio" name="sections[{{ $i }}][image_source]"
                                                                        value="remove" hidden {{ ($section['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                    <i class="fa fa-trash"></i> Remove
                                                                </label>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_image_placement" class="form-label">Image
                                                        Placement:</label>
                                                    <select id="sections_{{ $i }}_image_placement"
                                                        name="sections[{{ $i }}][image_placement]" class="form-control">
                                                        <option value="left" {{ ($section['image_placement'] ?? '') == 'left' ? 'selected' : '' }}>Left</option>
                                                        <option value="right" {{ ($section['image_placement'] ?? '') == 'right' ? 'selected' : '' }}>Right</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_url" class="form-label">URL:</label>
                                                    <input type="url" id="sections_{{ $i }}_url" name="sections[{{ $i }}][url]"
                                                        value="{{ $section['url'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_url_target" class="form-label">URL Target:</label>
                                                    <select id="sections_{{ $i }}_url_target" name="sections[{{ $i }}][url_target]"
                                                        class="form-control">
                                                        <option value="0" {{ isset($section['url_target']) && $section['url_target'] == '0' ? 'selected' : '' }}>Open in new tab</option>
                                                        <option value="1" {{ isset($section['url_target']) && $section['url_target'] == '1' ? 'selected' : '' }}>Open in same tab</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_url_text" class="form-label">URL Text:</label>
                                                    <input type="text" id="sections_{{ $i }}_url_text"
                                                        name="sections[{{ $i }}][url_text]" value="{{ $section['url_text'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "clients")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="clients">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        class="form-control" value="{{ $section['title'] ?? '' }}">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        class="form-control" value="{{ $section['color'] ?? '' }}">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]"
                                                        placeholder="Write something here...">{{ $section['description'] ?? '' }}"</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "grid-cards")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a type="button" class="" href="javascript:void(0)" onclick="addCard({{ $i }})"><i
                                                        class="fa fa-plus"></i> Add Cards</a>
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" name="sections[{{ $i }}][section_type]" value="grid-cards">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_subtitle" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_subtitle"
                                                        name="sections[{{ $i }}][subtitle]" value="{{ $section['subtitle'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_layout" class="form-label">Select Layout:</label>
                                                    <select class="form-control" id="sections_{{ $i }}_layout"
                                                        name="sections[{{ $i }}][layout]">
                                                        <option value="layout-1" {{ (isset($section['layout']) && $section['layout'] == 'layout-1') ? 'selected' : '' }}>Layout 1</option>
                                                        <option value="layout-2" {{ (isset($section['layout']) && $section['layout'] == 'layout-2') ? 'selected' : '' }}>Layout 2</option>
                                                        <option value="layout-3" {{ (isset($section['layout']) && $section['layout'] == 'layout-3') ? 'selected' : '' }}>Layout 3</option>
                                                        <option value="layout-4" {{ (isset($section['layout']) && $section['layout'] == 'layout-4') ? 'selected' : '' }}>Layout 4</option>
                                                        <option value="layout-5" {{ (isset($section['layout']) && $section['layout'] == 'layout-5') ? 'selected' : '' }}>Layout 5</option>
                                                        <option value="layout-6" {{ (isset($section['layout']) && $section['layout'] == 'layout-6') ? 'selected' : '' }}>Layout 6</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_columns" class="form-label">Columns:</label>
                                                    <select class="form-control" id="sections_{{ $i }}_columns"
                                                        name="sections[{{ $i }}][columns]">
                                                        <option value="1" {{ (isset($section['columns']) && $section['columns'] == '1') ? 'selected' : '' }}>1</option>
                                                        <option value="2" {{ (isset($section['columns']) && $section['columns'] == '2') ? 'selected' : '' }}>2</option>
                                                        <option value="3" {{ (isset($section['columns']) && $section['columns'] == '3') ? 'selected' : '' }}>3</option>
                                                        <option value="4" {{ (isset($section['columns']) && $section['columns'] == '4') ? 'selected' : '' }}>4</option>
                                                        <option value="5" {{ (isset($section['columns']) && $section['columns'] == '5') ? 'selected' : '' }}>5</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_image" class="form-label">Image:</label>
                                                    <div>
                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                            onclick="triggerFileInput({{ $i }}, 'image')">
                                                            <i class="fa fa-upload"></i>
                                                        </button>
                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                            onclick="openLibraryModal({{ $i }}, 'image')">
                                                            <i class="fa fa-image"></i>
                                                        </button>
                                                    </div>

                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="upload"
                                                        id="sections_{{ $i }}_radio_upload_image" {{ ($section['image_source'] ?? '') === 'upload' ? 'checked' : '' }} hidden>
                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="library"
                                                        id="sections_{{ $i }}_radio_library_image" {{ ($section['image_source'] ?? '') === 'library' ? 'checked' : '' }} hidden>

                                                    <input type="file" id="sections_{{ $i }}_image" name="sections[{{ $i }}][image]"
                                                        class="hidden" onchange="handleFileChange(this, {{ $i }}, 'image')">

                                                    <input type="hidden" id="sections_{{ $i }}_image_url"
                                                        name="sections[{{ $i }}][image]"
                                                        value="{{ $section['image'] ?? '' }}">

                                                    @include('admin.partials.image-alt-input', [
                                                        'id' => 'sections_' . $i . '_image_alt',
                                                        'name' => 'sections[' . $i . '][image_alt]',
                                                        'value' => old('sections.' . $i . '.image_alt', $section['image_alt'] ?? ''),
                                                    ])

                                                    @if (!empty($section['image_source']) && $section['image_source'] !== 'remove')
                                                        <div class="clearfix image-preview-block">
                                                            <small class="pull-left">
                                                                <a href="{{ $section['image_source'] === 'upload' ? asset('images/library/' . $section['image']) : $section['image'] }}"
                                                                    target="_blank" class="view-anchor">View</a>
                                                            </small>
                                                            <small class="pull-right">
                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                    <input type="radio" name="sections[{{ $i }}][image_source]"
                                                                        value="remove" hidden {{ ($section['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                    <i class="fa fa-trash"></i> Remove
                                                                </label>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                            </div>
                                            <div id="sections_{{ $i }}_cards_container">
                                                @if (!empty($section['cards']))
                                                    @foreach ($section['cards'] as $key => $card)
                                                        <div class="card-item mb-3" id="sections_{{ $i }}_card_{{ $key }}">
                                                            <div class="ibox-title border-0 p-0 bg-light">
                                                                <h5>Card {{ $key + 1 }}</h5>
                                                                <div class="ibox-tools">
                                                                    <a type="button" class="text-danger" href="javascript:void(0)"
                                                                        onclick="removeCard({{ $i }}, {{ $key }})">Remove Card</a>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_title"
                                                                        class="form-label">Card Title:</label>
                                                                    <input type="text" id="sections_{{ $i }}_cards_{{ $key }}_title"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][title]"
                                                                        value="{{ $card['title'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_image"
                                                                        class="form-label">Card Image:</label>
                                                                    <div>
                                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                            onclick="triggerFileInputCard({{ $i }}, {{ $key }}, 'image')">
                                                                            <i class="fa fa-upload"></i>
                                                                        </button>
                                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                            onclick="openLibraryModalCard({{ $i }}, {{ $key }}, 'image')">
                                                                            <i class="fa fa-image"></i>
                                                                        </button>
                                                                    </div>

                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image_source]"
                                                                        value="upload"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_radio_upload_image" {{ ($card['image_source'] ?? '') === 'upload' ? 'checked' : '' }}>
                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image_source]"
                                                                        value="library"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_radio_library_image" {{ ($card['image_source'] ?? '') === 'library' ? 'checked' : '' }}>

                                                                    <input type="file" id="sections_{{ $i }}_cards_{{ $key }}_image"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image]" class="hidden"
                                                                        onchange="handleCardFileChange(this, {{ $i }}, {{ $key }}, 'image')">

                                                                    <input type="hidden" id="sections_{{ $i }}_cards_{{ $key }}_image_url"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image]"
                                                                        value="{{ ($card['image_source'] ?? '') === 'library' ? ($card['image'] ?? '') : '' }}">

                                                                    @include('admin.partials.image-alt-input', [
                                                                        'id' => 'sections_' . $i . '_cards_' . $key . '_image_alt',
                                                                        'name' => 'sections[' . $i . '][cards][' . $key . '][image_alt]',
                                                                        'value' => old('sections.' . $i . '.cards.' . $key . '.image_alt', $card['image_alt'] ?? ''),
                                                                    ])

                                                                    @if (!empty($card['image']) && ($card['image_source'] ?? '') !== 'remove')
                                                                        <div class="clearfix image-preview-block">
                                                                            <small class="pull-left">
                                                                                <a href="{{ $card['image_source'] === 'upload' ? asset('images/library/' . $card['image']) : $card['image'] }}"
                                                                                    target="_blank" class="view-anchor">View</a>
                                                                            </small>
                                                                            <small class="pull-right">
                                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                                    <input type="radio"
                                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image_source]"
                                                                                        value="remove" hidden {{ ($card['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                                    <i class="fa fa-trash"></i> Remove
                                                                                </label>
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url" class="form-label">Card
                                                                        URL:</label>
                                                                    <input type="url" id="sections_{{ $i }}_cards_{{ $key }}_url"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url]"
                                                                        value="{{ $card['url'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url_target"
                                                                        class="form-label">URL Target:</label>
                                                                    <select id="sections_{{ $i }}_cards_{{ $key }}_url_target"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url_target]"
                                                                        class="form-control">
                                                                        <option value="0" {{ isset($card['url_target']) && $card['url_target'] == '0' ? 'selected' : '' }}>Open in new tab
                                                                        </option>
                                                                        <option value="1" {{ isset($card['url_target']) && $card['url_target'] == '1' ? 'selected' : '' }}>Open in same tab
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url_text"
                                                                        class="form-label">Card URL Text:</label>
                                                                    <input type="text" id="sections_{{ $i }}_cards_{{ $key }}_url_text"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url_text]"
                                                                        value="{{ $card['url_text'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_description"
                                                                        class="form-label">Card Description:</label>
                                                                    <textarea class="form-control editor"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_description"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][description]">{{ $card['description'] ?? '' }}</textarea>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_color"
                                                                        class="form-label">Card
                                                                        Color:</label>
                                                                    <input type="color" id="sections_{{ $i }}_cards_{{ $key }}_color"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][color]"
                                                                        value="{{ $card['color'] ?? '#000000' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_background"
                                                                        class="form-label">Card
                                                                        Background:</label>
                                                                    <input type="color" id="sections_{{ $i }}_cards_{{ $key }}_background"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][background]"
                                                                        value="{{ $card['background'] ?? '#FFFFFF' }}" class="form-control">
                                                                </div>
                                                            </div>
                                                            <hr>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "cards")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a type="button" class="" href="javascript:void(0)" onclick="addIconCard({{ $i }})"><i
                                                        class="fa fa-plus"></i> Add Cards</a>
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" name="sections[{{ $i }}][section_type]" value="cards">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_columns" class="form-label">Columns:</label>
                                                    <select class="form-control" id="sections_{{ $i }}_columns"
                                                        name="sections[{{ $i }}][columns]">
                                                        <option value="1" {{ (isset($section['columns']) && $section['columns'] == '1') ? 'selected' : '' }}>1</option>
                                                        <option value="2" {{ (isset($section['columns']) && $section['columns'] == '2') ? 'selected' : '' }}>2</option>
                                                        <option value="3" {{ (isset($section['columns']) && $section['columns'] == '3') ? 'selected' : '' }}>3</option>
                                                        <option value="4" {{ (isset($section['columns']) && $section['columns'] == '4') ? 'selected' : '' }}>4</option>
                                                        <option value="5" {{ (isset($section['columns']) && $section['columns'] == '5') ? 'selected' : '' }}>5</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_layout" class="form-label">Layout:</label>
                                                    <select class="form-control" id="sections_{{ $i }}_layout"
                                                        name="sections[{{ $i }}][layout]">
                                                        <option value="layout-1" {{ (isset($section['layout']) && $section['layout'] == 'layout-1') ? 'selected' : '' }}>Layout 1</option>
                                                        <option value="layout-2" {{ (isset($section['layout']) && $section['layout'] == 'layout-2') ? 'selected' : '' }}>Layout 2</option>
                                                        <option value="layout-3" {{ (isset($section['layout']) && $section['layout'] == 'layout-3') ? 'selected' : '' }}>Layout 3</option>
                                                        <option value="layout-4" {{ (isset($section['layout']) && $section['layout'] == 'layout-4') ? 'selected' : '' }}>Layout 4</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_image" class="form-label">Background Image:</label>
                                                    <div>
                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                            onclick="triggerFileInput({{ $i }}, 'image')">
                                                            <i class="fa fa-upload"></i>
                                                        </button>
                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                            onclick="openLibraryModal({{ $i }}, 'image')">
                                                            <i class="fa fa-image"></i>
                                                        </button>
                                                    </div>

                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="upload"
                                                        id="sections_{{ $i }}_radio_upload_image" {{ ($section['image_source'] ?? '') === 'upload' ? 'checked' : '' }} hidden>
                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="library"
                                                        id="sections_{{ $i }}_radio_library_image" {{ ($section['image_source'] ?? '') === 'library' ? 'checked' : '' }} hidden>

                                                    <input type="file" id="sections_{{ $i }}_image" name="sections[{{ $i }}][image]"
                                                        class="hidden" onchange="handleFileChange(this, {{ $i }}, 'image')">

                                                    <input type="hidden" id="sections_{{ $i }}_image_url"
                                                        name="sections[{{ $i }}][image]"
                                                        value="{{ $section['image'] ?? '' }}">

                                                    @include('admin.partials.image-alt-input', [
                                                        'id' => 'sections_' . $i . '_image_alt',
                                                        'name' => 'sections[' . $i . '][image_alt]',
                                                        'value' => old('sections.' . $i . '.image_alt', $section['image_alt'] ?? ''),
                                                    ])

                                                    @if (!empty($section['image_source']) && $section['image_source'] !== 'remove')
                                                        <div class="clearfix image-preview-block">
                                                            <small class="pull-left">
                                                                <a href="{{ $section['image_source'] === 'upload' ? asset('images/library/' . $section['image']) : $section['image'] }}"
                                                                    target="_blank" class="view-anchor">View</a>
                                                            </small>
                                                            <small class="pull-right">
                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                    <input type="radio" name="sections[{{ $i }}][image_source]"
                                                                        value="remove" hidden {{ ($section['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                    <i class="fa fa-trash"></i> Remove
                                                                </label>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_alignment" class="form-label">Text Alignment:</label>
                                                    <select name="sections[{{ $i }}][alignment]" id="sections_{{ $i }}_alignment"
                                                        class="form-control">
                                                        <option value="left" {{ (isset($section['alignment']) && $section['alignment'] == 'left') ? 'selected' : '' }}>Left</option>
                                                        <option value="center" {{ (isset($section['alignment']) && $section['alignment'] == 'center') ? 'selected' : '' }}>Center</option>
                                                        <option value="right" {{ (isset($section['alignment']) && $section['alignment'] == 'right') ? 'selected' : '' }}>Right</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div id="sections_{{ $i }}_cards_container">
                                                @if (!empty($section['cards']))
                                                    @foreach ($section['cards'] as $key => $card)
                                                        <div class="card-item mb-3" id="sections_{{ $i }}_card_{{ $key }}">
                                                            <div class="ibox-title border-0 p-0 bg-light">
                                                                <h5>Card {{ $key + 1 }}</h5>
                                                                <div class="ibox-tools">
                                                                    <a type="button" class="text-danger" href="javascript:void(0)"
                                                                        onclick="removeCard({{ $i }}, {{ $key }})">Remove Card</a>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_title"
                                                                        class="form-label">Card Title:</label>
                                                                    <input type="text" id="sections_{{ $i }}_cards_{{ $key }}_title"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][title]"
                                                                        value="{{ $card['title'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_icon" class="form-label">Card
                                                                        Icon:</label>
                                                                    <div>
                                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                            onclick="triggerFileInputCard({{ $i }}, {{ $key }}, 'icon')">
                                                                            <i class="fa fa-upload"></i>
                                                                        </button>
                                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                            onclick="openLibraryModalCard({{ $i }}, {{ $key }}, 'icon')">
                                                                            <i class="fa fa-image"></i>
                                                                        </button>
                                                                    </div>

                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][icon_source]" value="upload"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_radio_upload_icon" {{ ($card['icon_source'] ?? '') === 'upload' ? 'checked' : '' }}>
                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][icon_source]"
                                                                        value="library"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_radio_library_icon" {{ ($card['icon_source'] ?? '') === 'library' ? 'checked' : '' }}>

                                                                    <input type="file" id="sections_{{ $i }}_cards_{{ $key }}_icon"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][icon]" class="hidden"
                                                                        onchange="handleCardFileChange(this, {{ $i }}, {{ $key }}, 'icon')">

                                                                    <input type="hidden" id="sections_{{ $i }}_cards_{{ $key }}_icon_url"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][icon]"
                                                                        value="{{ ($card['icon_source'] ?? '') === 'library' ? ($card['icon'] ?? '') : '' }}">

                                                                    @if (!empty($card['icon']) && ($card['icon_source'] ?? '') !== 'remove')
                                                                        <div class="clearfix image-preview-block">
                                                                            <small class="pull-left">
                                                                                <a href="{{ $card['icon_source'] ?? '' === 'upload' ? asset('images/library/' . $card['icon']) : $card['icon'] }}"
                                                                                    target="_blank" class="view-anchor">View</a>
                                                                            </small>
                                                                            <small class="pull-right">
                                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                                    <input type="radio"
                                                                                        name="sections[{{ $i }}][cards][{{ $key }}][icon_source]"
                                                                                        value="remove" hidden {{ ($card['icon_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                                    <i class="fa fa-trash"></i> Remove
                                                                                </label>
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_image"
                                                                        class="form-label">Card Image:</label>
                                                                    <div>
                                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                            onclick="triggerFileInputCard({{ $i }}, {{ $key }}, 'image')">
                                                                            <i class="fa fa-upload"></i>
                                                                        </button>
                                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                            onclick="openLibraryModalCard({{ $i }}, {{ $key }}, 'image')">
                                                                            <i class="fa fa-image"></i>
                                                                        </button>
                                                                    </div>

                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image_source]"
                                                                        value="upload"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_radio_upload_image" {{ ($card['image_source'] ?? '') === 'upload' ? 'checked' : '' }}>
                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image_source]"
                                                                        value="library"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_radio_library_image" {{ ($card['image_source'] ?? '') === 'library' ? 'checked' : '' }}>

                                                                    <input type="file" id="sections_{{ $i }}_cards_{{ $key }}_image"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image]" class="hidden"
                                                                        onchange="handleCardFileChange(this, {{ $i }}, {{ $key }}, 'image')">

                                                                    <input type="hidden" id="sections_{{ $i }}_cards_{{ $key }}_image_url"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image]"
                                                                        value="{{ ($card['image_source'] ?? '') === 'library' ? ($card['image'] ?? '') : '' }}">

                                                                    @include('admin.partials.image-alt-input', [
                                                                        'id' => 'sections_' . $i . '_cards_' . $key . '_image_alt',
                                                                        'name' => 'sections[' . $i . '][cards][' . $key . '][image_alt]',
                                                                        'value' => old('sections.' . $i . '.cards.' . $key . '.image_alt', $card['image_alt'] ?? ''),
                                                                    ])

                                                                    @if (!empty($card['image']) && ($card['image_source'] ?? '') !== 'remove')
                                                                        <div class="clearfix image-preview-block">
                                                                            <small class="pull-left">
                                                                                <a href="{{ $card['image_source'] ?? '' === 'upload' ? asset('images/library/' . $card['image']) : $card['image'] }}"
                                                                                    target="_blank" class="view-anchor">View</a>
                                                                            </small>
                                                                            <small class="pull-right">
                                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                                    <input type="radio"
                                                                                        name="sections[{{ $i }}][cards][{{ $key }}][image_source]"
                                                                                        value="remove" hidden {{ ($card['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                                    <i class="fa fa-trash"></i> Remove
                                                                                </label>
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url" class="form-label">Card
                                                                        URL:</label>
                                                                    <input type="url" id="sections_{{ $i }}_cards_{{ $key }}_url"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url]"
                                                                        value="{{ $card['url'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url_target"
                                                                        class="form-label">URL Target:</label>
                                                                    <select id="sections_{{ $i }}_cards_{{ $key }}_url_target"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url_target]"
                                                                        class="form-control">
                                                                        <option value="0" {{ isset($card['url_target']) && $card['url_target'] == '0' ? 'selected' : '' }}>Open in new tab
                                                                        </option>
                                                                        <option value="1" {{ isset($card['url_target']) && $card['url_target'] == '1' ? 'selected' : '' }}>Open in same tab
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url_text"
                                                                        class="form-label">Card URL Text:</label>
                                                                    <input type="text" id="sections_{{ $i }}_cards_{{ $key }}_url_text"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url_text]"
                                                                        value="{{ $card['url_text'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_description"
                                                                        class="form-label">Card Description:</label>
                                                                    <textarea class="form-control editor"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_description"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][description]">{{ $card['description'] ?? '' }}</textarea>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "list")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a type="button" class="" href="javascript:void(0)" onclick="addListItem({{ $i }})">
                                                    <i class="fa fa-plus"></i> Add List Item
                                                </a>
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" name="sections[{{ $i }}][section_type]" value="list">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_layout" class="form-label">Select Layout:</label>
                                                    <select class="form-control" id="sections_{{ $i }}_layout"
                                                        name="sections[{{ $i }}][layout]">
                                                        <option value="layout-1" {{ (isset($section['layout']) && $section['layout'] == 'layout-1') ? 'selected' : '' }}>Layout 1</option>
                                                        <option value="layout-2" {{ (isset($section['layout']) && $section['layout'] == 'layout-2') ? 'selected' : '' }}>Layout 2</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_columns" class="form-label">Columns:</label>
                                                    <select class="form-control" id="sections_{{ $i }}_columns"
                                                        name="sections[{{ $i }}][columns]">
                                                        <option value="1" {{ (isset($section['columns']) && $section['columns'] == '1') ? 'selected' : '' }}>1</option>
                                                        <option value="2" {{ (isset($section['columns']) && $section['columns'] == '2') ? 'selected' : '' }}>2</option>
                                                        <option value="3" {{ (isset($section['columns']) && $section['columns'] == '3') ? 'selected' : '' }}>3</option>
                                                        <option value="4" {{ (isset($section['columns']) && $section['columns'] == '4') ? 'selected' : '' }}>4</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_seperator" class="form-label">Seperator:</label>
                                                    <select class="form-control" id="sections_{{ $i }}_seperator"
                                                        name="sections[{{ $i }}][seperator]">
                                                        <option value="0" {{ (isset($section['seperator']) && $section['seperator'] == '0') ? 'selected' : '' }}>No</option>
                                                        <option value="1" {{ (isset($section['seperator']) && $section['seperator'] == '1') ? 'selected' : '' }}>Yes</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div id="sections_{{ $i }}_list_container">
                                                @if (!empty($section['list_items']))
                                                    @foreach ($section['list_items'] as $key => $item)
                                                        <div class="list-item mb-3 p-2 border rounded bg-light"
                                                            id="sections_{{ $i }}_list_item_{{ $key }}">
                                                            <div class="ibox-title border-0 p-0 bg-light">
                                                                <h5>List Item {{ $key + 1 }}</h5>
                                                                <div class="ibox-tools">
                                                                    <a type="button" class="text-danger" href="javascript:void(0)"
                                                                        onclick="removeListItem({{ $i }}, {{ $key }})">
                                                                        Remove
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="row mt-2">
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_list_items_{{ $key }}_url_text"
                                                                        class="form-label">Item URL Text:</label>
                                                                    <input type="text" id="sections_{{ $i }}_list_items_{{ $key }}_url_text"
                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][url_text]"
                                                                        value="{{ $item['url_text'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_list_items_{{ $key }}_icon"
                                                                        class="form-label">Item
                                                                        Icon:</label>
                                                                    <div>
                                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                            onclick="triggerFileInputItem({{ $i }}, {{ $key }}, 'icon')">
                                                                            <i class="fa fa-upload"></i>
                                                                        </button>
                                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                            onclick="openLibraryModalItem({{ $i }}, {{ $key }}, 'icon')">
                                                                            <i class="fa fa-image"></i>
                                                                        </button>
                                                                    </div>

                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][icon_source]"
                                                                        value="upload"
                                                                        id="sections_{{ $i }}_list_items_{{ $key }}_radio_upload_icon" {{ ($item['icon_source'] ?? '') === 'upload' ? 'checked' : '' }}>
                                                                    <input type="radio" class="hidden"
                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][icon_source]"
                                                                        value="library"
                                                                        id="sections_{{ $i }}_list_items_{{ $key }}_radio_library_icon" {{ ($item['icon_source'] ?? '') === 'library' ? 'checked' : '' }}>

                                                                    <input type="file" id="sections_{{ $i }}_list_items_{{ $key }}_icon"
                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][icon]" class="hidden"
                                                                        onchange="handleItemFileChange(this, {{ $i }}, {{ $key }}, 'icon')">

                                                                    <input type="hidden" id="sections_{{ $i }}_list_items_{{ $key }}_icon_url"
                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][icon]"
                                                                        value="{{ ($item['icon_source'] ?? '') === 'library' ? ($item['icon'] ?? '') : '' }}">

                                                                    @if (!empty($item['icon']) && ($item['icon_source'] ?? '') !== 'remove')
                                                                        <div class="clearfix image-preview-block">
                                                                            <small class="pull-left">
                                                                                <a href="{{ $item['icon_source'] === 'upload' ? asset('images/library/' . $item['icon']) : $item['icon'] }}"
                                                                                    target="_blank" class="view-anchor">View</a>
                                                                            </small>
                                                                            <small class="pull-right">
                                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                                    <input type="radio"
                                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][icon_source]"
                                                                                        value="remove" hidden {{ ($item['icon_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                                    <i class="fa fa-trash"></i> Remove
                                                                                </label>
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_list_items_{{ $key }}_url"
                                                                        class="form-label">Item URL:</label>
                                                                    <input type="url" id="sections_{{ $i }}_list_items_{{ $key }}_url"
                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][url]"
                                                                        value="{{ $item['url'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="sections_{{ $i }}_list_items_{{ $key }}_url_target"
                                                                        class="form-label">URL Target:</label>
                                                                    <select id="sections_{{ $i }}_list_items_{{ $key }}_url_target"
                                                                        name="sections[{{ $i }}][list_items][{{ $key }}][url_target]"
                                                                        class="form-control">
                                                                        <option value="0" {{ isset($item['url_target']) && $item['url_target'] == '0' ? 'selected' : '' }}>Open in new tab
                                                                        </option>
                                                                        <option value="1" {{ isset($item['url_target']) && $item['url_target'] == '1' ? 'selected' : '' }}>Open in same tab
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "separator")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" name="sections[{{ $i }}][section_type]" value="separator">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_height" class="form-label">Height:</label>
                                                    <input type="text" id="sections_{{ $i }}_height" name="sections[{{ $i }}][height]"
                                                        value="{{ $section['height'] ?? '' }}" class="form-control">
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "content")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" name="sections[{{ $i }}][section_type]" value="content">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea type="text" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]"
                                                        value="{{ $section['description'] ?? '' }}"
                                                        class="form-control editor">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "certificate")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="certificate">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        class="form-control" value="{{ $section['color'] ?? '#000000' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_border_color" class="form-label">Border Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_border_color"
                                                            name="sections[{{ $i }}][border_color]" class="form-control"
                                                            style="flex: 1;" value="{{ $section['border_color'] ?? '' }}" {{ $section['border_color'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_border_color_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_border_color')" {{ $section['border_color'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        class="form-control" value="{{ $section['title'] ?? '' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_subtitle" class="form-label">Sub-Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_subtitle"
                                                        name="sections[{{ $i }}][subtitle]" class="form-control"
                                                        value="{{ $section['subtitle'] ?? '' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_image" class="form-label">Certificate:</label>
                                                    <div>
                                                        <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                            onclick="triggerFileInput({{ $i }}, 'image')">
                                                            <i class="fa fa-upload"></i>
                                                        </button>
                                                        <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                            onclick="openLibraryModal({{ $i }}, 'image')">
                                                            <i class="fa fa-image"></i>
                                                        </button>
                                                    </div>

                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="upload"
                                                        id="sections_{{ $i }}_radio_upload_image" {{ ($section['image_source'] ?? '') === 'upload' ? 'checked' : '' }} hidden>
                                                    <input type="radio" name="sections[{{ $i }}][image_source]" value="library"
                                                        id="sections_{{ $i }}_radio_library_image" {{ ($section['image_source'] ?? '') === 'library' ? 'checked' : '' }} hidden>

                                                    <input type="file" id="sections_{{ $i }}_image" name="sections[{{ $i }}][image]"
                                                        class="hidden" onchange="handleFileChange(this, {{ $i }}, 'image')">

                                                    <input type="hidden" id="sections_{{ $i }}_image_url"
                                                        name="sections[{{ $i }}][image]"
                                                        value="{{ $section['image'] ?? '' }}">

                                                    @include('admin.partials.image-alt-input', [
                                                        'id' => 'sections_' . $i . '_image_alt',
                                                        'name' => 'sections[' . $i . '][image_alt]',
                                                        'value' => old('sections.' . $i . '.image_alt', $section['image_alt'] ?? ''),
                                                    ])

                                                    @if (!empty($section['image_source']) && $section['image_source'] !== 'remove')
                                                        <div class="clearfix image-preview-block">
                                                            <small class="pull-left">
                                                                <a href="{{ $section['image_source'] === 'upload' ? asset('images/library/' . $section['image']) : $section['image'] }}"
                                                                    target="_blank" class="view-anchor">View</a>
                                                            </small>
                                                            <small class="pull-right">
                                                                <label class="text-danger remove-image" style="cursor: pointer;">
                                                                    <input type="radio" name="sections[{{ $i }}][image_source]"
                                                                        value="remove" hidden {{ ($section['image_source'] ?? '') === 'remove' ? 'checked' : '' }}>
                                                                    <i class="fa fa-trash"></i> Remove
                                                                </label>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_certificate_name" class="form-label">Certificate
                                                        Name:</label>
                                                    <input type="text" id="sections_{{ $i }}_certificate_name"
                                                        name="sections[{{ $i }}][certificate_name]" class="form-control"
                                                        value="{{ $section['certificate_name'] ?? '' }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "contactus")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="contactus">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        class="form-control" value="{{ $section['color'] ?? '#000000' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Border Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_iframe" class="form-label">Iframe:</label>
                                                    <input type="text" id="sections_{{ $i }}_iframe" name="sections[{{ $i }}][iframe]"
                                                        value="{{ $section['iframe'] ?? '' }}" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "career")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a type="button" class="" href="javascript:void(0)" onclick="addCareer({{ $i }})">
                                                    <i class="fa fa-plus"></i> Add New Career
                                                </a>
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" name="sections[{{ $i }}][section_type]" value="career">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_color" class="form-label">Card Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_color"
                                                        name="sections[{{ $i }}][card_color]" value="{{ $section['card_color'] ?? '' }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_background" class="form-label">Card
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_background"
                                                        name="sections[{{ $i }}][_card_background]" class="form-control"
                                                        value="{{ $section['card_background'] ?? '' }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_border_color" class="form-label">Card Border
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_border_color"
                                                        name="sections[{{ $i }}][card_border_color]"
                                                        value="{{ $section['card_border_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_hover_color" class="form-label">Card Hover
                                                        Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_hover_color"
                                                        name="sections[{{ $i }}][card_hover_color]"
                                                        value="{{ $section['card_hover_color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_hover_background" class="form-label">Card Hover
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_hover_background"
                                                        name="sections[{{ $i }}][card_hover_background]" class="form-control"
                                                        value="{{ $section['card_hover_background'] ?? '' }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_card_hover_border_color" class="form-label">Card Hover
                                                        Border Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_hover_border_color"
                                                        name="sections[{{ $i }}][card_hover_border_color]"
                                                        value="{{ $section['card_hover_border_color'] ?? '' }}" class="form-control">
                                                </div>
                                            </div>
                                            <div id="sections_{{ $i }}_cards_container">
                                                @if (!empty($section['cards']))
                                                    @foreach ($section['cards'] as $key => $item)
                                                        <div class="card-item mb-3 p-2 border rounded bg-light"
                                                            id="sections_{{ $i }}_cards_{{ $key }}">
                                                            <div class="ibox-title border-0 p-0 bg-light">
                                                                <h5>Career {{ $key + 1 }}</h5>
                                                                <div class="ibox-tools">
                                                                    <a type="button" class="text-danger" href="javascript:void(0)"
                                                                        onclick="removeListItem({{ $i }}, {{ $key }})">
                                                                        Remove
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="row mt-2">
                                                                <div class="col-lg-12 tags-field" style="margin-bottom: 15px;">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_tags"
                                                                        class="form-label">Tags</label>
                                                                    <input name="sections[{{ $i }}][cards][{{ $key }}][tags]"
                                                                        id="sections_{{ $i }}_cards_{{ $key }}_tags"
                                                                        value="{{ $item['tags'] ?? '' }}" class="form-control tags" />
                                                                </div>
                                                                <div class="col-md-12">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_content"
                                                                        class="form-label">Description:</label>
                                                                    <textarea type="text" id="sections_{{ $i }}_cards_{{ $key }}_content"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][content]"
                                                                        class="form-control editor">{{ $item['content'] ?? '' }}</textarea>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url_text"
                                                                        class="form-label">Item URL Text:</label>
                                                                    <input type="text" id="sections_{{ $i }}_cards_{{ $key }}_url_text"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url_text]"
                                                                        value="{{ $item['url_text'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url" class="form-label">Item
                                                                        URL:</label>
                                                                    <input type="url" id="sections_{{ $i }}_cards_{{ $key }}_url"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url]"
                                                                        value="{{ $item['url'] ?? '' }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label for="sections_{{ $i }}_cards_{{ $key }}_url_target"
                                                                        class="form-label">URL Target:</label>
                                                                    <select id="sections_{{ $i }}_cards_{{ $key }}_url_target"
                                                                        name="sections[{{ $i }}][cards][{{ $key }}][url_target]"
                                                                        class="form-control">
                                                                        <option value="0" {{ isset($item['url_target']) && $item['url_target'] == '0' ? 'selected' : '' }}>Open in new tab
                                                                        </option>
                                                                        <option value="1" {{ isset($item['url_target']) && $item['url_target'] == '1' ? 'selected' : '' }}>Open in same tab
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "search-bar")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="search-bar">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        class="form-control" value="{{ $section['color'] ?? '#000000' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_width" class="form-label">Width (%):</label>
                                                    <input type="text" id="sections_{{ $i }}_width" name="sections[{{ $i }}][width]"
                                                        value="{{ $section['width'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Label:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_placeholder" class="form-label">Placeholder:</label>
                                                    <input type="text" id="sections_{{ $i }}_placeholder"
                                                        name="sections[{{ $i }}][placeholder]"
                                                        value="{{ $section['placeholder'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_search_design" class="form-label">Search
                                                        design:</label>
                                                    <select name="sections[{{ $i }}][search_design]"
                                                        id="sections_{{ $i }}_search_design" class="form-control">
                                                        <option value="0" {{ ($section['search_design'] == 0 ? 'selected' : '') ?? '' }}>Straight</option>
                                                        <option value="1" {{ ($section['search_design'] == 1 ? 'selected' : '') ?? '' }}>Curve</option>
                                                        <option value="2" {{ ($section['search_design'] == 2 ? 'selected' : '') ?? '' }}>Pill</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_alignment" class="form-label">Alignment:</label>
                                                    <select name="sections[{{ $i }}][alignment]"
                                                            id="sections_{{ $i }}_alignment"
                                                            class="form-control">
                                                        <option value="left" {{ (isset($section['alignment']) && $section['alignment'] == 'left') ? 'selected' : '' }}>Left</option>
                                                        <option value="center" {{ (isset($section['alignment']) && $section['alignment'] == 'center') ? 'selected' : '' }}>Center</option>
                                                        <option value="end" {{ (isset($section['alignment']) && $section['alignment'] == 'right') ? 'selected' : '' }}>Right</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_button_background" class="form-label">Button Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_button_background"
                                                        name="sections[{{ $i }}][button_background]"
                                                        value="{{ $section['button_background'] ?? '#000000' }}"
                                                        class="form-control">
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_button_color" class="form-label">Button Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_button_color"
                                                        name="sections[{{ $i }}][button_color]"
                                                        value="{{ $section['button_color'] ?? '#ffffff' }}"
                                                        class="form-control">
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_button_text" class="form-label">Button Text:</label>
                                                    <input type="text" id="sections_{{ $i }}_button_text"
                                                        name="sections[{{ $i }}][button_text]"
                                                        value="{{ $section['button_text'] ?? 'Search' }}"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($section['section_type'] === "instructors")
                                <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                        <div class="ibox-title">
                                            <h5>{{ $sectionType }}</h5>
                                            <div class="ibox-tools">
                                                <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                <a class="close-link"><i class="fa fa-times"></i></a>
                                            </div>
                                        </div>
                                        <div class="ibox-content">
                                            <input type="hidden" name="sections[{{ $i }}][section_id]"
                                                value="{{ $page->sections[$i]->id }}">
                                            <input type="hidden" id="sections_{{ $i }}_section_type"
                                                name="sections[{{ $i }}][section_type]" value="instructors">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_background" class="form-label">Background
                                                        Color:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="color" id="sections_{{ $i }}_background"
                                                            name="sections[{{ $i }}][background]" class="form-control" style="flex: 1;"
                                                            value="{{ $section['background'] ?? '' }}" {{ $section['background'] ?? 'disabled' }}>
                                                        <label class="ml-2">
                                                            <input type="checkbox" id="sections_{{ $i }}_background_transparent"
                                                                onchange="toggleTransparent(this, 'sections_{{ $i }}_background')" {{ $section['background'] ?? 'checked' }}> Transparent
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_color" class="form-label">Text Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_color" name="sections[{{ $i }}][color]"
                                                        value="{{ $section['color'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_card_background" class="form-label">Card
                                                        Background:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_background"
                                                        name="sections[{{ $i }}][card_background]"
                                                        value="{{ $section['card_background'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_card_color" class="form-label">Card Color:</label>
                                                    <input type="color" id="sections_{{ $i }}_card_color" name="sections[{{ $i }}][card_color]"
                                                        value="{{ $section['card_color'] ?? '' }}" class="form-control">
                                                </div>

                                                <!-- Order By Selection -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_orderby" class="form-label">Order By:</label>
                                                    <select id="sections_{{ $i }}_orderby" name="sections[{{ $i }}][orderby]"
                                                        class="form-control">
                                                        <option value="id,asc" {{ isset($section['orderby']) && $section['orderby'] == 'id,asc' ? 'selected' : '' }}>Ascending order by Id
                                                        </option>
                                                        <option value="id,desc" {{ isset($section['orderby']) && $section['orderby'] == 'id,desc' ? 'selected' : '' }}>Descending order by Id
                                                        </option>
                                                        <option value="name,asc" {{ isset($section['orderby']) && $section['orderby'] == 'name,asc' ? 'selected' : '' }}>Ascending order by Name
                                                        </option>
                                                        <option value="name,desc" {{ isset($section['orderby']) && $section['orderby'] == 'name,desc' ? 'selected' : '' }}>Descending order by
                                                            Name</option>
                                                    </select>
                                                </div>

                                                <!-- Pagination Number Input -->
                                                <div class="col-md-4">
                                                    <label for="sections_{{ $i }}_pagination_num" class="form-label">Pagination
                                                        Number:</label>
                                                    <input type="number" id="sections_{{ $i }}_pagination_num"
                                                        name="sections[{{ $i }}][pagination_num]" class="form-control"
                                                        value="{{ isset($section['pagination_num']) ? $section['pagination_num'] : '6' }}"
                                                        {{ isset($section['pagination']) ? 'disabled' : '' }}>
                                                    <label for="sections_{{ $i }}_pagination" class="form-check form-label">
                                                        <input type="checkbox" id="sections_{{ $i }}_pagination"
                                                            name="sections[{{ $i }}][pagination]" value="1"
                                                            onchange="toggleDisable(this, 'sections_{{ $i }}_pagination_num')" {{ isset($section['pagination']) ? 'checked' : '' }}> Pagination
                                                    </label>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="sections_{{ $i }}_title" class="form-label">Title:</label>
                                                    <input type="text" id="sections_{{ $i }}_title" name="sections[{{ $i }}][title]"
                                                        value="{{ $section['title'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="sections_{{ $i }}_description" class="form-label">Description:</label>
                                                    <textarea class="form-control editor" id="sections_{{ $i }}_description"
                                                        name="sections[{{ $i }}][description]">{{ $section['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endfor
                    @endif
                </div>

                <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                        <div class="ibox-title">
                            <h5>SEO Meta</h5>
                        </div>
                        <div class="ibox-content">
                            <div class="row">
                                <div class="col-lg-6 mb">
                                    <label for="meta_title">Title <small>( Max {{ seo_field_limits()['title_max'] }} characters )</small></label>
                                    <input type="text" class="form-control" name="meta_title"
                                        placeholder="Add SEO Meta title"
                                        value="{{ old('meta_title', $meta->title ?? '') }}"
                                        data-maxlength="{{ seo_field_limits()['title_max'] }}"
                                        maxlength="{{ seo_field_limits()['title_max'] }}">
                                    @error('meta_title')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <!-- Image Input Group -->
                                <div class="col-lg-6 mb form-group">
                                    <label for="meta_thumbnail">Thumbnail <small>( 1200 X 627 px )</label>
                                    <div class="input-group">
                                        <input type="text" id="meta_thumbnail_path" name="meta_thumbnail_path"
                                            class="form-control" readonly onclick="showFileModal()"
                                            value="{{ old('meta_thumbnail_path', $meta->thumbnail ?? '') }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="showFileModal()">Browse</button>
                                        </span>
                                    </div>

                                    @error('meta_thumbnail_path') <span class="help-block text-danger">{{ $message }}</span>
                                    @enderror
                                    @error('local_meta_thumbnail_input') <span
                                    class="help-block text-danger">{{ $message }}</span> @enderror

                                    @if(!empty($meta->thumbnail))
                                        <small id="imageUrlText" class="help-block" style="display:block; margin-top:0px;">
                                            @if($meta->thumbnail)
                                                View image <a href="{{ old('meta_thumbnail_path', $meta->thumbnail ?? '') }}"
                                                    target="_blank" rel="noopener">here</a>
                                            @else
                                                <span class="text-muted">No image selected</span>
                                            @endif
                                        </small>
                                    @endif
                                </div>
                                @include('admin.partials.image-alt-input', [
                                    'id' => 'meta_thumbnail_alt',
                                    'name' => 'meta_thumbnail_alt',
                                    'value' => old('meta_thumbnail_alt', $meta->thumbnail_alt ?? ''),
                                ])

                                <!-- Hidden file input -->
                                <input type="file" id="local_meta_thumbnail_input" name="local_meta_thumbnail_input"
                                    style="display: none;" accept="image/*" onchange="uploadLocalFile(this)">

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
                                <div class="col-lg-12 mb">
                                    <label for="meta_description">Meta Description <small>( Max {{ seo_field_limits()['meta_description_max'] }} characters )</small></label>
                                    <textarea class="form-control editor" name="meta_description"
                                        placeholder="Add SEO Meta description"
                                        data-maxlength="{{ seo_field_limits()['meta_description_max'] }}"
                                        maxlength="{{ seo_field_limits()['meta_description_max'] }}">{{ old('meta_description', $meta->meta_description ?? '') }}</textarea>
                                    @error('meta_description')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-6" style="margin-bottom: 15px;">
                                    <label for="meta_keywords">Priority Keywords <small>( Max {{ seo_field_limits()['priority_keywords_max_tags'] }} keywords )</small></label>
                                    <input name="meta_keywords" id="meta_keywords"
                                        value="{{ old('meta_keywords', $meta->keywords ?? '') }}" class="form-control" />
                                    @error('meta_keywords')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="col-lg-6" style="margin-bottom: 15px;">
                                    <label for="meta_additional_keywords">Additional Keywords <small>( Max {{ seo_field_limits()['additional_keywords_max_tags'] }} keywords )</small></label>
                                    <input name="meta_additional_keywords" id="meta_additional_keywords"
                                        value="{{ old('meta_additional_keywords', $meta->additional_keywords ?? '') }}" class="form-control" />
                                    @error('meta_additional_keywords')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
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
                                    <div class="row">
                                        <div class="col-lg-12" style="text-align: right;">
                                            <button class="btn btn-primary " type="submit"><i
                                                    class="fa fa-check"></i>&nbsp;Submit</button>
                                            <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i>
                                                Reset</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
        $(document).ready(function () {
            function slugify(text) {
                return text.toString().toLowerCase().trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            $('#categorySelect').change(function () {
                if ($(this).val()) {
                    $('#parent_id').prop('disabled', true).val('');
                    let pageName = $('input[name="page_name"]').val();
                    if (!$('#page_url').val() && pageName) {
                        $('#page_url').val(slugify(pageName));
                    }
                } else {
                    $('#parent_id').prop('disabled', false);
                }
            });

            // Initialize SortableJS
            const builderContainer = document.getElementById('builder-container');
            const pageForm = builderContainer ? builderContainer.closest('form') : null;

            window.updateSectionOrder = function () {
                if (!builderContainer) {
                    return;
                }

                const sections = builderContainer.querySelectorAll('.ibox-container');
                sections.forEach((section, index) => {
                    const inputs = section.querySelectorAll('input, textarea, select');
                    inputs.forEach(input => {
                        if (!input.name) {
                            return;
                        }

                        input.name = input.name.replace(/sections\[\d+\]/g, `sections[${index}]`);

                        if (input.id && input.id.startsWith('sections_')) {
                            input.id = input.id.replace(/^sections_\d+/, `sections_${index}`);
                        }
                    });

                    section.querySelectorAll('label[for^="sections_"]').forEach(label => {
                        if (label.htmlFor) {
                            label.htmlFor = label.htmlFor.replace(/^sections_\d+/, `sections_${index}`);
                        }
                    });
                });
            };

            if (builderContainer) {
                new Sortable(builderContainer, {
                    animation: 150,
                    handle: '.ibox-title',
                    onEnd: function () {
                        window.updateSectionOrder();
                    }
                });
            }

            if (pageForm) {
                pageForm.addEventListener('submit', function () {
                    window.updateSectionOrder();
                });
            }
        });
    </script>
    @include('admin.seo.partials.tags-limits-script')
    <script>
        $(document).ready(function () {
            // Handle collapse toggle
            $("#builder-container").on("click", ".collapse-link", function (e) {
                e.preventDefault();  // Prevent default action

                var ibox = $(this).closest("div.ibox");
                var button = $(this).find("i");
                var content = ibox.children(".ibox-content");

                if (!ibox.hasClass("collapsed")) {
                    content.slideUp(200);
                    ibox.addClass("collapsed");
                    button.removeClass("fa-chevron-up").addClass("fa-chevron-down");
                } else {
                    content.slideDown(200);
                    ibox.removeClass("collapsed");
                    button.removeClass("fa-chevron-down").addClass("fa-chevron-up");
                }
            });

            // Handle close button for section removal
            $("#builder-container").on("click", ".close-link", function (e) {
                e.preventDefault();
                $(this).closest(".ibox-container").remove();
            });

            $("#builder-container").on("focus", ".tags", function () {
                $('.tags').tagsInput({
                    'defaultText': 'Add Tags',
                    'unique': true,
                });
            });

            // Lazy initialization of CKEditor on focus
            $("#builder-container").on("focus", ".editor", function () {
                if (!this.classList.contains("summernote-initialized")) {
                    $(this).summernote({
                        height: 200,
                        toolbar: [
                            ['style', ['style']],
                            ['font', ['bold', 'italic', 'underline', 'clear']],
                            ['fontsize', ['fontsize']],
                            ['color', ['color']],
                            ['para', ['ul', 'ol', 'paragraph']],
                            ['insert', ['link', 'hr']],
                            ['customCol', ['col2', 'col3', 'col4', 'col5']],
                            ['view', ['codeview']]
                        ],
                        buttons: {
                            col2: function (context) {
                                var ui = $.summernote.ui;
                                return ui.button({
                                    contents: '2',
                                    tooltip: '2 Columns',
                                    click: function () {
                                        context.invoke('editor.pasteHTML', `
                                                    <div class="grid md:grid-cols-2 gap-4">
                                                        <div class="p-4">Column 1</div>
                                                        <div class="p-4">Column 2</div>
                                                    </div>
                                                `);
                                    }
                                }).render();
                            },
                            col3: function (context) {
                                var ui = $.summernote.ui;
                                return ui.button({
                                    contents: '3',
                                    tooltip: '3 Columns',
                                    click: function () {
                                        context.invoke('editor.pasteHTML', `
                                                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                        <div class="p-4">Col 1</div>
                                                        <div class="p-4">Col 2</div>
                                                        <div class="p-4">Col 3</div>
                                                    </div>
                                                `);
                                    }
                                }).render();
                            },
                            col4: function (context) {
                                var ui = $.summernote.ui;
                                return ui.button({
                                    contents: '4',
                                    tooltip: '4 Columns',
                                    click: function () {
                                        context.invoke('editor.pasteHTML', `
                                                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                                                        <div class="p-4">Col 1</div>
                                                        <div class="p-4">Col 2</div>
                                                        <div class="p-4">Col 3</div>
                                                        <div class="p-4">Col 4</div>
                                                    </div>
                                                `);
                                    }
                                }).render();
                            },
                            col5: function (context) {
                                var ui = $.summernote.ui;
                                return ui.button({
                                    contents: '5',
                                    tooltip: '5 Columns',
                                    click: function () {
                                        context.invoke('editor.pasteHTML', `
                                                    <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-4">
                                                        <div class="p-4">1</div>
                                                        <div class="p-4">2</div>
                                                        <div class="p-4">3</div>
                                                        <div class="p-4">4</div>
                                                        <div class="p-4">5</div>
                                                    </div>
                                                `);
                                    }
                                }).render();
                            }
                        },
                        callbacks: {
                            onChange: function (contents, $editable) {
                                $editable.find('ul').addClass('list-disc list-inside');
                                $editable.find('ol').addClass('list-decimal list-inside');
                            }
                        }
                    });

                    this.classList.add("summernote-initialized");
                }
            });
        });

        function generateCareer(sectionCount, careerCount) {
            return `
                                                <div class="card-item mb-3" id="sections_${sectionCount}_card_${careerCount}">
                                                    <div class="ibox-title border-0 p-0 bg-light">
                                                        <h5>Card ${careerCount + 1}</h5>
                                                        <div class="ibox-tools">
                                                            <a type="button" class="text-danger" href="javascript:void(0)" onclick="removeCard(${sectionCount}, ${careerCount})">Remove Career</a>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-12 tags-field" style="margin-bottom: 15px;">
                                                            <label for="sections_${sectionCount}_cards_${careerCount}_tags" class="form-label">Tags</label>
                                                            <input name="sections[${sectionCount}][cards][${careerCount}][tags]" id="sections_${sectionCount}_cards_${careerCount}_tags" class="form-control tags" />
                                                        </div>
                                                        <div class="col-md-12">
                                                            <label for="sections_${sectionCount}_cards_${careerCount}_content" class="form-label">Content:</label>
                                                            <textarea class="form-control editor" maxlength="255" id="sections_${sectionCount}_cards_${careerCount}_content" name="sections[${sectionCount}][cards][${careerCount}][content]" placeholder="Write something here..."></textarea>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${careerCount}_url_text" class="form-label">Card URL Text:</label>
                                                            <input type="text" id="sections_${sectionCount}_cards_${careerCount}_url_text" name="sections[${sectionCount}][cards][${careerCount}][url_text]" class="form-control">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${careerCount}_url" class="form-label">Card URL:</label>
                                                            <input type="url" id="sections_${sectionCount}_cards_${careerCount}_url" name="sections[${sectionCount}][cards][${careerCount}][url]" class="form-control">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${careerCount}_url_target" class="form-label">URL Target:</label>
                                                            <select id="sections_${sectionCount}_cards_${careerCount}_url_target" name="sections[${sectionCount}][cards][${careerCount}][url_target]" class="form-control">
                                                                <option value="0">Open in New Tab</option>
                                                                <option value="1">Open in Same Tab</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                </div>
                                            `;
        }

        function generateCard(sectionCount, cardCount) {
            return `
                                                <div class="card-item mb-3" id="sections_${sectionCount}_card_${cardCount}">
                                                    <div class="ibox-title border-0 p-0 bg-light">
                                                        <h5>Card ${cardCount + 1}</h5>
                                                        <div class="ibox-tools">
                                                            <a type="button" class="text-danger" href="javascript:void(0)" onclick="removeCard(${sectionCount}, ${cardCount})">Remove Card</a>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_title" class="form-label">Card Title:</label>
                                                            <input type="text" id="sections_${sectionCount}_cards_${cardCount}_title" name="sections[${sectionCount}][cards][${cardCount}][title]" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_image" class="form-label">Card Image:</label>
                                                            <div>
                                                                <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                    onclick="triggerFileInputCard(${sectionCount}, ${cardCount}, 'image')">
                                                                    <i class="fa fa-upload"></i>
                                                                </button>
                                                                <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                    onclick="openLibraryModalCard(${sectionCount}, ${cardCount}, 'image')">
                                                                    <i class="fa fa-image"></i>
                                                                </button>
                                                            </div>

                                                            <input type="radio" class="hidden" name="sections[${sectionCount}][cards][${cardCount}][image_source]" value="upload"
                                                                id="sections_${sectionCount}_cards_${cardCount}_radio_upload_image">
                                                            <input type="radio" class="hidden" name="sections[${sectionCount}][cards][${cardCount}][image_source]" value="library"
                                                                id="sections_${sectionCount}_cards_${cardCount}_radio_library_image">

                                                            <input type="file" id="sections_${sectionCount}_cards_${cardCount}_image"
                                                                name="sections[${sectionCount}][cards][${cardCount}][image]" class="hidden"
                                                                onchange="handleCardFileChange(this, ${sectionCount}, ${cardCount}, 'image')">

                                                            <input type="hidden" id="sections_${sectionCount}_cards_${cardCount}_image_url"
                                                                name="sections[${sectionCount}][cards][${cardCount}][image]">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_url" class="form-label">Card URL:</label>
                                                            <input type="url" id="sections_${sectionCount}_cards_${cardCount}_url" name="sections[${sectionCount}][cards][${cardCount}][url]" class="form-control">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_url_target" class="form-label">URL Target:</label>
                                                            <select id="sections_${sectionCount}_cards_${cardCount}_url_target" name="sections[${sectionCount}][cards][${cardCount}][url_target]" class="form-control">
                                                                <option value="0">Open in New Tab</option>
                                                                <option value="1">Open in Same Tab</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_url_text" class="form-label">Card URL Text:</label>
                                                            <input type="text" id="sections_${sectionCount}_cards_${cardCount}_url_text" name="sections[${sectionCount}][cards][${cardCount}][url_text]" class="form-control">
                                                        </div>
                                                        <div class="col-md-12">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_description" class="form-label">Card Description:</label>
                                                            <textarea class="form-control editor" maxlength="255" id="sections_${sectionCount}_cards_${cardCount}_description" name="sections[${sectionCount}][cards][${cardCount}][description]" placeholder="Write something here..."></textarea>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_color" class="form-label">Card Color:</label>
                                                            <input type="color" id="sections_${sectionCount}_cards_${cardCount}_color" name="sections[${sectionCount}][cards][${cardCount}][color]" value="#000000" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_background" class="form-label">Card Background:</label>
                                                            <input type="color" id="sections_${sectionCount}_cards_${cardCount}_background" name="sections[${sectionCount}][cards][${cardCount}][background]" value="#FFFFFF" class="form-control">
                                                        </div>
                                                    </div>
                                                    <hr>
                                                </div>
                                            `;
        }

        function generateIconCard(sectionCount, cardCount) {
            return `
                                                <div class="card-item mb-3" id="sections_${sectionCount}_card_${cardCount}">
                                                    <div class="ibox-title border-0 p-0 bg-light">
                                                        <h5>Card ${cardCount + 1}</h5>
                                                        <div class="ibox-tools">
                                                            <a type="button" class="text-danger" href="javascript:void(0)" onclick="removeCard(${sectionCount}, ${cardCount})">Remove Card</a>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_title" class="form-label">Card Title:</label>
                                                            <input type="text" id="sections_${sectionCount}_cards_${cardCount}_title" name="sections[${sectionCount}][cards][${cardCount}][title]" class="form-control">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_icon" class="form-label">Card Icon:</label>
                                                            <div>
                                                                <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                    onclick="triggerFileInputCard(${sectionCount}, ${cardCount}, 'icon')">
                                                                    <i class="fa fa-upload"></i>
                                                                </button>
                                                                <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                    onclick="openLibraryModalCard(${sectionCount}, ${cardCount}, 'icon')">
                                                                    <i class="fa fa-image"></i>
                                                                </button>
                                                            </div>

                                                            <input type="radio" class="hidden" name="sections[${sectionCount}][cards][${cardCount}][icon_source]" value="upload"
                                                                id="sections_${sectionCount}_cards_${cardCount}_radio_upload_icon">
                                                            <input type="radio" class="hidden" name="sections[${sectionCount}][cards][${cardCount}][icon_source]" value="library"
                                                                id="sections_${sectionCount}_cards_${cardCount}_radio_library_icon">

                                                            <input type="file" id="sections_${sectionCount}_cards_${cardCount}_icon"
                                                                name="sections[${sectionCount}][cards][${cardCount}][icon]" class="hidden"
                                                                onchange="handleCardFileChange(this, ${sectionCount}, ${cardCount}, 'icon')">

                                                            <input type="hidden" id="sections_${sectionCount}_cards_${cardCount}_icon_url"
                                                                name="sections[${sectionCount}][cards][${cardCount}][icon]">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_image" class="form-label">Card Image:</label>
                                                            <div>
                                                                <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                    onclick="triggerFileInputCard(${sectionCount}, ${cardCount}, 'image')">
                                                                    <i class="fa fa-upload"></i>
                                                                </button>
                                                                <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                    onclick="openLibraryModalCard(${sectionCount}, ${cardCount}, 'image')">
                                                                    <i class="fa fa-image"></i>
                                                                </button>
                                                            </div>

                                                            <input type="radio" class="hidden" name="sections[${sectionCount}][cards][${cardCount}][image_source]" value="upload"
                                                                id="sections_${sectionCount}_cards_${cardCount}_radio_upload_image">
                                                            <input type="radio" class="hidden" name="sections[${sectionCount}][cards][${cardCount}][image_source]" value="library"
                                                                id="sections_${sectionCount}_cards_${cardCount}_radio_library_image">

                                                            <input type="file" id="sections_${sectionCount}_cards_${cardCount}_image"
                                                                name="sections[${sectionCount}][cards][${cardCount}][image]" class="hidden"
                                                                onchange="handleCardFileChange(this, ${sectionCount}, ${cardCount}, 'image')">

                                                            <input type="hidden" id="sections_${sectionCount}_cards_${cardCount}_image_url"
                                                                name="sections[${sectionCount}][cards][${cardCount}][image]">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_url" class="form-label">Card URL:</label>
                                                            <input type="url" id="sections_${sectionCount}_cards_${cardCount}_url" name="sections[${sectionCount}][cards][${cardCount}][url]" class="form-control">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_url_target" class="form-label">URL Target:</label>
                                                            <select id="sections_${sectionCount}_cards_${cardCount}_url_target" name="sections[${sectionCount}][cards][${cardCount}][url_target]" class="form-control">
                                                                <option value="0">Open in New Tab</option>
                                                                <option value="1">Open in Same Tab</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_url_text" class="form-label">Card URL Text:</label>
                                                            <input type="text" id="sections_${sectionCount}_cards_${cardCount}_url_text" name="sections[${sectionCount}][cards][${cardCount}][url_text]" class="form-control">
                                                        </div>
                                                        <div class="col-md-12">
                                                            <label for="sections_${sectionCount}_cards_${cardCount}_description" class="form-label">Card Description:</label>
                                                            <textarea class="form-control editor" maxlength="255" id="sections_${sectionCount}_cards_${cardCount}_description" name="sections[${sectionCount}][cards][${cardCount}][description]" placeholder="Write something here..."></textarea>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                </div>
                                            `;
        }

        function generateListItem(sectionCount, itemCount) {
            return `
                                            <div class="list-item mb-3 p-2 border rounded bg-light" id="sections_${sectionCount}_list_item_${itemCount}">
                                                <div class="ibox-title border-0 p-0 bg-light">
                                                    <h5>List Item ${itemCount + 1}</h5>
                                                    <div class="ibox-tools">
                                                        <a type="button" class="text-danger" href="javascript:void(0)"
                                                            onclick="removeListItem(${sectionCount}, ${itemCount})">
                                                            Remove
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-md-6">
                                                        <label for="sections_${sectionCount}_list_items_${itemCount}_url_text" class="form-label">Item URL Text:</label>
                                                        <input type="text" id="sections_${sectionCount}_list_items_${itemCount}_url_text" name="sections[${sectionCount}][list_items][${itemCount}][url_text]" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="sections_${sectionCount}_list_items_${itemCount}_icon" class="form-label">Item Icon:</label>
                                                        <div>
                                                            <button type="button" style="width: 49%;" class="btn btn-primary btn-sm"
                                                                onclick="triggerFileInputItem(${sectionCount}, ${itemCount}, 'icon')">
                                                                <i class="fa fa-upload"></i>
                                                            </button>
                                                            <button type="button" style="width: 49%;" class="btn btn-default btn-sm"
                                                                onclick="openLibraryModalItem(${sectionCount}, ${itemCount}, 'icon')">
                                                                <i class="fa fa-image"></i>
                                                            </button>
                                                        </div>

                                                        <input type="radio" class="hidden" name="sections[${sectionCount}][list_items][${itemCount}][icon_source]" value="upload"
                                                            id="sections_${sectionCount}_list_items_${itemCount}_radio_upload_icon">
                                                        <input type="radio" class="hidden" name="sections[${sectionCount}][list_items][${itemCount}][icon_source]" value="library"
                                                            id="sections_${sectionCount}_list_items_${itemCount}_radio_library_icon">

                                                        <input type="file" id="sections_${sectionCount}_list_items_${itemCount}_icon"
                                                            name="sections[${sectionCount}][list_items][${itemCount}][icon]" class="hidden"
                                                            onchange="handleItemFileChange(this, ${sectionCount}, ${itemCount}, 'icon')">

                                                        <input type="hidden" id="sections_${sectionCount}_list_items_${itemCount}_icon_url"
                                                            name="sections[${sectionCount}][list_items][${itemCount}][icon]">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="sections_${sectionCount}_list_items_${itemCount}_url" class="form-label">Item URL:</label>
                                                        <input type="url" id="sections_${sectionCount}_list_items_${itemCount}_url" name="sections[${sectionCount}][list_items][${itemCount}][url]" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="sections_${sectionCount}_list_items_${itemCount}_url_target" class="form-label">Item URL:</label>
                                                        <select id="sections_${sectionCount}_list_items_${itemCount}_url_target" name="sections[${sectionCount}][list_items][${itemCount}][url_target]" class="form-control">
                                                            <option value="0">Open in New Tab</option>
                                                            <option value="1">Open in Same Tab</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
        }

        function addCareer(sectionCount) {
            const container = document.querySelector(`#sections_${sectionCount}_cards_container`);
            const currentCareerCount = container.querySelectorAll('.card-item').length;
            alert(currentCareerCount);
            const newCareer = generateCareer(sectionCount, currentCareerCount);
            container.insertAdjacentHTML('beforeend', newCareer);
        }

        function addCard(sectionCount) {
            const container = document.querySelector(`#sections_${sectionCount}_cards_container`);
            const currentCardCount = container.querySelectorAll('.card-item').length;
            const newCard = generateCard(sectionCount, currentCardCount);
            container.insertAdjacentHTML('beforeend', newCard);
        }

        function addIconCard(sectionCount) {
            const container = document.querySelector(`#sections_${sectionCount}_cards_container`);
            const currentCardCount = container.querySelectorAll('.card-item').length;
            const newCard = generateIconCard(sectionCount, currentCardCount);
            container.insertAdjacentHTML('beforeend', newCard);
        }

        function removeCard(sectionCount, careerCount) {
            const card = document.querySelector(`#sections_${sectionCount}_card_${careerCount}`);
            if (card) {
                card.remove();
            }
        }

        function removeCard(sectionCount, cardCount) {
            const card = document.querySelector(`#sections_${sectionCount}_card_${cardCount}`);
            if (card) {
                card.remove();
            }
        }

        function addListItem(sectionCount) {
            const container = document.querySelector(`#sections_${sectionCount}_list_container`);
            if (!container) {
                console.error(`Container with ID "sections_${sectionCount}_list_container" not found.`);
                return;
            }

            const currentItemCount = container.querySelectorAll('.list-item').length;
            const newItem = generateListItem(sectionCount, currentItemCount);
            container.insertAdjacentHTML('beforeend', newItem);
        }

        function removeListItem(sectionCount, itemCount) {
            const item = document.querySelector(`#sections_${sectionCount}_list_item_${itemCount}`);
            if (item) {
                item.remove();
            }
        }

        function toggleTransparent(checkbox, colorInputId) {
            const colorInput = document.getElementById(colorInputId);
            if (checkbox.checked) {
                colorInput.value = '#000000';  // Set to default color
                colorInput.disabled = true;
            } else {
                colorInput.disabled = false;
            }
        }

        function toggleDisable(checkbox, inputId) {
            const input = document.getElementById(inputId);
            if (checkbox.checked) {
                input.disabled = true;
            } else {
                input.disabled = false;
            }
        }

        function editSections(section) {
            let sectionHTML = "";
            const sectionName = section.replace(/([a-z])([A-Z])/g, "$1 $2").replace(/^./, str => str.toUpperCase());
            const existingSections = document.querySelectorAll(`.ibox-container`);
            const count = existingSections.length;

            if (section === "heroBanner") {
                sectionHTML = `<div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                                        <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                            <div class="ibox-title">
                                                                                <h5>${sectionName}</h5>
                                                                                <div class="ibox-tools">
                                                                                    <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                                    <a class="close-link"><i class="fa fa-times"></i></a>
                                                                                </div>
                                                                            </div>
                                                                            <div class="ibox-content">
                                                                                <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="hero-banner">

                                                                                <div class="row">
                                                                                    <div class="col-md-6">
                                                                                        <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                                        <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <label for="sections_${count}_subtitle" class="form-label">Sub-title:</label>
                                                                                        <input type="text" id="sections_${count}_subtitle" name="sections[${count}][subtitle]" class="form-control">
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <label for="sections_${count}_image" class="control-label block">Background Image:</label>

                                                                                        <!-- Button Group -->
                                                                                        <div style="margin-bottom: 5px;">
                                                                                            <!-- Upload Button -->
                                                                                            <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-primary btn-sm" onclick="triggerFileInput(${count}, 'image')">
                                                                                                <i class="fa fa-upload"></i>
                                                                                            </button>

                                                                                            <!-- Library Button -->
                                                                                            <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-default btn-sm" onclick="openLibraryModal(${count}, 'image')">
                                                                                                <i class="fa fa-image"></i>
                                                                                            </button>
                                                                                        </div>

                                                                                        <input type="radio" name="sections[${count}][image_source]" value="upload" id="sections_${count}_radio_upload_image" hidden>
                                                                                        <input type="radio" name="sections[${count}][image_source]" value="library" id="sections_${count}_radio_library_image" hidden>

                                                                                        <!-- Hidden file input -->
                                                                                        <input type="file" id="sections_${count}_image" name="sections[${count}][image]" class="hidden" onchange="handleFileChange(this, ${count})">

                                                                                        <!-- Hidden Image URL input for library -->
                                                                                        <input type="hidden" id="sections_${count}_image_url" name="sections[${count}][image]">
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <label for="sections_${count}_video" class="form-label">YouTube Video URL:</label>
                                                                                        <input type="url" id="sections_${count}_video" name="sections[${count}][video]" class="form-control" placeholder="Enter YouTube Video URL">
                                                                                        <div id="video-preview-${count}" class="mt-2" style="display: none;">
                                                                                            <small>Current Video:</small>
                                                                                            <iframe width="100%" height="200" id="video-iframe-${count}" frameborder="0" allowfullscreen></iframe>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-md-12">
                                                                                        <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                                        <textarea id="sections_${count}_description" name="sections[${count}][description]" class="form-control editor"></textarea>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                `;
            } else if (section === "banner") {
                sectionHTML = `<div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                    <div class="ibox-title">
                                                                        <h5>${sectionName}</h5>
                                                                        <div class="ibox-tools">
                                                                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                            <a class="close-link"><i class="fa fa-times"></i></a>
                                                                        </div>
                                                                    </div>
                                                                    <div class="ibox-content">
                                                                        <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="banner">
                                                                        <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                                    <input type="color" id="sections_${count}_background_color" name="sections[${count}][background_color]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <label for="sections_${count}_color" class="form-label">Text Color:</label>
                                                                                    <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_image" class="control-label block">Background Image:</label>

                                                                                    <!-- Button Group -->
                                                                                    <div style="margin-bottom: 5px;">
                                                                                        <!-- Upload Button -->
                                                                                        <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-primary btn-sm" onclick="triggerFileInput(${count}, 'image')">
                                                                                            <i class="fa fa-upload"></i>
                                                                                        </button>

                                                                                        <!-- Library Button -->
                                                                                        <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-default btn-sm" onclick="openLibraryModal(${count}, 'image')">
                                                                                            <i class="fa fa-image"></i>
                                                                                        </button>
                                                                                    </div>

                                                                                    <input type="radio" name="sections[${count}][image_source]" value="upload" id="sections_${count}_radio_upload_image" hidden>
                                                                                    <input type="radio" name="sections[${count}][image_source]" value="library" id="sections_${count}_radio_library_image" hidden>

                                                                                    <!-- Hidden file input -->
                                                                                    <input type="file" id="sections_${count}_image" name="sections[${count}][image]" class="hidden" onchange="handleFileChange(this, ${count})">

                                                                                    <!-- Hidden Image URL input for library -->
                                                                                    <input type="hidden" id="sections_${count}_image_url" name="sections[${count}][image]">
                                                                                </div>

                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                                    <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_subtitle" class="form-label">SubTitle:</label>
                                                                                    <input type="text" id="sections_${count}_subtitle" name="sections[${count}][subtitle]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-12">
                                                                                    <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                                    <textarea type="text" id="sections_${count}_description" name="sections[${count}][description]" class="form-control editor"></textarea>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_solid_button_url" class="form-label">Button URL:</label>
                                                                                    <input type="url" id="sections_${count}_solid_button_url" name="sections[${count}][solid_button_url]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_solid_url_target" class="form-label">URL Target:</label>
                                                                                    <select id="sections_${count}_solid_url_target" name="sections[${count}][solid_url_target]" class="form-control">
                                                                                        <option value="0">Open in New Tab</option>
                                                                                        <option value="1">Open in Same Tab</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_solid_button_text" class="form-label">Button Text:</label>
                                                                                    <input type="text" id="sections_${count}_solid_button_text" name="sections[${count}][solid_button_text]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_outline_button_url" class="form-label">Button URL:</label>
                                                                                    <input type="url" id="sections_${count}_outline_button_url" name="sections[${count}][outline_button_url]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_outline_url_target" class="form-label">URL Target:</label>
                                                                                    <select id="sections_${count}_outline_url_target" name="sections[${count}][outline_url_target]" class="form-control">
                                                                                        <option value="0">Open in New Tab</option>
                                                                                        <option value="1">Open in Same Tab</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_outline_button_text" class="form-label">Button Text:</label>
                                                                                    <input type="text" id="sections_${count}_outline_button_text" name="sections[${count}][outline_button_text]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                <label for="sections_${count}_breadcrumbs" class="form-label">BreadCrumbs:</label>
                                                                                    <input type="checkbox" id="sections_${count}_breadcrumbs" name="sections[${count}][breadcrumbs]">
                                                                                </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        `;
            } else if (section === "category") {
                sectionHTML = `
                                                    <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                        <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                            <div class="ibox-title">
                                                                <h5>${sectionName}</h5>
                                                                <div class="ibox-tools">
                                                                    <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                    <a class="close-link"><i class="fa fa-times"></i></a>
                                                                </div>
                                                            </div>
                                                            <div class="ibox-content">
                                                                <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="category">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                        <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="sections_${count}_background_color" class="form-label">Border
                                                                                    Color:</label>
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="color" id="sections_${count}_background_color" name="sections[${count}][background_color]" class="form-control" style="flex: 1;">
                                                                            <label class="ml-2">
                                                                                <input type="checkbox" id="sections_${count}_background_color_transparent" onchange="toggleTransparent(this, 'sections_${count}_background_color')"> Transparent
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label for="sections_${count}_columns" class="form-label">Columns:</label>
                                                                        <select id="sections_${count}_columns" name="sections[${count}][columns]" class="form-control">
                                                                            <option value="2">2</option>
                                                                            <option value="3">3</option>
                                                                            <option value="4">4</option>
                                                                        </select>
                                                                    </div>

                                                                    <!-- Order By Selection -->
                                                                    <div class="col-md-4">
                                                                        <label for="sections_${count}_orderby" class="form-label">Order By:</label>
                                                                        <select id="sections_${count}_orderby" name="sections[${count}][orderby]" class="form-control">
                                                                            <option value="id,asc" >Ascending order by Id</option>
                                                                            <option value="id,desc">Descending order by Id</option>
                                                                            <option value="title,asc">Ascending order by Name</option>
                                                                            <option value="title,desc">Descending order by Name</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-4">
                                                                        <label for="sections_${count}_pagination_num" class="form-label">Pagination Number:</label>
                                                                        <input type="number" id="sections_${count}_pagination_num" 
                                                                            name="sections[${count}][pagination_num]" 
                                                                            class="form-control" disabled max="20" min="6" value="6">
                                                                        <label for="sections_${count}_pagination" class="form-check form-label">
                                                                            <input type="checkbox" id="sections_${count}_pagination"
                                                                                name="sections[${count}][pagination]" value="1" onchange="toggleDisable(this, 'sections_${count}_pagination_num')" checked> Pagination
                                                                        </label>
                                                                    </div>

                                                                    <div class="col-md-12">
                                                                        <label for="sections_${count}_categories" class="form-label">Select Category:</label>
                                                                        <select name="sections[${count}][category]" id="sections_${count}_category" class="form-control">
                                                                            @foreach($categories as $category)
                                                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;
            } else if (section === "school-category") {
                sectionHTML = `
                                                    <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                        <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                            <div class="ibox-title">
                                                                <h5>${sectionName}</h5>
                                                                <div class="ibox-tools">
                                                                    <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                    <a class="close-link"><i class="fa fa-times"></i></a>
                                                                </div>
                                                            </div>
                                                            <div class="ibox-content">
                                                                <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="school-category">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                        <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="sections_${count}_background_color" class="form-label">Border
                                                                                    Color:</label>
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="color" id="sections_${count}_background_color" name="sections[${count}][background_color]" class="form-control" style="flex: 1;">
                                                                            <label class="ml-2">
                                                                                <input type="checkbox" id="sections_${count}_background_color_transparent" onchange="toggleTransparent(this, 'sections_${count}_background_color')"> Transparent
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label for="sections_${count}_columns" class="form-label">Columns:</label>
                                                                        <select id="sections_${count}_columns" name="sections[${count}][columns]" class="form-control">
                                                                            <option value="2">2</option>
                                                                            <option value="3">3</option>
                                                                            <option value="4">4</option>
                                                                        </select>
                                                                    </div>

                                                                    <!-- Order By Selection -->
                                                                    <div class="col-md-4">
                                                                        <label for="sections_${count}_orderby" class="form-label">Order By:</label>
                                                                        <select id="sections_${count}_orderby" name="sections[${count}][orderby]" class="form-control">
                                                                            <option value="id,asc" >Ascending order by Id</option>
                                                                            <option value="id,desc">Descending order by Id</option>
                                                                            <option value="title,asc">Ascending order by Name</option>
                                                                            <option value="title,desc">Descending order by Name</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-4">
                                                                        <label for="sections_${count}_pagination_num" class="form-label">Pagination Number:</label>
                                                                        <input type="number" id="sections_${count}_pagination_num" 
                                                                            name="sections[${count}][pagination_num]" 
                                                                            class="form-control" disabled max="20" min="6" value="6">
                                                                        <label for="sections_${count}_pagination" class="form-check form-label">
                                                                            <input type="checkbox" id="sections_${count}_pagination"
                                                                                name="sections[${count}][pagination]" value="1" onchange="toggleDisable(this, 'sections_${count}_pagination_num')" checked> Pagination
                                                                        </label>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label for="sections_${count}_schools" class="form-label">Select School:</label>
                                                                        <select name="sections[${count}][school]" id="sections_${count}_school" class="form-control">
                                                                            @foreach($schools as $school)
                                                                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label for="sections_${count}_categories" class="form-label">Select Category:</label>
                                                                        <select name="sections[${count}][category]" id="sections_${count}_category" class="form-control">
                                                                            @foreach($categories as $category)
                                                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;
            } else if (section === "titleSection") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="title-section">
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_url" class="form-label">URL:</label>
                                                                            <input type="url" id="sections_${count}_url" name="sections[${count}][url]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_url_target" class="form-label">URL Target:</label>
                                                                            <select id="sections_${count}_url_target" name="sections[${count}][url_target]" class="form-control">
                                                                                <option value="0">Open in New Tab</option>
                                                                                <option value="1">Open in Same Tab</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_url_text" class="form-label">URL Text:</label>
                                                                            <input type="text" id="sections_${count}_url_text" name="sections[${count}][url_text]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_alignment" class="form-label">Text Alignment:</label>
                                                                            <select id="sections_${count}_alignment" name="sections[${count}][alignment]" class="form-control">
                                                                                <option value="left">Left</option>
                                                                                <option value="center">Center</option>
                                                                                <option value="right">Right</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_background" class="form-label">Background:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "mediaSection") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="media-section">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_background_color" class="form-label">Background Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background_color" name="sections[${count}][background_color]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_color_transparent" onchange="toggleTransparent(this, 'sections_${count}_background_color')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_content_background" class="form-label">Content Background:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_content_background" name="sections[${count}][content_background]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_content_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_content_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control" style="flex: 1;">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_layout" class="form-label">Layout:</label>
                                                                            <select id="sections_${count}_layout" name="sections[${count}][layout]" class="form-control">
                                                                                <option value="layout-1">Layout 1</option>
                                                                                <option value="layout-2">Layout 2</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_icon" class="form-label">Icon:</label>
                                                                            <!-- Button Group -->
                                                                            <div>
                                                                                <!-- Upload Button -->
                                                                                <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-primary btn-sm" onclick="triggerFileInput(${count}, 'icon')">
                                                                                    <i class="fa fa-upload"></i>
                                                                                </button>

                                                                                <!-- Library Button -->
                                                                                <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-default btn-sm" onclick="openLibraryModal(${count}, 'icon')">
                                                                                    <i class="fa fa-image"></i>
                                                                                </button>
                                                                            </div>

                                                                            <input type="radio" name="sections[${count}][icon_source]" value="upload" id="sections_${count}_radio_upload_icon" hidden>
                                                                            <input type="radio" name="sections[${count}][icon_source]" value="library" id="sections_${count}_radio_library_icon" hidden>

                                                                            <!-- Hidden file input -->
                                                                            <input type="file" id="sections_${count}_icon" name="sections[${count}][icon]" class="hidden" onchange="handleFileChange(this, ${count})">

                                                                            <!-- Hidden Image URL input for library -->
                                                                            <input type="hidden" id="sections_${count}_icon_url" name="sections[${count}][icon]">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_image" class="control-label block">Image:</label>

                                                                            <!-- Button Group -->
                                                                            <div>
                                                                                <!-- Upload Button -->
                                                                                <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-primary btn-sm" onclick="triggerFileInput(${count}, 'image')">
                                                                                    <i class="fa fa-upload"></i>
                                                                                </button>

                                                                                <!-- Library Button -->
                                                                                <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-default btn-sm" onclick="openLibraryModal(${count}, 'image')">
                                                                                    <i class="fa fa-image"></i>
                                                                                </button>
                                                                            </div>

                                                                            <input type="radio" name="sections[${count}][image_source]" value="upload" id="sections_${count}_radio_upload_image" hidden>
                                                                            <input type="radio" name="sections[${count}][image_source]" value="library" id="sections_${count}_radio_library_image" hidden>

                                                                            <!-- Hidden file input -->
                                                                            <input type="file" id="sections_${count}_image" name="sections[${count}][image]" class="hidden" onchange="handleFileChange(this, ${count})">

                                                                            <!-- Hidden Image URL input for library -->
                                                                            <input type="hidden" id="sections_${count}_image_url" name="sections[${count}][image]">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_image_placement" class="form-label">Image Placement:</label>
                                                                            <select id="sections_${count}_image_placement" name="sections[${count}][image_placement]" class="form-control">
                                                                                <option value="left">Left</option>
                                                                                <option value="right">Right</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_url" class="form-label">URL:</label>
                                                                            <input type="url" id="sections_${count}_url" name="sections[${count}][url]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_url_target" class="form-label">URL Target:</label>
                                                                            <select id="sections_${count}_url_target" name="sections[${count}][url_target]" class="form-control">
                                                                                <option value="0">Open in New Tab</option>
                                                                                <option value="1">Open in Same Tab</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_url_text" class="form-label">URL Text:</label>
                                                                            <input type="text" id="sections_${count}_url_text" name="sections[${count}][url_text]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "cards") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a type="button" class="" href="javascript:void(0)" onclick="addIconCard(${count})"><i class="fa fa-plus"></i> Add Cards</a>
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="cards">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_columns" class="form-label">Columns:</label>
                                                                            <select class="form-control" id="sections_${count}_columns" name="sections[${count}][columns]">
                                                                                <option value="1">1</option>
                                                                                <option value="2">2</option>
                                                                                <option value="3">3</option>
                                                                                <option value="4">4</option>
                                                                                <option value="5">5</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_layout" class="form-label">Layouts:</label>
                                                                            <select id="sections_${count}_layout" name="sections[${count}][layout]" class="form-control">
                                                                                <option value="layout-1">Layout 1</option>
                                                                                <option value="layout-2">Layout 2</option>
                                                                                <option value="layout-3">Layout 3</option>
                                                                                <option value="layout-4">Layout 4</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_image" class="control-label block">Background Image:</label>

                                                                            <!-- Button Group -->
                                                                            <div style="margin-bottom: 5px;">
                                                                                <!-- Upload Button -->
                                                                                <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-primary btn-sm" onclick="triggerFileInput(${count}, 'image')">
                                                                                    <i class="fa fa-upload"></i>
                                                                                </button>

                                                                                <!-- Library Button -->
                                                                                <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-default btn-sm" onclick="openLibraryModal(${count}, 'image')">
                                                                                    <i class="fa fa-image"></i>
                                                                                </button>
                                                                            </div>

                                                                            <input type="radio" name="sections[${count}][image_source]" value="upload" id="sections_${count}_radio_upload_image" hidden>
                                                                            <input type="radio" name="sections[${count}][image_source]" value="library" id="sections_${count}_radio_library_image" hidden>

                                                                            <!-- Hidden file input -->
                                                                            <input type="file" id="sections_${count}_image" name="sections[${count}][image]" class="hidden" onchange="handleFileChange(this, ${count})">

                                                                            <!-- Hidden Image URL input for library -->
                                                                            <input type="hidden" id="sections_${count}_image_url" name="sections[${count}][image]">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;" value="#ffffff">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_alignment" class="form-label">Text Alignment:</label>
                                                                            <select id="sections_${count}_alignment" name="sections[${count}][alignment]" class="form-control">
                                                                                <option value="left">Left</option>
                                                                                <option value="center">Center</option>
                                                                                <option value="right">Right</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div id="sections_${count}_cards_container">
                                                                        ${generateIconCard(count, 0)} <!-- Initial card -->
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "clients") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="clients">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_background" class="form-label">Background:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;" value="#ffffff">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === 'gridCards') {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="row">
                                                                <div class="col-lg-12">
                                                                    <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                        <div class="ibox-title">
                                                                            <h5>${sectionName}</h5>
                                                                            <div class="ibox-tools">
                                                                        <a type="button" class="" href="javascript:void(0)" onclick="addCard(${count})"><i class="fa fa-plus"></i> Add Cards</a>
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                        </div>
                                                                        <div class="ibox-content">
                                                                            <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="grid-cards" class="form-control">
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                                    <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <label for="sections_${count}_subtitle" class="form-label">Sub-title:</label>
                                                                                    <input type="text" id="sections_${count}_subtitle" name="sections[${count}][subtitle]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-12">
                                                                                    <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                                    <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_columns" class="form-label">Columns:</label>
                                                                                    <select id="sections_${count}_columns" name="sections[${count}][columns]" class="form-control">
                                                                                        <option value="1">1</option>
                                                                                        <option value="2">2</option>
                                                                                        <option value="3">3</option>
                                                                                        <option value="4">4</option>
                                                                                        <option value="5">5</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_layout" class="form-label">Layouts:</label>
                                                                                    <select id="sections_${count}_layout" name="sections[${count}][layout]" class="form-control">
                                                                                        <option value="layout-1">Layout 1</option>
                                                                                        <option value="layout-2">Layout 2</option>
                                                                                        <option value="layout-3">Layout 3</option>
                                                                                        <option value="layout-4">Layout 4</option>
                                                                                        <option value="layout-5">Layout 5</option>
                                                                                        <option value="layout-6">Layout 6</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <label for="sections_${count}_image" class="form-label">Background Image:</label>
                                                                                    <!-- Button Group -->
                                                                                    <div style="margin-bottom: 5px;">
                                                                                        <!-- Upload Button -->
                                                                                        <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-primary btn-sm" onclick="triggerFileInput(${count}, 'image')">
                                                                                            <i class="fa fa-upload"></i>
                                                                                        </button>

                                                                                        <!-- Library Button -->
                                                                                        <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-default btn-sm" onclick="openLibraryModal(${count}, 'image')">
                                                                                            <i class="fa fa-image"></i>
                                                                                        </button>
                                                                                    </div>

                                                                                    <input type="radio" name="sections[${count}][image_source]" value="upload" id="sections_${count}_radio_upload_image" hidden>
                                                                                    <input type="radio" name="sections[${count}][image_source]" value="library" id="sections_${count}_radio_library_image" hidden>

                                                                                    <!-- Hidden file input -->
                                                                                    <input type="file" id="sections_${count}_image" name="sections[${count}][image]" class="hidden" onchange="handleFileChange(this, ${count})">

                                                                                    <!-- Hidden Image URL input for library -->
                                                                                    <input type="hidden" id="sections_${count}_image_url" name="sections[${count}][image]">
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                                    <input type="color" id="sections_${count}_background_color" name="sections[${count}][background_color]" class="form-control">
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <label for="sections_${count}_color" class="form-label">Text Color:</label>
                                                                                    <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                                </div>
                                                                            </div>
                                                                            <div id="sections_${count}_cards_container">
                                                                        ${generateCard(count, 0)} <!-- Initial card -->
                                                                    </div>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "list") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a type="button" class="" href="javascript:void(0)" onclick="addListItem(${count})">
                                                                            <i class="fa fa-plus"></i> Add List Item
                                                                        </a>
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="list">
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description"
                                                                                name="sections[${count}][description]"></textarea>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_background" class="form-label">Background:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;" value="#ffffff">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_columns" class="form-label">Columns:</label>
                                                                            <select id="sections_${count}_columns" name="sections[${count}][columns]" class="form-control">
                                                                                <option value="1">1</option>
                                                                                <option value="2">2</option>
                                                                                <option value="3">3</option>
                                                                                <option value="4">4</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_layout" class="form-label">Layouts:</label>
                                                                            <select id="sections_${count}_layout" name="sections[${count}][layout]" class="form-control">
                                                                                <option value="layout-1">Layout 1</option>
                                                                                <option value="layout-2">Layout 2</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_seperator" class="form-label">Seperator:</label>
                                                                            <select id="sections_${count}_seperator" name="sections[${count}][seperator]" class="form-control">
                                                                                <option value="0">No</option>
                                                                                <option value="1"Yes</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div id="sections_${count}_list_container">
                                                                        ${generateListItem(count, 0)} <!-- Initial List Item -->
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "separator") {
                sectionHTML = `
                                            <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                    <div class="ibox-title">
                                                        <h5>${sectionName}</h5>
                                                        <div class="ibox-tools">
                                                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                            <a class="close-link"><i class="fa fa-times"></i></a>
                                                        </div>
                                                    </div>
                                                    <div class="ibox-content">
                                                        <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="separator">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_height" class="form-label">Height:</label>
                                                                <input type="number" id="sections_${count}_height" name="sections[${count}][height]" class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
            } else if (section === "certificate") {
                sectionHTML = `
                                            <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                    <div class="ibox-title">
                                                        <h5>${sectionName}</h5>
                                                        <div class="ibox-tools">
                                                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                            <a class="close-link"><i class="fa fa-times"></i></a>
                                                        </div>
                                                    </div>
                                                    <div class="ibox-content">
                                                        <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="certificate">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_border_color" class="form-label">Border Color:</label>
                                                                <div class="d-flex align-items-center">
                                                                    <input type="color" id="sections_${count}_border_color" name="sections[${count}][border_color]" class="form-control" style="flex: 1;" value="#ffffff">
                                                                    <label class="ml-2">
                                                                        <input type="checkbox" id="sections_${count}_border_color_transparent" onchange="toggleTransparent(this, 'sections_${count}_border_color')"> Transparent
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_subtitle" class="form-label">Sub-Title:</label>
                                                                <input type="text" id="sections_${count}_subtitle" name="sections[${count}][subtitle]" class="form-control">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_image" class="form-label">Certificate:</label>
                                                                <!-- Button Group -->
                                                                <div style="margin-bottom: 5px;">
                                                                    <!-- Upload Button -->
                                                                    <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-primary btn-sm" onclick="triggerFileInput(${count}, 'image')">
                                                                        <i class="fa fa-upload"></i>
                                                                    </button>

                                                                    <!-- Library Button -->
                                                                    <button type="button" style="width: 49%; display: inline-block; margin: 0;" class="btn btn-default btn-sm" onclick="openLibraryModal(${count}, 'image')">
                                                                        <i class="fa fa-image"></i>
                                                                    </button>
                                                                </div>

                                                                <input type="radio" name="sections[${count}][image_source]" value="upload" id="sections_${count}_radio_upload_image" hidden>
                                                                <input type="radio" name="sections[${count}][image_source]" value="library" id="sections_${count}_radio_library_image" hidden>

                                                                <!-- Hidden file input -->
                                                                <input type="file" id="sections_${count}_image" name="sections[${count}][image]" class="hidden" onchange="handleFileChange(this, ${count})">

                                                                <!-- Hidden Image URL input for library -->
                                                                <input type="hidden" id="sections_${count}_image_url" name="sections[${count}][image]">
                                                            </div>                                                          
                                                        <div class="col-md-6">
                                                            <label for="sections_${count}_certificate_name" class="form-label">Certificate Name:</label>
                                                            <input type="text" id="sections_${count}_certificate_name" name="sections[${count}][certifiate_name]" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
            } else if (section === "contactus") {
                sectionHTML = `
                                            <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                    <div class="ibox-title">
                                                        <h5>${sectionName}</h5>
                                                        <div class="ibox-tools">
                                                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                            <a class="close-link"><i class="fa fa-times"></i></a>
                                                        </div>
                                                    </div>
                                                    <div class="ibox-content">
                                                        <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="contactus">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_background" class="form-label">Background:</label>
                                                                <div class="d-flex align-items-center">
                                                                    <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;" value="#ffffff">
                                                                    <label class="ml-2">
                                                                        <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label for="sections_${count}_iframe" class="form-label">Iframe:</label>
                                                                <input type="text" id="sections_${count}_iframe" name="sections[${count}][iframe]" class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
            } else if (section === "programmes") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="programmes">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_border_color" class="form-label">Border Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_border_color" name="sections[${count}][border_color]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_border_color_transparent" onchange="toggleTransparent(this, 'sections_${count}_border_color')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_card_background" class="form-label">Card Background:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_card_background" name="sections[${count}][card_background]" value="#FFFFFF" class="form-control" style="flex: 1;">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_columns" class="form-label">Columns:</label>
                                                                            <select id="sections_${count}_columns" name="sections[${count}][columns]" class="form-control">
                                                                                <option value="2">2</option>
                                                                                <option value="3">3</option>
                                                                                <option value="4">4</option>
                                                                            </select>
                                                                        </div>

                                                                        <!-- Order By Selection -->
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_orderby" class="form-label">Order By:</label>
                                                                            <select id="sections_${count}_orderby" name="sections[${count}][orderby]" class="form-control">
                                                                                <option value="id,asc" >Ascending order by Id</option>
                                                                                <option value="id,desc">Descending order by Id</option>
                                                                                <option value="name,asc">Ascending order by Name</option>
                                                                                <option value="name,desc">Descending order by Name</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_pagination_num" class="form-label">Pagination Number:</label>
                                                                            <input type="number" id="sections_${count}_pagination_num" 
                                                                                name="sections[${count}][pagination_num]" 
                                                                                class="form-control" disabled max="20" min="6" value="6">
                                                                            <label for="sections_${count}_pagination" class="form-check form-label">
                                                                                <input type="checkbox" id="sections_${count}_pagination"
                                                                                    name="sections[${count}][pagination]" value="1" onchange="toggleDisable(this, 'sections_${count}_pagination_num')" checked> Pagination
                                                                            </label>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_url_target" class="form-label">URL Target:</label>
                                                                            <select id="sections_${count}_url_target" name="sections[${count}][url_target]" class="form-control">
                                                                                <option value="0">Open in New Tab</option>
                                                                                <option value="1">Open in Same Tab</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "filterCourses") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="filter-courses">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_filter_color" class="form-label">Filter Color:</label>
                                                                            <input type="color" id="sections_${count}_filter_color" name="sections[${count}][filter_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_filter_background" class="form-label">Filter Backgound:</label>
                                                                            <input type="color" id="sections_${count}_filter_background" name="sections[${count}][filter_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_filter_border_color" class="form-label">Filter Border Color:</label>
                                                                            <input type="color" id="sections_${count}_filter_border_color" name="sections[${count}][filter_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_filter_color" class="form-label">Active Filter Color:</label>
                                                                            <input type="color" id="sections_${count}_active_filter_color" name="sections[${count}][active_filter_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_filter_background" class="form-label">Active Filter Backgound:</label>
                                                                            <input type="color" id="sections_${count}_active_filter_background" name="sections[${count}][active_filter_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_filter_border_color" class="form-label">Active Filter Border Color:</label>
                                                                            <input type="color" id="sections_${count}_active_filter_border_color" name="sections[${count}][active_filter_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_tab_color" class="form-label">Tab Color:</label>
                                                                            <input type="color" id="sections_${count}_tab_color" name="sections[${count}][tab_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_tab_background" class="form-label">Tab Backgound:</label>
                                                                            <input type="color" id="sections_${count}_tab_background" name="sections[${count}][tab_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_tab_border_color" class="form-label">Tab Border Color:</label>
                                                                            <input type="color" id="sections_${count}_tab_border_color" name="sections[${count}][tab_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_tab_color" class="form-label">Active Tab Color:</label>
                                                                            <input type="color" id="sections_${count}_active_tab_color" name="sections[${count}][active_tab_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_tab_background" class="form-label">Active Tab Backgound:</label>
                                                                            <input type="color" id="sections_${count}_active_tab_background" name="sections[${count}][active_tab_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_tab_border_color" class="form-label">Active Tab Border Color:</label>
                                                                            <input type="color" id="sections_${count}_active_tab_border_color" name="sections[${count}][active_tab_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_content_color" class="form-label">Content Color:</label>
                                                                            <input type="color" id="sections_${count}_content_color" name="sections[${count}][content_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_content_background" class="form-label">Content Backgound:</label>
                                                                            <input type="color" id="sections_${count}_content_background" name="sections[${count}][content_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_content_border_color" class="form-label">Content Border Color:</label>
                                                                            <input type="color" id="sections_${count}_content_border_color" name="sections[${count}][content_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}background_transparent" onchange="toggleTransparent(this, 'sections${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Order By Selection -->
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_orderby" class="form-label">Order By:</label>
                                                                            <select id="sections_${count}_orderby" name="sections[${count}][orderby]" class="form-control">
                                                                                <option value="id,asc" >Ascending order by Id</option>
                                                                                <option value="id,desc">Descending order by Id</option>
                                                                                <option value="name,asc">Ascending order by Name</option>
                                                                                <option value="name,desc">Descending order by Name</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_url_target" class="form-label">URL Target:</label>
                                                                            <select id="sections_${count}_url_target" name="sections[${count}][url_target]" class="form-control">
                                                                                <option value="0">Open in New Tab</option>
                                                                                <option value="1">Open in Same Tab</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "course-agendas") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="course-agendas">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_filter_color" class="form-label">Filter Color:</label>
                                                                            <input type="color" id="sections_${count}_filter_color" name="sections[${count}][filter_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_filter_background" class="form-label">Filter Backgound:</label>
                                                                            <input type="color" id="sections_${count}_filter_background" name="sections[${count}][filter_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_filter_border_color" class="form-label">Filter Border Color:</label>
                                                                            <input type="color" id="sections_${count}_filter_border_color" name="sections[${count}][filter_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_filter_color" class="form-label">Active Filter Color:</label>
                                                                            <input type="color" id="sections_${count}_active_filter_color" name="sections[${count}][active_filter_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_filter_background" class="form-label">Active Filter Backgound:</label>
                                                                            <input type="color" id="sections_${count}_active_filter_background" name="sections[${count}][active_filter_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_active_filter_border_color" class="form-label">Active Filter Border Color:</label>
                                                                            <input type="color" id="sections_${count}_active_filter_border_color" name="sections[${count}][active_filter_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_color" class="form-label">Card Color:</label>
                                                                            <input type="color" id="sections_${count}_card_color" name="sections[${count}][card_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_background" class="form-label">Card Backgound:</label>
                                                                            <input type="color" id="sections_${count}_card_background" name="sections[${count}][card_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_border_color" class="form-label">Card Border Color:</label>
                                                                            <input type="color" id="sections_${count}_card_border_color" name="sections[${count}][card_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}background_transparent" onchange="toggleTransparent(this, 'sections${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Order By Selection -->
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_orderby" class="form-label">Order By:</label>
                                                                            <select id="sections_${count}_orderby" name="sections[${count}][orderby]" class="form-control">
                                                                                <option value="id,asc" >Ascending order by Id</option>
                                                                                <option value="id,desc">Descending order by Id</option>
                                                                                <option value="name,asc">Ascending order by Name</option>
                                                                                <option value="name,desc">Descending order by Name</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "testimonials") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="testimonials">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_sidebar_color" class="form-label">Sidebar Color:</label>
                                                                            <input type="color" id="sections_${count}_sidebar_color" name="sections[${count}][_sidebar_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_sidebar_background" class="form-label">Sidebar Backgound:</label>
                                                                            <input type="color" id="sections_${count}_sidebar_background" name="sections[${count}][sidebar_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_sidebar_border_color" class="form-label">Sidebar Border Color:</label>
                                                                            <input type="color" id="sections_${count}_sidebar_border_color" name="sections[${count}][sidebar_border_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_color" class="form-label">Card Color:</label>
                                                                            <input type="color" id="sections_${count}_card_color" name="sections[${count}][card_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_background" class="form-label">Card Backgound:</label>
                                                                            <input type="color" id="sections_${count}_card_background" name="sections[${count}][card_background]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_border_color" class="form-label">Card Border Color:</label>
                                                                            <input type="color" id="sections_${count}_card_border_color" name="sections[${count}][card_border_color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_color" class="form-label">Text Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}background_transparent" onchange="toggleTransparent(this, 'sections${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_columns" class="form-label">Columns:</label>
                                                                            <select id="sections_${count}_columns" name="sections[${count}][columns]" class="form-control">
                                                                                <option value="2">2</option>
                                                                                <option value="3" selected>3</option>
                                                                                <option value="4">4</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_pagination_num" class="form-label">Pagination Number:</label>
                                                                            <input type="number" id="sections_${count}_pagination_num" 
                                                                                name="sections[${count}][pagination_num]" 
                                                                                class="form-control" max="20" min="6" value="6">
                                                                        </div>

                                                                        <!-- Order By Selection -->
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_category_orderby" class="form-label">Category Order By:</label>
                                                                            <select id="sections_${count}_category_orderby" name="sections[${count}][category_orderby]" class="form-control">
                                                                                <option value="id,asc" >Ascending order by Id</option>
                                                                                <option value="id,desc">Descending order by Id</option>
                                                                                <option value="name,asc">Ascending order by Name</option>
                                                                                <option value="name,desc">Descending order by Name</option>
                                                                            </select>
                                                                        </div>

                                                                        <!-- Order By Selection -->
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_testimonials_orderby" class="form-label">Testimonials Order By:</label>
                                                                            <select id="sections_${count}_testimonials_orderby" name="sections[${count}][testimonials_orderby]" class="form-control">
                                                                                <option value="id,asc" >Ascending order by Id</option>
                                                                                <option value="id,desc">Descending order by Id</option>
                                                                                <option value="name,asc">Ascending order by Name</option>
                                                                                <option value="name,desc">Descending order by Name</option>
                                                                                <option value="priority,asc">Ascending order by Priority Set</option>
                                                                                <option value="priority,desc">Descending order by Priority Set</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_url_target" class="form-label">URL Target:</label>
                                                                            <select id="sections_${count}_url_target" name="sections[${count}][url_target]" class="form-control">
                                                                                <option value="0">Open in New Tab</option>
                                                                                <option value="1">Open in Same Tab</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-8">
                                                                            <div class="form-group">
                                                                                <label class="checkbox-inline">
                                                                                    <input type="hidden" name="sections[${count}][add_sorting]" value="0">
                                                                                    <input type="checkbox" name="sections[${count}][add_sorting]" value="1"> Add sorting
                                                                                </label>
                                                                                <label class="checkbox-inline">
                                                                                    <input type="hidden" name="sections[${count}][add_search]" value="0">
                                                                                    <input type="checkbox" name="sections[${count}][add_search]" value="1"> Add search box
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "content") {
                sectionHTML = `<div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                    <div class="ibox-title">
                                                                        <h5>${sectionName}</h5>
                                                                        <div class="ibox-tools">
                                                                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                            <a class="close-link"><i class="fa fa-times"></i></a>
                                                                        </div>
                                                                    </div>
                                                                    <div class="ibox-content">
                                                                        <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="content">
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                                <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                            </div>

                                                                            <div class="col-md-6">
                                                                                <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                                <div class="d-flex align-items-center">
                                                                                    <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;">
                                                                                    <label class="ml-2">
                                                                                        <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                                <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                                <textarea type="text" id="sections_${count}_description" name="sections[${count}][description]" class="form-control editor"></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        `;
            } else if (section === "career") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a type="button" class="" href="javascript:void(0)" onclick="addCareer(${count})">
                                                                            <i class="fa fa-plus"></i> Add New Career
                                                                        </a>
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="career">
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description"
                                                                                name="sections[${count}][description]"></textarea>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_background" class="form-label">Background:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;" value="#ffffff">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_color" class="form-label">Card Color:</label>
                                                                            <input type="color" id="sections_${count}_card_color" name="sections[${count}][card_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_background" class="form-label">Card Background:</label>
                                                                            <input type="color" id="sections_${count}_card_background" name="sections[${count}][card_background]" value="#FFFFFF" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_border_color" class="form-label">Card Color:</label>
                                                                            <input type="color" id="sections_${count}_card_border_color" name="sections[${count}][card_border_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_hover_color" class="form-label">Card Hover Color:</label>
                                                                            <input type="color" id="sections_${count}_card_hover_color" name="sections[${count}][card_hover_color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_hover_background" class="form-label">Card Hover Background:</label>
                                                                            <input type="color" id="sections_${count}_card_hover_background" name="sections[${count}][card_hover_background]" value="#FFFFFF" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_card_hover_border_color" class="form-label">Card Hover Color:</label>
                                                                            <input type="color" id="sections_${count}_card_hover_border_color" name="sections[${count}][card_hover_border_color]" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                    <div id="sections_${count}_list_container">
                                                                        ${generateCareer(count, 0)} <!-- Initial List Item -->
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            } else if (section === "search-bar") {
                sectionHTML = `
                                            <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                    <div class="ibox-title">
                                                        <h5>${sectionName}</h5>
                                                        <div class="ibox-tools">
                                                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                            <a class="close-link"><i class="fa fa-times"></i></a>
                                                        </div>
                                                    </div>
                                                    <div class="ibox-content">
                                                        <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="search-bar">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_background" class="form-label">Background:</label>
                                                                <div class="d-flex align-items-center">
                                                                    <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;" value="#ffffff">
                                                                    <label class="ml-2">
                                                                        <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_width" class="form-label">Width (%):</label>
                                                                <input type="text" id="sections_${count}_width" name="sections[${count}][width]" class="form-control">
                                                            </div>                                                            
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_title" class="form-label">Label:</label>
                                                                <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_placeholder" class="form-label">Placeholder:</label>
                                                                <input type="text" id="sections_${count}_placeholder" name="sections[${count}][placeholder]" class="form-control">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_search_design" class="form-label">Search design:</label>
                                                                <select name="sections[${count}][search_design]" id="sections_${count}_search_design" class="form-control">
                                                                    <option value="0">Straight</option>
                                                                    <option value="1">Curve</option>
                                                                    <option value="2">Pill</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_alignment" class="form-label">Alignment:</label>
                                                                <select name="sections[${count}][alignment]" id="sections_${count}_alignment" class="form-control">
                                                                    <option value="left">Left</option>
                                                                    <option value="center" selected>Center</option>
                                                                    <option value="right">Right</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_button_background" class="form-label">Button Background:</label>
                                                                <input type="color" id="sections_${count}_button_background" name="sections[${count}][button_background]" class="form-control" value="#000000">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_button_color" class="form-label">Button Text Color:</label>
                                                                <input type="color" id="sections_${count}_button_color" name="sections[${count}][button_color]" class="form-control" value="#ffffff">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sections_${count}_button_text" class="form-label">Button Text:</label>
                                                                <input type="text" id="sections_${count}_button_text" name="sections[${count}][button_text]" class="form-control" placeholder="Search">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
            } else if (section === "instructors") {
                sectionHTML = `
                                                        <div class="wrapper wrapper-content animated fadeInRight ibox-container" style="padding-bottom:0px;">
                                                            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                                                                <div class="ibox-title">
                                                                    <h5>${sectionName}</h5>
                                                                    <div class="ibox-tools">
                                                                        <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                                                        <a class="close-link"><i class="fa fa-times"></i></a>
                                                                    </div>
                                                                </div>
                                                                <div class="ibox-content">
                                                                    <input type="hidden" id="sections_${count}_section_type" name="sections[${count}][section_type]" value="instructors">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_background" class="form-label">Background Color:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_background" name="sections[${count}][background]" class="form-control" style="flex: 1;">
                                                                                <label class="ml-2">
                                                                                    <input type="checkbox" id="sections_${count}_background_transparent" onchange="toggleTransparent(this, 'sections_${count}_background')"> Transparent
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_color" name="sections[${count}][color]" class="form-control">
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_card_background" class="form-label">Card Background:</label>
                                                                            <div class="d-flex align-items-center">
                                                                                <input type="color" id="sections_${count}_card_background" name="sections[${count}][card_background]" value="#FFFFFF" class="form-control" style="flex: 1;">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_card_color" class="form-label">Color:</label>
                                                                            <input type="color" id="sections_${count}_card_color" name="sections[${count}][card_color]" class="form-control">
                                                                        </div>

                                                                        <!-- Order By Selection -->
                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_orderby" class="form-label">Order By:</label>
                                                                            <select id="sections_${count}_orderby" name="sections[${count}][orderby]" class="form-control">
                                                                                <option value="id,asc" >Ascending order by Id</option>
                                                                                <option value="id,desc">Descending order by Id</option>
                                                                                <option value="name,asc">Ascending order by Name</option>
                                                                                <option value="name,desc">Descending order by Name</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="col-md-4">
                                                                            <label for="sections_${count}_pagination_num" class="form-label">Pagination Number:</label>
                                                                            <input type="number" id="sections_${count}_pagination_num" 
                                                                                name="sections[${count}][pagination_num]" 
                                                                                class="form-control" disabled max="20" min="6" value="6">
                                                                            <label for="sections_${count}_pagination" class="form-check form-label">
                                                                                <input type="checkbox" id="sections_${count}_pagination"
                                                                                    name="sections[${count}][pagination]" value="1" onchange="toggleDisable(this, 'sections_${count}_pagination_num')" checked> Pagination
                                                                            </label>
                                                                        </div>

                                                                        <div class="col-md-6">
                                                                            <label for="sections_${count}_title" class="form-label">Title:</label>
                                                                            <input type="text" id="sections_${count}_title" name="sections[${count}][title]" class="form-control">
                                                                        </div>

                                                                        <div class="col-md-12">
                                                                            <label for="sections_${count}_description" class="form-label">Description:</label>
                                                                            <textarea class="form-control editor" id="sections_${count}_description" name="sections[${count}][description]" placeholder="Write something here..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
            }
            // Append section to builder-container
            $("#builder-container").append(sectionHTML);

            if (typeof window.updateSectionOrder === 'function') {
                window.updateSectionOrder();
            }
        }

        let currentSectionId = null;
        let currentFieldType = null;

        function triggerFileInput(i, field) {
            document.getElementById(`sections_${i}_${field}`).click();
        }

        function triggerFileInputCard(sectionIndex, cardIndex, field) {
            document.getElementById(`sections_${sectionIndex}_cards_${cardIndex}_${field}`).click();
        }

        function triggerFileInputItem(sectionIndex, cardIndex, field) {
            document.getElementById(`sections_${sectionIndex}_list_items_${cardIndex}_${field}`).click();
        }

        function handleFileChange(input, i, field) {
            if (input.files && input.files.length > 0) {
                document.getElementById(`sections_${i}_radio_upload_${field}`).checked = true;
                document.getElementById(`sections_${i}_${field}_url`).value = '';
            }
        }

        function handleCardFileChange(input, sectionIndex, cardIndex, field) {
            if (input.files && input.files.length > 0) {
                document.getElementById(`sections_${sectionIndex}_cards_${cardIndex}_radio_upload_${field}`).checked = true;
                document.getElementById(`sections_${sectionIndex}_cards_${cardIndex}_${field}_url`).value = '';
            }
        }

        function handleItemFileChange(input, sectionIndex, itemIndex, field) {
            if (input.files && input.files.length > 0) {
                document.getElementById(`sections_${sectionIndex}_list_items_${itemIndex}_radio_upload_${field}`).checked = true;
                document.getElementById(`sections_${sectionIndex}_list_items_${itemIndex}_${field}_url`).value = '';
            }
        }

        function openLibraryModal(i, field) {
            currentSectionId = `sections_${i}`;
            currentFieldType = field;
            $('#imageLibraryModal').modal('show');
        }

        function openLibraryModalCard(sectionIndex, cardIndex, field) {
            currentSectionId = `sections_${sectionIndex}_cards_${cardIndex}`;
            currentFieldType = field;
            $('#imageLibraryModal').modal('show');
        }

        function openLibraryModalItem(sectionIndex, cardIndex, field) {
            currentSectionId = `sections_${sectionIndex}_list_items_${cardIndex}`;
            currentFieldType = field;
            $('#imageLibraryModal').modal('show');
        }

        document.addEventListener('click', function (event) {

            const searchInput = document.getElementById('imageSearch');
            const imageItems = document.querySelectorAll('.image-item');

            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    const searchValue = this.value.toLowerCase();

                    imageItems.forEach(function (item) {
                        const name = item.getAttribute('data-name');
                        if (name.includes(searchValue)) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            if (event.target.classList.contains('image-select')) {
                let selectedImageUrl = event.target.getAttribute('data-url');

                if (currentSectionId !== null && currentFieldType !== null) {
                    let hiddenInput = document.getElementById(`${currentSectionId}_${currentFieldType}_url`);
                    if (hiddenInput) {
                        hiddenInput.value = selectedImageUrl;
                    }

                    const radio = document.getElementById(`${currentSectionId}_radio_library_${currentFieldType}`);
                    if (radio) {
                        radio.checked = true;
                    }

                    $('#imageLibraryModal').modal('hide');
                    currentSectionId = null;
                    currentFieldType = null;
                }
            }

            if (event.target.closest('.remove-image')) {
                const removeLabel = event.target.closest('.remove-image');
                const wrapper = removeLabel.closest('.image-preview-block');
                if (wrapper) {
                    const viewLink = wrapper.querySelector('.view-anchor');
                    if (viewLink) {
                        viewLink.innerHTML = '<s>View</s>'; // or viewLink.remove(); to completely remove
                        viewLink.setAttribute('href', 'javascript:void(0)');
                        viewLink.style.pointerEvents = 'none';
                        viewLink.style.color = '#999';
                    }
                }
            }
        });

        function showFileModal() {
            $('#fileModal').modal('show');
        }

        function pickLocalFile() {
            $('#fileModal').modal('hide');
            $('#local_meta_thumbnail_input').click();
        }

        function uploadLocalFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function () {
                    document.getElementById('meta_thumbnail_path').value = file.name;
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
            const isMultiple = $('#meta_thumbnail_path').prop('multiple');
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
            const isMultiple = $('#meta_thumbnail_path').prop('multiple');
            if (isMultiple) {
                $('#meta_thumbnail_path').val(selectedImageURLs.join(','));
            } else {
                $('#meta_thumbnail_path').val(selectedImageURLs.length > 0 ? selectedImageURLs[0] : '');
            }
            $('#fileManagerModal').modal('hide');
        }
    </script>
@endpush