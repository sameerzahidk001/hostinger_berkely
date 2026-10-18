@extends('admin.layout.app')
@section('title', 'Disabled Pages')
@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
    <style>
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
    @include('admin.layout.partials.admin-list-table-css')
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Disabled Pages List</h2>
            <ol class="breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a></li>
                <li><a>Pages</a></li>
                <li class="active"><strong>Disabled</strong></li>
            </ol>
        </div>
        <div class="col-lg-2">
            <a class="btn btn-primary" href="{{ route('pages.index') }}">Back to Active Pages</a>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Disabled Pages</h5>
                    </div>
                    <div class="ibox-content">
                        <div class="admin-list-wrap">
                            <table class="table table-striped table-bordered table-hover dataTables-example admin-list-table">
                                <thead>
                                    <tr>
                                        <th class="col-id">ID</th>
                                        <th>Page</th>
                                        <th>URL</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-faq">FAQ<br>Count</th>
                                        <th>SEO</th>
                                        <th>Created</th>
                                        <th>Created<br>By</th>
                                        <th>Last<br>Modified</th>
                                        <th>Modified<br>By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pages as $index => $page)
                                        @php
                                            $protectedPages = [
                                                'Home' => $settings->home,
                                                'Categories' => $settings->categories ?? 'category'
                                            ];
                                            $pageName = array_search($page->id, $protectedPages);
                                        @endphp
                                        <tr>
                                            <td class="col-id" data-order="{{ (int) $page->id }}">{{ $page->id }}</td>
                                            <td>
                                                <strong>{{ $page->page_name }}</strong>
                                                @if ($pageName)
                                                    <span class="badge bg-primary">{{ $pageName }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $page->parent
                                                    ? $page->parent->url . '/' . $page->url
                                                    : ($page->category_id
                                                        ? ($settings->category_perma ?? 'category') . '/' . $page->url
                                                        : $page->url)
                                                }}
                                            </td>
                                            <td class="col-status">
                                                <select name="page_status" class="form-control"
                                                    id="page_status_{{ $page->id }}"
                                                    onchange="updatePageStatus(this.value, {{ $page->id }})">
                                                    <option value="active">Active</option>
                                                    <option value="disable" selected>Disabled</option>
                                                </select>
                                            </td>
                                            <td class="col-faq">{{ $page->faqs->count() }}</td>
                                            @include('admin.seo.partials.list-seo-cell', [
                                                'seo' => $page->seo,
                                                'analysis' => $page->seo_analysis ?? null,
                                                'editUrl' => $page->seo
                                                    ? route('courses-pages-seo.edit', ['pages_seo' => $page->seo->id, 'page_name' => $page->page_name, 'page_id' => $page->id])
                                                    : '#',
                                                'createUrl' => route('courses-pages-seo.create', ['page_name' => $page->page_name, 'page_id' => $page->id]),
                                            ])
                                            @include('admin.layout.partials.audit-columns-cells', ['model' => $page])
                                            <td class="center">
                                                <div class="lms-cell-stack">
                                                    <a href="{{ route('pages.edit', $page->id) }}" class="btn-primary btn btn-xs" target="_blank">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center text-muted">No disabled pages found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
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
                responsive: false,
                autoWidth: false,
                scrollX: false,
                dom: 'lftip',
                order: [[0, 'desc']]
            });
        });

        function updatePageStatus(status, pageId) {
            $.ajax({
                url: '/admin/pages/' + pageId + '/update-status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.success);
                        if (status === 'active') {
                            $('#page_status_' + pageId).closest('tr').fadeOut(500, function () {
                                $(this).remove();
                            });
                        }
                    } else {
                        toastr.error('Unexpected response received.');
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.error
                        ? xhr.responseJSON.error
                        : 'Error updating page status.';
                    toastr.error(message);
                }
            });
        }
    </script>
@endpush
