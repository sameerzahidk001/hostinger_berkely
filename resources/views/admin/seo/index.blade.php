@extends('admin.layout.app')
@section('title', 'Pages SEO')
@push('style')
<style>
    td > p { margin: 0; }
    .seo-score-pill {
        display: inline-block;
        min-width: 58px;
        text-align: center;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
        color: #fff;
    }
    .seo-score-pill.excellent { background: #1ab394; }
    .seo-score-pill.good { background: #f8ac59; }
    .seo-score-pill.poor { background: #ed5565; }
    .seo-details small { display: block; color: #676a6c; line-height: 1.5; }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>SEO</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>SEO</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>SEO</h5>
                    <div class="ibox-tools">
                        <a class="btn-primary btn btn-xs" href="{{ route('pages-seo.create') }}">
                            <i class="fa fa-plus-square"></i> &nbsp;Add
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <div class="table-responsive">
                        <form action="{{ route('pages-seo.index') }}" method="GET">
                            <div class="row" style="margin-bottom: 6px;">
                                <div class="col-lg-4">
                                    <select name="type" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="page" {{ request('type') == 'page' ? 'selected' : '' }}>Page</option>
                                        <option value="course" {{ request('type') == 'course' ? 'selected' : '' }}>Course</option>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" name="name" class="form-control" placeholder="Search title"
                                        value="{{ request('name') }}">
                                </div>
                                <div class="col-lg-2">
                                    <button type="submit" class="btn-primary btn btn-md" style="width:100%;">Filter</button>
                                </div>
                            </div>
                            <div class="row" style="margin-bottom: 12px;">
                                <div class="col-lg-2">
                                    <select name="per_page" class="form-control" onchange="this.form.submit()">
                                        @foreach([10,20,50,100] as $n)
                                            <option value="{{ $n }}" {{ (int) request('per_page', $per_page ?? 20) === $n ? 'selected' : '' }}>{{ $n }} / page</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>

                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Title</th>
                                    <th style="width:90px;">Type</th>
                                    <th style="width:90px;">Score</th>
                                    <th style="width:220px;">SEO Details</th>
                                    <th>Meta Description</th>
                                    @include('admin.layout.partials.audit-columns-head')
                                    <th style="width:110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pages_seo as $page_seo)
                                    @php
                                        $analysis = $page_seo->analysis ?? [];
                                        $score = $analysis['score'] ?? 0;
                                        $scoreClass = $score >= 80 ? 'excellent' : ($score >= 50 ? 'good' : 'poor');
                                        $isCourse = ! empty($page_seo->course_id);
                                        $itemTitle = $page_seo->title ?: ($isCourse ? ($page_seo->course->title ?? 'Course') : ($page_seo->page->page_name ?? 'Page'));
                                        $itemUrl = $isCourse
                                            ? ($page_seo->course->slug ?? '')
                                            : ($page_seo->page->full_url ?? $page_seo->page->url ?? '');
                                    @endphp
                                    <tr>
                                        <td>{{ ($pages_seo->firstItem() ?? 0) + $loop->index }}</td>
                                        <td>
                                            <strong>{{ $itemTitle }}</strong>
                                            @if($itemUrl)
                                                <br><a href="{{ url($itemUrl) }}" target="_blank" style="font-size:12px;">{{ $itemUrl }}</a>
                                            @endif
                                        </td>
                                        <td>{{ $isCourse ? 'Course' : 'Page' }}</td>
                                        <td>
                                            <span class="seo-score-pill {{ $scoreClass }}">{{ $score }}/100</span>
                                        </td>
                                        <td class="seo-details">
                                            <small><strong>Keyword:</strong> {{ $analysis['focus_keyword'] ?: '—' }}</small>
                                            <small><strong>Schema:</strong> {{ $analysis['schema'] ?? 'Article' }}</small>
                                            <small><strong>Links:</strong> {{ $analysis['external_links'] ?? 0 }} ext / {{ $analysis['internal_links'] ?? 0 }} int</small>
                                            <small><strong>Words:</strong> {{ number_format($analysis['word_count'] ?? 0) }}</small>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($page_seo->meta_description ?? '', 120) }}</td>
                                        @include('admin.layout.partials.audit-columns-cells', ['model' => $page_seo])
                                        <td>
                                            <a href="{{ route('pages-seo.edit', $page_seo->id) }}" class="btn-primary btn btn-xs">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>
                                            @include('admin.layout.partials.delete-button', [
                                                'id' => $page_seo->id,
                                                'action' => route('pages-seo.destroy', $page_seo->id),
                                            ])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No SEO records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if(method_exists($pages_seo, 'links'))
                            <div class="text-center">
                                {{ $pages_seo->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
