@extends('admin.layout.app')
@section('title', 'Content Editor Settings')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Content Editor Settings</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a>Settings</a></li>
            <li class="active"><strong>Content Editor</strong></li>
        </ol>
    </div>
</div>

<form method="POST" action="{{ route('admin.content-editor.update') }}">
    @csrf
    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>AI Writing (OpenAI-compatible)</h5>
                    </div>
                    <div class="ibox-content">
                        <div class="row">
                            <div class="col-md-3" style="margin-bottom:12px;">
                                <label>Enable AI Write / Rewrite</label>
                                <select name="ai_enabled" class="form-control">
                                    <option value="1" @selected(!empty($ai['enabled']))>Enabled</option>
                                    <option value="0" @selected(empty($ai['enabled']))>Disabled</option>
                                </select>
                            </div>
                            <div class="col-md-5" style="margin-bottom:12px;">
                                <label>API Key @if(!empty($ai['api_key_set'])) <span class="label label-primary">Saved</span> @endif</label>
                                <input type="password" name="ai_api_key" class="form-control" autocomplete="new-password"
                                    placeholder="{{ !empty($ai['api_key_set']) ? 'Leave blank to keep current key' : 'sk-...' }}">
                                <small class="text-muted">Or set <code>OPENAI_API_KEY</code> in <code>.env</code>.</small>
                            </div>
                            <div class="col-md-4" style="margin-bottom:12px;">
                                <label>Model</label>
                                <input type="text" name="ai_model" class="form-control" value="{{ old('ai_model', $ai['model'] ?? 'gpt-4o-mini') }}">
                            </div>
                            <div class="col-md-6" style="margin-bottom:12px;">
                                <label>API Base URL</label>
                                <input type="url" name="ai_base_url" class="form-control" value="{{ old('ai_base_url', $ai['base_url'] ?? 'https://api.openai.com/v1') }}">
                            </div>
                            <div class="col-md-3" style="margin-bottom:12px;">
                                <label>Timeout (seconds)</label>
                                <input type="number" name="ai_timeout" min="10" max="180" class="form-control" value="{{ old('ai_timeout', $ai['timeout'] ?? 60) }}">
                            </div>
                        </div>
                        <p class="text-muted m-b-none">
                            Modes available in Pages/Courses editors: <strong>AI Write</strong>, <strong>AI Rewrite</strong>,
                            <strong>Improve Grammar</strong>, <strong>Optimize for SEO</strong>. Generated text is shown for review before Apply.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach(['pages' => 'Pages module', 'courses' => 'Courses module'] as $module => $moduleTitle)
    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Character limits — {{ $moduleTitle }}</h5>
                    </div>
                    <div class="ibox-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th style="width:160px;">Max characters</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($limits[$module] ?? []) as $key => $meta)
                                        <tr>
                                            <td>
                                                <strong>{{ $meta['label'] ?? $key }}</strong>
                                                <div class="text-muted small"><code>{{ $key }}</code></div>
                                            </td>
                                            <td>
                                                <input type="number" min="50" max="20000" class="form-control"
                                                    name="limits[{{ $module }}][{{ $key }}][max]"
                                                    value="{{ old("limits.$module.$key.max", $meta['max'] ?? 1000) }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <div class="wrapper wrapper-content animated fadeInRight">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-check"></i> Save settings
        </button>
        <span class="text-muted m-l-sm">
            Storage: {{ ($storageMode ?? ($ai['storage'] ?? 'auto')) === 'file' ? 'file (auto)' : 'database (auto-created)' }}
        </span>
    </div>
</form>
@endsection
