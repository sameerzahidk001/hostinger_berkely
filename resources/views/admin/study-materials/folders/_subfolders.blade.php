@php
    $structureRows = old('structure', [['name' => '']]);
    if (!is_array($structureRows) || count($structureRows) === 0) {
        $structureRows = [['name' => '']];
    }
@endphp
<div class="col-md-12 form-group">
    <label>Folder structure</label>
    <span class="help-block">Create the main folder tree first (subfolder / sub-subfolder). Files are added after you save.</span>
    <div id="structure-root">
        @include('admin.study-materials.folders._structure_rows', [
            'rows' => $structureRows,
            'namePrefix' => 'structure',
            'depth' => 0,
        ])
    </div>
    <button type="button" class="btn btn-default btn-sm" id="add-structure-root"><i class="fa fa-plus"></i> Add subfolder</button>
</div>
<template id="structure-row-template">
    <div class="structure-row" data-depth="__DEPTH__" style="margin-bottom:8px;padding:8px 10px;background:#f9f9f9;border:1px solid #eee;">
        <div class="row">
            <div class="col-md-7">
                <input type="text" name="__PREFIX__[name]" class="form-control" placeholder="__PLACEHOLDER__">
            </div>
            <div class="col-md-5">
                <button type="button" class="btn btn-default btn-sm add-nested" data-max-depth="2">+ Nested folder</button>
                <button type="button" class="btn btn-danger btn-sm remove-structure">Remove</button>
            </div>
        </div>
        <div class="structure-children" style="margin-left:18px;margin-top:8px;"></div>
    </div>
</template>
