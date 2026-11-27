@extends('admin.layout.app')

@section('title', $row->exists ? 'Edit Path Branding' : 'Add Path Branding')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $row->exists ? 'Edit Path Branding' : 'Add Path Branding' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.path-branding.index') }}">Path Branding</a></li>
            <li class="active"><strong>{{ $row->exists ? 'Edit' : 'Add' }}</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('fail'))
        <div class="alert alert-danger">{{ session('fail') }}</div>
    @endif

    <div class="ibox">
        <div class="ibox-content">
            <form method="POST"
                  action="{{ $row->exists ? route('admin.path-branding.update', $row->id) : route('admin.path-branding.store') }}"
                  enctype="multipart/form-data">
                @csrf
                @if($row->exists)
                    @method('PUT')
                @endif

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $row->name) }}"
                               placeholder="Academic Journal" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Path prefix</label>
                        <input type="text" name="path_prefix" class="form-control"
                               value="{{ old('path_prefix', $row->path_prefix) }}"
                               placeholder="journal" required>
                        <span class="help-block">
                            Applies to <code>/journal</code> and every page under
                            <code>/journal/...</code>
                        </span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Menu group</label>
                        <select name="menu_group" class="form-control">
                            <option value="">— Use main site menu —</option>
                            @foreach($menuGroups as $group)
                                <option value="{{ $group }}" {{ old('menu_group', $row->menu_group) === $group ? 'selected' : '' }}>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">Create the journal menu under Settings → Menu, then select it here.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Home URL for logo click</label>
                        <input type="text" name="home_url" class="form-control"
                               value="{{ old('home_url', $row->home_url) }}"
                               placeholder="/journal">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Logo image</label>
                        <input type="file" name="logo_file" class="form-control" accept="image/*">
                        @if($row->logoUrl())
                            <div style="margin-top:10px;">
                                <img src="{{ $row->logoUrl() }}" alt="Current logo" style="max-height:60px;max-width:220px;object-fit:contain;">
                                <div><a href="{{ $row->logoUrl() }}" target="_blank" rel="noopener">View current logo</a></div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 form-group">
                        <label style="display:block;">Status</label>
                        <label class="checkbox-inline" style="padding-left:0;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ? 1 : 0) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>
                </div>

                <hr>
                <h3 style="margin-top:8px;">Path footer (optional)</h3>
                <p class="text-muted">Enable a custom footer for this path (e.g. <code>/journal</code>) including social links and WhatsApp. Other paths like <code>/events</code> can use the same setup later.</p>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="checkbox-inline" style="padding-left:0;">
                            <input type="checkbox" name="custom_footer" value="1" {{ old('custom_footer', $row->custom_footer ? 1 : 0) ? 'checked' : '' }}>
                            Use custom footer for this path
                        </label>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Footer menu group</label>
                        <select name="footer_menu_group" class="form-control">
                            <option value="">— Same as header menu group —</option>
                            @foreach($menuGroups as $group)
                                <option value="{{ $group }}" {{ old('footer_menu_group', $row->footer_menu_group) === $group ? 'selected' : '' }}>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Footer copyright</label>
                        <input type="text" name="footer_copyright" class="form-control"
                               value="{{ old('footer_copyright', $row->footer_copyright) }}"
                               placeholder="Copyright © {{ date('Y') }} Berkeley Press | Journal">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Footer background</label>
                        <input type="text" name="footer_bg" class="form-control" value="{{ old('footer_bg', $row->footer_bg) }}" placeholder="#0e0e0e">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Footer text colour</label>
                        <input type="text" name="footer_text_color" class="form-control" value="{{ old('footer_text_color', $row->footer_text_color) }}" placeholder="#8996a0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Footer title colour</label>
                        <input type="text" name="footer_title_color" class="form-control" value="{{ old('footer_title_color', $row->footer_title_color) }}" placeholder="#ffffff">
                    </div>
                </div>

                @php
                    $social = old('footer_social', is_array($row->footer_social) ? $row->footer_social : []);
                    $waRows = old('wa_url') !== null
                        ? collect(old('wa_label', []))->map(fn ($label, $i) => ['label' => $label, 'url' => old('wa_url.' . $i)])
                        : collect(is_array($row->footer_whatsapp) ? $row->footer_whatsapp : []);
                    while ($waRows->count() < 3) {
                        $waRows->push(['label' => '', 'url' => '']);
                    }
                @endphp

                <h4>Social media URLs</h4>
                <div class="row">
                    @foreach(['facebook','twitter','instagram','linkedin','youtube','tiktok'] as $platform)
                        <div class="col-md-4 form-group">
                            <label>{{ ucfirst($platform) }}</label>
                            <input type="text" name="footer_social[{{ $platform }}_url]" class="form-control"
                                   value="{{ $social[$platform . '_url'] ?? '' }}"
                                   placeholder="https://">
                        </div>
                    @endforeach
                </div>

                <h4>WhatsApp buttons</h4>
                @foreach($waRows->take(3) as $i => $wa)
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Label {{ $i + 1 }}</label>
                            <input type="text" name="wa_label[]" class="form-control" value="{{ $wa['label'] ?? '' }}"
                                   placeholder="{{ $i === 0 ? 'USA & CANADA' : ($i === 1 ? 'UK & EUROPE' : 'MIDDLE EAST & AFRICA') }}">
                        </div>
                        <div class="col-md-8 form-group">
                            <label>WhatsApp URL {{ $i + 1 }}</label>
                            <input type="text" name="wa_url[]" class="form-control" value="{{ $wa['url'] ?? '' }}" placeholder="https://wa.me/...">
                        </div>
                    </div>
                @endforeach

                <div class="form-group" style="margin-top:12px;">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.path-branding.index') }}" class="btn btn-white">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
