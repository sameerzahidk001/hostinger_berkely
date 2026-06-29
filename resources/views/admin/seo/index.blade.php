@extends('admin.layout.app')
@section('title', 'Pages SEO')
@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
@include('admin.layout.partials.datatable-excel-toolbar')
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
                    <small class="text-muted">Score column shows <strong>SEO</strong> (content + metadata) and <strong>Live</strong> (public page) together.</small>
                </div>
                <div class="ibox-content">
                    <div class="table-responsive">
                        <form action="{{ route('courses-pages-seo.index') }}" method="GET">
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
                        </form>

                        <table class="table table-striped table-bordered table-hover dataTables-example">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Title</th>
                                    <th style="width:90px;">Type</th>
                                    <th style="width:110px;">Score<br><small class="text-muted" style="font-weight:normal;">SEO / Live</small></th>
                                    <th style="width:220px;">SEO Details</th>
                                    <th>Meta Description</th>
                                    @include('admin.layout.partials.audit-columns-head')
                                    <th style="width:110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pages_seo as $page_seo)
                                    @php
                                        $analysis = $page_seo->seo_analysis ?? null;
                                        $isCourse = ! empty($page_seo->course_id);
                                        $itemTitle = $page_seo->title ?: ($isCourse ? ($page_seo->course->title ?? 'Course') : ($page_seo->page->page_name ?? 'Page'));
                                        $itemUrl = seo_list_item_url($page_seo, $category_perma ?? 'category');
                                    @endphp
                                    <tr>
                                        <td data-order="{{ $page_seo->id }}">{{ $loop->iteration }}</td>
                                        <td data-order="{{ $itemTitle }}">
                                            <strong>{{ $itemTitle }}</strong>
                                            @if($itemUrl)
                                                <br><a href="{{ url($itemUrl) }}" target="_blank" style="font-size:12px;">{{ $itemUrl }}</a>
                                            @endif
                                        </td>
                                        <td data-order="{{ $isCourse ? 1 : 0 }}">{{ $isCourse ? 'Course' : 'Page' }}</td>
                                        @include('admin.seo.partials.list-seo-columns', [
                                            'seo' => $page_seo,
                                            'analysis' => $analysis,
                                        ])
                                        @include('admin.layout.partials.audit-columns-cells', ['model' => $page_seo])
                                        <td>
                                            <a href="{{ route('courses-pages-seo.edit', $page_seo->id) }}" class="btn-primary btn btn-xs">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>
                                            @include('admin.layout.partials.delete-button', [
                                                'id' => $page_seo->id,
                                                'action' => route('courses-pages-seo.destroy', $page_seo->id),
                                            ])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">No SEO records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Score <small class="text-muted">(SEO / Live)</small></th>
                                    <th>SEO Details</th>
                                    <th>Meta Description</th>
                                    @include('admin.layout.partials.audit-columns-head')
                                    <th>Action</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.dataTables-example').DataTable({
                pageLength: 10,
                lengthMenu: [10, 20, 50, 100],
                searching: true,
                lengthChange: true,
                paging: true,
                info: true,
                ordering: true,
                responsive: true,
                dom: '<"admin-dt-toolbar"<l><B><f>>rtip',
                order: [[0, 'desc']],
                buttons: [adminDatatableExcelButton('SEO List', 'seo_list')],
                columnDefs: [
                    { orderable: false, targets: [10] }
                ]
            });
        });
    </script>
@endpush
