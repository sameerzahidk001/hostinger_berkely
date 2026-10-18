@extends('admin.layout.app')
@section('title', 'Subjects')
@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
    <style>
        td .label {
            display: inline-block;
        }

        td .label+.label {
            margin-left: 6px;
        }

        .page-heading {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .col {
            flex: 1;
        }

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
        .courses-list-wrap { overflow-x: hidden; }
        .courses-list-table {
            width: 100% !important;
            table-layout: fixed;
            font-size: 12px;
        }
        .courses-list-table thead th {
            white-space: normal !important;
            word-break: break-word;
            line-height: 1.2;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            padding: 6px 4px;
        }
        .courses-list-table td {
            white-space: normal !important;
            word-break: break-word;
            vertical-align: top;
            padding: 6px 4px;
        }
        .courses-list-table .col-id { width: 44px; text-align: center; }
        .courses-list-table .col-status { width: 72px; }
        .courses-list-table .col-status select {
            width: 100%;
            max-width: 72px;
            padding: 2px 4px;
            height: auto;
            font-size: 11px;
        }
        .courses-list-table td ul { margin: 0; padding-left: 14px; }
        .courses-list-table .lms-cell-stack {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        .courses-list-table .lms-cell-stack .label,
        .courses-list-table .lms-cell-stack .btn,
        .courses-list-table .lms-cell-stack form {
            margin: 0;
            display: block;
        }
        .courses-list-table .label + .label { margin-left: 0; }
    </style>
    @include('admin.layout.partials.datatable-excel-toolbar')
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col">
            <h2>Courses List</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="index.html">Home</a>
                </li>
                <li>
                    <a>Courses</a>
                </li>
                <li class="active">
                    <strong>All</strong>
                </li>
            </ol>
        </div>
        <div class="col-auto">
            <a class="btn btn-primary" href="{{ route('admin.course.disabled') }}"><i class="fa fa-eye-slash"></i> Show
                Disabled Courses</a>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Course List</h5>
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
                        <div class="courses-list-wrap">
                            <table class="table table-striped table-bordered table-hover dataTables-example courses-list-table">
                                <thead>
                                    <tr>
                                        <th class="col-id">ID</th>
                                        <th>Title</th>
                                        <th>Categories</th>
                                        <th>Schools</th>
                                        <th>Related<br>Courses</th>
                                        <th>Fee<br>Structure</th>
                                        <th>Course<br>Structure</th>
                                        <th>Lecture<br>Plan</th>
                                        <th>SEO</th>
                                        <th>FAQs</th>
                                        <th>Testimonial</th>
                                        <th>Instructors</th>
                                        <th class="col-status">Status</th>
                                        <th>Created</th>
                                        <th>Created<br>By</th>
                                        <th>Last<br>Modified</th>
                                        <th>Modified<br>By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($courses as $index => $data)
                                        @php
                                            $courseStatusExport = is_null($data->deleted_at) ? 'Active' : 'Disabled';
                                            $categoriesExport = $data->categories->pluck('name')->filter()->implode(', ');
                                            $schoolsExport = $data->schools->pluck('name')->filter()->implode(', ');
                                        @endphp
                                        <tr>
                                            <td class="col-id" data-order="{{ (int) $data->id }}">{{ $data->id }}</td>
                                            <td style="vertical-align: middle;" data-export="{{ $data->title }}">
                                                <a href="{{ route('course.details', ['course' => $data->slug]) }}"
                                                    target="blank" target="blank">
                                                    {{ $data->title }}
                                                </a>
                                                @if($data->seo?->exists() && !empty($data->seo->thumbnail))
                                                    <a href="{{ asset($data->seo->thumbnail) }}" target="_blank"
                                                        style="display:block; margin-top:4px;">
                                                        <img src="{{ asset($data->seo->thumbnail) }}" alt="" width="80">
                                                    </a>
                                                @endif
                                            </td>
                                            <td data-export="{{ $categoriesExport }}">
                                                <ul style="padding-left:10px;">
                                                    @foreach($data->categories as $key => $category)
                                                        <li>{{ $category->name }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td data-export="{{ $schoolsExport }}">
                                                <ul style="padding-left:10px;">
                                                    @foreach($data->schools as $key => $school)
                                                        <li>{{ $school->name }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td>
                                                @if($data->relatedCourses->isEmpty())
                                                    <a href="{{ route('course.related-courses', ['id' => $data->id]) }}"
                                                        class="label label-danger" target="_blank">
                                                        Add
                                                    </a>
                                                @else
                                                    <a href="{{ route('course.related-courses', ['id' => $data->id]) }}"
                                                        class="label label-primary" target="_blank">
                                                        View
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="lms-cell-stack">
                                                @if ($data->courseFeePackages->isEmpty())
                                                    <a href="{{ route('course.create-fee', ['id' => $data->id]) }}"
                                                        class="label label-danger" target="_blank">
                                                        Add
                                                    </a>
                                                @else
                                                    <a href="{{ route('course.fee', ['id' => $data->id]) }}"
                                                        class="label label-primary" target="_blank">
                                                        View
                                                    </a>
                                                    <span
                                                        class="label {{ $data->fee_visibility ? 'label-primary' : 'label-danger' }}">
                                                        {{ $data->fee_visibility ? 'Active' : 'Disabled' }}
                                                    </span>
                                                @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($data->courseStructuresFirst->isEmpty())
                                                    <a href="{{ route('course.course-structure-first', ['id' => $data->id]) }}"
                                                        class="label label-danger" target="_blank">
                                                        Add
                                                    </a>
                                                @else
                                                    <a href="{{ route('course.course-structure-first', ['id' => $data->id]) }}"
                                                        class="label label-primary" target="_blank">
                                                        View
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="lms-cell-stack">
                                                @if($data->courseStructures->isEmpty())
                                                    <a href="{{ route('course.course-structure', ['id' => $data->id]) }}"
                                                        class="label label-danger" target="_blank">Add</a>
                                                @else
                                                    <a href="{{ route('course.course-structure', ['id' => $data->id]) }}"
                                                        class="label label-primary" target="_blank">View</a>
                                                    <span
                                                        class="label {{ $data->lecture_plan_section ? 'label-primary' : 'label-danger' }}">
                                                        {{ $data->lecture_plan_section ? 'Active' : 'Disabled' }}
                                                    </span>
                                                @endif
                                                </div>
                                            </td>
                                            @include('admin.seo.partials.list-seo-cell', [
                                                'seo' => $data->seo,
                                                'analysis' => $data->seo_analysis ?? null,
                                                'editUrl' => $data->seo
                                                    ? route('courses-pages-seo.edit', ['pages_seo' => $data->seo->id, 'course_name' => $data->title, 'course_id' => $data->id])
                                                    : '#',
                                                'createUrl' => route('courses-pages-seo.create', ['course_name' => $data->title, 'course_id' => $data->id]),
                                            ])
                                            <td>
                                                @if ($data->courseFaq->isEmpty())
                                                    <a href="{{ route('course.show-faqs', ['id' => $data->id]) }}"
                                                        class="label label-danger" target="_blank">
                                                        Add
                                                    </a>
                                                @else
                                                    <a href="{{ route('course.show-faqs', ['id' => $data->id]) }}"
                                                        class="label label-primary" target="_blank">
                                                        View
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($data->testimonials->isEmpty())
                                                    <a href="{{ route('testimonial.show', ['testimonial' => $data->id]) }}"
                                                        class="label label-danger" target="_blank">
                                                        Add
                                                    </a>
                                                @else
                                                    <a href="{{ route('testimonial.show', ['testimonial' => $data->id]) }}"
                                                        class="label label-primary" target="_blank">
                                                        View
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                @if($data->instructor_id && !empty($data->instructor_id))
                                                    <a href="{{ route('admin.courses.instructors', ['id' => $data->id]) }}"
                                                        class="label label-primary" target="_blank">
                                                        View
                                                    </a>
                                                @else
                                                    <a href="{{ route('admin.courses.instructors', ['id' => $data->id]) }}"
                                                        class="label label-danger" target="_blank">
                                                        Add
                                                    </a>
                                                @endif
                                            </td>
                                            <td class="col-status" data-export="{{ $courseStatusExport }}">
                                                <select name="course_status" class="form-control"
                                                    id="course_status_{{ $data->id }}"
                                                    onchange="updateCourseStatus(this.value, {{ $data->id }})">
                                                    <option value="active" @if(is_null($data->deleted_at)) selected @endif>
                                                        Active
                                                    </option>
                                                    <option value="disable" @if(!is_null($data->deleted_at)) selected @endif>
                                                        Disable
                                                    </option>
                                                </select>
                                            </td>
                                            @include('admin.layout.partials.audit-columns-cells', ['model' => $data])
                                            <td>
                                                <div class="lms-cell-stack">
                                                    <a href="{{ route('course.edit', ['id' => $data->id]) }}"
                                                        class="btn-primary btn btn-xs" target="_blank">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    <form action="{{ route('course.duplicate', $data->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-info btn-xs"
                                                            onclick="return confirm('Duplicate this course as a disabled draft? Original stays unchanged.');"
                                                            title="Duplicate course">
                                                            <i class="fa fa-copy"></i> Duplicate
                                                        </button>
                                                    </form>
                                                    @include('admin.layout.partials.delete-button', [
                                                        'id' => $data->id,
                                                        'action' => route('course.delete', $data->id),
                                                    ])
                                                </div>
                                            </td>

                                        </tr>
                                    @empty
                                        <tr class="text-align:center;">
                                            <td class="text-align:center;" colspan="20">No Record Found!</td>
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
                dom: '<"admin-dt-toolbar"<l><B><f>>rtip',
                order: [[0, 'desc']],
                buttons: [adminDatatableExcelButton('Courses List', 'courses_list')]
            });
        });

        function updateCourseStatus(status, courseId) {
            // AJAX call to update course status
            $.ajax({
                url: '/admin/course/' + courseId + '/update-status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // For Laravel CSRF protection
                    status: status
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.success);  // Show success notification
                        if (status === 'disable') {
                            $('#course_status_' + courseId).closest('tr').fadeOut(500, function () {
                                $(this).remove();
                            });
                        }
                    } else {
                        toastr.error('Unexpected response received.');
                    }
                },
                error: function (xhr, status, error) {
                    alert('Error updating course status.');
                }
            });
        }
    </script>
@endpush