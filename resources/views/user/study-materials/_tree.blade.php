@if($items->isEmpty())
    <p class="text-muted">No files in this folder.</p>
@else
<ul class="list-unstyled" style="padding-left: {{ isset($depth) ? ($depth * 16) : 0 }}px;">
@foreach($items as $item)
    <li style="padding:8px 0;border-bottom:1px solid #eee;">
        @if($item->type === 'folder')
            <i class="fa fa-folder text-warning"></i> <strong>{{ $item->name }}</strong>
            @php $nested = $item->relationLoaded('childrenRecursive') ? $item->childrenRecursive : $item->children; @endphp
            @if($nested->count())
                @include('user.study-materials._tree', ['items' => $nested, 'depth' => ($depth ?? 0) + 1])
            @endif
        @else
            <a href="{{ $item->portalPreviewUrl() }}"
               data-sm-file
               data-sm-kind="{{ $item->portalKind() }}"
               data-sm-name="{{ $item->name }}"
               @if($item->allowsDownload()) data-sm-download="{{ $item->portalDownloadUrl() }}" @endif
               @if($item->portalPosterUrl()) data-sm-poster="{{ $item->portalPosterUrl() }}" @endif
               style="color:#000435;font-weight:600;">
                <i class="fa {{ $item->isExternal() ? 'fa-cloud' : 'fa-file-o' }}"></i> {{ $item->name }}
            </a>
        @endif
    </li>
@endforeach
</ul>
@endif
