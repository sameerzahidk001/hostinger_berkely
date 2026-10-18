@extends('admin.layout.app')
@section('title', 'Disabled Courses')
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
    </style>
    @include('admin.layout.partials.admin-list-table-css')
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col">
            <h2>Disabled Courses List</h2>
            <ol class="breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a></li>
                <li><a>Courses</a></li>
                <li class="active"><strong>Disabled</strong></li>
            </ol>
        </div>
        <div class="col-auto">
            <a class="btn btn-primary" href="{{ route('admin.courses') }}">Back</a>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Course List</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                            <a class="close-link"><i class="fa fa-times"></i></a>
                        </div>
                    </div>
                    <div class="ibox-content">
                        <div class="admin-list-wrap">
                            <table class="table table-striped table-bordered table-hover dataTables-example admin-list-table">
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
                                        <tr>
                                            <td class="col-id" data-order="{{ (int) $data->id }}">{{ $data->id }}</td>
                                            <td style="vertical-align: middle;">
                                                <a href="{{ route('course.details', ['course' => $data->slug]) }}" target="_blank">
                                                    {{ $data->title }}
                                                </a>
                                                @if($data->seo?->exists() && !empty($data->seo->thumbnail))
                                                    <a href="{{ asset($data->seo->thumbnail) }}" target="_blank" style="display:block; margin-top:4px;">
                                                        <img src="{{ asset($data->seo->thumbnail) }}" alt="" width="80">
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                <ul style="padding-left:10px;">
                                                    @foreach($data->categories as $category)
                                                        <li>{{ $category->name }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td>
                                                <ul style="padding-left:10px;">
                                                    @foreach($data->schools as $school)
                                                        <li>{{ $school->name }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td>
                                                @if($data->relatedCourses->isEmpty())
                                                    <a href="{{ route('course.related-courses', ['id' => $data->id]) }}" class="label label-danger" target="_blank">Add</a>
                                                @else
                                                    <a href="{{ route('course.related-courses', ['id' => $data->id]) }}" class="label label-primary" target="_blank">View</a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="lms-cell-stack">
                                                @if ($data->courseFeePackages->isEmpty())
                                                    <a href="{{ route('course.create-fee', ['id' => $data->id]) }}" class="label label-danger" target="_blank">Add</a>
                                                @else
                                                    <a href="{{ route('course.fee', ['id' => $data->id]) }}" class="label label-primary" target="_blank">View</a>
                                                    <span class="label {{ $data->fee_visibility ? 'label-primary' : 'label-danger' }}">
                                                        {{ $data->fee_visibility ? 'Active' : 'Disabled' }}
                                                    </span>
                                                @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($data->courseStructuresFirst->isEmpty())
                                                    <a href="{{ route('course.course-structure-first', ['id' => $data->id]) }}" class="label label-danger" target="_blank">Add</a>
                                                @else
                                                    <a href="{{ route('course.course-structure-first', ['id' => $data->id]) }}" class="label label-primary" target="_blank">View</a>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="lms-cell-stack">
                                                @if($data->courseStructures->isEmpty())
                                                    <a href="{{ route('course.course-structure', ['id' => $data->id]) }}" class="label label-danger" target="_blank">Add</a>
                                                @else
                                                    <a href="{{ route('course.course-structure', ['id' => $data->id]) }}" class="label label-primary" target="_blank">View</a>
                                                    <span class="label {{ $data->lecture_plan_section ? 'label-primary' : 'label-danger' }}">
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
                                                    <a href="{{ route('course.show-faqs', ['id' => $data->id]) }}" class="label label-danger" target="_blank">Add</a>
                                                @else
                                                    <a href="{{ route('course.show-faqs', ['id' => $data->id]) }}" class="label label-primary" target="_blank">View</a>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($data->testimonials->isEmpty())
                                                    <a href="{{ route('testimonial.show', ['testimonial' => $data->id]) }}" class="label label-danger" target="_blank">Add</a>
                                                @else
                                                    <a href="{{ route('testimonial.show', ['testimonial' => $data->id]) }}" class="label label-primary" target="_blank">View</a>
                                                @endif
                                            </td>
                                            <td>
                                                @if($data->instructor_id && !empty($data->instructor_id))
                                                    <a href="{{ route('admin.courses.instructors', ['id' => $data->id]) }}" class="label label-primary" target="_blank">View</a>
                                                @else
                                                    <a href="{{ route('admin.courses.instructors', ['id' => $data->id]) }}" class="label label-danger" target="_blank">Add</a>
                                                @endif
                                            </td>
                                            <td class="col-status">
                                                <select name="course_status" class="form-control" id="course_status_{{ $data->id }}" required
                                                    onchange="updateCourseStatus(this.value, {{ $data->id }})">
                                                    <option value="active" @if(is_null($data->deleted_at)) selected @endif>Active</option>
                                                    <option value="disable" @if(!is_null($data->deleted_at)) selected @endif>Disable</option>
                                                </select>
                                            </td>
                                            @include('admin.layout.partials.audit-columns-cells', ['model' => $data])
                                            <td>
                                                <div class="lms-cell-stack">
                                                    <a href="{{ route('course.edit', ['id' => $data->id]) }}" class="btn-primary btn btn-xs" target="_blank">
                                                        <i class="fa fa-pencil"></i> Edit
                                                    </a>
                                                    @include('admin.layout.partials.delete-button', [
                                                        'id' => $data->id,
                                                        'action' => route('course.delete', $data->id),
                                                    ])
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="20" class="text-center">No Record Found!</td>
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

        function updateCourseStatus(status, courseId) {
            $.ajax({
                url: '/admin/course/' + courseId + '/update-status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.success);
                        if (status === 'active') {
                            $('#course_status_' + courseId).closest('tr').fadeOut(500, function () {
                                $(this).remove();
                            });
                        }
                    } else {
                        toastr.error('Unexpected response received.');
                    }
                },
                error: function () {
                    alert('Error updating course status.');
                }
            });
        }
    </script>
@endpush
