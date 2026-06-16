@if(request()->ajax())
    @php
        $imageFiles = collect(\File::allFiles(public_path()))
            ->filter(fn($file) => in_array($file->getExtension(), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
            ->sortByDesc(fn($file) => $file->getMTime());
    @endphp

    {{-- Search UI --}}
    <div class="row" style="margin-bottom:10px;">
        <div class="col-xs-12">
            <div class="input-group">
                <input type="text" id="fileSearchInput" class="form-control" placeholder="Search filename…">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" id="fileSearchClear">Clear</button>
                </span>
            </div>
            <small id="fileSearchCount" class="text-muted"></small>
        </div>
    </div>

    {{-- Grid --}}
    <div class="row" id="fileGrid">
        @foreach($imageFiles as $file)
            @php
                $filePath = str_replace('\\', '/', $file->getRelativePathname());
                $fileUrl = asset($filePath);
                $baseName = basename($filePath);
            @endphp

            <div class="col-xs-6 col-sm-4 col-md-3 col-lg-3 file-card" data-filename="{{ $baseName }}">
                <div class="thumbnail position-relative" style="position: relative;">
                    <!-- Checkbox in top-left corner -->
                    <label style="position: absolute; top: 8px; left: 8px; z-index: 10;">
                        <input type="checkbox" class="image-selector" value="{{ $fileUrl }}" onclick="selectImageFromCheckbox(this)">
                    </label>

                    <img src="{{ $fileUrl }}" alt="Image" class="img-thumbnail file-thumbnail" style="height: 180px; object-fit: cover; cursor: pointer;" data-url="{{ $fileUrl }}" onclick="selectImage('{{ $fileUrl }}')">

                    <div class="caption text-center">
                        <p class="small text-truncate" title="{{ $baseName }}">{{ $baseName }}</p>
                        {{-- Delete button removed --}}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        // --- Existing functions (unchanged) ---
        function selectImage(url) {
            if (window.opener && window.opener.SetImageUrl) {
                window.opener.SetImageUrl(url);
                window.close();
            } else if (window.parent && window.parent.selectImage) {
                window.parent.selectImage(url); // For modal-based usage
            }
        }

        function selectImageFromCheckbox(checkbox) {
            const url = checkbox.value;
            const isMultiple = $('#image_path').prop('multiple');

            if (isMultiple) {
                if (checkbox.checked) {
                    if (!selectedImageURLs.includes(url)) selectedImageURLs.push(url);
                } else {
                    selectedImageURLs = selectedImageURLs.filter(u => u !== url);
                }
            } else {
                selectedImageURLs = [url];
                $('.image-selector').not(checkbox).prop('checked', false);
            }

            $('.file-thumbnail').removeClass('selected');
            selectedImageURLs.forEach(function (selected) {
                $(`.file-thumbnail[data-url="${selected}"]`).addClass('selected');
            });
        }

        // --- NEW: Filename search ---
        (function () {
            var $grid  = $('#fileGrid');
            var $cards = $grid.find('.file-card');
            var $inp   = $('#fileSearchInput');
            var $clear = $('#fileSearchClear');
            var $count = $('#fileSearchCount');

            function updateCount() {
                var visible = $cards.filter(':visible').length;
                $count.text('Showing ' + visible + ' of ' + $cards.length + ' files');
            }

            function filter() {
                var q = ($inp.val() || '').toLowerCase().trim();
                $cards.each(function () {
                    var name = ($(this).data('filename') + '').toLowerCase();
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                updateCount();
            }

            $inp.on('input', filter);
            $clear.on('click', function () { $inp.val(''); filter(); $inp.focus(); });
            $inp.on('keydown', function (e) { if (e.key === 'Escape') { $clear.click(); } });

            updateCount(); // initial
        })();
    </script>

@else
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>File Manager</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .thumbnail {
                position: relative;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h3 class="text-center">File Manager</h3>
            @include('filemanager') {{-- Assumes this file is stored as views/filemanager.blade.php --}}
        </div>
    </body>
    </html>
@endif