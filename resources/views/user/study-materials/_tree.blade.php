@if($items->isEmpty())
    <p class="text-muted">No files in this folder.</p>
@else
    @php
        $treeItems = \App\Models\StudyMaterialItem::naturalSort($items);
        $folders = \App\Models\StudyMaterialItem::naturalSort(
            $treeItems->filter(fn ($item) => ($item->type ?? '') === 'folder')
        );
        $files = \App\Models\StudyMaterialItem::naturalSort(
            $treeItems->filter(fn ($item) => ($item->type ?? '') !== 'folder')
        );
        $pad = isset($depth) ? ((int) $depth * 16) : 0;
    @endphp

    <div class="sm-tree-level" style="padding-left: {{ $pad }}px;">
        @foreach($folders as $item)
            <div class="sm-tree-folder">
                <div class="sm-tree-folder-head">
                    <i class="fa fa-folder text-warning"></i>
                    <strong>{{ $item->name }}</strong>
                </div>
                @php $nested = $item->relationLoaded('childrenRecursive') ? $item->childrenRecursive : $item->children; @endphp
                @if($nested->count())
                    @include('user.study-materials._tree', ['items' => $nested, 'depth' => ($depth ?? 0) + 1])
                @endif
            </div>
        @endforeach

        @if($files->isNotEmpty())
            <div class="sm-files-grid">
                @foreach($files as $item)
                    @php
                        $iconClass = method_exists($item, 'iconFaClass')
                            ? $item->iconFaClass()
                            : ($item->isExternal() ? 'fa-cloud text-info' : 'fa-file-o text-muted');
                    @endphp
                    <div class="sm-file-card">
                        @php
                            $kind = $item->portalKind();
                            if (method_exists($item, 'resolvedIconType') && $item->resolvedIconType() === 'video') {
                                $kind = 'video';
                            }
                        @endphp
                        <a href="{{ $item->portalPreviewUrl() }}"
                           class="sm-file-link"
                           data-sm-file
                           data-sm-kind="{{ $kind }}"
                           data-sm-name="{{ $item->name }}"
                           @if($item->allowsDownload()) data-sm-download="{{ $item->portalDownloadUrl() }}" @endif
                           @if($item->portalPosterUrl()) data-sm-poster="{{ $item->portalPosterUrl() }}" @endif>
                            <i class="fa {{ $kind === 'video' ? 'fa-file-video-o text-info' : $iconClass }} sm-file-icon"></i>
                            <span class="sm-file-name">{{ $item->name }}</span>
                        </a>
                        @if($kind === 'video')
                            @php
                                $previewUrl = $item->portalPreviewUrl();
                                $posterUrl = $item->portalPosterUrl();
                            @endphp
                            <a href="{{ $previewUrl }}"
                               class="sm-video-thumb"
                               data-sm-file
                               data-sm-kind="video"
                               data-sm-name="{{ $item->name }}"
                               @if($item->allowsDownload()) data-sm-download="{{ $item->portalDownloadUrl() }}" @endif
                               @if($posterUrl) data-sm-poster="{{ $posterUrl }}" @endif>
                                @if($posterUrl)
                                    <img src="{{ $posterUrl }}" alt="{{ $item->name }}">
                                @else
                                    <i class="fa fa-file-video-o sm-video-mark"></i>
                                @endif
                                <div class="sm-video-play"><span><i class="fa fa-play"></i></span></div>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
