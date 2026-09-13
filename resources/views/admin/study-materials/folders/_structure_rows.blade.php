@foreach($rows as $index => $row)
    @php
        $prefix = $namePrefix . '[' . $index . ']';
        $placeholder = $depth === 0 ? 'Subfolder name' : ($depth === 1 ? 'Sub-subfolder name' : 'Nested folder name');
    @endphp
    <div class="structure-row" data-depth="{{ $depth }}" style="margin-bottom:8px;padding:8px 10px;background:#f9f9f9;border:1px solid #eee;">
        <div class="row">
            <div class="col-md-7">
                <input type="text" name="{{ $prefix }}[name]" class="form-control" placeholder="{{ $placeholder }}" value="{{ $row['name'] ?? '' }}">
            </div>
            <div class="col-md-5">
                @if($depth < 2)
                    <button type="button" class="btn btn-default btn-sm add-nested" data-max-depth="2">+ Nested folder</button>
                @endif
                <button type="button" class="btn btn-danger btn-sm remove-structure">Remove</button>
            </div>
        </div>
        <div class="structure-children" style="margin-left:18px;margin-top:8px;">
            @if(!empty($row['children']) && is_array($row['children']))
                @include('admin.study-materials.folders._structure_rows', [
                    'rows' => $row['children'],
                    'namePrefix' => $prefix . '[children]',
                    'depth' => $depth + 1,
                ])
            @endif
        </div>
    </div>
@endforeach
