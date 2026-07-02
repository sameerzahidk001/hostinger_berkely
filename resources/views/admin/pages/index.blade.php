@extends('admin.layout.app')
@section('title', 'Pages')
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
    @include('admin.layout.partials.datatable-excel-toolbar')
@endpush
@section('content')

    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Pages List</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="index.html">Home</a>
                </li>
                <li>
                    <a>Pages</a>
                </li>
                <li class="active">
                    <strong>All</strong>
                </li>
            </ol>
        </div>
        <div class="col-lg-2">
            @if($pagesStatusEnabled ?? false)
                <a class="btn btn-primary" href="{{ route('admin.pages.disabled') }}"><i class="fa fa-eye-slash"></i> Show Disabled Pages</a>
            @endif
        </div>
    </div>
    @if(!($pagesStatusEnabled ?? true))
        <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0;">
            <div class="alert alert-warning">
                <strong>Page disable is not active on the database.</strong>
                Run <code>database/sql/add-pages-status-column.sql</code> on the server before Active/Disabled will save.
            </div>
        </div>
    @endif
    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Add Page</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>

                    <div class="ibox-content">

                        <form role="form" action="{{ route('pages.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-lg-4">
                                    <label for="">Page Name</label>
                                    <input class="form-control" placeholder="Add Page Name" type="text" name="page_name"
                                        value="{{old('page_name')}}">
                                </div>
                                <div class="col-lg-4">
                                    <label class="mb-1">Page URL</label>
                                    <input class="form-control" placeholder="Add Page URL" type="text" name="url" id="page_url"
                                        value="{{old('url')}}">
                                </div>
                                <div class="col-lg-4">
                                    <label class="mb-1">Parent Page</label>
                                    <select class="form-control" name="parent_id" id="parent_id">
                                        <option value="">- Select Parent Page -</option>
                                        @foreach($pages as $page)
                                            <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label class="mb-1">Categories</label>
                                    <select class="form-control" name="category_id" id="categorySelect">
                                        <option value="">- Select Category Page -</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" data-url="{{ $category->slug }}" data-parent="{{ $category_page_id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label class="mb-1">Status</label>
                                    <select class="form-control" name="status" required>
                                        <option value="1" @selected(old('status', '1') == '1')>Active</option>
                                        <option value="0" @selected(old('status') === '0')>Disabled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Submit</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Pages List</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content">

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover dataTables-example">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">SR#</th>
                                        <th>Page</th>
                                        <th>URL</th>
                                        <th style="width:90px;">Status</th>
                                        <th>FAQ's Count</th>
                                        <th>SEO</th>
                                        @include('admin.layout.partials.audit-columns-head')
                                        <th style="width:130px;">Action</th>
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
                                            $pageUrl = $page->parent
                                                ? $page->parent->url . '/' . $page->url
                                                : ($page->category_id
                                                    ? ($settings->category_perma ?? 'category') . '/' . $page->url
                                                    : $page->url);
                                            $pageStatus = (int) ($page->status ?? 1);
                                            $pageStatusExport = $pageStatus ? 'Active' : 'Disabled';
                                        @endphp
                                        <tr>
                                            <td style="vertical-align: middle;">{{ ++$index }}</td>

                                            <td style="vertical-align: middle;" data-export="{{ $page->page_name }}">
                                                <a href="{{ url($page->parent 
                                                    ? $page->parent->url . '/' . $page->url 
                                                    : ($page->category_id 
                                                        ? ($settings->category_perma ?? 'category') . '/' . $page->url 
                                                        : $page->url)) }}" target="_blank">
                                                    {{ $page->page_name }}
                                                </a>
                                                @if ($pageName)
                                                    <span class="badge bg-primary">{{ $pageName }}</span>
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle;" data-export="{{ $pageUrl }}">
                                                {{ $pageUrl }}
                                            </td>
                                            <td style="vertical-align: middle;" data-export="{{ $pageStatusExport }}">
                                                @if($pagesStatusEnabled ?? false)
                                                    <select name="page_status" class="form-control"
                                                        id="page_status_{{ $page->id }}"
                                                        onchange="updatePageStatus(this.value, {{ $page->id }})">
                                                        <option value="active" @selected($pageStatus === 1)>Active</option>
                                                        <option value="disable" @selected($pageStatus === 0)>Disabled</option>
                                                    </select>
                                                @else
                                                    <span class="label label-primary">Active</span>
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle;">
                                                {{ $page->faqs->count() }}
                                            </td>
                                            @include('admin.seo.partials.list-seo-cell', [
                                                'seo' => $page->seo,
                                                'analysis' => $page->seo_analysis ?? null,
                                                'editUrl' => $page->seo
                                                    ? route('courses-pages-seo.edit', ['pages_seo' => $page->seo->id, 'page_name' => $page->page_name, 'page_id' => $page->id])
                                                    : '#',
                                                'createUrl' => route('courses-pages-seo.create', ['page_name' => $page->page_name, 'page_id' => $page->id]),
                                            ])
                                            @include('admin.layout.partials.audit-columns-cells', ['model' => $page])
                                            <td style="vertical-align: middle;" class="center">

                                                <div class="btn-group">
                                                    <a href="{{ route('pages.edit', $page->id) }}"
                                                        class="btn-primary btn btn-xs">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    @if(!$pageName)
                                                        @include('admin.layout.partials.delete-button', [
                                                            'id' => $page->id,
                                                            'action' => route('pages.destroy', $page->id),
                                                        ])
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-align:center;" colspan="4">No Record Found!</td>
                                        </tr>

                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>SR#</th>
                                        <th>Page</th>
                                        <th>URL</th>
                                        <th>Status</th>
                                        <th>FAQ's Count</th>
                                        <th>SEO</th>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
    <!-- Page-Level Scripts -->
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
                order: [[6, 'desc']],
                buttons: [adminDatatableExcelButton('Pages List', 'pages_list')]
            });

            function slugify(text) {
                return text.toString().toLowerCase().trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            $('#categorySelect').change(function() {
                let selectedOption = $(this).find('option:selected');

                if ($(this).val()) {
                    $('#parent_id').prop('disabled', true).val('');
                    let pageName = $('input[name="page_name"]').val();
                    if (!$('#page_url').val() && pageName) {
                        $('#page_url').val(slugify(pageName));
                    }
                } else {
                    $('#parent_id').prop('disabled', false);
                }
            });

            $('input[name="page_name"]').on('blur', function() {
                if ($('#categorySelect').val() && !$('#page_url').val()) {
                    $('#page_url').val(slugify($(this).val()));
                }
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
                        if (status === 'disable') {
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

        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
@endpush