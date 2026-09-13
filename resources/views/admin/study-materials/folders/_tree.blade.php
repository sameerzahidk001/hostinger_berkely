@if($items->isEmpty())
    <p class="text-muted">No subfolders or files yet.</p>
@else
<ul class="list-unstyled" style="padding-left: {{ isset($depth) ? ($depth * 18) : 0 }}px;">
    @foreach($items as $item)
        <li style="padding:8px 0;border-bottom:1px solid #eee;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <div>
                    @if($item->type === 'folder')
                        <i class="fa fa-folder text-warning"></i> <strong>{{ $item->name }}</strong>
                    @elseif($item->isExternal())
                        <i class="fa fa-external-link"></i> {{ $item->name }}
                        <span class="label label-info">Zoho WorkDrive</span>
                        <a href="{{ $item->external_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-default">Open</a>
                    @else
                        <i class="fa fa-file-o"></i> {{ $item->name }}
                        <small class="text-muted">({{ number_format(($item->size ?? 0) / 1024, 1) }} KB)</small>
                    @endif
                </div>
                <div style="white-space:nowrap;">
                    <button type="button" class="btn btn-xs btn-primary" onclick="var f=document.getElementById('rename-item-{{ $item->id }}'); if(f){ f.style.display = f.style.display==='none' ? 'block' : 'none'; }">Rename</button>
                    @if($item->type === 'file')
                        <form action="{{ route('admin.study-materials.items.download', $item->id) }}" method="POST" style="display:inline;">
                            @csrf @method('PUT')
                            <input type="hidden" name="allow_download" value="{{ $item->allowsDownload() ? 0 : 1 }}">
                            <button type="submit" class="btn btn-xs {{ $item->allowsDownload() ? 'btn-success' : 'btn-default' }}" title="Students {{ $item->allowsDownload() ? 'can' : 'cannot' }} download this file">
                                Download: {{ $item->allowsDownload() ? 'Yes' : 'No' }}
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('admin.study-materials.items.destroy', $item->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-danger">Remove</button>
                    </form>
                </div>
            </div>
            <form id="rename-item-{{ $item->id }}" action="{{ route('admin.study-materials.items.rename', $item->id) }}" method="POST" style="display:none;margin-top:8px;max-width:420px;">
                @csrf @method('PUT')
                <div class="input-group input-group-sm">
                    <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary">Save name</button>
                    </span>
                </div>
            </form>
            @if($item->type === 'folder')
                @php $nested = $item->relationLoaded('childrenRecursive') ? $item->childrenRecursive : $item->children; @endphp
                @if($nested->count())
                    @include('admin.study-materials.folders._tree', ['items' => $nested, 'depth' => ($depth ?? 0) + 1])
                @endif
            @endif
        </li>
    @endforeach
</ul>
@endif
