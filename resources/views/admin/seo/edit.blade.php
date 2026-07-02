@extends('admin.layout.app')
@section('title', 'Edit Page SEO')
@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.css">
    <style>
        .mb {
            margin-bottom: 1.5rem;
        }

        #keywords_tagsinput, #additional_keywords_tagsinput {
            width: 100% !important;
            height: auto !important;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-height: auto !important;
        }

        #keywords_addTag, #additional_keywords_addTag {
            flex: 0 0 auto;
        }

        .tag,
        #keywords_tag, #additional_keywords_tag {
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
    </style>
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>SEO</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="index.html">Home</a>
                </li>
                <li>
                    <a>SEO</a>
                </li>
                <li class="active">
                    <strong>Edit</strong>
                </li>
            </ol>
        </div>
        <div class="col-lg-2">

        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Edit {{ $page_seo->title }} SEO</h5>
                        <div class="ibox-tools">
                            <!-- <a data-toggle="modal" href="#AddSyllabusModal" data-item-id="">
                                            <i class="fa fa-chevron-circle-right"></i>
                                        </a> -->
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content">
                        <form role="form" action="{{ route('courses-pages-seo.update.post', $page_seo->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-5">
                                    @include('admin.seo.partials.seo-score')
                                </div>
                                <div class="col-lg-7">
                                @if($page_seo->course_id && $page_seo->course)
                                    <div class="col-lg-12 mb">
                                        <label>Course Title</label>
                                        <input type="text" class="form-control"
                                            value="{{ $page_seo->course->title }}" readonly>
                                        <p class="help-block text-muted" style="margin-top:4px;">The course name on the website (H1 banner). Edit under Courses, not here.</p>
                                    </div>
                                @elseif($page_seo->page_id && $page_seo->page)
                                    <div class="col-lg-12 mb">
                                        <label>Page Name</label>
                                        <input type="text" class="form-control"
                                            value="{{ $page_seo->page->page_name }}" readonly>
                                        <p class="help-block text-muted" style="margin-top:4px;">The CMS page name. Main on-page heading comes from the page banner section.</p>
                                    </div>
                                @endif
                                @include('admin.seo.partials.seo-meta-fields', [
                                    'page_seo' => $page_seo,
                                ])
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px;">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Submit</button>
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
    <script src="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.js"></script>
    @include('admin.seo.partials.tags-limits-script')
    <script>
        $(document).ready(function () {
            // Prevent form submit on Enter except inside tagsinput field
            $('form').on('keydown', function (e) {
                if (e.key === 'Enter' && !$(e.target).closest('#keywords_tagsinput, #additional_keywords_tagsinput').length) {
                    e.preventDefault();
                    return false;
                }
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