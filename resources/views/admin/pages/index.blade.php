@extends('admin.layout.app')
@section('title', 'Pages')
@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
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

        </div>
    </div>
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
                                        <th>FAQ's Count</th>
                                        <th>SEO Status</th>
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
                                        @endphp
                                        <tr>
                                            <td style="vertical-align: middle;">{{ ++$index }}</td>

                                            <td style="vertical-align: middle;">
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
                                            <td style="vertical-align: middle;">
                                                {{ $page->parent 
                                                    ? $page->parent->url . '/' . $page->url 
                                                    : ($page->category_id 
                                                        ? ($settings->category_perma ?? 'category') . '/' . $page->url 
                                                        : $page->url) 
                                                }}
                                            </td>
                                            <td style="vertical-align: middle;">
                                                {{ $page->faqs->count() }}
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span class="label {{ $page->seo ? 'label-primary' : 'label-danger' }}">
                                                    {{ $page->seo ? 'Added' : 'Not Added' }}
                                                </span>
                                            </td>
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
                                        <th>FAQ's Count</th>
                                        <th>SEO Status</th>
                                        <th>Created</th>
                                        <th>Created By</th>
                                        <th>Last Modified</th>
                                        <th>Modified By</th>
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
                searching: true,
                lengthChange: true,
                paging: true,
                info: false,
                ordering: true,
                responsive: true,
                dom: 'lftip'
            });

            $('#categorySelect').change(function() {
                let selectedOption = $(this).find('option:selected');
                let categorySlug = selectedOption.data('url');
                let categoryParent = selectedOption.data('parent');

                if ($(this).val()) {
                    $('#page_url').prop('readonly', true).val(categorySlug);
                    $('#parent_id').prop('disabled', true).val('');
                } else {
                    $('#page_url').prop('readonly', false).val('');
                    $('#parent_id').prop('disabled', false).val('');
                }
            });
        });

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