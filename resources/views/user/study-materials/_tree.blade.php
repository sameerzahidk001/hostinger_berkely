@if($items->isEmpty())
    <p class="text-muted">No files in this folder.</p>
@else
    @php
        $treeItems = $items instanceof \Illuminate\Support\Collection ? $items : collect($items);
        $folders = $treeItems->filter(fn ($item) => ($item->type ?? '') === 'folder')->values();
        $files = $treeItems->filter(fn ($item) => ($item->type ?? '') !== 'folder')->values();
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
                        <a href="{{ $item->portalPreviewUrl() }}"
                           class="sm-file-link"
                           data-sm-file
                           data-sm-kind="{{ $item->portalKind() }}"
                           data-sm-name="{{ $item->name }}"
                           @if($item->allowsDownload()) data-sm-download="{{ $item->portalDownloadUrl() }}" @endif
                           @if($item->portalPosterUrl()) data-sm-poster="{{ $item->portalPosterUrl() }}" @endif>
                            <i class="fa {{ $iconClass }} sm-file-icon"></i>
                            <span class="sm-file-name">{{ $item->name }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
